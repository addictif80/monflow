<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>#{{ $user->display_name }} — MonFlow</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="icon" type="image/x-icon" href="/favicon.ico">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
</head>
<body class="bg-zinc-950 text-zinc-100 min-h-screen">
<div class="max-w-2xl mx-auto px-4 py-12">

    {{-- Header --}}
    <div class="flex items-center gap-6 mb-10">
        @if($user->avatar_path)
            <img src="{{ asset($user->avatar_path) }}" alt="Avatar"
                 class="w-24 h-24 rounded-full object-cover ring-4 ring-indigo-500/30">
        @else
            <div class="w-24 h-24 rounded-full bg-indigo-600/20 border border-indigo-500/20 flex items-center justify-center text-4xl font-bold text-indigo-400 ring-4 ring-indigo-500/20">
                {{ strtoupper(substr($user->display_name, 0, 1)) }}
            </div>
        @endif
        <div>
            <h1 class="text-2xl font-semibold text-zinc-100">{{ $user->display_name }}</h1>
            <p class="text-indigo-400 font-mono text-sm">#{{ $user->display_name }}</p>
            <p class="text-zinc-500 text-sm mt-1">
                Utilisateur depuis {{ $memberSince > 0 ? $memberSince . ' mois' : 'moins d\'un mois' }}
            </p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4 mb-10">
        <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4 text-center">
            <div class="text-2xl font-semibold text-indigo-400">{{ count($playlists) }}</div>
            <div class="text-xs text-zinc-500 mt-1">Playlist{{ count($playlists) > 1 ? 's' : '' }}</div>
        </div>
        <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4 text-center">
            <div class="text-2xl font-semibold text-indigo-400">{{ $totalTracks }}</div>
            <div class="text-xs text-zinc-500 mt-1">Titre{{ $totalTracks > 1 ? 's' : '' }} en playlist</div>
        </div>
        <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4 text-center">
            @php
                $h = floor($stats['totalSeconds'] / 3600);
                $m = floor(($stats['totalSeconds'] % 3600) / 60);
            @endphp
            <div class="text-2xl font-semibold text-indigo-400">
                {{ $h > 0 ? "{$h}h{$m}m" : "{$m}min" }}
            </div>
            <div class="text-xs text-zinc-500 mt-1">Écoute totale</div>
        </div>
    </div>

    {{-- Top 3 titres --}}
    @if(count($topSongs) > 0)
    <div class="mb-10">
        <h2 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-4">Top titres</h2>
        <div class="space-y-2">
            @foreach($topSongs as $i => $song)
            <div class="bg-zinc-900 border border-zinc-800 rounded-xl px-4 py-3 flex items-center gap-4 group hover:bg-zinc-800/50 transition">
                <span class="text-2xl font-bold text-zinc-700 w-6 flex-shrink-0">{{ $i + 1 }}</span>
                <div class="flex-1 min-w-0">
                    <div class="font-medium text-sm text-zinc-200 truncate">{{ $song['title'] ?? '—' }}</div>
                    <div class="text-xs text-zinc-500 truncate">
                        {{ $song['artist'] ?? '' }}{{ isset($song['album']) ? ' · ' . $song['album'] : '' }}
                    </div>
                </div>
                <div class="text-xs text-zinc-600 flex-shrink-0">
                    {{ $song['playCount'] ?? 0 }} écoute{{ ($song['playCount'] ?? 0) > 1 ? 's' : '' }}
                </div>
                @auth
                @if(Auth::user()->activeSubscription || Auth::user()->is_admin)
                <a href="/player?play_id={{ $song['id'] }}"
                   class="opacity-0 group-hover:opacity-100 flex-shrink-0 px-3 py-1 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-medium transition">
                    ▶ Lire
                </a>
                @endif
                @endauth
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Playlists publiques --}}
    @if(count($playlists) > 0)
    <div>
        <h2 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-4">Playlists publiques</h2>
        <div class="space-y-2">
            @foreach($playlists as $pl)
            @php $sharedId = $pl['shared_playlist_id'] ?? null; @endphp
            <div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
                <div class="px-4 py-3 flex items-center justify-between gap-4 cursor-pointer hover:bg-zinc-800/50 transition"
                     onclick="togglePlaylist('{{ $sharedId }}', this)">
                    <div class="min-w-0 flex-1">
                        <div class="font-medium text-sm text-zinc-200 truncate">{{ $pl['name'] }}</div>
                        <div class="text-xs text-zinc-600 mt-0.5">
                            {{ $pl['songCount'] ?? 0 }} titre{{ ($pl['songCount'] ?? 0) > 1 ? 's' : '' }}
                            @if(($pl['subscriber_count'] ?? 0) > 0)
                                · {{ $pl['subscriber_count'] }} abonné{{ $pl['subscriber_count'] > 1 ? 's' : '' }}
                            @endif
                        </div>
                    </div>
                    <span class="text-zinc-600 text-xs flex-shrink-0 mr-2">▼</span>
                @if($sharedId)
                    @auth
                        @if(Auth::id() !== $user->id)
                            @if(isset($viewerSubscribed[$sharedId]))
                            <form action="/portal/shared/{{ $sharedId }}/unsubscribe" method="POST" class="flex-shrink-0">
                                @csrf @method('DELETE')
                                <button class="px-3 py-1 bg-zinc-800 hover:bg-red-500/10 text-zinc-400 hover:text-red-400 rounded-lg text-xs border border-zinc-700 hover:border-red-500/20 transition">
                                    ✓ Abonné
                                </button>
                            </form>
                            @else
                            <form action="/portal/shared/{{ $sharedId }}/subscribe" method="POST" class="flex-shrink-0">
                                @csrf
                                <button class="px-3 py-1 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-medium transition">
                                    + S'abonner
                                </button>
                            </form>
                            @endif
                        @endif
                    @else
                    <a href="/login" class="flex-shrink-0 px-3 py-1 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-medium transition">
                        + S'abonner
                    </a>
                    @endauth
                @endif
                </div>{{-- end header row --}}
                <div class="playlist-tracks hidden border-t border-zinc-800 divide-y divide-zinc-800/50"
                     data-shared-id="{{ $sharedId }}"></div>
            </div>{{-- end card --}}
            @endforeach
        </div>
    </div>
    @endif

    @if(!count($playlists) && !count($topSongs))
    <div class="text-center text-zinc-600 py-12">Ce profil est encore vide.</div>
    @endif

</div>
<footer class="text-center text-zinc-700 text-xs py-8">MonFlow &copy; {{ date('Y') }}</footer>

<script>
@auth
const canPlay = {{ (Auth::user()->activeSubscription || Auth::user()->is_admin) ? 'true' : 'false' }};
@else
const canPlay = false;
@endauth

async function togglePlaylist(sharedId, headerEl) {
    if (!sharedId) return;
    const card    = headerEl.closest('.overflow-hidden');
    const panel   = card.querySelector('.playlist-tracks');
    const chevron = headerEl.querySelector('span');
    const isOpen  = !panel.classList.contains('hidden');

    if (isOpen) {
        panel.classList.add('hidden');
        chevron.textContent = '▼';
        return;
    }

    chevron.textContent = '▲';
    panel.classList.remove('hidden');

    if (panel.dataset.loaded) return;
    panel.dataset.loaded = '1';
    panel.innerHTML = '<div class="px-4 py-3 text-xs text-zinc-600">Chargement…</div>';

    try {
        const tracks = await fetch(`/public/playlists/${sharedId}/tracks`).then(r => r.json());
        if (!tracks.length) { panel.innerHTML = '<div class="px-4 py-3 text-xs text-zinc-600">Playlist vide.</div>'; return; }
        panel.innerHTML = '';
        tracks.forEach((t, i) => {
            const row = document.createElement('div');
            row.className = 'flex items-center gap-3 px-4 py-2 hover:bg-zinc-800/40 transition group';
            row.innerHTML = `
                <span class="w-5 text-xs text-zinc-600 flex-shrink-0">${i+1}</span>
                <div class="flex-1 min-w-0">
                    <div class="text-sm text-zinc-300 truncate">${escHtml(t.title || '—')}</div>
                    <div class="text-xs text-zinc-500 truncate">${escHtml(t.artist || '')}${t.album ? ' · ' + escHtml(t.album) : ''}</div>
                </div>
                ${canPlay ? `<a href="/player?play_id=${t.id}" class="opacity-0 group-hover:opacity-100 flex-shrink-0 px-2 py-0.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded text-xs transition">▶</a>` : ''}`;
            panel.appendChild(row);
        });
    } catch(e) {
        panel.innerHTML = '<div class="px-4 py-3 text-xs text-red-400">Impossible de charger les titres.</div>';
    }
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
</body>
</html>
