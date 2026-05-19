<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#09090b">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="MonFlow">
<link rel="manifest" href="/manifest.json">
<link rel="icon" type="image/x-icon" href="/favicon.ico">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
<link rel="apple-touch-startup-image" href="/icons/splash-1290x2796.png" media="(device-width: 430px) and (device-height: 932px) and (-webkit-device-pixel-ratio: 3)">
<link rel="apple-touch-startup-image" href="/icons/splash-1170x2532.png" media="(device-width: 390px) and (device-height: 844px) and (-webkit-device-pixel-ratio: 3)">
<link rel="apple-touch-startup-image" href="/icons/splash-2048x2732.png" media="(device-width: 1024px) and (device-height: 1366px) and (-webkit-device-pixel-ratio: 2)">
<title>Lecteur — MonFlow</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
    :root { --c-bg:#09090b; --c-surface:#18181b; --c-border:#27272a; --c-muted:#71717a; }
    body { background:var(--c-bg); color:#f4f4f5; }
    .card { background:var(--c-surface); border:1px solid var(--c-border); }
    .list-item:hover { background:var(--c-border); }
    .active-track { background:#4338ca !important; color:#fff; }
    ::-webkit-scrollbar { width:4px; height:4px; }
    ::-webkit-scrollbar-track { background:transparent; }
    ::-webkit-scrollbar-thumb { background:var(--c-border); border-radius:2px; }
    * { scrollbar-width:thin; scrollbar-color:var(--c-border) transparent; }
    #sidebar { transition:transform 0.25s cubic-bezier(0.4,0,0.2,1); }
    @media (max-width:639px) {
        #sidebar { position:fixed; top:0; left:0; bottom:0; z-index:300; transform:translateX(-100%); width:272px; background:var(--c-bg); border-right:1px solid var(--c-border); }
        #sidebar.open { transform:translateX(0); box-shadow:8px 0 40px rgba(0,0,0,.7); }
        #sidebarBackdrop { display:none; }
        #sidebarBackdrop.open { display:block; }
    }
    #pwaInstallBanner { display:none; }
    #pwaInstallBanner.visible { display:flex; }
    /* Range inputs */
    input[type=range] { -webkit-appearance:none; appearance:none; height:3px; background:rgba(255,255,255,.15); border-radius:2px; outline:none; cursor:pointer; }
    input[type=range]::-webkit-slider-thumb { -webkit-appearance:none; width:13px; height:13px; border-radius:50%; background:#fff; }
    input[type=range]::-moz-range-thumb { width:13px; height:13px; border-radius:50%; background:#fff; border:none; }
    #npProgress { height:4px; background:rgba(255,255,255,.2); }
    #npProgress::-webkit-slider-thumb { width:20px; height:20px; }
    #npProgress::-moz-range-thumb { width:20px; height:20px; }
    /* Now Playing */
    #nowPlayingScreen { background:var(--c-bg); }
    /* Tab bar active state */
    .tabBtn.active svg, .tabBtn.active span { color:#818cf8; }
</style>
</head>
<body class="flex flex-col overflow-hidden" style="height:100vh;height:100dvh">

{{-- ─── Header ─── --}}
<header class="card border-b flex items-center gap-3 px-4 h-14 shrink-0">
    <div class="flex items-center gap-2.5 shrink-0">
        <button id="sidebarToggle" class="sm:hidden w-9 h-9 flex items-center justify-center text-zinc-400 rounded-lg active:bg-zinc-800">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <button onclick="openPortalOverlay()" class="hidden sm:inline-flex items-center gap-1.5 text-sm text-zinc-500 hover:text-zinc-200 px-2.5 py-1.5 rounded-lg hover:bg-zinc-800 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Mon compte
        </button>
        <div class="flex items-center gap-2">
            <img src="/icons/icon-192.png" alt="MonFlow" class="w-6 h-6 rounded-lg">
            <span class="font-semibold text-zinc-100 hidden sm:inline">MonFlow</span>
        </div>
    </div>
    <div class="flex items-center gap-2 flex-1 max-w-2xl mx-auto">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="m21 21-4.35-4.35"/></svg>
            <input id="searchInput" type="search" placeholder="Titre, artiste, album…"
                   class="w-full pl-9 pr-3 py-2 bg-zinc-800/80 border border-zinc-700/60 rounded-xl text-sm placeholder-zinc-500 focus:outline-none focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 transition">
        </div>
        <button id="searchBtn" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-xl text-sm font-medium transition shrink-0">Chercher</button>
    </div>
    <div class="hidden sm:flex items-center gap-2 shrink-0">
        <span class="text-sm text-zinc-500">{{ Auth::user()->username }}</span>
        <button id="pwaInstallBtn" class="hidden px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs rounded-lg font-medium transition">↓ Installer</button>
    </div>
</header>

{{-- PWA Banner --}}
<div id="pwaInstallBanner" class="items-center justify-between gap-3 px-4 py-2 bg-indigo-500/10 border-b border-indigo-500/20 text-xs">
    <span class="text-indigo-300">Installez MonFlow sur votre écran d'accueil pour une expérience plein écran.</span>
    <div class="flex items-center gap-2 shrink-0">
        <button id="pwaInstallBannerBtn" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg font-medium transition">Installer</button>
        <button id="pwaInstallBannerDismiss" class="text-zinc-400 hover:text-white px-1 text-xl leading-none">&times;</button>
    </div>
</div>

{{-- Sidebar backdrop --}}
<div id="sidebarBackdrop" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[299] sm:hidden" onclick="closeSidebar()"></div>

<main class="flex-1 flex overflow-hidden relative min-h-0">
    {{-- Portal overlay --}}
    <div id="portalOverlay" class="hidden absolute inset-0 z-[200] bg-zinc-950">
        <iframe id="portalFrame" src="" class="w-full h-full border-0"></iframe>
        <button onclick="closePortalOverlay()"
            class="absolute top-3 right-4 z-[201] flex items-center gap-1.5 px-3 py-1.5 bg-zinc-900 hover:bg-zinc-800 border border-zinc-700 text-indigo-400 hover:text-indigo-300 rounded-full text-xs shadow-xl transition">
            ← Retour au lecteur
        </button>
    </div>

    {{-- Sidebar --}}
    <aside id="sidebar" class="w-52 card border-r p-3 shrink-0 flex flex-col gap-0.5 text-sm overflow-y-auto">
        <div class="flex items-center justify-between mb-2 sm:hidden">
            <button onclick="openPortalOverlay()" class="text-indigo-400 text-xs hover:text-indigo-300">Mon compte</button>
            <button onclick="closeSidebar()" class="w-8 h-8 flex items-center justify-center text-zinc-400 hover:text-white rounded-lg hover:bg-zinc-800 text-xl leading-none">&times;</button>
        </div>
        <div class="text-[10px] font-semibold text-zinc-600 uppercase tracking-wider px-2 pt-1 pb-1">Navigation</div>
        <button data-view="artists" class="nav-btn text-left px-3 py-2 rounded-lg hover:bg-zinc-800 text-zinc-400 hover:text-zinc-100 transition text-sm">Artistes</button>
        <button data-view="albums" class="nav-btn text-left px-3 py-2 rounded-lg hover:bg-zinc-800 text-zinc-400 hover:text-zinc-100 transition text-sm">Albums récents</button>
        <button data-view="random" class="nav-btn text-left px-3 py-2 rounded-lg hover:bg-zinc-800 text-zinc-400 hover:text-zinc-100 transition text-sm">Lecture aléatoire</button>
        <div class="text-[10px] font-semibold text-zinc-600 uppercase tracking-wider px-2 pt-3 pb-1">Classements</div>
        <button data-view="weekArtists" class="nav-btn text-left px-3 py-2 rounded-lg hover:bg-zinc-800 text-zinc-400 hover:text-zinc-100 transition text-sm w-full">Top artistes</button>
        <button data-view="weekSongs" class="nav-btn text-left px-3 py-2 rounded-lg hover:bg-zinc-800 text-zinc-400 hover:text-zinc-100 transition text-sm w-full">Top titres</button>
        <button data-view="weekAlbums" class="nav-btn text-left px-3 py-2 rounded-lg hover:bg-zinc-800 text-zinc-400 hover:text-zinc-100 transition text-sm w-full">Ajouts récents</button>
        <div class="border-t border-zinc-800 mt-3 pt-3">
            <div class="flex items-center justify-between mb-1.5 px-2">
                <div class="text-[10px] font-semibold text-zinc-600 uppercase tracking-wider">File d'attente (<span id="queueCount">0</span>)</div>
                <button id="saveQueueBtn" class="text-xs text-indigo-400 hover:text-indigo-300 hidden" title="Sauvegarder">💾</button>
            </div>
            <div id="queueList" class="space-y-0.5 overflow-y-auto max-h-40"></div>
        </div>
        <div class="border-t border-zinc-800 mt-3 pt-3">
            <div class="flex items-center justify-between mb-1.5 px-2">
                <div class="text-[10px] font-semibold text-zinc-600 uppercase tracking-wider">Playlists</div>
                <button id="newPlaylistPlayerBtn" class="text-xs text-indigo-400 hover:text-indigo-300 w-6 h-6 flex items-center justify-center rounded hover:bg-zinc-800">+</button>
            </div>
            <div id="playlistNavList" class="space-y-0.5 overflow-y-auto max-h-52"></div>
        </div>
    </aside>

    {{-- Main content --}}
    <section class="flex-1 flex flex-col overflow-hidden min-w-0">
        <div class="px-5 py-3 border-b border-zinc-800/60 flex items-center justify-between shrink-0">
            <h2 id="viewTitle" class="text-base font-semibold text-zinc-100">Artistes</h2>
            <span id="viewCount" class="text-xs text-zinc-500"></span>
        </div>
        <div id="mainArea" class="flex-1 overflow-y-auto p-4"></div>
    </section>
</main>

{{-- ─── Bottom: player bar + mobile tab bar ─── --}}
<div class="shrink-0">
    <div class="border-t border-zinc-800/60 bg-zinc-950/95 backdrop-blur-sm">
        {{-- Mobile: mini player (tappable → opens Now Playing) --}}
        <div id="mobileBar" class="sm:hidden flex items-center gap-3 px-4 py-2.5 cursor-pointer select-none active:bg-zinc-800/30 transition">
            <div id="mbCoverArt" class="w-11 h-11 bg-zinc-800 rounded-xl flex items-center justify-center shrink-0 overflow-hidden">
                <svg class="w-5 h-5 text-zinc-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <div id="mbTitle" class="text-sm font-medium truncate text-zinc-100">Aucune piste</div>
                <div id="mbArtist" class="text-xs text-zinc-500 truncate">—</div>
            </div>
            <div class="flex items-center gap-1 shrink-0">
                <button id="mbPlay" class="w-10 h-10 bg-white text-zinc-950 rounded-full flex items-center justify-center active:scale-90 transition-transform shadow-md" onclick="event.stopPropagation();togglePlay()">
                    <svg id="mbPlayIcon" class="w-4 h-4 translate-x-px" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                </button>
                <button class="w-10 h-10 flex items-center justify-center text-zinc-400 rounded-lg active:text-white" id="mbNext" onclick="event.stopPropagation()">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z"/></svg>
                </button>
            </div>
        </div>

        {{-- Desktop: full controls --}}
        <div class="hidden sm:flex items-center h-20 px-6 gap-6">
            <div class="flex items-center gap-3 min-w-0 w-72 shrink-0">
                <div id="coverArt" class="w-12 h-12 bg-zinc-900 rounded-xl flex items-center justify-center shrink-0 overflow-hidden">
                    <svg class="w-5 h-5 text-zinc-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg>
                </div>
                <div class="min-w-0">
                    <div id="trackTitle" class="text-sm font-medium truncate text-zinc-100">Aucune piste</div>
                    <div id="trackArtist" class="text-xs text-zinc-500 truncate">—</div>
                </div>
            </div>
            <div class="flex-1 flex flex-col items-center gap-1.5 max-w-xl mx-auto">
                <div class="flex items-center gap-3">
                    <button id="shuffleBtn" class="w-8 h-8 flex items-center justify-center text-zinc-600 hover:text-zinc-300 rounded-lg hover:bg-zinc-800 transition" title="Aléatoire">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 3h5v5M4 20l17-17M16 21h5v-5M4 4l5 5"/></svg>
                    </button>
                    <button id="prevBtn" class="w-9 h-9 flex items-center justify-center text-zinc-400 hover:text-zinc-100 rounded-lg hover:bg-zinc-800 transition">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M6 6h2v12H6zm3.5 6 8.5 6V6z"/></svg>
                    </button>
                    <button id="playBtn" class="w-10 h-10 bg-white text-zinc-950 rounded-full flex items-center justify-center hover:scale-105 transition shadow-lg">
                        <svg id="playBtnIcon" class="w-5 h-5 translate-x-px" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    </button>
                    <button id="nextBtn" class="w-9 h-9 flex items-center justify-center text-zinc-400 hover:text-zinc-100 rounded-lg hover:bg-zinc-800 transition">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z"/></svg>
                    </button>
                    <button id="repeatBtn" class="w-8 h-8 flex items-center justify-center text-zinc-600 hover:text-zinc-300 rounded-lg hover:bg-zinc-800 transition" title="Répéter">
                        <svg id="repeatBtnIcon" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 1l4 4-4 4M3 11V9a4 4 0 014-4h14M7 23l-4-4 4-4M21 13v2a4 4 0 01-4 4H3"/></svg>
                    </button>
                </div>
                <div class="flex items-center gap-2 w-full">
                    <span id="curTime" class="text-xs text-zinc-500 w-9 text-right tabular-nums">0:00</span>
                    <input id="progress" type="range" min="0" max="100" value="0" class="flex-1 accent-indigo-500">
                    <span id="totTime" class="text-xs text-zinc-500 w-9 tabular-nums">0:00</span>
                </div>
            </div>
            <div class="hidden sm:flex items-center gap-2 w-56 justify-end shrink-0">
                <button id="addToPlaylistBtn" class="hidden items-center gap-1.5 text-sm px-2.5 py-1.5 rounded-lg hover:bg-zinc-800 transition">
                    <span id="addToPlaylistHeart" class="text-zinc-400">♡</span>
                    <span id="addToPlaylistLabel" class="text-xs text-zinc-400 max-w-[80px] truncate hidden"></span>
                </button>
                <button id="lyricsBtn" class="text-zinc-500 hover:text-zinc-200 text-xs font-bold px-2.5 py-1.5 rounded-lg hover:bg-zinc-800 transition font-mono tracking-tight">Aa</button>
                <svg class="w-4 h-4 text-zinc-600 ml-1 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                <input id="volume" type="range" min="0" max="100" value="80" class="w-20 accent-indigo-500">
            </div>
        </div>
    </div>

    {{-- Mobile tab bar --}}
    <nav class="sm:hidden flex bg-zinc-950 border-t border-zinc-800/60" style="padding-bottom:env(safe-area-inset-bottom,0)">
        <button data-tab="home" class="tabBtn flex-1 flex flex-col items-center justify-center gap-0.5 py-2.5 text-zinc-500 active transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline stroke-linecap="round" stroke-linejoin="round" points="9 22 9 12 15 12 15 22"/></svg>
            <span class="text-[10px] font-medium">Accueil</span>
        </button>
        <button data-tab="search" class="tabBtn flex-1 flex flex-col items-center justify-center gap-0.5 py-2.5 text-zinc-500 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="m21 21-4.35-4.35"/></svg>
            <span class="text-[10px] font-medium">Rechercher</span>
        </button>
        <button data-tab="library" class="tabBtn flex-1 flex flex-col items-center justify-center gap-0.5 py-2.5 text-zinc-500 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h10"/></svg>
            <span class="text-[10px] font-medium">Bibliothèque</span>
        </button>
        <button data-tab="account" class="tabBtn flex-1 flex flex-col items-center justify-center gap-0.5 py-2.5 text-zinc-500 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4" stroke-linecap="round"/></svg>
            <span class="text-[10px] font-medium">Compte</span>
        </button>
    </nav>
</div>

{{-- ─── Now Playing Screen (mobile full-screen) ─── --}}
<div id="nowPlayingScreen" class="sm:hidden fixed inset-0 z-[500] flex flex-col overflow-hidden"
     style="transform:translateY(100%);transition:transform 0.35s cubic-bezier(0.4,0,0.2,1)">

    {{-- Ambient blurred background --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
        <img id="npBgImg" src="" alt="" class="absolute inset-0 w-full h-full object-cover" style="transform:scale(1.15);filter:blur(60px) saturate(2) brightness(0.18)">
        <div class="absolute inset-0 bg-zinc-950/65"></div>
    </div>

    {{-- Content --}}
    <div class="relative flex flex-col h-full" style="padding-top:env(safe-area-inset-top,0);padding-bottom:env(safe-area-inset-bottom,16px)">

        {{-- Top bar --}}
        <div class="flex items-center justify-between px-5 py-3 shrink-0">
            <button id="npClose" class="w-11 h-11 flex items-center justify-center text-zinc-400 rounded-full active:bg-white/10">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <span class="text-xs font-semibold text-zinc-500 uppercase tracking-widest">En lecture</span>
            <button id="npOptions" class="w-11 h-11 flex items-center justify-center text-zinc-400 rounded-full active:bg-white/10">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/></svg>
            </button>
        </div>

        {{-- Cover art --}}
        <div class="flex-1 flex items-center justify-center px-10 py-2 min-h-0">
            <div class="w-full aspect-square max-h-full rounded-2xl overflow-hidden shadow-2xl shadow-black/80 bg-zinc-800/80">
                <div id="npCoverArt" class="w-full h-full flex items-center justify-center">
                    <svg class="w-16 h-16 text-zinc-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/></svg>
                </div>
            </div>
        </div>

        {{-- Track info + controls --}}
        <div class="px-8 shrink-0">
            {{-- Title + heart --}}
            <div class="flex items-start justify-between gap-4 mb-5">
                <div class="min-w-0 flex-1">
                    <div id="npTitle" class="text-2xl font-bold text-white leading-tight truncate">—</div>
                    <div id="npArtist" class="text-zinc-400 text-sm mt-1.5 truncate">—</div>
                </div>
                <button id="npHeart" class="w-11 h-11 flex items-center justify-center rounded-full active:scale-110 transition-transform shrink-0 mt-0.5">
                    <svg id="npHeartIcon" class="w-6 h-6 text-zinc-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg>
                </button>
            </div>

            {{-- Seek bar --}}
            <div class="py-1 mb-1">
                <input id="npProgress" type="range" min="0" max="100" value="0" class="w-full accent-white">
            </div>
            <div class="flex justify-between text-xs text-zinc-500 mb-7 tabular-nums">
                <span id="npCurTime">0:00</span>
                <span id="npTotTime">0:00</span>
            </div>

            {{-- Shuffle / Prev / Play / Next / Repeat --}}
            <div class="flex items-center justify-between mb-8">
                <button id="npShuffle" class="w-11 h-11 flex items-center justify-center text-zinc-600 rounded-full active:bg-white/10 transition" title="Aléatoire">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 3h5v5M4 20l17-17M16 21h5v-5M4 4l5 5"/></svg>
                </button>
                <button id="npPrev" class="w-12 h-12 flex items-center justify-center text-zinc-200 rounded-full active:bg-white/10 transition">
                    <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24"><path d="M6 6h2v12H6zm3.5 6 8.5 6V6z"/></svg>
                </button>
                <button id="npPlay" class="w-16 h-16 bg-white text-zinc-950 rounded-full flex items-center justify-center shadow-2xl active:scale-95 transition-transform">
                    <svg id="npPlayIcon" class="w-7 h-7 translate-x-px" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                </button>
                <button id="npNext" class="w-12 h-12 flex items-center justify-center text-zinc-200 rounded-full active:bg-white/10 transition">
                    <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24"><path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z"/></svg>
                </button>
                <button id="npRepeat" class="w-11 h-11 flex items-center justify-center text-zinc-600 rounded-full active:bg-white/10 transition" title="Répéter">
                    <svg id="npRepeatIcon" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 1l4 4-4 4M3 11V9a4 4 0 014-4h14M7 23l-4-4 4-4M21 13v2a4 4 0 01-4 4H3"/></svg>
                </button>
            </div>

            {{-- Action buttons --}}
            <div class="flex items-center justify-around mb-2">
                <button id="npLyricsBtn" class="flex flex-col items-center gap-1 text-zinc-500 py-2 px-5 rounded-xl active:bg-white/10 transition-colors">
                    <span class="text-xl leading-none font-bold font-mono">Aa</span>
                    <span class="text-[10px] uppercase tracking-wide mt-0.5">Paroles</span>
                </button>
                <button id="npQueueBtn" class="flex flex-col items-center gap-1 text-zinc-500 py-2 px-5 rounded-xl active:bg-white/10 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h10"/></svg>
                    <span class="text-[10px] uppercase tracking-wide mt-0.5">File</span>
                </button>
                <button id="npMenuBtn" class="flex flex-col items-center gap-1 text-zinc-500 py-2 px-5 rounded-xl active:bg-white/10 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h10M4 18h7"/></svg>
                    <span class="text-[10px] uppercase tracking-wide mt-0.5">Menu</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Sheet: paroles --}}
    <div id="npLyricsSheet" class="absolute inset-x-0 bottom-0 rounded-t-2xl flex flex-col overflow-hidden bg-zinc-900/98 border-t border-zinc-800"
         style="height:75%;transform:translateY(100%);transition:transform 0.3s cubic-bezier(0.4,0,0.2,1);padding-bottom:env(safe-area-inset-bottom,0)">
        <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-800 shrink-0">
            <span class="font-semibold text-zinc-100">Paroles</span>
            <button id="npLyricsClose" class="w-8 h-8 flex items-center justify-center text-zinc-400 hover:text-white rounded-lg hover:bg-zinc-800 text-2xl">&times;</button>
        </div>
        <div id="npLyricsContent" class="flex-1 overflow-y-auto p-5 text-sm text-zinc-300 leading-relaxed"></div>
    </div>

    {{-- Sheet: file d'attente --}}
    <div id="npQueueSheet" class="absolute inset-x-0 bottom-0 rounded-t-2xl flex flex-col overflow-hidden bg-zinc-900/98 border-t border-zinc-800"
         style="height:75%;transform:translateY(100%);transition:transform 0.3s cubic-bezier(0.4,0,0.2,1);padding-bottom:env(safe-area-inset-bottom,0)">
        <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-800 shrink-0">
            <span class="font-semibold text-zinc-100">File d'attente <span id="npQueueCount" class="text-zinc-500 font-normal text-sm"></span></span>
            <button id="npQueueClose" class="w-8 h-8 flex items-center justify-center text-zinc-400 hover:text-white rounded-lg hover:bg-zinc-800 text-2xl">&times;</button>
        </div>
        <div id="npQueueList" class="flex-1 overflow-y-auto divide-y divide-zinc-800/50"></div>
    </div>
</div>

{{-- Desktop lyrics panel --}}
<div id="lyricsPanel" class="fixed right-0 top-14 bottom-20 w-80 card border-l transform translate-x-full transition-transform duration-300 z-50 flex flex-col" style="display:none">
    <div class="px-4 py-3 border-b border-zinc-800 flex items-center justify-between shrink-0">
        <span class="text-sm font-semibold">Paroles</span>
        <button id="lyricsClose" class="text-zinc-400 hover:text-white text-lg">&times;</button>
    </div>
    <div id="lyricsContent" class="flex-1 overflow-y-auto p-4 text-sm text-zinc-300 leading-relaxed"></div>
</div>

<audio id="audio"></audio>

{{-- Modals --}}
<div id="shareModal" class="fixed inset-0 bg-black/70 flex items-end sm:items-center justify-center z-50 p-4" style="display:none">
    <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-5 w-full max-w-sm shadow-2xl">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-sm">Partager la playlist</h3>
            <button onclick="document.getElementById('shareModal').style.display='none'" class="text-zinc-400 hover:text-white w-8 h-8 flex items-center justify-center rounded-lg hover:bg-zinc-800">&times;</button>
        </div>
        <div id="sharePlaylistName" class="text-xs text-zinc-500 mb-3 truncate"></div>
        <input type="hidden" id="sharePlaylistId">
        <div class="relative mb-3">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-500 text-sm">#</span>
            <input id="shareTargetInput" type="text" placeholder="pseudo du destinataire"
                class="w-full pl-7 pr-3 py-2.5 bg-zinc-800 border border-zinc-700 rounded-xl text-sm placeholder-zinc-500 focus:outline-none focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20">
        </div>
        <div class="flex gap-2">
            <button onclick="document.getElementById('shareModal').style.display='none'" class="flex-1 py-2.5 bg-zinc-800 hover:bg-zinc-700 rounded-xl text-xs transition">Annuler</button>
            <button onclick="confirmSharePlaylist()" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-medium transition">Partager</button>
        </div>
    </div>
</div>

<div id="playlistPickerModal" class="fixed inset-0 bg-black/70 flex items-end sm:items-center justify-center z-50 p-4" style="display:none">
    <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-5 w-full max-w-sm shadow-2xl">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-sm">Ajouter à une playlist</h3>
            <button onclick="document.getElementById('playlistPickerModal').style.display='none'" class="text-zinc-400 hover:text-white w-8 h-8 flex items-center justify-center rounded-lg hover:bg-zinc-800">&times;</button>
        </div>
        <div id="pickerSongInfo" class="text-xs text-zinc-500 mb-3 truncate"></div>
        <div id="pickerList" class="space-y-1 max-h-52 overflow-y-auto mb-3"></div>
        <button onclick="openNewPlaylistFromPicker()"
            class="w-full py-2.5 border border-dashed border-zinc-700 hover:border-indigo-500 text-zinc-400 hover:text-indigo-400 rounded-xl text-xs transition">
            + Nouvelle playlist
        </button>
    </div>
</div>

<div id="renamePlaylistModal" class="fixed inset-0 bg-black/70 flex items-end sm:items-center justify-center z-50 p-4" style="display:none">
    <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-5 w-full max-w-xs shadow-2xl">
        <h3 class="font-semibold text-sm mb-3">Renommer la playlist</h3>
        <input type="hidden" id="renamePlaylistId">
        <input id="renamePlaylistInput" type="text" placeholder="Nouveau nom…" maxlength="200"
            class="w-full px-3 py-2.5 bg-zinc-800 border border-zinc-700 rounded-xl text-sm placeholder-zinc-500 focus:outline-none focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 mb-3">
        <div class="flex gap-2">
            <button onclick="document.getElementById('renamePlaylistModal').style.display='none'" class="flex-1 py-2.5 bg-zinc-800 hover:bg-zinc-700 rounded-xl text-xs transition">Annuler</button>
            <button onclick="confirmRenamePlaylist()" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-medium transition">Renommer</button>
        </div>
    </div>
</div>

<div id="newPlaylistModal" class="fixed inset-0 bg-black/70 flex items-end sm:items-center justify-center z-50 p-4" style="display:none">
    <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-5 w-full max-w-xs shadow-2xl">
        <h3 class="font-semibold text-sm mb-3">Nouvelle playlist</h3>
        <input id="newPlaylistName" type="text" placeholder="Nom…" maxlength="200"
            class="w-full px-3 py-2.5 bg-zinc-800 border border-zinc-700 rounded-xl text-sm placeholder-zinc-500 focus:outline-none focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 mb-3">
        <div id="newPlaylistSongId" data-song-id=""></div>
        <div class="flex gap-2">
            <button onclick="closeNewPlaylistModal()" class="flex-1 py-2.5 bg-zinc-800 hover:bg-zinc-700 rounded-xl text-xs transition">Annuler</button>
            <button onclick="createAndAddPlaylist()" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-medium transition">Créer</button>
        </div>
    </div>
</div>

<script>
const ND = {
    url: @json($ndUrl),
    user: @json($ndUser),
    salt: @json($ndSalt),
    token: @json($ndToken),
    client: 'MonFlow',
    version: '1.16.1',
    format: 'json'
};

function ndUrl(endpoint, params = {}) {
    const qs = new URLSearchParams({ u: ND.user, t: ND.token, s: ND.salt, v: ND.version, c: ND.client, f: ND.format, ...params });
    return `${ND.url}/rest/${endpoint}?${qs}`;
}

async function ndCall(endpoint, params = {}) {
    try {
        const r = await fetch(ndUrl(endpoint, params));
        const j = await r.json();
        const resp = j['subsonic-response'];
        if (resp.status !== 'ok') throw new Error(resp.error?.message || 'Erreur Subsonic');
        return resp;
    } catch (e) {
        alert('Erreur Navidrome : ' + e.message);
        throw e;
    }
}

function streamUrl(id) { return ndUrl('stream.view', { id }); }
function coverUrl(id, size = 100) { return ndUrl('getCoverArt.view', { id, size }); }

// ─── State ───
const state = {
    queue: [],
    currentIndex: -1,
    shuffle: false,
    repeat: 'none', // 'none' | 'one' | 'all'
};

const audio = document.getElementById('audio');
const mainArea = document.getElementById('mainArea');
const viewTitle = document.getElementById('viewTitle');
const viewCount = document.getElementById('viewCount');

// ─── Views ───
async function loadArtists() {
    viewTitle.textContent = 'Artistes';
    mainArea.innerHTML = '<div class="text-zinc-500 text-center py-10">Chargement…</div>';
    const resp = await ndCall('getArtists.view');
    const indexes = resp.artists?.index || [];
    const all = indexes.flatMap(i => i.artist || []);
    viewCount.textContent = `${all.length} artistes`;
    mainArea.innerHTML = '<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3"></div>';
    const grid = mainArea.firstElementChild;
    all.forEach(a => {
        const el = document.createElement('button');
        el.className = 'bg-zinc-900 border border-zinc-800 rounded-xl p-3 text-left hover:bg-zinc-800 transition';
        el.innerHTML = `<div class="font-medium truncate text-zinc-100 text-sm">${escapeHtml(a.name)}</div><div class="text-xs text-zinc-500 mt-1">${a.albumCount || 0} albums</div>`;
        el.onclick = () => loadArtist(a.id, a.name);
        grid.appendChild(el);
    });
}

async function loadArtist(id, name) {
    viewTitle.textContent = name;
    mainArea.innerHTML = '<div class="text-zinc-500 text-center py-10">Chargement…</div>';
    const resp = await ndCall('getArtist.view', { id });
    const albums = resp.artist?.album || [];
    viewCount.textContent = `${albums.length} albums`;
    mainArea.innerHTML = '<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3"></div>';
    const grid = mainArea.firstElementChild;
    albums.forEach(al => {
        const el = document.createElement('button');
        el.className = 'bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden text-left hover:bg-zinc-800 transition';
        el.innerHTML = `
            <div class="aspect-square bg-zinc-800 flex items-center justify-center">
                <img src="${coverUrl(al.coverArt || al.id, 300)}" class="w-full h-full object-cover" onerror="this.style.display='none';this.parentElement.innerHTML='<svg class=\'w-8 h-8 text-zinc-600\' fill=\'currentColor\' viewBox=\'0 0 24 24\'><path d=\'M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z\'/></svg>'">
            </div>
            <div class="p-2.5">
                <div class="font-medium truncate text-sm text-zinc-100">${escapeHtml(al.name)}</div>
                <div class="text-xs text-zinc-500 truncate mt-0.5">${al.year || ''} · ${al.songCount || 0} titres</div>
            </div>`;
        el.onclick = () => loadAlbum(al.id, al.name);
        grid.appendChild(el);
    });
}

async function loadAlbum(id, name) {
    viewTitle.textContent = name;
    mainArea.innerHTML = '<div class="text-zinc-500 text-center py-10">Chargement…</div>';
    const resp = await ndCall('getAlbum.view', { id });
    const songs = resp.album?.song || [];
    viewCount.textContent = `${songs.length} titres`;

    const container = document.createElement('div');
    container.className = 'space-y-0.5';
    const header = document.createElement('div');
    header.className = 'mb-4 flex gap-2';
    header.innerHTML = `
        <button class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-xl text-sm font-medium transition">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg> Tout lire
        </button>
        <button class="px-4 py-2 bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 rounded-xl text-sm transition">+ File d'attente</button>`;
    header.children[0].onclick = () => { state.queue = [...songs]; playIndex(0); };
    header.children[1].onclick = () => { state.queue.push(...songs); renderQueue(); };
    container.appendChild(header);

    songs.forEach((s, i) => {
        const el = document.createElement('div');
        el.className = 'list-item flex items-center gap-3 px-3 py-2.5 rounded-xl cursor-pointer text-sm';
        el.innerHTML = `
            <span class="w-6 text-zinc-600 text-xs text-right shrink-0">${s.track || i + 1}</span>
            <div class="flex-1 min-w-0">
                <div class="truncate text-zinc-100">${escapeHtml(s.title)}</div>
                <div class="text-xs text-zinc-500 truncate mt-0.5">${escapeHtml(s.artist || '')}</div>
            </div>
            <span class="text-xs text-zinc-600 tabular-nums">${formatTime(s.duration || 0)}</span>`;
        el.onclick = () => { state.queue = [...songs]; playIndex(i); };
        container.appendChild(el);
    });

    mainArea.innerHTML = '';
    mainArea.appendChild(container);
}

async function loadAlbums() {
    viewTitle.textContent = 'Albums récents';
    mainArea.innerHTML = '<div class="text-zinc-500 text-center py-10">Chargement…</div>';
    const resp = await ndCall('getAlbumList2.view', { type: 'newest', size: 50 });
    const albums = resp.albumList2?.album || [];
    viewCount.textContent = `${albums.length} albums`;
    mainArea.innerHTML = '<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3"></div>';
    const grid = mainArea.firstElementChild;
    albums.forEach(al => {
        const el = document.createElement('button');
        el.className = 'bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden text-left hover:bg-zinc-800 transition';
        el.innerHTML = `
            <div class="aspect-square bg-zinc-800">
                <img src="${coverUrl(al.coverArt || al.id, 300)}" class="w-full h-full object-cover" onerror="this.style.display='none'">
            </div>
            <div class="p-2.5">
                <div class="font-medium truncate text-sm text-zinc-100">${escapeHtml(al.name)}</div>
                <div class="text-xs text-zinc-500 truncate mt-0.5">${escapeHtml(al.artist || '')}</div>
            </div>`;
        el.onclick = () => loadAlbum(al.id, al.name);
        grid.appendChild(el);
    });
}

async function loadRandom() {
    viewTitle.textContent = 'Lecture aléatoire';
    mainArea.innerHTML = '<div class="text-zinc-500 text-center py-10">Chargement…</div>';
    const resp = await ndCall('getRandomSongs.view', { size: 50 });
    const songs = resp.randomSongs?.song || [];
    viewCount.textContent = `${songs.length} titres`;

    const container = document.createElement('div');
    container.className = 'space-y-0.5';
    const header = document.createElement('div');
    header.className = 'mb-4';
    header.innerHTML = `<button class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-xl text-sm font-medium transition"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg> Tout lire</button>`;
    header.children[0].onclick = () => { state.queue = [...songs]; playIndex(0); };
    container.appendChild(header);

    songs.forEach((s, i) => {
        const el = document.createElement('div');
        el.className = 'list-item flex items-center gap-3 px-3 py-2.5 rounded-xl cursor-pointer text-sm';
        el.innerHTML = `
            <div class="flex-1 min-w-0">
                <div class="truncate text-zinc-100">${escapeHtml(s.title)}</div>
                <div class="text-xs text-zinc-500 truncate mt-0.5">${escapeHtml(s.artist || '')} · ${escapeHtml(s.album || '')}</div>
            </div>
            <span class="text-xs text-zinc-600 tabular-nums">${formatTime(s.duration || 0)}</span>`;
        el.onclick = () => { state.queue = [...songs]; playIndex(i); };
        container.appendChild(el);
    });
    mainArea.innerHTML = '';
    mainArea.appendChild(container);
}

async function doSearch(q) {
    viewTitle.textContent = `Recherche : ${q}`;
    mainArea.innerHTML = '<div class="text-zinc-500 text-center py-10">Recherche…</div>';
    const resp = await ndCall('search3.view', { query: q });
    const r = resp.searchResult3 || {};
    const artists = r.artist || [], albums = r.album || [], songs = r.song || [];
    viewCount.textContent = `${artists.length} artistes, ${albums.length} albums, ${songs.length} titres`;

    const c = document.createElement('div');
    c.className = 'space-y-6';

    if (artists.length) {
        const sec = document.createElement('div');
        sec.innerHTML = '<h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-3">Artistes</h3>';
        const grid = document.createElement('div');
        grid.className = 'grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3';
        artists.forEach(a => {
            const el = document.createElement('button');
            el.className = 'bg-zinc-900 border border-zinc-800 rounded-xl p-3 text-left hover:bg-zinc-800 transition';
            el.innerHTML = `<div class="font-medium truncate text-sm text-zinc-100">${escapeHtml(a.name)}</div>`;
            el.onclick = () => loadArtist(a.id, a.name);
            grid.appendChild(el);
        });
        sec.appendChild(grid);
        c.appendChild(sec);
    }

    if (albums.length) {
        const sec = document.createElement('div');
        sec.innerHTML = '<h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-3">Albums</h3>';
        const grid = document.createElement('div');
        grid.className = 'grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3';
        albums.forEach(al => {
            const el = document.createElement('button');
            el.className = 'bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden text-left hover:bg-zinc-800 transition';
            el.innerHTML = `
                <div class="aspect-square bg-zinc-800"><img src="${coverUrl(al.coverArt || al.id, 300)}" class="w-full h-full object-cover" onerror="this.style.display='none'"></div>
                <div class="p-2.5"><div class="font-medium truncate text-sm text-zinc-100">${escapeHtml(al.name)}</div><div class="text-xs text-zinc-500 truncate mt-0.5">${escapeHtml(al.artist || '')}</div></div>`;
            el.onclick = () => loadAlbum(al.id, al.name);
            grid.appendChild(el);
        });
        sec.appendChild(grid);
        c.appendChild(sec);
    }

    if (songs.length) {
        const sec = document.createElement('div');
        sec.innerHTML = '<h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-3">Titres</h3>';
        const list = document.createElement('div');
        list.className = 'space-y-0.5';
        songs.forEach((s, i) => {
            const el = document.createElement('div');
            el.className = 'list-item flex items-center gap-3 px-3 py-2.5 rounded-xl cursor-pointer text-sm';
            el.innerHTML = `<div class="flex-1 min-w-0"><div class="truncate text-zinc-100">${escapeHtml(s.title)}</div><div class="text-xs text-zinc-500 truncate mt-0.5">${escapeHtml(s.artist || '')} · ${escapeHtml(s.album || '')}</div></div><span class="text-xs text-zinc-600 tabular-nums">${formatTime(s.duration || 0)}</span>`;
            el.onclick = () => { state.queue = [...songs]; playIndex(i); };
            list.appendChild(el);
        });
        sec.appendChild(list);
        c.appendChild(sec);
    }

    mainArea.innerHTML = '';
    mainArea.appendChild(c);
}

// ─── Playback ───
function playIndex(i) {
    if (i < 0 || i >= state.queue.length) return;
    state.currentIndex = i;
    const s = state.queue[i];
    audio.src = streamUrl(s.id);
    audio.play().catch(() => {});
    const artworkUrl = coverUrl(s.coverArt || s.id, 300);

    // Desktop footer
    document.getElementById('trackTitle').textContent  = s.title  || '—';
    document.getElementById('trackArtist').textContent = `${s.artist || ''} · ${s.album || ''}`;
    const cover = document.getElementById('coverArt');
    if (s.coverArt || s.id) cover.innerHTML = `<img src="${coverUrl(s.coverArt || s.id, 100)}" class="w-full h-full object-cover" onerror="this.parentElement.innerHTML='<svg class=\'w-5 h-5 text-zinc-600\' fill=\'currentColor\' viewBox=\'0 0 24 24\'><path d=\'M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z\'/></svg>'">`;

    syncMobileBar(s);
    syncNowPlaying(s, artworkUrl);
    renderQueue();
    renderNpQueue();
    if (lyricsVisible) loadLyrics();
    document.getElementById('addToPlaylistBtn').classList.remove('hidden');
    document.getElementById('addToPlaylistBtn').classList.add('flex');
    document.getElementById('saveQueueBtn').classList.remove('hidden');
    updateAddToPlaylistBtn(s.id);
    saveStateToStorage();

    if ('mediaSession' in navigator) {
        navigator.mediaSession.metadata = new MediaMetadata({
            title: s.title || '—', artist: s.artist || '', album: s.album || '',
            artwork: [{ src: artworkUrl, sizes: '300x300', type: 'image/jpeg' }],
        });
        navigator.mediaSession.setActionHandler('play',          () => { audio.play(); setPlayState(true); });
        navigator.mediaSession.setActionHandler('pause',         () => { audio.pause(); setPlayState(false); });
        navigator.mediaSession.setActionHandler('previoustrack', () => playIndex(state.currentIndex - 1));
        navigator.mediaSession.setActionHandler('nexttrack',     () => playIndex(state.currentIndex + 1));
        navigator.mediaSession.setActionHandler('seekto',        e  => { if (e.seekTime !== undefined) audio.currentTime = e.seekTime; });
    }
}

const PLAY_PATH  = 'M8 5v14l11-7z';
const PAUSE_PATH = 'M6 19h4V5H6v14zm8-14v14h4V5h-4z';

function setPlayState(playing) {
    const path = playing ? PAUSE_PATH : PLAY_PATH;
    ['playBtnIcon','npPlayIcon','mbPlayIcon'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.innerHTML = `<path d="${path}"/>`;
    });
    if ('mediaSession' in navigator) navigator.mediaSession.playbackState = playing ? 'playing' : 'paused';
}

function syncMobileBar(s) {
    document.getElementById('mbTitle').textContent  = s.title  || '—';
    document.getElementById('mbArtist').textContent = s.artist || s.album || '';
    const el = document.getElementById('mbCoverArt');
    if (s.coverArt || s.id) el.innerHTML = `<img src="${coverUrl(s.coverArt || s.id, 80)}" class="w-full h-full object-cover" onerror="this.parentElement.innerHTML='<svg class=\'w-5 h-5 text-zinc-600\' fill=\'currentColor\' viewBox=\'0 0 24 24\'><path d=\'M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z\'/></svg>'">`;
}

function syncNowPlaying(s, artworkUrl) {
    document.getElementById('npTitle').textContent  = s.title  || '—';
    document.getElementById('npArtist').textContent = `${s.artist || ''}${s.album ? ' · ' + s.album : ''}`;
    const url = artworkUrl || coverUrl(s.coverArt || s.id, 300);
    const cover = document.getElementById('npCoverArt');
    cover.innerHTML = `<img src="${url}" class="w-full h-full object-cover" onerror="this.parentElement.innerHTML='<svg class=\'w-16 h-16 text-zinc-600\' fill=\'currentColor\' viewBox=\'0 0 24 24\'><path d=\'M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z\'/></svg>'">`;
    // Ambient background
    const bg = document.getElementById('npBgImg');
    if (bg) bg.src = url;
}

function renderQueue() {
    const el = document.getElementById('queueList');
    el.innerHTML = '';
    state.queue.forEach((s, i) => {
        const item = document.createElement('div');
        item.className = `px-2 py-1.5 rounded-lg cursor-pointer text-xs truncate ${i === state.currentIndex ? 'active-track' : 'text-zinc-400 hover:bg-zinc-800'}`;
        item.textContent = s.title || '—';
        item.onclick = () => playIndex(i);
        el.appendChild(item);
    });
    document.getElementById('queueCount').textContent = state.queue.length;
}

// ─── Controls ───
function togglePlay() {
    if (!audio.src) return;
    if (audio.paused) audio.play();
    else audio.pause();
}
audio.addEventListener('play',  () => setPlayState(true));
audio.addEventListener('pause', () => setPlayState(false));
audio.addEventListener('ended', () => {
    if (state.repeat === 'one') { audio.currentTime = 0; audio.play().catch(() => {}); return; }
    if (state.shuffle) {
        let idx;
        do { idx = Math.floor(Math.random() * state.queue.length); }
        while (state.queue.length > 1 && idx === state.currentIndex);
        playIndex(idx);
    } else {
        const next = state.currentIndex + 1;
        if (next < state.queue.length) playIndex(next);
        else if (state.repeat === 'all') playIndex(0);
    }
});
audio.addEventListener('timeupdate', () => {
    const pct = audio.duration ? (audio.currentTime / audio.duration) * 100 : 0;
    document.getElementById('progress').value   = pct;
    document.getElementById('npProgress').value = pct;
    const cur = formatTime(audio.currentTime);
    const tot = formatTime(audio.duration || 0);
    document.getElementById('curTime').textContent   = cur;
    document.getElementById('totTime').textContent   = tot;
    document.getElementById('npCurTime').textContent = cur;
    document.getElementById('npTotTime').textContent = tot;
});

// Desktop controls
document.getElementById('playBtn').onclick  = togglePlay;
document.getElementById('prevBtn').onclick  = () => playIndex(state.currentIndex - 1);
document.getElementById('nextBtn').onclick  = () => playIndex(state.currentIndex + 1);
document.getElementById('progress').oninput = e => { if (audio.duration) audio.currentTime = (e.target.value / 100) * audio.duration; };
document.getElementById('volume').oninput   = e => { audio.volume = e.target.value / 100; };
audio.volume = 0.8;

// Mobile bar
document.getElementById('mbNext').addEventListener('click', e => { e.stopPropagation(); playIndex(state.currentIndex + 1); });
document.getElementById('mobileBar').addEventListener('click', () => { if (state.currentIndex >= 0) openNowPlaying(); });

// Now Playing controls
document.getElementById('npPlay').onclick     = togglePlay;
document.getElementById('npPrev').onclick     = () => playIndex(state.currentIndex - 1);
document.getElementById('npNext').onclick     = () => playIndex(state.currentIndex + 1);
document.getElementById('npClose').onclick    = closeNowPlaying;
document.getElementById('npProgress').oninput = e => { if (audio.duration) audio.currentTime = (e.target.value / 100) * audio.duration; };
document.getElementById('npHeart').onclick    = () => { const s = state.queue[state.currentIndex]; if (s) openPlaylistPicker(s.id, s.title); };
document.getElementById('npOptions').onclick  = () => { const s = state.queue[state.currentIndex]; if (s) openPlaylistPicker(s.id, s.title); };
document.getElementById('npMenuBtn').onclick  = () => { closeNowPlaying(); setTimeout(openSidebar, 350); };
document.getElementById('npLyricsBtn').onclick = () => openNpSheet('lyrics');
document.getElementById('npQueueBtn').onclick  = () => openNpSheet('queue');
document.getElementById('npLyricsClose').onclick = () => closeNpSheet('lyrics');
document.getElementById('npQueueClose').onclick  = () => closeNpSheet('queue');

// ─── Shuffle / Repeat ───
function updateShuffleUI() {
    const on = state.shuffle;
    ['shuffleBtn','npShuffle'].forEach(id => {
        const b = document.getElementById(id);
        if (!b) return;
        b.classList.toggle('text-indigo-400', on);
        b.classList.toggle('text-zinc-600', !on);
    });
}
function updateRepeatUI() {
    const r = state.repeat;
    const active = r !== 'none';
    ['repeatBtn','npRepeat'].forEach(id => {
        const b = document.getElementById(id);
        if (!b) return;
        b.classList.toggle('text-indigo-400', active);
        b.classList.toggle('text-zinc-600', !active);
    });
    // Show "1" badge for repeat-one
    const icon = document.getElementById('repeatBtnIcon');
    const npIcon = document.getElementById('npRepeatIcon');
    if (r === 'one') {
        if (icon) icon.setAttribute('data-one', '1');
        if (npIcon) npIcon.setAttribute('data-one', '1');
    }
}
document.getElementById('shuffleBtn').onclick = () => { state.shuffle = !state.shuffle; updateShuffleUI(); };
document.getElementById('npShuffle').onclick  = () => { state.shuffle = !state.shuffle; updateShuffleUI(); };
document.getElementById('repeatBtn').onclick  = () => { state.repeat = state.repeat === 'none' ? 'all' : state.repeat === 'all' ? 'one' : 'none'; updateRepeatUI(); };
document.getElementById('npRepeat').onclick   = () => { state.repeat = state.repeat === 'none' ? 'all' : state.repeat === 'all' ? 'one' : 'none'; updateRepeatUI(); };

// ─── Tab bar ───
function setActiveTab(name) {
    document.querySelectorAll('.tabBtn').forEach(b => {
        const active = b.dataset.tab === name;
        b.classList.toggle('text-indigo-400', active);
        b.classList.toggle('text-zinc-500', !active);
        b.classList.toggle('active', active);
    });
}
document.querySelector('[data-tab="home"]').onclick    = () => { setActiveTab('home'); closeSidebar(); loadArtists(); };
document.querySelector('[data-tab="search"]').onclick  = () => { setActiveTab('search'); closeSidebar(); document.getElementById('searchInput').focus(); };
document.querySelector('[data-tab="library"]').onclick = () => { setActiveTab('library'); openSidebar(); };
document.querySelector('[data-tab="account"]').onclick = () => { setActiveTab('account'); closeSidebar(); openPortalOverlay(); };
setActiveTab('home');

// ─── Lyrics ───
const lyricsPanel = document.getElementById('lyricsPanel');
const lyricsContent = document.getElementById('lyricsContent');
let lyricsVisible = false;
let lyricsLines = [];
let lyricsSynced = false;

document.getElementById('lyricsBtn').onclick = () => {
    lyricsVisible = !lyricsVisible;
    if (lyricsVisible) {
        lyricsPanel.style.display = 'flex';
        requestAnimationFrame(() => lyricsPanel.classList.remove('translate-x-full'));
        document.getElementById('lyricsBtn').classList.add('text-indigo-400');
        loadLyrics();
    } else {
        closeLyrics();
    }
};
document.getElementById('lyricsClose').onclick = () => closeLyrics();

function closeLyrics() {
    lyricsVisible = false;
    lyricsPanel.classList.add('translate-x-full');
    document.getElementById('lyricsBtn').classList.remove('text-indigo-400');
    setTimeout(() => { if (!lyricsVisible) lyricsPanel.style.display = 'none'; }, 300);
}

async function loadLyrics() {
    const s = state.queue[state.currentIndex];
    if (!s) { lyricsContent.innerHTML = '<div class="text-zinc-500 text-center py-8">Aucune piste en cours</div>'; return; }
    lyricsContent.innerHTML = '<div class="text-zinc-500 text-center py-8">Chargement…</div>';
    lyricsLines = [];
    lyricsSynced = false;
    try {
        const resp = await ndCall('getLyricsBySongId.view', { id: s.id });
        const lyricsList = resp.lyricsList?.structuredLyrics || [];
        if (lyricsList.length) {
            const synced = lyricsList.find(l => l.synced) || lyricsList[0];
            if (synced.synced && synced.line) {
                lyricsSynced = true;
                lyricsLines = synced.line.map(l => ({ time: (l.start || 0) / 1000, text: l.value || '' }));
                renderSyncedLyrics();
                return;
            }
            if (synced.line) {
                lyricsContent.innerHTML = synced.line.map(l => `<p class="py-1">${escapeHtml(l.value || '')}</p>`).join('');
                return;
            }
        }
        const resp2 = await ndCall('getLyrics.view', { artist: s.artist || '', title: s.title || '' });
        const text = resp2.lyrics?.value;
        if (text) {
            lyricsContent.innerHTML = text.split('\n').map(l => `<p class="py-1">${escapeHtml(l)}</p>`).join('');
        } else {
            lyricsContent.innerHTML = '<div class="text-zinc-500 text-center py-8">Aucune parole disponible</div>';
        }
    } catch (e) {
        try {
            const resp2 = await ndCall('getLyrics.view', { artist: s.artist || '', title: s.title || '' });
            const text = resp2.lyrics?.value;
            if (text) {
                lyricsContent.innerHTML = text.split('\n').map(l => `<p class="py-1">${escapeHtml(l)}</p>`).join('');
            } else {
                lyricsContent.innerHTML = '<div class="text-zinc-500 text-center py-8">Aucune parole disponible</div>';
            }
        } catch (e2) {
            lyricsContent.innerHTML = '<div class="text-zinc-500 text-center py-8">Aucune parole disponible</div>';
        }
    }
}

function renderSyncedLyrics() {
    lyricsContent.innerHTML = lyricsLines.map((l, i) =>
        `<p class="lyrics-line py-2 px-2 rounded-lg transition-all duration-300 cursor-pointer" data-idx="${i}">${escapeHtml(l.text) || '♪'}</p>`
    ).join('');
    lyricsContent.querySelectorAll('.lyrics-line').forEach(el => {
        el.onclick = () => { audio.currentTime = lyricsLines[el.dataset.idx].time; };
    });
}

audio.addEventListener('timeupdate', () => {
    if (!lyricsSynced || !lyricsLines.length) return;
    const t = audio.currentTime;
    let active = -1;
    for (let i = lyricsLines.length - 1; i >= 0; i--) {
        if (t >= lyricsLines[i].time) { active = i; break; }
    }
    [lyricsContent, document.getElementById('npLyricsContent')].forEach(container => {
        if (!container) return;
        container.querySelectorAll('.lyrics-line').forEach((el, i) => {
            if (i === active) {
                el.classList.add('text-white', 'font-semibold', 'text-base');
                el.classList.remove('text-zinc-500', 'text-sm');
                if (container === lyricsContent && lyricsVisible) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                el.classList.remove('text-white', 'font-semibold', 'text-base');
                el.classList.add('text-zinc-500', 'text-sm');
            }
        });
    });
});

// ─── Rankings ───
async function loadWeekArtists() {
    viewTitle.textContent = 'Classement des artistes';
    mainArea.innerHTML = '<div class="text-zinc-500 text-center py-10">Chargement…</div>';
    const resp = await ndCall('getAlbumList2.view', { type: 'frequent', size: 50 });
    const albums = resp.albumList2?.album || [];
    if (!albums.length) { mainArea.innerHTML = '<div class="text-zinc-500 text-center py-10">Aucune donnée d\'écoute. Écoutez de la musique depuis n\'importe quel client Navidrome.</div>'; viewCount.textContent = ''; return; }
    const artistMap = {};
    albums.forEach(al => {
        const name = al.artist || 'Inconnu';
        if (!artistMap[name]) artistMap[name] = { name, id: al.artistId, playCount: 0, albumCount: 0 };
        artistMap[name].playCount += al.playCount || 0;
        artistMap[name].albumCount++;
    });
    const artists = Object.values(artistMap).sort((a, b) => b.playCount - a.playCount).slice(0, 10);
    viewCount.textContent = `${artists.length} artistes`;
    const container = document.createElement('div');
    container.className = 'space-y-1';
    artists.forEach((a, i) => {
        const el = document.createElement('div');
        el.className = 'list-item flex items-center gap-3 px-3 py-3 rounded-xl cursor-pointer text-sm';
        const medal = i < 3 ? ['🥇','🥈','🥉'][i] : `<span class="text-zinc-500 font-bold w-5 text-center">${i+1}</span>`;
        el.innerHTML = `
            <span class="w-8 text-center text-lg shrink-0">${medal}</span>
            <div class="flex-1 min-w-0">
                <div class="font-medium truncate text-zinc-100">${escapeHtml(a.name)}</div>
                <div class="text-xs text-zinc-500 mt-0.5">${a.playCount} écoute(s) · ${a.albumCount} album(s)</div>
            </div>`;
        el.onclick = () => { if (a.id) loadArtist(a.id, a.name); };
        container.appendChild(el);
    });
    mainArea.innerHTML = '';
    mainArea.appendChild(container);
}

async function loadWeekSongs() {
    viewTitle.textContent = 'Classement des titres';
    mainArea.innerHTML = '<div class="text-zinc-500 text-center py-10">Chargement…</div>';
    const resp = await ndCall('getAlbumList2.view', { type: 'frequent', size: 20 });
    const albums = resp.albumList2?.album || [];
    if (!albums.length) { mainArea.innerHTML = '<div class="text-zinc-500 text-center py-10">Aucune donnée d\'écoute disponible.</div>'; viewCount.textContent = ''; return; }
    const allSongs = [];
    for (const al of albums.slice(0, 10)) {
        try {
            const r = await ndCall('getAlbum.view', { id: al.id });
            const songs = r.album?.song || [];
            songs.forEach(s => { if (s.playCount > 0) allSongs.push(s); });
        } catch(e) {}
    }
    allSongs.sort((a, b) => (b.playCount || 0) - (a.playCount || 0));
    const topSongs = allSongs.slice(0, 20);
    viewCount.textContent = `${topSongs.length} titres`;
    if (!topSongs.length) { mainArea.innerHTML = '<div class="text-zinc-500 text-center py-10">Aucun titre écouté pour le moment.</div>'; return; }
    const container = document.createElement('div');
    container.className = 'space-y-0.5';
    const header = document.createElement('div');
    header.className = 'mb-4';
    header.innerHTML = `<button class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-xl text-sm font-medium transition"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg> Tout lire</button>`;
    header.children[0].onclick = () => { state.queue = [...topSongs]; playIndex(0); };
    container.appendChild(header);
    topSongs.forEach((s, i) => {
        const el = document.createElement('div');
        el.className = 'list-item flex items-center gap-3 px-3 py-3 rounded-xl cursor-pointer text-sm';
        const medal = i < 3 ? ['🥇','🥈','🥉'][i] : `<span class="text-zinc-500 font-bold">${i+1}</span>`;
        el.innerHTML = `
            <span class="w-8 text-center text-lg shrink-0">${medal}</span>
            <div class="flex-1 min-w-0">
                <div class="truncate text-zinc-100">${escapeHtml(s.title)}</div>
                <div class="text-xs text-zinc-500 truncate mt-0.5">${escapeHtml(s.artist || '')} · ${escapeHtml(s.album || '')}</div>
            </div>
            <span class="text-xs text-zinc-600 tabular-nums">${s.playCount || 0} ×</span>
            <span class="text-xs text-zinc-600 tabular-nums">${formatTime(s.duration || 0)}</span>`;
        el.onclick = () => { state.queue = [...topSongs]; playIndex(i); };
        container.appendChild(el);
    });
    mainArea.innerHTML = '';
    mainArea.appendChild(container);
}

async function loadWeekAlbums() {
    viewTitle.textContent = 'Ajouts récents';
    mainArea.innerHTML = '<div class="text-zinc-500 text-center py-10">Chargement…</div>';
    const resp = await ndCall('getAlbumList2.view', { type: 'newest', size: 10 });
    const albums = resp.albumList2?.album || [];
    viewCount.textContent = `${albums.length} albums`;
    if (!albums.length) { mainArea.innerHTML = '<div class="text-zinc-500 text-center py-10">Aucun ajout récent.</div>'; return; }
    mainArea.innerHTML = '<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3"></div>';
    const grid = mainArea.firstElementChild;
    albums.forEach(al => {
        const el = document.createElement('button');
        el.className = 'bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden text-left hover:bg-zinc-800 transition';
        el.innerHTML = `
            <div class="aspect-square bg-zinc-800">
                <img src="${coverUrl(al.coverArt || al.id, 300)}" class="w-full h-full object-cover" onerror="this.style.display='none';this.parentElement.innerHTML='♪'">
            </div>
            <div class="p-2.5">
                <div class="font-medium truncate text-sm text-zinc-100">${escapeHtml(al.name)}</div>
                <div class="text-xs text-zinc-500 truncate mt-0.5">${escapeHtml(al.artist || '')}</div>
                <div class="text-xs text-zinc-600 mt-0.5">${al.songCount || 0} titre(s)</div>
            </div>`;
        el.onclick = () => loadAlbum(al.id, al.name);
        grid.appendChild(el);
    });
}

// ─── Navigation ───
document.querySelectorAll('.nav-btn').forEach(btn => {
    btn.onclick = () => {
        if (window.innerWidth < 640) closeSidebar();
        const view = btn.dataset.view;
        if (view === 'artists') loadArtists();
        else if (view === 'albums') loadAlbums();
        else if (view === 'random') loadRandom();
        else if (view === 'weekArtists') loadWeekArtists();
        else if (view === 'weekSongs') loadWeekSongs();
        else if (view === 'weekAlbums') loadWeekAlbums();
    };
});

// ─── Search ───
document.getElementById('searchBtn').onclick = () => {
    const q = document.getElementById('searchInput').value.trim();
    if (q) doSearch(q);
};
document.getElementById('searchInput').addEventListener('keydown', e => {
    if (e.key === 'Enter') document.getElementById('searchBtn').click();
});

// ─── Helpers ───
function formatTime(s) {
    s = Math.floor(s);
    return `${Math.floor(s / 60)}:${(s % 60).toString().padStart(2, '0')}`;
}
function escapeHtml(s) {
    return String(s || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

// ─── Playlists ───
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
let playerPlaylists = [];
let pickerTargetSongId = null;
let pickerTargetSongTitle = null;
const songPlaylistMap = new Map();

async function portalApi(method, url, body = null) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content || CSRF;
    const payload = method !== 'GET' ? { ...(body || {}), _token: token } : null;
    const opts = { method, credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' } };
    if (payload) opts.body = JSON.stringify(payload);
    const r = await fetch(url, opts);
    if (!r.ok) { const e = await r.json().catch(() => ({})); throw new Error(e.message || `Erreur ${r.status}`); }
    return r.json();
}

function playerToast(msg, ok = true) {
    const el = document.createElement('div');
    el.className = `fixed z-[9999] px-4 py-2.5 rounded-xl text-xs font-medium shadow-2xl backdrop-blur-sm border ${ok ? 'bg-emerald-500/20 border-emerald-500/30 text-emerald-400' : 'bg-red-500/20 border-red-500/30 text-red-400'}`;
    el.style.cssText = 'bottom:calc(env(safe-area-inset-bottom,0px) + 140px);right:16px;max-width:260px';
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 2500);
}

async function loadPlayerPlaylists() {
    try {
        const resp = await ndCall('getPlaylists.view');
        const raw = resp.playlists?.playlist || [];
        playerPlaylists = Array.isArray(raw) ? raw : (raw.id ? [raw] : []);
        renderPlaylistNav();
    } catch(e) {
        document.getElementById('playlistNavList').innerHTML = '<div class="px-3 text-xs text-zinc-500">Indisponible</div>';
    }
}

function renderPlaylistNav() {
    const el = document.getElementById('playlistNavList');
    el.innerHTML = '';
    if (!playerPlaylists.length) {
        el.innerHTML = '<div class="px-3 text-xs text-zinc-500 italic">Aucune playlist</div>';
        return;
    }
    playerPlaylists.forEach(pl => {
        const row = document.createElement('div');
        row.className = 'group flex items-center gap-1 rounded-lg hover:bg-zinc-800';
        const btn = document.createElement('button');
        btn.className = 'nav-btn text-left px-3 py-2 flex-1 text-xs truncate min-w-0 text-zinc-400 hover:text-zinc-100';
        btn.innerHTML = `<span class="text-zinc-600">♪</span> ${escapeHtml(pl.name)} <span class="text-zinc-600">(${pl.songCount||0})</span>`;
        btn.addEventListener('click', () => loadPlaylistInPlayer(pl.id, pl.name));
        const menu = document.createElement('button');
        menu.className = 'opacity-0 group-hover:opacity-100 px-1.5 py-1 text-zinc-500 hover:text-zinc-200 shrink-0 transition text-base leading-none';
        menu.title = 'Options';
        menu.textContent = '⋯';
        menu.addEventListener('click', (e) => { e.stopPropagation(); openPlaylistMenu(e, pl); });
        row.appendChild(btn);
        row.appendChild(menu);
        el.appendChild(row);
    });
}

function openPlaylistMenu(e, pl) {
    document.getElementById('playlistCtxMenu')?.remove();
    const m = document.createElement('div');
    m.id = 'playlistCtxMenu';
    m.className = 'fixed z-[9999] bg-zinc-900 border border-zinc-800 rounded-xl shadow-2xl py-1 text-xs w-36';
    const rect = e.target.getBoundingClientRect();
    m.style.top = rect.bottom + 4 + 'px';
    m.style.left = rect.left + 'px';
    const renameOpt = document.createElement('button');
    renameOpt.className = 'w-full text-left px-3 py-2.5 hover:bg-zinc-800 transition text-zinc-300';
    renameOpt.textContent = '✏️  Renommer';
    renameOpt.addEventListener('click', () => { m.remove(); openRenamePlaylistModal(pl); });
    const deleteOpt = document.createElement('button');
    deleteOpt.className = 'w-full text-left px-3 py-2.5 hover:bg-zinc-800 transition text-red-400';
    deleteOpt.textContent = '🗑  Supprimer';
    deleteOpt.addEventListener('click', () => { m.remove(); deletePlaylistFromPlayer(pl); });
    m.appendChild(renameOpt);
    m.appendChild(deleteOpt);
    document.body.appendChild(m);
    setTimeout(() => document.addEventListener('click', () => m.remove(), { once: true }), 0);
}

function openRenamePlaylistModal(pl) {
    document.getElementById('renamePlaylistId').value = pl.id;
    document.getElementById('renamePlaylistInput').value = pl.name;
    document.getElementById('renamePlaylistModal').style.display = 'flex';
    setTimeout(() => document.getElementById('renamePlaylistInput').select(), 50);
}

async function deletePlaylistFromPlayer(pl) {
    if (!confirm(`Supprimer la playlist "${pl.name}" ?`)) return;
    try {
        await portalApi('DELETE', `/portal/playlists/${pl.id}`);
        playerToast(`Playlist "${pl.name}" supprimée.`);
        loadPlayerPlaylists();
        viewTitle.textContent = 'Artistes';
        viewCount.textContent = '';
        mainArea.innerHTML = '';
    } catch(e) { playerToast(e.message, false); }
}

function renderPublicToggle(btn, memberInfo, info, playlistId) {
    const isOwner = info.role === 'owner' || info.role === null;
    const isPublic = info.is_public;
    btn.className = `px-2.5 py-1.5 rounded-lg transition text-xs border ${isPublic ? 'bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 border-emerald-500/20' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700 border-zinc-700'}`;
    btn.textContent = isPublic ? '🌐 Publique' : '🔒 Privée';
    btn.disabled = !isOwner;
    if (!isOwner) btn.classList.add('opacity-50', 'cursor-not-allowed');
    else {
        btn.onclick = async () => {
            try {
                const res = await portalApi('POST', `/portal/playlists/${playlistId}/toggle-public`);
                renderPublicToggle(btn, memberInfo, { ...info, is_public: res.is_public, member_count: res.member_count }, playlistId);
            } catch(e) { playerToast(e.message, false); }
        };
    }
    memberInfo.textContent = info.member_count > 0 ? `${info.member_count} membre${info.member_count > 1 ? 's' : ''}` : '';
}

async function loadPlaylistInPlayer(id, name) {
    viewTitle.textContent = name;
    mainArea.innerHTML = '<div class="text-zinc-500 text-center py-10">Chargement…</div>';
    try {
        const resp = await ndCall('getPlaylist.view', { id });
        let songs = resp.playlist?.entry || [];
        if (songs.id) songs = [songs];
        viewCount.textContent = `${songs.length} titre(s)`;
        const container = document.createElement('div');
        container.className = 'space-y-0.5';
        const header = document.createElement('div');
        header.className = 'mb-4 flex gap-2 flex-wrap';
        header.innerHTML = `
            <button class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-xl text-sm font-medium transition">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg> Tout lire
            </button>
            <button class="px-4 py-2 bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 rounded-xl text-sm transition">+ File d'attente</button>
            <button class="px-4 py-2 bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 rounded-xl text-sm transition ml-auto">⤴ Partager</button>`;
        header.children[0].onclick = () => { state.queue = [...songs]; playIndex(0); };
        header.children[1].onclick = () => { state.queue.push(...songs); renderQueue(); };
        header.children[2].onclick = () => openShareModal(id, name);
        container.appendChild(header);

        const metaRow = document.createElement('div');
        metaRow.className = 'flex items-center gap-3 text-xs mb-3';
        const publicBtn = document.createElement('button');
        publicBtn.textContent = '⟳';
        const memberInfo = document.createElement('span');
        memberInfo.className = 'text-zinc-500';
        metaRow.appendChild(publicBtn);
        metaRow.appendChild(memberInfo);
        container.appendChild(metaRow);
        portalApi('GET', `/portal/playlists/${id}/info`).then(info => {
            renderPublicToggle(publicBtn, memberInfo, info, id);
        }).catch(() => { metaRow.remove(); });

        songs.forEach((s, i) => {
            const el = document.createElement('div');
            el.className = 'list-item flex items-center gap-3 px-3 py-2.5 rounded-xl cursor-pointer text-sm group';
            el.innerHTML = `
                <span class="w-5 text-zinc-600 text-xs text-right shrink-0">${i+1}</span>
                <div class="flex-1 min-w-0">
                    <div class="truncate text-zinc-100">${escapeHtml(s.title)}</div>
                    <div class="text-xs text-zinc-500 truncate mt-0.5">${escapeHtml(s.artist||'')}${s.album?' · '+escapeHtml(s.album):''}</div>
                </div>
                <span class="text-xs text-zinc-600 tabular-nums">${formatTime(s.duration||0)}</span>`;
            const removeBtn = document.createElement('button');
            removeBtn.className = 'opacity-0 group-hover:opacity-100 ml-1 text-zinc-500 hover:text-red-400 transition text-lg leading-none shrink-0';
            removeBtn.title = 'Retirer';
            removeBtn.textContent = '×';
            removeBtn.addEventListener('click', async (e) => {
                e.stopPropagation();
                try {
                    await portalApi('DELETE', `/portal/playlists/${id}/tracks`, { index: i });
                    playerToast(`"${s.title}" retiré.`);
                    loadPlaylistInPlayer(id, name);
                } catch(err) { playerToast(err.message, false); }
            });
            el.appendChild(removeBtn);
            el.onclick = () => { state.queue = [...songs]; playIndex(i); };
            container.appendChild(el);
        });
        mainArea.innerHTML = '';
        mainArea.appendChild(container);
    } catch(e) {
        mainArea.innerHTML = `<div class="text-red-400 text-center py-10 text-sm">${escapeHtml(e.message)}</div>`;
    }
}

document.getElementById('saveQueueBtn').onclick = async () => {
    if (!state.queue.length) return;
    const name = prompt('Nom de la nouvelle playlist :');
    if (!name?.trim()) return;
    try {
        const pl = await portalApi('POST', '/portal/playlists', { name: name.trim() });
        const ids = state.queue.map(s => s.id);
        await portalApi('POST', `/portal/playlists/${pl.id}/tracks`, { song_ids: ids });
        playerToast(`Playlist "${pl.name}" créée (${ids.length} titre(s)).`);
        loadPlayerPlaylists();
    } catch(e) { playerToast(e.message, false); }
};

document.getElementById('newPlaylistPlayerBtn').onclick = async () => {
    const name = prompt('Nom de la nouvelle playlist :');
    if (!name?.trim()) return;
    try {
        await portalApi('POST', '/portal/playlists', { name: name.trim() });
        playerToast(`Playlist "${name}" créée.`);
        loadPlayerPlaylists();
    } catch(e) { playerToast(e.message, false); }
};

document.getElementById('addToPlaylistBtn').onclick = () => {
    const s = state.queue[state.currentIndex];
    if (!s) return;
    openPlaylistPicker(s.id, s.title);
};

function openPlaylistPicker(songId, songTitle) {
    pickerTargetSongId = songId;
    pickerTargetSongTitle = songTitle;
    document.getElementById('pickerSongInfo').textContent = `"${songTitle}"`;
    const list = document.getElementById('pickerList');
    list.innerHTML = '';
    if (playerPlaylists.length) {
        playerPlaylists.forEach(pl => {
            const btn = document.createElement('button');
            btn.className = 'w-full text-left px-3 py-2.5 rounded-xl hover:bg-zinc-800 text-xs truncate transition text-zinc-300';
            btn.innerHTML = `<span class="text-zinc-500">♪</span> ${escapeHtml(pl.name)} <span class="text-zinc-600">(${pl.songCount||0})</span>`;
            btn.addEventListener('click', () => addCurrentToPlaylist(pl.id, pl.name));
            list.appendChild(btn);
        });
    } else {
        list.innerHTML = '<div class="text-zinc-500 text-xs py-3 text-center">Aucune playlist</div>';
    }
    document.getElementById('playlistPickerModal').style.display = 'flex';
}

function updateAddToPlaylistBtn(songId) {
    const heart   = document.getElementById('addToPlaylistHeart');
    const label   = document.getElementById('addToPlaylistLabel');
    const npHeart = document.getElementById('npHeartIcon');
    const pl = songId ? songPlaylistMap.get(songId) : null;
    if (heart) { heart.textContent = pl ? '♥' : '♡'; heart.className = pl ? 'text-indigo-400' : 'text-zinc-400'; }
    if (label) { label.textContent = pl || ''; pl ? label.classList.remove('hidden') : label.classList.add('hidden'); }
    if (npHeart) {
        if (pl) {
            npHeart.setAttribute('fill', 'currentColor');
            npHeart.removeAttribute('stroke');
            npHeart.parentElement.classList.remove('text-zinc-500');
            npHeart.parentElement.classList.add('text-indigo-400');
        } else {
            npHeart.setAttribute('fill', 'none');
            npHeart.setAttribute('stroke', 'currentColor');
            npHeart.parentElement.classList.add('text-zinc-500');
            npHeart.parentElement.classList.remove('text-indigo-400');
        }
    }
}

async function addCurrentToPlaylist(playlistId, playlistName) {
    document.getElementById('playlistPickerModal').style.display = 'none';
    try {
        await portalApi('POST', `/portal/playlists/${playlistId}/tracks`, { song_ids: [pickerTargetSongId] });
        songPlaylistMap.set(pickerTargetSongId, playlistName);
        updateAddToPlaylistBtn(pickerTargetSongId);
        playerToast(`Ajouté à "${playlistName}".`);
        loadPlayerPlaylists();
    } catch(e) { playerToast(e.message, false); }
}

function openNewPlaylistFromPicker() {
    document.getElementById('playlistPickerModal').style.display = 'none';
    document.getElementById('newPlaylistName').value = '';
    document.getElementById('newPlaylistSongId').dataset.songId = pickerTargetSongId || '';
    document.getElementById('newPlaylistModal').style.display = 'flex';
    setTimeout(() => document.getElementById('newPlaylistName').focus(), 50);
}

function openShareModal(playlistId, playlistName) {
    document.getElementById('sharePlaylistId').value = playlistId;
    document.getElementById('sharePlaylistName').textContent = `"${playlistName}"`;
    document.getElementById('shareTargetInput').value = '';
    document.getElementById('shareModal').style.display = 'flex';
    setTimeout(() => document.getElementById('shareTargetInput').focus(), 50);
}

async function confirmSharePlaylist() {
    const playlistId = document.getElementById('sharePlaylistId').value;
    const target = document.getElementById('shareTargetInput').value.trim();
    if (!target) return;
    document.getElementById('shareModal').style.display = 'none';
    try {
        const res = await portalApi('POST', `/portal/playlists/${playlistId}/share`, { target });
        playerToast(res.message || 'Playlist partagée.');
    } catch(e) { playerToast(e.message, false); }
}
document.getElementById('shareTargetInput').addEventListener('keydown', e => { if (e.key === 'Enter') confirmSharePlaylist(); });

function closeNewPlaylistModal() { document.getElementById('newPlaylistModal').style.display = 'none'; }
document.getElementById('newPlaylistName').addEventListener('keydown', e => { if (e.key === 'Enter') createAndAddPlaylist(); });

async function confirmRenamePlaylist() {
    const id = document.getElementById('renamePlaylistId').value;
    const name = document.getElementById('renamePlaylistInput').value.trim();
    if (!name) return;
    document.getElementById('renamePlaylistModal').style.display = 'none';
    try {
        await portalApi('PUT', `/portal/playlists/${id}`, { name });
        playerToast(`Playlist renommée en "${name}".`);
        loadPlayerPlaylists();
        if (viewTitle.textContent && viewTitle.textContent !== 'Artistes') viewTitle.textContent = name;
    } catch(e) { playerToast(e.message, false); }
}
document.getElementById('renamePlaylistInput').addEventListener('keydown', e => { if (e.key === 'Enter') confirmRenamePlaylist(); });

async function createAndAddPlaylist() {
    const name = document.getElementById('newPlaylistName').value.trim();
    const songId = document.getElementById('newPlaylistSongId').dataset.songId;
    if (!name) return;
    closeNewPlaylistModal();
    try {
        const pl = await portalApi('POST', '/portal/playlists', { name });
        if (songId) await portalApi('POST', `/portal/playlists/${pl.id}/tracks`, { song_ids: [songId] });
        playerToast(`Playlist "${name}" créée.`);
        loadPlayerPlaylists();
    } catch(e) { playerToast(e.message, false); }
}

// ─── Portal overlay ───
function openPortalOverlay(url = '/portal') {
    const frame = document.getElementById('portalFrame');
    if (!frame.src || !frame.src.endsWith(url)) frame.src = url;
    document.getElementById('portalOverlay').classList.remove('hidden');
}
function closePortalOverlay() {
    document.getElementById('portalOverlay').classList.add('hidden');
}

// ─── Persist state ───
function saveStateToStorage() {
    const s = state.queue[state.currentIndex];
    if (!s) return;
    try {
        localStorage.setItem('mf_nd', JSON.stringify({ url: ND.url, user: ND.user, salt: ND.salt, token: ND.token, version: ND.version, client: ND.client, format: ND.format }));
        localStorage.setItem('mf_now', JSON.stringify({ id: s.id, title: s.title || '—', artist: s.artist || '', album: s.album || '', coverArt: s.coverArt || s.id, duration: s.duration || 0, time: audio.currentTime, playing: !audio.paused, ts: Date.now() }));
        localStorage.setItem('mf_queue', JSON.stringify(state.queue));
        localStorage.setItem('mf_qidx', String(state.currentIndex));
    } catch(e) {}
}
setInterval(saveStateToStorage, 5000);
audio.addEventListener('pause', saveStateToStorage);
audio.addEventListener('play',  saveStateToStorage);

// ─── URL param: ?play_id ───
(async function handleUrlParams() {
    const params = new URLSearchParams(location.search);
    const playId = params.get('play_id');
    if (!playId) return;
    try {
        const resp = await ndCall('getSong.view', { id: playId });
        const s = resp.song;
        if (s) { state.queue = [s]; playIndex(0); }
    } catch(e) {}
    history.replaceState({}, '', '/player');
})();

// ─── PWA Install ───
let deferredInstallPrompt = null;
const pwaInstallBtn    = document.getElementById('pwaInstallBtn');
const pwaInstallBanner = document.getElementById('pwaInstallBanner');

window.addEventListener('beforeinstallprompt', e => {
    e.preventDefault();
    deferredInstallPrompt = e;
    pwaInstallBtn.classList.remove('hidden');
    pwaInstallBtn.classList.add('flex');
    if (!sessionStorage.getItem('pwa_banner_dismissed')) pwaInstallBanner.classList.add('visible');
});

async function triggerInstall() {
    if (!deferredInstallPrompt) return;
    deferredInstallPrompt.prompt();
    const { outcome } = await deferredInstallPrompt.userChoice;
    deferredInstallPrompt = null;
    pwaInstallBtn.classList.add('hidden');
    pwaInstallBanner.classList.remove('visible');
}

pwaInstallBtn.addEventListener('click', triggerInstall);
document.getElementById('pwaInstallBannerBtn').addEventListener('click', triggerInstall);
document.getElementById('pwaInstallBannerDismiss').addEventListener('click', () => {
    pwaInstallBanner.classList.remove('visible');
    sessionStorage.setItem('pwa_banner_dismissed', '1');
});
window.addEventListener('appinstalled', () => {
    pwaInstallBtn.classList.add('hidden');
    pwaInstallBanner.classList.remove('visible');
});

// ─── Now Playing Screen ───
const npScreen = document.getElementById('nowPlayingScreen');

function openNowPlaying() {
    npScreen.style.display = 'flex';
    requestAnimationFrame(() => { npScreen.style.transform = 'translateY(0)'; });
    document.body.style.overflow = 'hidden';
}
function closeNowPlaying() {
    npScreen.style.transform = 'translateY(100%)';
    document.body.style.overflow = '';
    closeNpSheet('lyrics');
    closeNpSheet('queue');
}

// Swipe to dismiss
let npTouchStartY = 0, npTouchDelta = 0;
npScreen.addEventListener('touchstart', e => { npTouchStartY = e.touches[0].clientY; npTouchDelta = 0; }, { passive: true });
npScreen.addEventListener('touchmove', e => {
    npTouchDelta = e.touches[0].clientY - npTouchStartY;
    if (npTouchDelta > 0) npScreen.style.transform = `translateY(${npTouchDelta}px)`;
}, { passive: true });
npScreen.addEventListener('touchend', () => {
    if (npTouchDelta > 80) closeNowPlaying();
    else npScreen.style.transform = 'translateY(0)';
    npTouchDelta = 0;
});

// ─── Sheets ───
function openNpSheet(type) {
    const sheet = document.getElementById(type === 'lyrics' ? 'npLyricsSheet' : 'npQueueSheet');
    sheet.style.transform = 'translateY(0)';
    if (type === 'lyrics') loadNpLyrics();
    if (type === 'queue')  renderNpQueue();
}
function closeNpSheet(type) {
    const sheet = document.getElementById(type === 'lyrics' ? 'npLyricsSheet' : 'npQueueSheet');
    if (sheet) sheet.style.transform = 'translateY(100%)';
}

function renderNpQueue() {
    const list = document.getElementById('npQueueList');
    const count = document.getElementById('npQueueCount');
    if (!list) return;
    list.innerHTML = '';
    count.textContent = `(${state.queue.length})`;
    state.queue.forEach((s, i) => {
        const row = document.createElement('div');
        const active = i === state.currentIndex;
        row.className = `flex items-center gap-3 px-5 py-3 cursor-pointer ${active ? 'bg-indigo-600/20' : 'active:bg-zinc-800/50'} transition`;
        row.innerHTML = `
            <div class="w-9 h-9 shrink-0 rounded-lg bg-zinc-800 flex items-center justify-center overflow-hidden">
                <img src="${coverUrl(s.coverArt || s.id, 40)}" class="w-full h-full object-cover" onerror="this.parentElement.innerHTML='♪'">
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-sm truncate ${active ? 'text-indigo-300 font-medium' : 'text-zinc-200'}">${escapeHtml(s.title || '—')}</div>
                <div class="text-xs text-zinc-500 truncate mt-0.5">${escapeHtml(s.artist || '')}</div>
            </div>
            ${active ? '<svg class="w-4 h-4 text-indigo-400 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>' : ''}`;
        row.onclick = () => { playIndex(i); closeNpSheet('queue'); };
        list.appendChild(row);
    });
    const activeRow = list.children[state.currentIndex];
    if (activeRow) setTimeout(() => activeRow.scrollIntoView({ block: 'center' }), 100);
}

async function loadNpLyrics() {
    const target = document.getElementById('npLyricsContent');
    const s = state.queue[state.currentIndex];
    if (!s) { target.innerHTML = '<div class="text-zinc-500 text-center py-8">Aucune piste en cours</div>'; return; }
    target.innerHTML = '<div class="text-zinc-500 text-center py-8">Chargement…</div>';
    try {
        const resp = await ndCall('getLyricsBySongId.view', { id: s.id });
        const list = resp.lyricsList?.structuredLyrics || [];
        if (list.length) {
            const synced = list.find(l => l.synced) || list[0];
            if (synced.line) {
                target.innerHTML = synced.line.map(l => `<p class="py-1.5">${escapeHtml(l.value || '')}</p>`).join('');
                return;
            }
        }
        const resp2 = await ndCall('getLyrics.view', { artist: s.artist || '', title: s.title || '' });
        const text = resp2.lyrics?.value;
        target.innerHTML = text
            ? text.split('\n').map(l => `<p class="py-1">${escapeHtml(l)}</p>`).join('')
            : '<div class="text-zinc-500 text-center py-8">Aucune parole disponible</div>';
    } catch {
        target.innerHTML = '<div class="text-zinc-500 text-center py-8">Aucune parole disponible</div>';
    }
}

// ─── Sidebar mobile ───
function openSidebar() {
    document.getElementById('sidebar').classList.add('open');
    document.getElementById('sidebarBackdrop').classList.add('open');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarBackdrop').classList.remove('open');
}
document.getElementById('sidebarToggle').onclick = openSidebar;

// ─── Service Worker ───
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').catch(() => {});
}

// ─── Init ───
loadArtists();
loadPlayerPlaylists();
</script>
</body>
</html>
