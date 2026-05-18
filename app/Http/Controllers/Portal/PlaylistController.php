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
}
