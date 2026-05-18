<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>#{{ $user->display_name }} — MonFlow</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen">
<div class="max-w-2xl mx-auto px-4 py-12">

    {{-- Header --}}
    <div class="flex items-center gap-6 mb-10">
        @if($user->avatar_path)
            <img src="{{ asset($user->avatar_path) }}" alt="Avatar"
                 class="w-24 h-24 rounded-full object-cover ring-4 ring-indigo-500/40">
        @else
            <div class="w-24 h-24 rounded-full bg-indigo-700 flex items-center justify-center text-4xl font-bold text-white ring-4 ring-indigo-500/40">
                {{ strtoupper(substr($user->display_name, 0, 1)) }}
            </div>
        @endif
        <div>
            <h1 class="text-3xl font-bold text-white">{{ $user->display_name }}</h1>
            <p class="text-indigo-400 font-mono">#{{ $user->display_name }}</p>
            <p class="text-gray-400 text-sm mt-1">
                Utilisateur depuis {{ $memberSince > 0 ? $memberSince . ' mois' : 'moins d\'un mois' }}
            </p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4 mb-10">
        <div class="bg-gray-800 border border-gray-700 rounded-xl p-4 text-center">
            <div class="text-2xl font-bold text-indigo-400">{{ count($playlists) }}</div>
            <div class="text-xs text-gray-400 mt-1">Playlist{{ count($playlists) > 1 ? 's' : '' }}</div>
        </div>
        <div class="bg-gray-800 border border-gray-700 rounded-xl p-4 text-center">
            <div class="text-2xl font-bold text-indigo-400">{{ $totalTracks }}</div>
            <div class="text-xs text-gray-400 mt-1">Titre{{ $totalTracks > 1 ? 's' : '' }} en playlist</div>
        </div>
        <div class="bg-gray-800 border border-gray-700 rounded-xl p-4 text-center">
            @php
                $h = floor($stats['totalSeconds'] / 3600);
                $m = floor(($stats['totalSeconds'] % 3600) / 60);
            @endphp
            <div class="text-2xl font-bold text-indigo-400">
                {{ $h > 0 ? "{$h}h{$m}m" : "{$m}min" }}
            </div>
            <div class="text-xs text-gray-400 mt-1">Écoute totale</div>
        </div>
    </div>

    {{-- Top 3 titres --}}
    @if(count($topSongs) > 0)
    <div class="mb-10">
        <h2 class="text-sm font-medium text-gray-400 uppercase tracking-wider mb-4">Top titres</h2>
        <div class="space-y-2">
            @foreach($topSongs as $i => $song)
            <div class="bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 flex items-center gap-4">
                <span class="text-2xl font-bold text-gray-600 w-6 flex-shrink-0">{{ $i + 1 }}</span>
                <div class="flex-1 min-w-0">
                    <div class="font-medium text-sm truncate">{{ $song['title'] ?? '—' }}</div>
                    <div class="text-xs text-gray-400 truncate">
                        {{ $song['artist'] ?? '' }}{{ isset($song['album']) ? ' · ' . $song['album'] : '' }}
                    </div>
                </div>
                <div class="text-xs text-gray-500 flex-shrink-0">
                    {{ $song['playCount'] ?? 0 }} écoute{{ ($song['playCount'] ?? 0) > 1 ? 's' : '' }}
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Playlists publiques --}}
    @if(count($playlists) > 0)
    <div>
        <h2 class="text-sm font-medium text-gray-400 uppercase tracking-wider mb-4">Playlists publiques</h2>
        <div class="space-y-2">
            @foreach($playlists as $pl)
            @php $sharedId = $pl['shared_playlist_id'] ?? null; @endphp
            <div class="bg-gray-800 border border-gray-700 rounded-lg px-4 py-3 flex items-center justify-between gap-4">
                <div class="min-w-0">
                    <div class="font-medium text-sm truncate">{{ $pl['name'] }}</div>
                    <div class="text-xs text-gray-500 mt-0.5">
                        {{ $pl['songCount'] ?? 0 }} titre{{ ($pl['songCount'] ?? 0) > 1 ? 's' : '' }}
                        @if(($pl['subscriber_count'] ?? 0) > 0)
                            · {{ $pl['subscriber_count'] }} abonné{{ $pl['subscriber_count'] > 1 ? 's' : '' }}
                        @endif
                    </div>
                </div>
                @if($sharedId)
                    @auth
                        @if(Auth::id() !== $user->id)
                            @if(isset($viewerSubscribed[$sharedId]))
                            <form action="/portal/shared/{{ $sharedId }}/unsubscribe" method="POST" class="flex-shrink-0">
                                @csrf @method('DELETE')
                                <button class="px-3 py-1 bg-gray-700 hover:bg-red-700/60 text-gray-300 hover:text-red-300 rounded text-xs transition">
                                    ✓ Abonné
                                </button>
                            </form>
                            @else
                            <form action="/portal/shared/{{ $sharedId }}/subscribe" method="POST" class="flex-shrink-0">
                                @csrf
                                <button class="px-3 py-1 bg-indigo-600 hover:bg-indigo-500 text-white rounded text-xs font-medium transition">
                                    + S'abonner
                                </button>
                            </form>
                            @endif
                        @endif
                    @else
                    <a href="/login" class="flex-shrink-0 px-3 py-1 bg-indigo-600 hover:bg-indigo-500 text-white rounded text-xs font-medium transition">
                        + S'abonner
                    </a>
                    @endauth
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if(!count($playlists) && !count($topSongs))
    <div class="text-center text-gray-500 py-12">Ce profil est encore vide.</div>
    @endif

</div>
<footer class="text-center text-gray-600 text-xs py-8">MonFlow &copy; {{ date('Y') }}</footer>
</body>
</html>
