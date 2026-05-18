@extends('layouts.app')

@section('title', 'Mes playlists — MonFlow')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold">Mes playlists</h1>
        <p class="text-gray-400 text-sm mt-1">Synchronisées avec tous vos appareils Navidrome</p>
    </div>
    <button onclick="openCreateModal()"
        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">
        + Nouvelle playlist
    </button>
</div>

<div class="flex gap-6" style="min-height: 520px">
    {{-- Left: playlist list --}}
    <div class="w-72 shrink-0 space-y-2" id="playlistList">
        @forelse($playlists as $pl)
            <div class="playlist-card bg-gray-800 border border-gray-700 rounded-lg p-4 cursor-pointer hover:border-indigo-500 transition"
                 data-id="{{ $pl['id'] }}" onclick="loadPlaylist('{{ $pl['id'] }}', this)">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="font-medium truncate">{{ $pl['name'] }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $pl['songCount'] ?? 0 }} titre(s)</p>
                    </div>
                    <div class="flex gap-1 shrink-0">
                        <button onclick="event.stopPropagation(); openRenameModal('{{ $pl['id'] }}', {{ json_encode($pl['name']) }})"
                            class="text-gray-500 hover:text-indigo-400 p-1 rounded" title="Renommer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <button onclick="event.stopPropagation(); deletePlaylist('{{ $pl['id'] }}', {{ json_encode($pl['name']) }})"
                            class="text-gray-500 hover:text-red-400 p-1 rounded" title="Supprimer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div id="emptyState" class="bg-gray-800 border border-dashed border-gray-600 rounded-lg p-8 text-center text-gray-500">
                <p class="text-sm">Aucune playlist.</p>
                <p class="text-xs mt-1">Créez-en une ou utilisez une app mobile.</p>
            </div>
        @endforelse
    </div>

    {{-- Right: playlist detail --}}
    <div class="flex-1 bg-gray-800 border border-gray-700 rounded-lg overflow-hidden flex flex-col">
        <div id="detailEmpty" class="flex-1 flex items-center justify-center text-gray-600 text-sm">
            Sélectionnez une playlist
        </div>

        <div id="detailPanel" class="flex-1 flex flex-col" style="display:none!important">
            {{-- Header --}}
            <div class="px-6 py-4 border-b border-gray-700 flex items-center justify-between gap-4 shrink-0">
                <div>
                    <h2 id="detailName" class="text-lg font-semibold"></h2>
                    <p id="detailCount" class="text-xs text-gray-500 mt-0.5"></p>
                </div>
                <a id="playInPlayerBtn" href="/player" target="_blank"
                   class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs font-medium transition">
                    ▶ Ouvrir dans le lecteur
                </a>
            </div>

            {{-- Search to add --}}
            <div class="px-6 py-3 border-b border-gray-700 shrink-0">
                <div class="flex gap-2">
                    <input id="songSearch" type="text" placeholder="Rechercher un titre à ajouter…"
                        class="flex-1 px-3 py-1.5 bg-gray-700 border border-gray-600 rounded text-sm text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <button onclick="searchSongs()"
                        class="px-3 py-1.5 bg-gray-700 hover:bg-gray-600 border border-gray-600 rounded text-sm transition">Rechercher</button>
                </div>
                <div id="searchResults" class="mt-2 space-y-1 max-h-48 overflow-y-auto" style="display:none"></div>
            </div>

            {{-- Track list --}}
            <div id="trackList" class="flex-1 overflow-y-auto divide-y divide-gray-700/50"></div>
        </div>
    </div>
</div>

{{-- Create modal --}}
<div id="createModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50" style="display:none">
    <div class="bg-gray-800 border border-gray-700 rounded-xl p-6 w-full max-w-sm shadow-xl">
        <h3 class="text-lg font-semibold mb-4">Nouvelle playlist</h3>
        <input id="createName" type="text" placeholder="Nom de la playlist" maxlength="200"
            class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-sm text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 mb-4">
        <div class="flex gap-3 justify-end">
            <button onclick="closeCreateModal()" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm transition">Annuler</button>
            <button onclick="createPlaylist()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">Créer</button>
        </div>
    </div>
</div>

{{-- Rename modal --}}
<div id="renameModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50" style="display:none">
    <div class="bg-gray-800 border border-gray-700 rounded-xl p-6 w-full max-w-sm shadow-xl">
        <h3 class="text-lg font-semibold mb-4">Renommer la playlist</h3>
        <input id="renameName" type="text" maxlength="200"
            class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-sm text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 mb-4">
        <input type="hidden" id="renameId">
        <div class="flex gap-3 justify-end">
            <button onclick="closeRenameModal()" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm transition">Annuler</button>
            <button onclick="renamePlaylist()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">Enregistrer</button>
        </div>
    </div>
</div>

<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;
let currentPlaylistId = null;
let currentSongs = [];

// ─── API helpers ───
async function api(method, url, body = null) {
    const opts = {
        method,
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
    };
    if (body) opts.body = JSON.stringify(body);
    const r = await fetch(url, opts);
    if (!r.ok) {
        const err = await r.json().catch(() => ({}));
        throw new Error(err.message || `Erreur ${r.status}`);
    }
    return r.json();
}

function toast(msg, ok = true) {
    const el = document.createElement('div');
    el.className = `fixed bottom-6 right-6 z-[9999] px-4 py-2 rounded-lg text-sm font-medium shadow-lg transition ${ok ? 'bg-green-700 text-green-100' : 'bg-red-700 text-red-100'}`;
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 3000);
}

function formatDuration(s) {
    s = Math.floor(s || 0);
    return `${Math.floor(s/60)}:${(s%60).toString().padStart(2,'0')}`;
}
function esc(s) {
    return String(s||'').replace(/[&<>"']/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

// ─── Load playlist detail ───
async function loadPlaylist(id, cardEl) {
    document.querySelectorAll('.playlist-card').forEach(c => c.classList.remove('border-indigo-500', 'bg-gray-750'));
    cardEl?.classList.add('border-indigo-500');
    currentPlaylistId = id;
    document.getElementById('detailEmpty').style.display = 'none';
    const panel = document.getElementById('detailPanel');
    panel.style.removeProperty('display');
    document.getElementById('trackList').innerHTML = '<div class="p-6 text-gray-500 text-sm">Chargement…</div>';
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('songSearch').value = '';

    try {
        const pl = await api('GET', `/portal/playlists/${id}`);
        currentSongs = pl.entry || [];
        document.getElementById('detailName').textContent = pl.name || '—';
        document.getElementById('detailCount').textContent = `${currentSongs.length} titre(s) · ${pl.duration ? formatDuration(pl.duration) : ''}`;
        renderTracks();
    } catch(e) {
        document.getElementById('trackList').innerHTML = `<div class="p-6 text-red-400 text-sm">${esc(e.message)}</div>`;
    }
}

function renderTracks() {
    const list = document.getElementById('trackList');
    if (!currentSongs.length) {
        list.innerHTML = '<div class="p-6 text-gray-500 text-sm text-center">Playlist vide. Ajoutez des titres via la recherche ci-dessus.</div>';
        return;
    }
    list.innerHTML = currentSongs.map((s, i) => `
        <div class="flex items-center gap-3 px-5 py-2.5 hover:bg-gray-700/50 group text-sm">
            <span class="w-6 text-gray-600 text-xs text-right shrink-0">${i+1}</span>
            <div class="flex-1 min-w-0">
                <div class="truncate font-medium">${esc(s.title)}</div>
                <div class="text-xs text-gray-500 truncate">${esc(s.artist||'')}${s.album ? ' · '+esc(s.album) : ''}</div>
            </div>
            <span class="text-xs text-gray-600 shrink-0">${formatDuration(s.duration)}</span>
            <button onclick="removeTrack(${i})"
                class="opacity-0 group-hover:opacity-100 text-gray-500 hover:text-red-400 p-1 rounded transition shrink-0" title="Retirer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>`).join('');
}

// ─── Remove track ───
async function removeTrack(index) {
    if (!currentPlaylistId) return;
    try {
        await api('DELETE', `/portal/playlists/${currentPlaylistId}/tracks`, { index });
        currentSongs.splice(index, 1);
        document.getElementById('detailCount').textContent = `${currentSongs.length} titre(s)`;
        renderTracks();
        updateCardCount(currentPlaylistId, currentSongs.length);
    } catch(e) { toast(e.message, false); }
}

// ─── Song search & add ───
let searchTimeout;
document.getElementById('songSearch').addEventListener('input', () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(searchSongs, 400);
});
document.getElementById('songSearch').addEventListener('keydown', e => {
    if (e.key === 'Enter') { clearTimeout(searchTimeout); searchSongs(); }
});

async function searchSongs() {
    const q = document.getElementById('songSearch').value.trim();
    const resultsEl = document.getElementById('searchResults');
    if (!q) { resultsEl.style.display = 'none'; return; }
    resultsEl.style.display = 'block';
    resultsEl.innerHTML = '<div class="text-gray-500 text-xs py-1">Recherche…</div>';
    try {
        const songs = await api('GET', `/portal/playlists/search?q=${encodeURIComponent(q)}`);
        if (!songs.length) { resultsEl.innerHTML = '<div class="text-gray-500 text-xs py-1">Aucun résultat.</div>'; return; }
        resultsEl.innerHTML = songs.map(s => `
            <div class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-gray-700 text-xs">
                <div class="flex-1 min-w-0">
                    <span class="font-medium">${esc(s.title)}</span>
                    <span class="text-gray-500 ml-1">${esc(s.artist||'')}${s.album?' · '+esc(s.album):''}</span>
                </div>
                <button onclick="addSong('${esc(s.id)}', '${esc(s.title)}')"
                    class="shrink-0 px-2 py-0.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded text-xs transition">
                    + Ajouter
                </button>
            </div>`).join('');
    } catch(e) { resultsEl.innerHTML = `<div class="text-red-400 text-xs py-1">${esc(e.message)}</div>`; }
}

async function addSong(songId, songTitle) {
    if (!currentPlaylistId) return;
    try {
        await api('POST', `/portal/playlists/${currentPlaylistId}/tracks`, { song_ids: [songId] });
        toast(`"${songTitle}" ajouté.`);
        // Reload full playlist to get updated entry list
        const pl = await api('GET', `/portal/playlists/${currentPlaylistId}`);
        currentSongs = pl.entry || [];
        document.getElementById('detailCount').textContent = `${currentSongs.length} titre(s)`;
        renderTracks();
        updateCardCount(currentPlaylistId, currentSongs.length);
    } catch(e) { toast(e.message, false); }
}

// ─── Create ───
function openCreateModal() {
    document.getElementById('createName').value = '';
    document.getElementById('createModal').style.display = 'flex';
    setTimeout(() => document.getElementById('createName').focus(), 50);
}
function closeCreateModal() { document.getElementById('createModal').style.display = 'none'; }
document.getElementById('createName').addEventListener('keydown', e => { if (e.key === 'Enter') createPlaylist(); });

async function createPlaylist() {
    const name = document.getElementById('createName').value.trim();
    if (!name) return;
    try {
        const pl = await api('POST', '/portal/playlists', { name });
        closeCreateModal();
        // Add card to list
        const card = document.createElement('div');
        card.className = 'playlist-card bg-gray-800 border border-gray-700 rounded-lg p-4 cursor-pointer hover:border-indigo-500 transition';
        card.dataset.id = pl.id;
        card.innerHTML = `
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="font-medium truncate">${esc(pl.name)}</p>
                    <p class="text-xs text-gray-500 mt-0.5" data-count="${pl.id}">0 titre(s)</p>
                </div>
                <div class="flex gap-1 shrink-0">
                    <button onclick="event.stopPropagation(); openRenameModal('${pl.id}', ${JSON.stringify(pl.name)})" class="text-gray-500 hover:text-indigo-400 p-1 rounded" title="Renommer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button onclick="event.stopPropagation(); deletePlaylist('${pl.id}', ${JSON.stringify(pl.name)})" class="text-gray-500 hover:text-red-400 p-1 rounded" title="Supprimer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>`;
        card.onclick = () => loadPlaylist(pl.id, card);
        document.getElementById('emptyState')?.remove();
        document.getElementById('playlistList').appendChild(card);
        toast(`Playlist "${pl.name}" créée.`);
        loadPlaylist(pl.id, card);
    } catch(e) { toast(e.message, false); }
}

// ─── Rename ───
function openRenameModal(id, name) {
    document.getElementById('renameId').value = id;
    document.getElementById('renameName').value = name;
    document.getElementById('renameModal').style.display = 'flex';
    setTimeout(() => document.getElementById('renameName').focus(), 50);
}
function closeRenameModal() { document.getElementById('renameModal').style.display = 'none'; }
document.getElementById('renameName').addEventListener('keydown', e => { if (e.key === 'Enter') renamePlaylist(); });

async function renamePlaylist() {
    const id = document.getElementById('renameId').value;
    const name = document.getElementById('renameName').value.trim();
    if (!name) return;
    try {
        await api('PUT', `/portal/playlists/${id}`, { name });
        closeRenameModal();
        // Update card text
        const card = document.querySelector(`.playlist-card[data-id="${id}"] p.font-medium`);
        if (card) card.textContent = name;
        // Update detail header if open
        if (currentPlaylistId === id) document.getElementById('detailName').textContent = name;
        toast('Playlist renommée.');
    } catch(e) { toast(e.message, false); }
}

// ─── Delete ───
async function deletePlaylist(id, name) {
    if (!confirm(`Supprimer la playlist "${name}" ?`)) return;
    try {
        await api('DELETE', `/portal/playlists/${id}`);
        document.querySelector(`.playlist-card[data-id="${id}"]`)?.remove();
        if (currentPlaylistId === id) {
            currentPlaylistId = null;
            document.getElementById('detailPanel').style.setProperty('display', 'none', 'important');
            document.getElementById('detailEmpty').style.display = '';
        }
        if (!document.querySelectorAll('.playlist-card').length) {
            const list = document.getElementById('playlistList');
            list.innerHTML = '<div id="emptyState" class="bg-gray-800 border border-dashed border-gray-600 rounded-lg p-8 text-center text-gray-500"><p class="text-sm">Aucune playlist.</p><p class="text-xs mt-1">Créez-en une ou utilisez une app mobile.</p></div>';
        }
        toast('Playlist supprimée.');
    } catch(e) { toast(e.message, false); }
}

function updateCardCount(id, count) {
    const el = document.querySelector(`.playlist-card[data-id="${id}"] .text-gray-500`);
    if (el) el.textContent = `${count} titre(s)`;
}

// Close modals on backdrop click
['createModal','renameModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', e => { if (e.target.id === id) e.target.style.display = 'none'; });
});
</script>
@endsection
