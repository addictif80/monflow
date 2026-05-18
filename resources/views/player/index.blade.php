<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Lecteur — MonFlow</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
    body { background: #0f172a; color: #e2e8f0; }
    .card { background: #1e293b; border: 1px solid #334155; }
    .list-item:hover { background: #334155; }
    .active-track { background: #4338ca !important; }
    .scroll { scrollbar-width: thin; scrollbar-color: #475569 #1e293b; }
    .scroll::-webkit-scrollbar { width: 8px; }
    .scroll::-webkit-scrollbar-track { background: #1e293b; }
    .scroll::-webkit-scrollbar-thumb { background: #475569; border-radius: 4px; }
</style>
</head>
<body class="h-screen flex flex-col overflow-hidden">

<header class="card border-b flex items-center justify-between px-4 h-14 shrink-0">
    <div class="flex items-center gap-4">
        <a href="/portal" class="text-indigo-400 hover:text-indigo-300 text-sm">&larr; Portail</a>
        <h1 class="text-lg font-bold text-indigo-400">🎵 MonFlow Lecteur</h1>
    </div>
    <div class="flex items-center gap-2">
        <input id="searchInput" type="text" placeholder="Rechercher artiste, album, titre..." class="px-3 py-1.5 bg-slate-900 border border-slate-700 rounded text-sm w-80 focus:outline-none focus:border-indigo-500">
        <button id="searchBtn" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 rounded text-sm">Rechercher</button>
    </div>
    <div class="text-sm text-slate-400">{{ Auth::user()->username }}</div>
</header>

<main class="flex-1 flex overflow-hidden">
    <aside class="w-48 card border-r p-3 shrink-0 flex flex-col gap-1 text-sm">
        <button data-view="artists" class="nav-btn text-left px-3 py-2 rounded hover:bg-slate-700">Artistes</button>
        <button data-view="albums" class="nav-btn text-left px-3 py-2 rounded hover:bg-slate-700">Albums récents</button>
        <button data-view="random" class="nav-btn text-left px-3 py-2 rounded hover:bg-slate-700">Lecture aléatoire</button>
        <div class="border-t border-slate-700 mt-3 pt-3">
            <div class="text-xs text-slate-500 uppercase mb-2 px-3">Classements</div>
            <button data-view="weekArtists" class="nav-btn text-left px-3 py-2 rounded hover:bg-slate-700 w-full">Top artistes</button>
            <button data-view="weekSongs" class="nav-btn text-left px-3 py-2 rounded hover:bg-slate-700 w-full">Top titres</button>
            <button data-view="weekAlbums" class="nav-btn text-left px-3 py-2 rounded hover:bg-slate-700 w-full">Ajouts de la semaine</button>
        </div>
        <div class="border-t border-slate-700 mt-3 pt-3">
            <div class="flex items-center justify-between mb-2 px-3">
                <div class="text-xs text-slate-500 uppercase">File d'attente (<span id="queueCount">0</span>)</div>
                <button id="saveQueueBtn" class="text-xs text-indigo-400 hover:text-indigo-300 hidden" title="Sauvegarder la file comme playlist">💾</button>
            </div>
            <div id="queueList" class="space-y-1 scroll overflow-y-auto max-h-40"></div>
        </div>
        <div class="border-t border-slate-700 mt-3 pt-3">
            <div class="flex items-center justify-between mb-2 px-3">
                <div class="text-xs text-slate-500 uppercase">Playlists</div>
                <button id="newPlaylistPlayerBtn" class="text-xs text-indigo-400 hover:text-indigo-300" title="Nouvelle playlist">+</button>
            </div>
            <div id="playlistNavList" class="space-y-1 scroll overflow-y-auto max-h-52"></div>
        </div>
    </aside>

    <section class="flex-1 flex flex-col overflow-hidden">
        <div class="px-6 py-3 border-b border-slate-700 flex items-center justify-between">
            <h2 id="viewTitle" class="text-xl font-semibold">Artistes</h2>
            <span id="viewCount" class="text-sm text-slate-400"></span>
        </div>
        <div id="mainArea" class="flex-1 overflow-y-auto scroll p-4"></div>
    </section>
</main>

<footer class="card border-t h-20 shrink-0 flex items-center px-4 gap-4">
    <div class="flex items-center gap-3 min-w-0 w-80">
        <div id="coverArt" class="w-12 h-12 bg-slate-900 rounded flex items-center justify-center text-2xl">🎵</div>
        <div class="min-w-0">
            <div id="trackTitle" class="text-sm font-medium truncate">Aucune piste</div>
            <div id="trackArtist" class="text-xs text-slate-400 truncate">—</div>
        </div>
    </div>
    <div class="flex-1 flex flex-col items-center gap-1">
        <div class="flex items-center gap-3">
            <button id="prevBtn" class="text-slate-400 hover:text-white text-xl" title="Précédent">⏮</button>
            <button id="playBtn" class="w-10 h-10 bg-white text-slate-900 rounded-full text-xl flex items-center justify-center hover:scale-105 transition">▶</button>
            <button id="nextBtn" class="text-slate-400 hover:text-white text-xl" title="Suivant">⏭</button>
        </div>
        <div class="flex items-center gap-2 w-full max-w-xl">
            <span id="curTime" class="text-xs text-slate-400 w-10 text-right">0:00</span>
            <input id="progress" type="range" min="0" max="100" value="0" class="flex-1 accent-indigo-500">
            <span id="totTime" class="text-xs text-slate-400 w-10">0:00</span>
        </div>
    </div>
    <div class="flex items-center gap-2 w-56 justify-end">
        <button id="addToPlaylistBtn" class="text-slate-400 hover:text-indigo-400 text-sm px-2 py-1 rounded hover:bg-slate-700 hidden" title="Ajouter à une playlist">♡</button>
        <button id="lyricsBtn" class="text-slate-400 hover:text-white text-sm px-2 py-1 rounded hover:bg-slate-700" title="Paroles">Aa</button>
        <span class="text-slate-400">🔊</span>
        <input id="volume" type="range" min="0" max="100" value="80" class="w-24 accent-indigo-500">
    </div>
</footer>

<div id="lyricsPanel" class="fixed right-0 top-14 bottom-20 w-80 card border-l transform translate-x-full transition-transform duration-300 z-50 flex flex-col" style="display:none">
    <div class="px-4 py-3 border-b border-slate-700 flex items-center justify-between shrink-0">
        <span class="text-sm font-semibold">Paroles</span>
        <button id="lyricsClose" class="text-slate-400 hover:text-white text-lg">&times;</button>
    </div>
    <div id="lyricsContent" class="flex-1 overflow-y-auto scroll p-4 text-sm text-slate-300 leading-relaxed"></div>
</div>

<audio id="audio"></audio>

{{-- Playlist modals --}}
<div id="playlistPickerModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50" style="display:none">
    <div class="bg-slate-800 border border-slate-600 rounded-xl p-5 w-80 shadow-xl">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-sm">Ajouter à une playlist</h3>
            <button onclick="document.getElementById('playlistPickerModal').style.display='none'" class="text-slate-400 hover:text-white">&times;</button>
        </div>
        <div id="pickerSongInfo" class="text-xs text-slate-400 mb-3 truncate"></div>
        <div id="pickerList" class="space-y-1 max-h-48 overflow-y-auto mb-3"></div>
        <button onclick="openNewPlaylistFromPicker()"
            class="w-full py-1.5 border border-dashed border-slate-600 hover:border-indigo-500 text-slate-400 hover:text-indigo-400 rounded text-xs transition">
            + Nouvelle playlist
        </button>
    </div>
</div>

<div id="newPlaylistModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50" style="display:none">
    <div class="bg-slate-800 border border-slate-600 rounded-xl p-5 w-72 shadow-xl">
        <h3 class="font-semibold text-sm mb-3">Nouvelle playlist</h3>
        <input id="newPlaylistName" type="text" placeholder="Nom…" maxlength="200"
            class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 mb-3">
        <div id="newPlaylistSongId" data-song-id=""></div>
        <div class="flex gap-2">
            <button onclick="closeNewPlaylistModal()" class="flex-1 py-1.5 bg-slate-700 hover:bg-slate-600 rounded text-xs transition">Annuler</button>
            <button onclick="createAndAddPlaylist()" class="flex-1 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded text-xs font-medium transition">Créer</button>
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
    const qs = new URLSearchParams({
        u: ND.user, t: ND.token, s: ND.salt,
        v: ND.version, c: ND.client, f: ND.format,
        ...params
    });
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
};

const audio = document.getElementById('audio');
const mainArea = document.getElementById('mainArea');
const viewTitle = document.getElementById('viewTitle');
const viewCount = document.getElementById('viewCount');

// ─── Views ───
async function loadArtists() {
    viewTitle.textContent = 'Artistes';
    mainArea.innerHTML = '<div class="text-slate-500 text-center py-8">Chargement...</div>';
    const resp = await ndCall('getArtists.view');
    const indexes = resp.artists?.index || [];
    const all = indexes.flatMap(i => i.artist || []);
    viewCount.textContent = `${all.length} artistes`;
    mainArea.innerHTML = '<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3"></div>';
    const grid = mainArea.firstElementChild;
    all.forEach(a => {
        const el = document.createElement('button');
        el.className = 'card rounded p-3 text-left hover:bg-slate-700 transition';
        el.innerHTML = `<div class="font-medium truncate">${escapeHtml(a.name)}</div><div class="text-xs text-slate-400 mt-1">${a.albumCount || 0} albums</div>`;
        el.onclick = () => loadArtist(a.id, a.name);
        grid.appendChild(el);
    });
}

async function loadArtist(id, name) {
    viewTitle.textContent = name;
    mainArea.innerHTML = '<div class="text-slate-500 text-center py-8">Chargement...</div>';
    const resp = await ndCall('getArtist.view', { id });
    const albums = resp.artist?.album || [];
    viewCount.textContent = `${albums.length} albums`;
    mainArea.innerHTML = '<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3"></div>';
    const grid = mainArea.firstElementChild;
    albums.forEach(al => {
        const el = document.createElement('button');
        el.className = 'card rounded overflow-hidden text-left hover:bg-slate-700 transition';
        el.innerHTML = `
            <div class="aspect-square bg-slate-900 flex items-center justify-center">
                <img src="${coverUrl(al.coverArt || al.id, 300)}" class="w-full h-full object-cover" onerror="this.style.display='none';this.parentElement.innerHTML='🎵'">
            </div>
            <div class="p-2">
                <div class="font-medium truncate text-sm">${escapeHtml(al.name)}</div>
                <div class="text-xs text-slate-400 truncate">${al.year || ''} · ${al.songCount || 0} titres</div>
            </div>`;
        el.onclick = () => loadAlbum(al.id, al.name);
        grid.appendChild(el);
    });
}

async function loadAlbum(id, name) {
    viewTitle.textContent = name;
    mainArea.innerHTML = '<div class="text-slate-500 text-center py-8">Chargement...</div>';
    const resp = await ndCall('getAlbum.view', { id });
    const songs = resp.album?.song || [];
    viewCount.textContent = `${songs.length} titres`;

    const container = document.createElement('div');
    container.className = 'space-y-1';
    const header = document.createElement('div');
    header.className = 'mb-4 flex gap-3';
    header.innerHTML = `
        <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded text-sm font-medium">▶ Tout lire</button>
        <button class="px-4 py-2 bg-slate-700 hover:bg-slate-600 rounded text-sm">+ File d'attente</button>`;
    header.children[0].onclick = () => { state.queue = [...songs]; playIndex(0); };
    header.children[1].onclick = () => { state.queue.push(...songs); renderQueue(); };
    container.appendChild(header);

    songs.forEach((s, i) => {
        const el = document.createElement('div');
        el.className = 'list-item flex items-center gap-3 px-3 py-2 rounded cursor-pointer text-sm';
        el.innerHTML = `
            <span class="w-6 text-slate-500">${s.track || i + 1}</span>
            <div class="flex-1 min-w-0">
                <div class="truncate">${escapeHtml(s.title)}</div>
                <div class="text-xs text-slate-400 truncate">${escapeHtml(s.artist || '')}</div>
            </div>
            <span class="text-xs text-slate-500">${formatTime(s.duration || 0)}</span>`;
        el.onclick = () => { state.queue = [...songs]; playIndex(i); };
        container.appendChild(el);
    });

    mainArea.innerHTML = '';
    mainArea.appendChild(container);
}

async function loadAlbums() {
    viewTitle.textContent = 'Albums récents';
    mainArea.innerHTML = '<div class="text-slate-500 text-center py-8">Chargement...</div>';
    const resp = await ndCall('getAlbumList2.view', { type: 'newest', size: 50 });
    const albums = resp.albumList2?.album || [];
    viewCount.textContent = `${albums.length} albums`;
    mainArea.innerHTML = '<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3"></div>';
    const grid = mainArea.firstElementChild;
    albums.forEach(al => {
        const el = document.createElement('button');
        el.className = 'card rounded overflow-hidden text-left hover:bg-slate-700 transition';
        el.innerHTML = `
            <div class="aspect-square bg-slate-900 flex items-center justify-center">
                <img src="${coverUrl(al.coverArt || al.id, 300)}" class="w-full h-full object-cover" onerror="this.style.display='none';this.parentElement.innerHTML='🎵'">
            </div>
            <div class="p-2">
                <div class="font-medium truncate text-sm">${escapeHtml(al.name)}</div>
                <div class="text-xs text-slate-400 truncate">${escapeHtml(al.artist || '')}</div>
            </div>`;
        el.onclick = () => loadAlbum(al.id, al.name);
        grid.appendChild(el);
    });
}

async function loadRandom() {
    viewTitle.textContent = 'Lecture aléatoire';
    mainArea.innerHTML = '<div class="text-slate-500 text-center py-8">Chargement...</div>';
    const resp = await ndCall('getRandomSongs.view', { size: 50 });
    const songs = resp.randomSongs?.song || [];
    viewCount.textContent = `${songs.length} titres`;

    const container = document.createElement('div');
    container.className = 'space-y-1';
    const header = document.createElement('div');
    header.className = 'mb-4';
    header.innerHTML = '<button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded text-sm font-medium">▶ Tout lire</button>';
    header.children[0].onclick = () => { state.queue = [...songs]; playIndex(0); };
    container.appendChild(header);

    songs.forEach((s, i) => {
        const el = document.createElement('div');
        el.className = 'list-item flex items-center gap-3 px-3 py-2 rounded cursor-pointer text-sm';
        el.innerHTML = `
            <div class="flex-1 min-w-0">
                <div class="truncate">${escapeHtml(s.title)}</div>
                <div class="text-xs text-slate-400 truncate">${escapeHtml(s.artist || '')} · ${escapeHtml(s.album || '')}</div>
            </div>
            <span class="text-xs text-slate-500">${formatTime(s.duration || 0)}</span>`;
        el.onclick = () => { state.queue = [...songs]; playIndex(i); };
        container.appendChild(el);
    });
    mainArea.innerHTML = '';
    mainArea.appendChild(container);
}

async function doSearch(q) {
    viewTitle.textContent = `Recherche : ${q}`;
    mainArea.innerHTML = '<div class="text-slate-500 text-center py-8">Recherche...</div>';
    const resp = await ndCall('search3.view', { query: q });
    const r = resp.searchResult3 || {};
    const artists = r.artist || [], albums = r.album || [], songs = r.song || [];
    viewCount.textContent = `${artists.length} artistes, ${albums.length} albums, ${songs.length} titres`;

    const c = document.createElement('div');
    c.className = 'space-y-6';

    if (artists.length) {
        const sec = document.createElement('div');
        sec.innerHTML = '<h3 class="text-sm font-semibold text-slate-400 uppercase mb-2">Artistes</h3>';
        const grid = document.createElement('div');
        grid.className = 'grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3';
        artists.forEach(a => {
            const el = document.createElement('button');
            el.className = 'card rounded p-3 text-left hover:bg-slate-700';
            el.innerHTML = `<div class="font-medium truncate">${escapeHtml(a.name)}</div>`;
            el.onclick = () => loadArtist(a.id, a.name);
            grid.appendChild(el);
        });
        sec.appendChild(grid);
        c.appendChild(sec);
    }

    if (albums.length) {
        const sec = document.createElement('div');
        sec.innerHTML = '<h3 class="text-sm font-semibold text-slate-400 uppercase mb-2">Albums</h3>';
        const grid = document.createElement('div');
        grid.className = 'grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3';
        albums.forEach(al => {
            const el = document.createElement('button');
            el.className = 'card rounded overflow-hidden text-left hover:bg-slate-700';
            el.innerHTML = `
                <div class="aspect-square bg-slate-900"><img src="${coverUrl(al.coverArt || al.id, 300)}" class="w-full h-full object-cover" onerror="this.style.display='none'"></div>
                <div class="p-2"><div class="font-medium truncate text-sm">${escapeHtml(al.name)}</div><div class="text-xs text-slate-400 truncate">${escapeHtml(al.artist || '')}</div></div>`;
            el.onclick = () => loadAlbum(al.id, al.name);
            grid.appendChild(el);
        });
        sec.appendChild(grid);
        c.appendChild(sec);
    }

    if (songs.length) {
        const sec = document.createElement('div');
        sec.innerHTML = '<h3 class="text-sm font-semibold text-slate-400 uppercase mb-2">Titres</h3>';
        const list = document.createElement('div');
        list.className = 'space-y-1';
        songs.forEach((s, i) => {
            const el = document.createElement('div');
            el.className = 'list-item flex items-center gap-3 px-3 py-2 rounded cursor-pointer text-sm';
            el.innerHTML = `<div class="flex-1 min-w-0"><div class="truncate">${escapeHtml(s.title)}</div><div class="text-xs text-slate-400 truncate">${escapeHtml(s.artist || '')} · ${escapeHtml(s.album || '')}</div></div><span class="text-xs text-slate-500">${formatTime(s.duration || 0)}</span>`;
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
    document.getElementById('trackTitle').textContent = s.title || '—';
    document.getElementById('trackArtist').textContent = `${s.artist || ''} · ${s.album || ''}`;
    document.getElementById('playBtn').textContent = '⏸';
    const cover = document.getElementById('coverArt');
    if (s.coverArt || s.id) {
        cover.innerHTML = `<img src="${coverUrl(s.coverArt || s.id, 100)}" class="w-full h-full object-cover rounded" onerror="this.parentElement.innerHTML='🎵'">`;
    }
    renderQueue();
    if (lyricsVisible) loadLyrics();
    document.getElementById('addToPlaylistBtn').classList.remove('hidden');
    document.getElementById('saveQueueBtn').classList.remove('hidden');
}

function renderQueue() {
    const el = document.getElementById('queueList');
    el.innerHTML = '';
    state.queue.forEach((s, i) => {
        const item = document.createElement('div');
        item.className = `px-2 py-1 rounded cursor-pointer text-xs truncate ${i === state.currentIndex ? 'active-track' : 'hover:bg-slate-700'}`;
        item.textContent = s.title || '—';
        item.onclick = () => playIndex(i);
        el.appendChild(item);
    });
    document.getElementById('queueCount').textContent = state.queue.length;
}

// ─── Controls ───
document.getElementById('playBtn').onclick = () => {
    if (!audio.src) return;
    if (audio.paused) { audio.play(); document.getElementById('playBtn').textContent = '⏸'; }
    else { audio.pause(); document.getElementById('playBtn').textContent = '▶'; }
};
document.getElementById('prevBtn').onclick = () => playIndex(state.currentIndex - 1);
document.getElementById('nextBtn').onclick = () => playIndex(state.currentIndex + 1);
audio.addEventListener('ended', () => playIndex(state.currentIndex + 1));
audio.addEventListener('timeupdate', () => {
    const p = document.getElementById('progress');
    if (audio.duration) p.value = (audio.currentTime / audio.duration) * 100;
    document.getElementById('curTime').textContent = formatTime(audio.currentTime);
    document.getElementById('totTime').textContent = formatTime(audio.duration || 0);
});
document.getElementById('progress').oninput = (e) => {
    if (audio.duration) audio.currentTime = (e.target.value / 100) * audio.duration;
};
document.getElementById('volume').oninput = (e) => { audio.volume = e.target.value / 100; };
audio.volume = 0.8;

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
    if (!s) { lyricsContent.innerHTML = '<div class="text-slate-500 text-center py-8">Aucune piste en cours</div>'; return; }
    lyricsContent.innerHTML = '<div class="text-slate-500 text-center py-8">Chargement...</div>';
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
            lyricsContent.innerHTML = '<div class="text-slate-500 text-center py-8">Aucune parole disponible</div>';
        }
    } catch (e) {
        try {
            const resp2 = await ndCall('getLyrics.view', { artist: s.artist || '', title: s.title || '' });
            const text = resp2.lyrics?.value;
            if (text) {
                lyricsContent.innerHTML = text.split('\n').map(l => `<p class="py-1">${escapeHtml(l)}</p>`).join('');
            } else {
                lyricsContent.innerHTML = '<div class="text-slate-500 text-center py-8">Aucune parole disponible</div>';
            }
        } catch (e2) {
            lyricsContent.innerHTML = '<div class="text-slate-500 text-center py-8">Aucune parole disponible</div>';
        }
    }
}

function renderSyncedLyrics() {
    lyricsContent.innerHTML = lyricsLines.map((l, i) =>
        `<p class="lyrics-line py-2 px-2 rounded transition-all duration-300 cursor-pointer" data-idx="${i}">${escapeHtml(l.text) || '♪'}</p>`
    ).join('');
    lyricsContent.querySelectorAll('.lyrics-line').forEach(el => {
        el.onclick = () => { audio.currentTime = lyricsLines[el.dataset.idx].time; };
    });
}

audio.addEventListener('timeupdate', () => {
    if (!lyricsSynced || !lyricsVisible || !lyricsLines.length) return;
    const t = audio.currentTime;
    let active = -1;
    for (let i = lyricsLines.length - 1; i >= 0; i--) {
        if (t >= lyricsLines[i].time) { active = i; break; }
    }
    lyricsContent.querySelectorAll('.lyrics-line').forEach((el, i) => {
        if (i === active) {
            el.classList.add('text-white', 'font-semibold', 'text-base');
            el.classList.remove('text-slate-500', 'text-sm');
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            el.classList.remove('text-white', 'font-semibold', 'text-base');
            el.classList.add('text-slate-500', 'text-sm');
        }
    });
});

// ─── Rankings (Subsonic API — user's own play stats from all Navidrome clients) ───
async function loadWeekArtists() {
    viewTitle.textContent = 'Classement des artistes';
    mainArea.innerHTML = '<div class="text-slate-500 text-center py-8">Chargement...</div>';
    const resp = await ndCall('getAlbumList2.view', { type: 'frequent', size: 50 });
    const albums = resp.albumList2?.album || [];
    if (!albums.length) { mainArea.innerHTML = '<div class="text-slate-500 text-center py-8">Aucune donnée d\'écoute disponible. Écoutez de la musique depuis n\'importe quel client Navidrome.</div>'; viewCount.textContent = ''; return; }
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
        el.className = 'list-item flex items-center gap-3 px-3 py-3 rounded cursor-pointer text-sm';
        const medal = i < 3 ? ['&#129351;','&#129352;','&#129353;'][i] : `<span class="text-slate-500 font-bold">${i+1}</span>`;
        el.innerHTML = `
            <span class="w-8 text-center text-lg">${medal}</span>
            <div class="flex-1 min-w-0">
                <div class="font-medium truncate">${escapeHtml(a.name)}</div>
                <div class="text-xs text-slate-400">${a.playCount} écoute(s) · ${a.albumCount} album(s)</div>
            </div>`;
        el.onclick = () => { if (a.id) loadArtist(a.id, a.name); };
        container.appendChild(el);
    });
    mainArea.innerHTML = '';
    mainArea.appendChild(container);
}

async function loadWeekSongs() {
    viewTitle.textContent = 'Classement des titres';
    mainArea.innerHTML = '<div class="text-slate-500 text-center py-8">Chargement...</div>';
    const resp = await ndCall('getAlbumList2.view', { type: 'frequent', size: 20 });
    const albums = resp.albumList2?.album || [];
    if (!albums.length) { mainArea.innerHTML = '<div class="text-slate-500 text-center py-8">Aucune donnée d\'écoute disponible. Écoutez de la musique depuis n\'importe quel client Navidrome.</div>'; viewCount.textContent = ''; return; }
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
    if (!topSongs.length) { mainArea.innerHTML = '<div class="text-slate-500 text-center py-8">Aucun titre écouté pour le moment.</div>'; return; }
    const container = document.createElement('div');
    container.className = 'space-y-1';
    const header = document.createElement('div');
    header.className = 'mb-4';
    header.innerHTML = '<button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded text-sm font-medium">▶ Tout lire</button>';
    header.children[0].onclick = () => { state.queue = [...topSongs]; playIndex(0); };
    container.appendChild(header);
    topSongs.forEach((s, i) => {
        const el = document.createElement('div');
        el.className = 'list-item flex items-center gap-3 px-3 py-3 rounded cursor-pointer text-sm';
        const medal = i < 3 ? ['&#129351;','&#129352;','&#129353;'][i] : `<span class="text-slate-500 font-bold">${i+1}</span>`;
        el.innerHTML = `
            <span class="w-8 text-center text-lg">${medal}</span>
            <div class="flex-1 min-w-0">
                <div class="truncate">${escapeHtml(s.title)}</div>
                <div class="text-xs text-slate-400 truncate">${escapeHtml(s.artist || '')} · ${escapeHtml(s.album || '')}</div>
            </div>
            <span class="text-xs text-slate-500">${s.playCount || 0} écoute(s)</span>
            <span class="text-xs text-slate-500">${formatTime(s.duration || 0)}</span>`;
        el.onclick = () => { state.queue = [...topSongs]; playIndex(i); };
        container.appendChild(el);
    });
    mainArea.innerHTML = '';
    mainArea.appendChild(container);
}

async function loadWeekAlbums() {
    viewTitle.textContent = 'Ajouts de la semaine';
    mainArea.innerHTML = '<div class="text-slate-500 text-center py-8">Chargement...</div>';
    const resp = await ndCall('getAlbumList2.view', { type: 'newest', size: 10 });
    const albums = resp.albumList2?.album || [];
    viewCount.textContent = `${albums.length} albums`;
    if (!albums.length) { mainArea.innerHTML = '<div class="text-slate-500 text-center py-8">Aucun ajout récent.</div>'; return; }
    mainArea.innerHTML = '<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3"></div>';
    const grid = mainArea.firstElementChild;
    albums.forEach(al => {
        const el = document.createElement('button');
        el.className = 'card rounded overflow-hidden text-left hover:bg-slate-700 transition';
        el.innerHTML = `
            <div class="aspect-square bg-slate-900 flex items-center justify-center">
                <img src="${coverUrl(al.coverArt || al.id, 300)}" class="w-full h-full object-cover" onerror="this.style.display='none';this.parentElement.innerHTML='&#9835;'">
            </div>
            <div class="p-2">
                <div class="font-medium truncate text-sm">${escapeHtml(al.name)}</div>
                <div class="text-xs text-slate-400 truncate">${escapeHtml(al.artist || '')}</div>
                <div class="text-xs text-slate-500">${al.songCount || 0} titre(s)</div>
            </div>`;
        el.onclick = () => loadAlbum(al.id, al.name);
        grid.appendChild(el);
    });
}

// ─── Navigation ───
document.querySelectorAll('.nav-btn').forEach(btn => {
    btn.onclick = () => {
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
    const m = Math.floor(s / 60);
    const sec = (s % 60).toString().padStart(2, '0');
    return `${m}:${sec}`;
}
function escapeHtml(s) {
    return String(s || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

// ─── Playlists ───
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
let playerPlaylists = [];
let pickerTargetSongId = null;
let pickerTargetSongTitle = null;

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
    el.className = `fixed bottom-24 right-4 z-[9999] px-3 py-2 rounded text-xs font-medium shadow-lg ${ok ? 'bg-green-700 text-green-100' : 'bg-red-700 text-red-100'}`;
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
        document.getElementById('playlistNavList').innerHTML = '<div class="px-3 text-xs text-slate-500">Indisponible</div>';
    }
}

function renderPlaylistNav() {
    const el = document.getElementById('playlistNavList');
    el.innerHTML = '';
    if (!playerPlaylists.length) {
        el.innerHTML = '<div class="px-3 text-xs text-slate-500 italic">Aucune playlist</div>';
        return;
    }
    playerPlaylists.forEach(pl => {
        const btn = document.createElement('button');
        btn.className = 'nav-btn text-left px-3 py-2 rounded hover:bg-slate-700 w-full text-xs truncate';
        btn.innerHTML = `♪ ${escapeHtml(pl.name)} <span class="text-slate-500">(${pl.songCount||0})</span>`;
        btn.addEventListener('click', () => loadPlaylistInPlayer(pl.id, pl.name));
        el.appendChild(btn);
    });
}

async function loadPlaylistInPlayer(id, name) {
    viewTitle.textContent = name;
    mainArea.innerHTML = '<div class="text-slate-500 text-center py-8">Chargement…</div>';
    try {
        const resp = await ndCall('getPlaylist.view', { id });
        let songs = resp.playlist?.entry || [];
        if (songs.id) songs = [songs]; // single entry normalisation
        viewCount.textContent = `${songs.length} titre(s)`;
        const container = document.createElement('div');
        container.className = 'space-y-1';
        const header = document.createElement('div');
        header.className = 'mb-4 flex gap-2';
        header.innerHTML = `
            <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded text-sm font-medium">▶ Tout lire</button>
            <button class="px-4 py-2 bg-slate-700 hover:bg-slate-600 rounded text-sm">+ File d'attente</button>`;
        header.children[0].onclick = () => { state.queue = [...songs]; playIndex(0); };
        header.children[1].onclick = () => { state.queue.push(...songs); renderQueue(); };
        container.appendChild(header);
        songs.forEach((s, i) => {
            const el = document.createElement('div');
            el.className = 'list-item flex items-center gap-3 px-3 py-2 rounded cursor-pointer text-sm group';
            el.innerHTML = `
                <span class="w-6 text-slate-500">${i+1}</span>
                <div class="flex-1 min-w-0">
                    <div class="truncate">${escapeHtml(s.title)}</div>
                    <div class="text-xs text-slate-400 truncate">${escapeHtml(s.artist||'')}${s.album?' · '+escapeHtml(s.album):''}</div>
                </div>
                <span class="text-xs text-slate-500">${formatTime(s.duration||0)}</span>`;
            const removeBtn = document.createElement('button');
            removeBtn.className = 'opacity-0 group-hover:opacity-100 ml-1 text-slate-500 hover:text-red-400 transition text-lg leading-none flex-shrink-0';
            removeBtn.title = 'Retirer de la playlist';
            removeBtn.textContent = '×';
            removeBtn.addEventListener('click', async (e) => {
                e.stopPropagation();
                try {
                    await portalApi('DELETE', `/portal/playlists/${id}/tracks`, { index: i });
                    playerToast(`"${s.title}" retiré de la playlist.`);
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
        mainArea.innerHTML = `<div class="text-red-400 text-center py-8 text-sm">${escapeHtml(e.message)}</div>`;
    }
}

// Save current queue as new playlist
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

// Add current track to playlist
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
            btn.className = 'w-full text-left px-3 py-2 rounded hover:bg-slate-700 text-xs truncate transition';
            btn.innerHTML = `♪ ${escapeHtml(pl.name)} <span class="text-slate-500">(${pl.songCount||0})</span>`;
            btn.addEventListener('click', () => addCurrentToPlaylist(pl.id, pl.name));
            list.appendChild(btn);
        });
    } else {
        list.innerHTML = '<div class="text-slate-500 text-xs py-2 text-center">Aucune playlist</div>';
    }
    document.getElementById('playlistPickerModal').style.display = 'flex';
}

async function addCurrentToPlaylist(playlistId, playlistName) {
    document.getElementById('playlistPickerModal').style.display = 'none';
    try {
        await portalApi('POST', `/portal/playlists/${playlistId}/tracks`, { song_ids: [pickerTargetSongId] });
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
function closeNewPlaylistModal() { document.getElementById('newPlaylistModal').style.display = 'none'; }
document.getElementById('newPlaylistName').addEventListener('keydown', e => { if (e.key === 'Enter') createAndAddPlaylist(); });

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

// ─── Init ───
loadArtists();
loadPlayerPlaylists();
</script>
</body>
</html>
