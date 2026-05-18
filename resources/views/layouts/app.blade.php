<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'MonFlow')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen flex flex-col">
    <nav class="bg-gray-800 border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 flex items-center justify-between h-16">
            <a href="/portal" class="text-xl font-bold text-indigo-400">MonFlow</a>
            <div class="flex items-center gap-4 text-sm">
                <a href="/portal" class="hover:text-indigo-400">Tableau de bord</a>
                <a href="/portal/plans" class="hover:text-indigo-400">Formules</a>
                <a href="/portal/wallet" class="hover:text-indigo-400">Portefeuille</a>
                <a href="/portal/payments" class="hover:text-indigo-400">Paiements</a>
                <a href="/portal/devices" class="hover:text-indigo-400">Appareils</a>
                <a href="/portal/playlists" class="hover:text-indigo-400">Playlists</a>
                <a href="/support/tickets" class="hover:text-indigo-400">Support</a>
                <a href="/portal/feedback" class="hover:text-indigo-400">Feedback</a>
                <a href="/player" class="hover:text-indigo-400 text-indigo-300 font-medium">Lecteur</a>
                <a href="/portal/deemix" class="hover:text-indigo-400 text-indigo-300 font-medium">Deemix</a>
                @php $unreadCount = \App\Models\Notification::where('user_id', Auth::id())->whereNull('read_at')->count(); @endphp
                <a href="/portal/notifications" class="relative hover:text-indigo-400">
                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    @if($unreadCount > 0)<span class="absolute -top-1 -right-2 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>@endif
                </a>
                <span class="text-gray-400">|</span>
                <a href="/portal/profile" class="text-gray-300 hover:text-indigo-400">{{ Auth::user()->username }}</a>
                <form action="/logout" method="POST" class="inline">@csrf<button class="hover:text-red-400">Déconnexion</button></form>
            </div>
        </div>
    </nav>
    @if(session('impersonating_admin_id'))
    <div class="bg-amber-500 text-gray-900 text-sm font-medium">
        <div class="max-w-7xl mx-auto px-4 py-2 flex items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                Mode observation — vous naviguez en tant que <span class="font-bold ml-1">{{ Auth::user()->username }}</span>
            </div>
            <form action="/portal/stop-impersonate" method="POST">
                @csrf
                <button type="submit" class="px-3 py-1 bg-gray-900 hover:bg-gray-800 text-amber-400 rounded text-xs font-semibold transition">
                    ← Revenir admin
                </button>
            </form>
        </div>
    </div>
    @endif
    <main class="flex-1 max-w-7xl mx-auto w-full px-4 py-8">
        @if(session('success'))<div class="mb-4 p-3 bg-green-900/50 border border-green-700 rounded text-green-300">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="mb-4 p-3 bg-red-900/50 border border-red-700 rounded text-red-300">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="mb-4 p-3 bg-red-900/50 border border-red-700 rounded text-red-300">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
        @yield('content')
    </main>
    <footer class="bg-gray-800 border-t border-gray-700 py-4 text-center text-gray-500 text-sm" style="margin-bottom:64px">MonFlow &copy; {{ date('Y') }}</footer>
</main-end>

{{-- ─── Mini-player (persistent, reads localStorage state saved by /player) ─── --}}
<div id="miniPlayer" class="fixed bottom-0 left-0 right-0 h-16 bg-gray-900 border-t border-gray-700 z-50 hidden items-center px-4 gap-3">
    <img id="mpCover" src="" alt="" class="w-10 h-10 rounded object-cover flex-shrink-0 bg-gray-700" onerror="this.src=''">
    <div class="flex-1 min-w-0">
        <div id="mpTitle" class="text-sm font-medium truncate text-gray-100">—</div>
        <div id="mpArtist" class="text-xs text-gray-400 truncate">—</div>
    </div>
    <div class="flex items-center gap-2 flex-shrink-0">
        <button id="mpPrev" class="text-gray-400 hover:text-white transition text-lg">⏮</button>
        <button id="mpPlayPause" class="w-9 h-9 bg-indigo-600 hover:bg-indigo-500 rounded-full flex items-center justify-center text-white text-sm transition">▶</button>
        <button id="mpNext" class="text-gray-400 hover:text-white transition text-lg">⏭</button>
    </div>
    <input id="mpSeek" type="range" min="0" max="100" value="0" class="w-28 accent-indigo-500 flex-shrink-0">
    <a href="/player" class="text-xs text-indigo-400 hover:text-indigo-300 flex-shrink-0 ml-1 whitespace-nowrap">⤢ Lecteur</a>
    <audio id="mpAudio" style="display:none"></audio>
</div>

<script>
(function() {
    // Don't run inside the player's iframe overlay — the main player handles audio
    if (window !== window.top) return;

    const mp = document.getElementById('miniPlayer');
    const mpAudio = document.getElementById('mpAudio');

    function ndStreamUrl(nd, id) {
        return `${nd.url}/rest/stream.view?u=${encodeURIComponent(nd.user)}&t=${nd.token}&s=${nd.salt}&v=${nd.version}&c=${nd.client}&id=${id}`;
    }
    function ndCoverUrl(nd, id) {
        return `${nd.url}/rest/getCoverArt.view?u=${encodeURIComponent(nd.user)}&t=${nd.token}&s=${nd.salt}&v=${nd.version}&c=${nd.client}&id=${id}&size=50`;
    }

    let nd, queue = [], qidx = 0;

    try {
        nd    = JSON.parse(localStorage.getItem('mf_nd') || 'null');
        queue = JSON.parse(localStorage.getItem('mf_queue') || '[]');
        qidx  = parseInt(localStorage.getItem('mf_qidx') || '0', 10);
    } catch(e) {}

    if (!nd || !queue.length) return;

    // Show mini-player
    mp.classList.remove('hidden');
    mp.classList.add('flex');
    document.body.style.paddingBottom = '64px';

    const now = queue[qidx];
    if (!now) return;

    function loadTrack(idx, resume) {
        const s = queue[idx];
        if (!s) return;
        qidx = idx;
        localStorage.setItem('mf_qidx', String(idx));
        document.getElementById('mpTitle').textContent  = s.title || '—';
        document.getElementById('mpArtist').textContent = s.artist || s.album || '';
        const cover = document.getElementById('mpCover');
        cover.src = ndCoverUrl(nd, s.coverArt || s.id);
        mpAudio.src = ndStreamUrl(nd, s.id);
        if (resume) {
            mpAudio.play().catch(() => {});
            document.getElementById('mpPlayPause').textContent = '⏸';
        }
    }

    // Try to resume from saved position
    const savedNow = JSON.parse(localStorage.getItem('mf_now') || 'null');
    loadTrack(qidx, false);
    if (savedNow && savedNow.playing && (Date.now() - (savedNow.ts || 0)) < 300000) {
        mpAudio.addEventListener('canplay', function seek() {
            mpAudio.currentTime = savedNow.time || 0;
            mpAudio.play().catch(() => {});
            document.getElementById('mpPlayPause').textContent = '⏸';
            mpAudio.removeEventListener('canplay', seek);
        }, { once: true });
        mpAudio.load();
    }

    // Controls
    document.getElementById('mpPlayPause').addEventListener('click', () => {
        if (mpAudio.paused) { mpAudio.play(); document.getElementById('mpPlayPause').textContent = '⏸'; }
        else { mpAudio.pause(); document.getElementById('mpPlayPause').textContent = '▶'; }
    });
    document.getElementById('mpPrev').addEventListener('click', () => loadTrack(Math.max(0, qidx - 1), true));
    document.getElementById('mpNext').addEventListener('click', () => loadTrack(Math.min(queue.length - 1, qidx + 1), true));
    mpAudio.addEventListener('ended', () => loadTrack(qidx + 1, true));

    // Progress bar
    const seek = document.getElementById('mpSeek');
    mpAudio.addEventListener('timeupdate', () => {
        if (mpAudio.duration) seek.value = (mpAudio.currentTime / mpAudio.duration) * 100;
    });
    seek.addEventListener('input', () => {
        if (mpAudio.duration) mpAudio.currentTime = (seek.value / 100) * mpAudio.duration;
    });
})();
</script>
</body>
</html>
