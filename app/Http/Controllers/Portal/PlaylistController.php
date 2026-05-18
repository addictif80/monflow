<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\NavidromeService;
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
        return response()->json(['success' => true]);
    }

    public function destroy(string $id, NavidromeService $nd)
    {
        [$u, $p] = $this->credentials();
        $nd->deletePlaylist($u, $p, $id);
        return response()->json(['success' => true]);
    }

    public function addTracks(string $id, Request $request, NavidromeService $nd)
    {
        $request->validate(['song_ids' => 'required|array|min:1', 'song_ids.*' => 'string']);
        [$u, $p] = $this->credentials();
        $nd->addSongsToPlaylist($u, $p, $id, $request->song_ids);
        return response()->json(['success' => true]);
    }

    public function removeTrack(string $id, Request $request, NavidromeService $nd)
    {
        $request->validate(['index' => 'required|integer|min:0']);
        [$u, $p] = $this->credentials();
        $nd->removeSongsFromPlaylist($u, $p, $id, [$request->index]);
        return response()->json(['success' => true]);
    }

    public function search(Request $request, NavidromeService $nd)
    {
        $request->validate(['q' => 'required|string|min:1|max:100']);
        [$u, $p] = $this->credentials();
        $songs = $nd->searchSongsSubsonic($u, $p, $request->q);
        return response()->json($songs);
    }

    public function share(string $id, Request $request, NavidromeService $nd)
    {
        $request->validate([
            'target' => ['required', 'string', 'regex:/^#?[a-zA-Z0-9_\-\.]+$/'],
        ]);

        $targetName = ltrim(trim($request->target), '#');
        $target = \App\Models\User::where('display_name', $targetName)->first();
        if (!$target) {
            return response()->json(['message' => "Utilisateur #$targetName introuvable."], 404);
        }
        if ($target->id === \Illuminate\Support\Facades\Auth::id()) {
            return response()->json(['message' => 'Vous ne pouvez pas partager avec vous-même.'], 422);
        }

        $targetPassword = $target->getDecryptedPassword();
        if (!$targetPassword || !$target->navidrome_id) {
            return response()->json(['message' => 'Cet utilisateur ne peut pas recevoir de playlist.'], 422);
        }

        [$fromUser, $fromPass] = $this->credentials();
        // Get playlist name
        $playlist = $nd->getPlaylist($fromUser, $fromPass, $id);
        $playlistName = $playlist['name'] ?? 'Playlist partagée';

        $nd->copyPlaylistToUser($fromUser, $fromPass, $target->username, $targetPassword, $id, $playlistName);

        // Send notification to target
        \App\Models\Notification::send(
            $target->id,
            'playlist_shared',
            'Playlist reçue',
            \Illuminate\Support\Facades\Auth::user()->display_name . ' vous a partagé la playlist "' . $playlistName . '".',
            '/portal/playlists'
        );

        return response()->json(['success' => true, 'message' => "Playlist partagée avec #{$targetName}."]);
    }
}
