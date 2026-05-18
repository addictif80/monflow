<?php

namespace App\Http\Controllers;

use App\Models\SharedPlaylist;
use App\Models\User;
use App\Services\NavidromeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PublicProfileController extends Controller
{
    public function playlistTracks(string $sharedId, NavidromeService $nd)
    {
        $shared = SharedPlaylist::with('owner')->findOrFail($sharedId);
        if (!$shared->is_public) abort(403);

        $ownerPw = $shared->owner->getDecryptedPassword();
        if (!$ownerPw) return response()->json([]);

        $playlist = $nd->getPlaylist($shared->owner->username, $ownerPw, $shared->owner_nd_playlist_id);
        return response()->json($playlist['entry'] ?? []);
    }

    public function show(string $displayName, NavidromeService $nd)
    {
        $user = User::where('display_name', $displayName)->firstOrFail();
        $password = $user->getDecryptedPassword();

        $playlists = [];
        $topSongs = [];
        $stats = ['totalSeconds' => 0];
        $totalTracks = 0;

        // Public playlists: only those explicitly marked is_public in MonFlow
        $publicShared = SharedPlaylist::where('owner_id', $user->id)
            ->where('is_public', true)
            ->get()
            ->keyBy('owner_nd_playlist_id');

        if ($password && $user->navidrome_id) {
            try {
                $allPlaylists = $nd->getPlaylists($user->username, $password);
                // Filter to public only, attach MonFlow shared_playlist_id for subscribe button
                foreach ($allPlaylists as $pl) {
                    if (isset($publicShared[$pl['id']])) {
                        $pl['shared_playlist_id'] = $publicShared[$pl['id']]->id;
                        $pl['subscriber_count']   = $publicShared[$pl['id']]->subscribers()->count();
                        $playlists[] = $pl;
                    }
                }
                $totalTracks = array_sum(array_column($playlists, 'songCount'));
            } catch (\Exception) {}

            $topSongs = $nd->getUserTopSongs($user->username, $password, 3);
            $stats    = $nd->getUserListeningStats($user->username, $password);
        }

        $memberSince      = $user->created_at->diffInMonths(now());
        $viewerSubscribed = [];
        if (Auth::check()) {
            foreach ($playlists as $pl) {
                $sharedId = $pl['shared_playlist_id'] ?? null;
                if ($sharedId) {
                    $isSub = \App\Models\PlaylistMember::where('shared_playlist_id', $sharedId)
                        ->where('user_id', Auth::id())->exists();
                    if ($isSub) $viewerSubscribed[$sharedId] = true;
                }
            }
        }

        return view('public.profile', compact(
            'user', 'playlists', 'topSongs', 'stats', 'totalTracks', 'memberSince', 'viewerSubscribed'
        ));
    }
}
