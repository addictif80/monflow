<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\NavidromeService;
use Illuminate\Http\Request;

class PublicProfileController extends Controller
{
    public function show(string $displayName, NavidromeService $nd)
    {
        $user = User::where('display_name', $displayName)->firstOrFail();
        $password = $user->getDecryptedPassword();

        $playlists = [];
        $topSongs = [];
        $stats = ['totalSeconds' => 0];
        $totalTracks = 0;

        if ($password && $user->navidrome_id) {
            try {
                $playlists = $nd->getPlaylists($user->username, $password);
                $totalTracks = array_sum(array_column($playlists, 'songCount'));
            } catch (\Exception) {}

            $topSongs = $nd->getUserTopSongs($user->username, $password, 3);
            $stats    = $nd->getUserListeningStats($user->username, $password);
        }

        $memberSince = $user->created_at->diffInMonths(now());

        return view('public.profile', compact('user', 'playlists', 'topSongs', 'stats', 'totalTracks', 'memberSince'));
    }
}
