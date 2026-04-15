<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            <div class="text-xs text-slate-500 uppercase mb-2 px-3">File d'attente (<span id="queueCount">0</span>)</div>
            <div id="queueList" class="space-y-1 scroll overflow-y-auto max-h-64"></div>
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
    <div class="flex items-center gap-2 w-40 justify-end">
        <span class="text-slate-400">🔊</span>
        <input id="volume" type="range" min="0" max="100" value="80" class="w-24 accent-indigo-500">
    </div>
</footer>

<audio id="audio"></audio>

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

// ─── Navigation ───
document.querySelectorAll('.nav-btn').forEach(btn => {
    btn.onclick = () => {
        const view = btn.dataset.view;
        if (view === 'artists') loadArtists();
        else if (view === 'albums') loadAlbums();
        else if (view === 'random') loadRandom();
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

// ─── Init ───
loadArtists();
</script>
</body>
</html>
