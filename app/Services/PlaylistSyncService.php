<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\PlaylistMember;
use App\Models\SharedPlaylist;
use App\Models\User;

class PlaylistSyncService
{
    public function __construct(private NavidromeService $nd) {}

    /**
     * Owner added tracks → propagate to all members.
     */
    public function propagateAddByOwner(SharedPlaylist $shared, array $songIds): void
    {
        $shared->loadMissing(['members.user', 'owner']);
        $ownerName = $shared->owner->display_name ?: $shared->owner->username;
        $count = count($songIds);
        $label = $count > 1 ? 's' : '';

        foreach ($shared->members as $member) {
            [$u, $p] = $this->creds($member->user);
            if (!$u) continue;
            try {
                $this->nd->addSongsToPlaylist($u, $p, $member->member_nd_playlist_id, $songIds);
            } catch (\Exception) {}
            Notification::send(
                $member->user_id, 'playlist_updated', 'Playlist mise à jour',
                "{$ownerName} a ajouté {$count} titre{$label} à \"{$shared->name}\".", null
            );
        }
    }

    /**
     * Owner removed a track → rebuild every member's Navidrome copy from the canonical list.
     */
    public function propagateRemoveByOwner(SharedPlaylist $shared): void
    {
        $shared->loadMissing(['members.user', 'owner']);
        [$ownerUser, $ownerPass] = $this->creds($shared->owner);
        if (!$ownerUser) return;

        $playlist  = $this->nd->getPlaylist($ownerUser, $ownerPass, $shared->owner_nd_playlist_id);
        $songIds   = array_column($playlist['entry'] ?? [], 'id');
        $ownerName = $shared->owner->display_name ?: $shared->owner->username;

        foreach ($shared->members as $member) {
            [$u, $p] = $this->creds($member->user);
            if (!$u) continue;

            try { $this->nd->deletePlaylist($u, $p, $member->member_nd_playlist_id); } catch (\Exception) {}

            try {
                $newPl = $this->nd->createPlaylist($u, $p, $shared->name);
                if (!empty($songIds)) {
                    $this->nd->addSongsToPlaylist($u, $p, $newPl['id'], $songIds);
                }
                $member->update(['member_nd_playlist_id' => $newPl['id']]);
            } catch (\Exception) {
                continue;
            }

            if ($member->role === 'collaborator') {
                Notification::send(
                    $member->user_id, 'playlist_updated', 'Playlist mise à jour',
                    "{$ownerName} a modifié \"{$shared->name}\".", null
                );
            }
        }
    }

    /**
     * Collaborator added tracks → propagate to owner + all other members.
     */
    public function propagateAddByMember(SharedPlaylist $shared, PlaylistMember $actor, array $songIds): void
    {
        $shared->loadMissing(['members.user', 'owner']);

        // Add to owner's Navidrome playlist
        [$ownerUser, $ownerPass] = $this->creds($shared->owner);
        if ($ownerUser) {
            try {
                $this->nd->addSongsToPlaylist($ownerUser, $ownerPass, $shared->owner_nd_playlist_id, $songIds);
            } catch (\Exception) {}
        }

        // Add to every other member's copy
        foreach ($shared->members as $member) {
            if ($member->id === $actor->id) continue;
            [$u, $p] = $this->creds($member->user);
            if (!$u) continue;
            try {
                $this->nd->addSongsToPlaylist($u, $p, $member->member_nd_playlist_id, $songIds);
            } catch (\Exception) {}
        }

        // Notify owner
        $actorName = $actor->user->display_name ?: $actor->user->username;
        $count = count($songIds);
        $label = $count > 1 ? 's' : '';
        Notification::send(
            $shared->owner_id, 'playlist_updated', 'Titre ajouté à votre playlist',
            "{$actorName} a ajouté {$count} titre{$label} à \"{$shared->name}\".", null
        );
    }

    private function creds(User $user): array
    {
        $pw = $user->getDecryptedPassword();
        if (!$pw || !$user->navidrome_id) return [null, null];
        return [$user->username, $pw];
    }
}
