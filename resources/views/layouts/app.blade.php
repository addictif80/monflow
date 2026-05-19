<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'MonFlow')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/css/monflow.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
</head>
<body class="bg-zinc-950 text-zinc-100 min-h-screen flex flex-col">

<nav class="border-b border-zinc-800/60 bg-zinc-950/90 backdrop-blur-sm sticky top-0 z-40">
    <div class="max-w-6xl mx-auto px-4 flex items-center h-12 gap-1">
        <a href="/portal" class="flex items-center gap-2 mr-3 flex-shrink-0">
            <img src="/icons/icon-192.png" alt="MonFlow" class="w-5 h-5 rounded">
            <span class="text-sm font-semibold text-zinc-100">MonFlow</span>
        </a>
        <div class="flex items-center gap-0.5 flex-1 overflow-x-auto">
            <a href="/portal" class="text-sm text-zinc-400 hover:text-zinc-200 px-2.5 py-1.5 rounded-md whitespace-nowrap">Tableau de bord</a>
            <a href="/portal/plans" class="text-sm text-zinc-400 hover:text-zinc-200 px-2.5 py-1.5 rounded-md whitespace-nowrap">Formules</a>
            <a href="/portal/wallet" class="text-sm text-zinc-400 hover:text-zinc-200 px-2.5 py-1.5 rounded-md whitespace-nowrap">Portefeuille</a>
            <a href="/portal/payments" class="text-sm text-zinc-400 hover:text-zinc-200 px-2.5 py-1.5 rounded-md whitespace-nowrap">Paiements</a>
            <a href="/portal/devices" class="text-sm text-zinc-400 hover:text-zinc-200 px-2.5 py-1.5 rounded-md whitespace-nowrap">Appareils</a>
            <a href="/portal/playlists" class="text-sm text-zinc-400 hover:text-zinc-200 px-2.5 py-1.5 rounded-md whitespace-nowrap">Playlists</a>
            <a href="/support/tickets" class="text-sm text-zinc-400 hover:text-zinc-200 px-2.5 py-1.5 rounded-md whitespace-nowrap">Support</a>
            <a href="/portal/feedback" class="text-sm text-zinc-400 hover:text-zinc-200 px-2.5 py-1.5 rounded-md whitespace-nowrap">Feedback</a>
            <a href="/player" class="text-sm text-zinc-400 hover:text-zinc-200 px-2.5 py-1.5 rounded-md whitespace-nowrap">Lecteur</a>
            <a href="/portal/deemix" class="text-sm text-zinc-400 hover:text-zinc-200 px-2.5 py-1.5 rounded-md whitespace-nowrap">Deemix</a>
        </div>
        <div class="flex items-center gap-1 flex-shrink-0">
            @php $unreadCount = \App\Models\Notification::where('user_id', Auth::id())->whereNull('read_at')->count(); @endphp
            <a href="/portal/notifications" class="relative p-2 text-zinc-500 hover:text-zinc-200 rounded-md">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                @if($unreadCount > 0)<span class="absolute top-1 right-1 w-2 h-2 bg-indigo-500 rounded-full"></span>@endif
            </a>
            <a href="/portal/profile" class="text-sm text-zinc-400 hover:text-zinc-200 px-2.5 py-1.5 rounded-md">{{ Auth::user()->username }}</a>
            <form action="/logout" method="POST" class="inline">@csrf<button class="text-sm text-zinc-600 hover:text-zinc-400 px-2.5 py-1.5 rounded-md">Déconnexion</button></form>
        </div>
    </div>
</nav>

@if(session('impersonating_admin_id'))
<div class="bg-amber-400/10 border-b border-amber-400/20 text-amber-400 text-xs">
    <div class="max-w-6xl mx-auto px-4 py-2 flex items-center justify-between">
        <span>Mode observation — connecté en tant que <strong>{{ Auth::user()->username }}</strong></span>
        <form action="/portal/stop-impersonate" method="POST">
            @csrf
            <button type="submit" class="px-3 py-1 bg-zinc-900 hover:bg-zinc-800 text-amber-400 rounded text-xs border border-amber-400/20">← Revenir admin</button>
        </form>
    </div>
</div>
@endif

<main class="flex-1 max-w-6xl mx-auto w-full px-4 py-8">
    @if(session('success'))<div class="mb-5 flex items-start gap-3 bg-emerald-500/10 border border-emerald-500/20 rounded-lg p-3 text-sm text-emerald-400">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="mb-5 flex items-start gap-3 bg-red-500/10 border border-red-500/20 rounded-lg p-3 text-sm text-red-400">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="mb-5 bg-red-500/10 border border-red-500/20 rounded-lg p-3 text-sm text-red-400 space-y-1">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
    @yield('content')
</main>

<footer class="border-t border-zinc-800/60 py-4 text-center text-zinc-600 text-xs" style="margin-bottom:64px">MonFlow &copy; {{ date('Y') }}</footer>

{{-- Mini-player (persistent, lit le localStorage sauvegardé par /player) --}}
<div id="miniPlayer" class="fixed bottom-0 left-0 right-0 h-16 bg-zinc-900/95 backdrop-blur-sm border-t border-zinc-800/60 z-50 hidden items-center px-4 gap-3">
    <img id="mpCover" src="" alt="" class="w-9 h-9 rounded-lg object-cover flex-shrink-0 bg-zinc-800" onerror="this.src=''">
    <div class="flex-1 min-w-0">
        <div id="mpTitle" class="text-sm font-medium truncate text-zinc-100">—</div>
        <div id="mpArtist" class="text-xs text-zinc-500 truncate">—</div>
    </div>
    <div class="flex items-center gap-2 flex-shrink-0">
        <button id="mpPrev" class="text-zinc-500 hover:text-zinc-200 text-lg">⏮</button>
        <button id="mpPlayPause" class="w-8 h-8 bg-indigo-600 hover:bg-indigo-500 rounded-full flex items-center justify-center text-white text-xs">▶</button>
        <button id="mpNext" class="text-zinc-500 hover:text-zinc-200 text-lg">⏭</button>
    </div>
    <input id="mpSeek" type="range" min="0" max="100" value="0" class="w-24 flex-shrink-0">
    <a href="/player" class="text-xs text-indigo-400 hover:text-indigo-300 flex-shrink-0 ml-1 whitespace-nowrap">⤢ Lecteur</a>
    <audio id="mpAudio" style="display:none"></audio>
</div>

<script>
(function() {
    if (window !== window.top) return;
    const mp = document.getElementById('miniPlayer');
    const mpAudio = document.getElementById('mpAudio');
    function ndStreamUrl(nd, id) { return `${nd.url}/rest/stream.view?u=${encodeURIComponent(nd.user)}&t=${nd.token}&s=${nd.salt}&v=${nd.version}&c=${nd.client}&id=${id}`; }
    function ndCoverUrl(nd, id)  { return `${nd.url}/rest/getCoverArt.view?u=${encodeURIComponent(nd.user)}&t=${nd.token}&s=${nd.salt}&v=${nd.version}&c=${nd.client}&id=${id}&size=50`; }
    let nd, queue = [], qidx = 0;
    try { nd = JSON.parse(localStorage.getItem('mf_nd') || 'null'); queue = JSON.parse(localStorage.getItem('mf_queue') || '[]'); qidx = parseInt(localStorage.getItem('mf_qidx') || '0', 10); } catch(e) {}
    if (!nd || !queue.length) return;
    mp.classList.remove('hidden'); mp.classList.add('flex'); document.body.style.paddingBottom = '64px';
    const now = queue[qidx]; if (!now) return;
    function loadTrack(idx, resume) {
        const s = queue[idx]; if (!s) return; qidx = idx; localStorage.setItem('mf_qidx', String(idx));
        document.getElementById('mpTitle').textContent = s.title || '—';
        document.getElementById('mpArtist').textContent = s.artist || s.album || '';
        document.getElementById('mpCover').src = ndCoverUrl(nd, s.coverArt || s.id);
        mpAudio.src = ndStreamUrl(nd, s.id);
        if (resume) { mpAudio.play().catch(() => {}); document.getElementById('mpPlayPause').textContent = '⏸'; }
    }
    const savedNow = JSON.parse(localStorage.getItem('mf_now') || 'null');
    loadTrack(qidx, false);
    if (savedNow && savedNow.playing && (Date.now() - (savedNow.ts || 0)) < 300000) {
        mpAudio.addEventListener('canplay', function seek() { mpAudio.currentTime = savedNow.time || 0; mpAudio.play().catch(() => {}); document.getElementById('mpPlayPause').textContent = '⏸'; mpAudio.removeEventListener('canplay', seek); }, { once: true });
        mpAudio.load();
    }
    document.getElementById('mpPlayPause').addEventListener('click', () => { if (mpAudio.paused) { mpAudio.play(); document.getElementById('mpPlayPause').textContent = '⏸'; } else { mpAudio.pause(); document.getElementById('mpPlayPause').textContent = '▶'; } });
    document.getElementById('mpPrev').addEventListener('click', () => loadTrack(Math.max(0, qidx - 1), true));
    document.getElementById('mpNext').addEventListener('click', () => loadTrack(Math.min(queue.length - 1, qidx + 1), true));
    mpAudio.addEventListener('ended', () => loadTrack(qidx + 1, true));
    const seek = document.getElementById('mpSeek');
    mpAudio.addEventListener('timeupdate', () => { if (mpAudio.duration) seek.value = (mpAudio.currentTime / mpAudio.duration) * 100; });
    seek.addEventListener('input', () => { if (mpAudio.duration) mpAudio.currentTime = (seek.value / 100) * mpAudio.duration; });
})();
</script>
</body>
</html>
