<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\PlaylistMember;
use App\Models\SharedPlaylist;
use App\Models\User;
use App\Services\NavidromeService;
use App\Services\PlaylistSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlaylistController extends Controller
{
    private function credentials(): array
    {
        $user = Auth::user();
        $pw = $user->getDecryptedPassword();
        if (!$pw || !$user->navidrome_id) {
            abort(422, 'Compte Navidrome non disponible. Reconnectez-vous ou contactez le support.');
        }
        return [$user->username, $pw];
    }

    public function index(NavidromeService $nd)
    {
        [$u, $p] = $this->credentials();
        try {
            $playlists = $nd->getPlaylists($u, $p);
        } catch (\Exception $e) {
            $playlists = [];
        }
        return view('portal.playlists', compact('playlists'));
    }

    public function store(Request $request, NavidromeService $nd)
    {
        $request->validate(['name' => 'required|string|max:200']);
        [$u, $p] = $this->credentials();
        $playlist = $nd->createPlaylist($u, $p, trim($request->name));
        return response()->json($playlist);
    }

    public function show(string $id, NavidromeService $nd)
    {
        [$u, $p] = $this->credentials();
        $playlist = $nd->getPlaylist($u, $p, $id);
        return response()->json($playlist);
    }

    public function update(string $id, Request $request, NavidromeService $nd)
    {
        $request->validate(['name' => 'required|string|max:200']);
        [$u, $p] = $this->credentials();
        $nd->renamePlaylist($u, $p, $id, trim($request->name));
        SharedPlaylist::where('owner_id', Auth::id())
            ->where('owner_nd_playlist_id', $id)
            ->update(['name' => trim($request->name)]);
        return response()->json(['success' => true]);
    }

    public function destroy(string $id, NavidromeService $nd)
    {
        [$u, $p] = $this->credentials();
        $nd->deletePlaylist($u, $p, $id);

        $shared = SharedPlaylist::where('owner_id', Auth::id())
            ->where('owner_nd_playlist_id', $id)->first();
        if ($shared) {
            foreach ($shared->members()->with('user')->get() as $member) {
                $pw = $member->user->getDecryptedPassword();
                if ($pw) {
                    try { $nd->deletePlaylist($member->user->username, $pw, $member->member_nd_playlist_id); } catch (\Exception) {}
                }
            }
            $shared->delete();
        }

        return response()->json(['success' => true]);
    }

    public function addTracks(string $id, Request $request, NavidromeService $nd, PlaylistSyncService $sync)
    {
        $request->validate(['song_ids' => 'required|array|min:1', 'song_ids.*' => 'string']);
        [$u, $p] = $this->credentials();
        $songIds = $request->song_ids;

        $sharedAsOwner = SharedPlaylist::where('owner_id', Auth::id())
            ->where('owner_nd_playlist_id', $id)->first();

        if ($sharedAsOwner) {
            $nd->addSongsToPlaylist($u, $p, $id, $songIds);
            $sync->propagateAddByOwner($sharedAsOwner, $songIds);
            return response()->json(['success' => true]);
        }

        $memberRecord = PlaylistMember::where('user_id', Auth::id())
            ->where('member_nd_playlist_id', $id)
            ->where('role', 'collaborator')
            ->first();

        if ($memberRecord) {
            $nd->addSongsToPlaylist($u, $p, $id, $songIds);
            $sync->propagateAddByMember($memberRecord->sharedPlaylist, $memberRecord, $songIds);
            return response()->json(['success' => true]);
        }

        $isSubscriber = PlaylistMember::where('user_id', Auth::id())
            ->where('member_nd_playlist_id', $id)
            ->where('role', 'subscriber')
            ->exists();
        if ($isSubscriber) {
            return response()->json(['message' => 'Les abonnés ne peuvent pas modifier cette playlist.'], 403);
        }

        $nd->addSongsToPlaylist($u, $p, $id, $songIds);
        return response()->json(['success' => true]);
    }

    public function removeTrack(string $id, Request $request, NavidromeService $nd, PlaylistSyncService $sync)
    {
        $request->validate(['index' => 'required|integer|min:0']);
        [$u, $p] = $this->credentials();

        $isMember = PlaylistMember::where('user_id', Auth::id())
            ->where('member_nd_playlist_id', $id)->exists();
        if ($isMember) {
            return response()->json(['message' => 'Seul le propriétaire peut supprimer des titres.'], 403);
        }

        $nd->removeSongsFromPlaylist($u, $p, $id, [$request->index]);

        $sharedAsOwner = SharedPlaylist::where('owner_id', Auth::id())
            ->where('owner_nd_playlist_id', $id)->first();
        if ($sharedAsOwner) {
            $sync->propagateRemoveByOwner($sharedAsOwner);
        }

        return response()->json(['success' => true]);
    }

    public function search(Request $request, NavidromeService $nd)
    {
        $request->validate(['q' => 'required|string|min:1|max:100']);
        [$u, $p] = $this->credentials();
        $songs = $nd->searchSongsSubsonic($u, $p, $request->q);
        return response()->json($songs);
    }

    // ─── Sharing & visibility ───

    public function info(string $id)
    {
        $shared = SharedPlaylist::where('owner_id', Auth::id())
            ->where('owner_nd_playlist_id', $id)->first();

        $role = null;
        if (!$shared) {
            $memberRecord = PlaylistMember::where('user_id', Auth::id())
                ->where('member_nd_playlist_id', $id)
                ->with('sharedPlaylist')->first();
            if ($memberRecord) {
                $shared = $memberRecord->sharedPlaylist;
                $role   = $memberRecord->role;
            }
        } else {
            $role = 'owner';
        }

        return response()->json([
            'is_public'          => $shared?->is_public ?? false,
            'shared_playlist_id' => $shared?->id,
            'role'               => $role,
            'member_count'       => $shared?->members()->count() ?? 0,
        ]);
    }

    public function togglePublic(string $id, NavidromeService $nd)
    {
        [$u, $p] = $this->credentials();
        $playlist = $nd->getPlaylist($u, $p, $id);
        $name = $playlist['name'] ?? 'Playlist';

        $shared = SharedPlaylist::firstOrCreate(
            ['owner_id' => Auth::id(), 'owner_nd_playlist_id' => $id],
            ['name' => $name, 'is_public' => false]
        );
        $shared->name      = $name;
        $shared->is_public = !$shared->is_public;
        $shared->save();

        return response()->json([
            'success'      => true,
            'is_public'    => $shared->is_public,
            'member_count' => $shared->members()->count(),
        ]);
    }

    public function share(string $id, Request $request, NavidromeService $nd)
    {
        $request->validate([
            'target' => ['required', 'string', 'regex:/^#?[a-zA-Z0-9_\-\.]+$/'],
        ]);

        $targetName = ltrim(trim($request->target), '#');
        $target = User::where('display_name', $targetName)->first();
        if (!$target) {
            return response()->json(['message' => "Utilisateur #$targetName introuvable."], 404);
        }
        if ($target->id === Auth::id()) {
            return response()->json(['message' => 'Vous ne pouvez pas partager avec vous-même.'], 422);
        }

        $targetPw = $target->getDecryptedPassword();
        if (!$targetPw || !$target->navidrome_id) {
            return response()->json(['message' => 'Cet utilisateur ne peut pas recevoir de playlist.'], 422);
        }

        [$fromUser, $fromPass] = $this->credentials();
        $playlist     = $nd->getPlaylist($fromUser, $fromPass, $id);
        $playlistName = $playlist['name'] ?? 'Playlist partagée';
        $songIds      = array_column($playlist['entry'] ?? [], 'id');

        $shared = SharedPlaylist::firstOrCreate(
            ['owner_id' => Auth::id(), 'owner_nd_playlist_id' => $id],
            ['name' => $playlistName, 'is_public' => false]
        );

        $existing = PlaylistMember::where('shared_playlist_id', $shared->id)
            ->where('user_id', $target->id)->first();
        if ($existing) {
            return response()->json(['message' => "#{$targetName} a déjà accès à cette playlist."], 422);
        }

        $newPl = $nd->createPlaylist($target->username, $targetPw, $playlistName);
        if (!empty($songIds)) {
            $nd->addSongsToPlaylist($target->username, $targetPw, $newPl['id'], $songIds);
        }

        PlaylistMember::create([
            'shared_playlist_id'    => $shared->id,
            'user_id'               => $target->id,
            'role'                  => 'collaborator',
            'member_nd_playlist_id' => $newPl['id'],
        ]);

        $senderName = Auth::user()->display_name ?: Auth::user()->username;
        Notification::send(
            $target->id, 'playlist_shared', 'Playlist partagée',
            "{$senderName} vous a ajouté à la playlist \"{$playlistName}\" en tant que collaborateur.",
            null
        );

        return response()->json(['success' => true, 'message' => "Playlist partagée avec #{$targetName}."]);
    }

    public function subscribe(string $sharedId, NavidromeService $nd)
    {
        $shared = SharedPlaylist::with('owner')->findOrFail($sharedId);
        if (!$shared->is_public) {
            abort(403, 'Cette playlist n\'est pas publique.');
        }

        $user = Auth::user();
        if ($shared->owner_id === $user->id) {
            return redirect()->back()->with('error', 'Vous êtes le propriétaire de cette playlist.');
        }

        $existing = PlaylistMember::where('shared_playlist_id', $sharedId)
            ->where('user_id', $user->id)->first();
        if ($existing) {
            return redirect()->back()->with('error', 'Vous êtes déjà abonné à cette playlist.');
        }

        $pw = $user->getDecryptedPassword();
        if (!$pw || !$user->navidrome_id) {
            return redirect()->back()->with('error', 'Votre compte Navidrome n\'est pas disponible.');
        }

        $ownerPw = $shared->owner->getDecryptedPassword();
        if ($ownerPw) {
            $playlist = $nd->getPlaylist($shared->owner->username, $ownerPw, $shared->owner_nd_playlist_id);
            $songIds  = array_column($playlist['entry'] ?? [], 'id');
        } else {
            $songIds = [];
        }

        $newPl = $nd->createPlaylist($user->username, $pw, $shared->name);
        if (!empty($songIds)) {
            $nd->addSongsToPlaylist($user->username, $pw, $newPl['id'], $songIds);
        }

        PlaylistMember::create([
            'shared_playlist_id'    => $sharedId,
            'user_id'               => $user->id,
            'role'                  => 'subscriber',
            'member_nd_playlist_id' => $newPl['id'],
        ]);

        $subscriberName = $user->display_name ?: $user->username;
        Notification::send(
            $shared->owner_id, 'playlist_subscribed', 'Nouvel abonné',
            "{$subscriberName} s'est abonné à votre playlist \"{$shared->name}\".", null
        );

        $ownerHandle = $shared->owner->display_name;
        return redirect($ownerHandle ? "/u/{$ownerHandle}" : '/portal')
            ->with('success', "Abonné à \"{$shared->name}\".");
    }

    public function unsubscribe(string $sharedId, NavidromeService $nd)
    {
        $member = PlaylistMember::where('shared_playlist_id', $sharedId)
            ->where('user_id', Auth::id())->firstOrFail();

        $user = Auth::user();
        $pw   = $user->getDecryptedPassword();
        if ($pw) {
            try { $nd->deletePlaylist($user->username, $pw, $member->member_nd_playlist_id); } catch (\Exception) {}
        }

        $name = $member->sharedPlaylist->name;
        $member->delete();

        return redirect()->back()->with('success', "Désabonné de \"{$name}\".");
    }
}
