@extends('layouts.admin')
@section('title', 'Gestion des métadonnées — Admin MonFlow')
@section('content')

<div class="mb-5 flex items-center justify-between gap-4 flex-wrap">
    <div>
        <h1 class="text-base font-semibold text-zinc-100">Gestion des métadonnées</h1>
        <p class="text-sm text-zinc-500 mt-0.5">{{ number_format($total) }} titre(s) au total</p>
    </div>
    <button type="button" onclick="openBulkCovers()"
            class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-sm font-medium px-4 py-2 rounded-lg border border-zinc-700 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        Pochettes manquantes
    </button>
</div>

{{-- Modal pochettes manquantes --}}
<div id="bulkCoverModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.7)">
    <div class="bg-zinc-900 border border-zinc-700 rounded-2xl w-full max-w-lg p-6 shadow-2xl">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-zinc-100">Correction automatique des pochettes</h2>
            <button onclick="closeBulkCovers()" class="text-zinc-600 hover:text-zinc-300 transition text-lg leading-none">✕</button>
        </div>

        {{-- Étape 1 : scan --}}
        <div id="bulkStep1">
            <p class="text-sm text-zinc-400 mb-4">Analyse tous les titres de la bibliothèque pour trouver ceux sans pochette, puis télécharge et intègre automatiquement la meilleure correspondance via iTunes.</p>
            <button onclick="startBulkScan()" id="bulkScanBtn"
                    class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <span id="bulkScanLabel">Analyser la bibliothèque</span>
                <svg id="bulkScanSpin" class="hidden w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
            </button>
        </div>

        {{-- Étape 2 : résultats + lancement --}}
        <div id="bulkStep2" class="hidden">
            <div class="mb-4 p-3 bg-zinc-800 rounded-lg text-sm">
                <span class="text-zinc-300 font-medium" id="bulkFoundCount">0</span>
                <span class="text-zinc-500"> titre(s) sans pochette détecté(s)</span>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="startBulkFix()" id="bulkFixBtn"
                        class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                    Lancer la correction automatique
                </button>
                <button onclick="closeBulkCovers()" class="text-sm text-zinc-500 hover:text-zinc-300 transition">Annuler</button>
            </div>
        </div>

        {{-- Étape 3 : progression --}}
        <div id="bulkStep3" class="hidden">
            <div class="mb-3 flex items-center justify-between text-xs text-zinc-500">
                <span id="bulkProgressLabel">Initialisation…</span>
                <span id="bulkProgressCount">0 / 0</span>
            </div>
            <div class="w-full bg-zinc-800 rounded-full h-2 mb-4">
                <div id="bulkProgressBar" class="bg-indigo-500 h-2 rounded-full transition-all duration-300" style="width:0%"></div>
            </div>
            <div class="text-xs text-zinc-600 mb-3 truncate" id="bulkCurrentTitle">—</div>
            <div class="flex gap-4 text-xs">
                <span class="text-emerald-400"><span id="bulkOk">0</span> appliquées</span>
                <span class="text-zinc-500"><span id="bulkSkipped">0</span> ignorées (aucun résultat)</span>
                <span class="text-red-400"><span id="bulkFailed">0</span> erreurs</span>
            </div>
        </div>

        {{-- Étape 4 : terminé --}}
        <div id="bulkStep4" class="hidden">
            <div class="flex items-center gap-2 text-emerald-400 mb-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span class="text-sm font-medium">Correction terminée</span>
            </div>
            <div class="text-xs text-zinc-500 space-y-1 mb-4">
                <div><span class="text-emerald-400 font-medium" id="bulkFinalOk">0</span> pochette(s) appliquée(s)</div>
                <div><span class="text-zinc-400 font-medium" id="bulkFinalSkipped">0</span> ignorée(s) (aucun résultat iTunes)</div>
                <div><span class="text-red-400 font-medium" id="bulkFinalFailed">0</span> erreur(s)</div>
            </div>
            <button onclick="closeBulkCovers()" class="text-sm bg-zinc-800 hover:bg-zinc-700 text-zinc-300 px-4 py-2 rounded-lg border border-zinc-700 transition">Fermer</button>
        </div>
    </div>
</div>

<form method="GET" class="mb-4 flex items-center gap-3 flex-wrap">
    <input name="q" value="{{ $q }}" placeholder="Filtrer par titre, artiste…"
           class="bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-sm text-zinc-200 placeholder-zinc-600 focus:outline-none focus:border-indigo-500 w-72">
    <button class="bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-sm px-4 py-2 rounded-lg border border-zinc-700 transition">Filtrer</button>
    @if($q)
        <a href="/admin/metadata" class="text-xs text-zinc-500 hover:text-zinc-300">Effacer</a>
    @endif
    <span class="ml-auto text-xs text-zinc-600">Page {{ $page }} / {{ max(1, $lastPage) }}</span>
</form>

<div id="saveNotif" class="hidden mb-4 text-sm px-3 py-2 rounded-lg border"></div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-zinc-800">
                <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-zinc-500 uppercase tracking-wider">Titre</th>
                <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-zinc-500 uppercase tracking-wider">Artiste</th>
                <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-zinc-500 uppercase tracking-wider">Album</th>
                <th class="px-4 py-2.5 w-24"></th>
            </tr>
        </thead>
        <tbody id="songsTable">
        @forelse($songs as $s)
        <tr class="song-row border-t border-zinc-800/50 hover:bg-zinc-800/20 transition" data-id="{{ $s['id'] }}"
            data-title="{{ $s['title'] ?? '' }}"
            data-artist="{{ $s['artist'] ?? '' }}"
            data-album="{{ $s['album'] ?? '' }}"
            data-album-artist="{{ $s['albumArtist'] ?? '' }}"
            data-genre="{{ $s['genre'] ?? '' }}"
            data-track="{{ $s['trackNumber'] ?? '' }}"
            data-year="{{ $s['year'] ?? '' }}">
            <td class="px-4 py-2.5 text-zinc-200 truncate max-w-[260px]">{{ $s['title'] ?? '—' }}</td>
            <td class="px-4 py-2.5 text-zinc-400 truncate max-w-[200px]">{{ $s['artist'] ?? '—' }}</td>
            <td class="px-4 py-2.5 text-zinc-500 truncate max-w-[200px]">{{ $s['album'] ?? '—' }}</td>
            <td class="px-4 py-2.5 text-right">
                <button type="button" onclick="toggleEdit(this)"
                        class="text-xs text-indigo-400 hover:text-indigo-300 transition edit-toggle-btn">Modifier</button>
            </td>
        </tr>
        <tr class="edit-row border-t border-indigo-500/20 bg-indigo-500/5 hidden" data-for="{{ $s['id'] }}">
            <td colspan="4" class="px-4 py-4">
                <div class="grid grid-cols-2 gap-3 mb-3 sm:grid-cols-3">
                    <div>
                        <label class="block text-[10px] font-semibold text-zinc-500 uppercase tracking-wider mb-1">Titre</label>
                        <input type="text" name="title" class="edit-input w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-1.5 text-sm text-zinc-100 focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-zinc-500 uppercase tracking-wider mb-1">Artiste</label>
                        <input type="text" name="artist" class="edit-input w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-1.5 text-sm text-zinc-100 focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-zinc-500 uppercase tracking-wider mb-1">Album</label>
                        <input type="text" name="album" class="edit-input w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-1.5 text-sm text-zinc-100 focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-zinc-500 uppercase tracking-wider mb-1">Artiste album</label>
                        <input type="text" name="albumArtist" class="edit-input w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-1.5 text-sm text-zinc-100 focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-semibold text-zinc-500 uppercase tracking-wider mb-1">Genre</label>
                        <input type="text" name="genre" class="edit-input w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-1.5 text-sm text-zinc-100 focus:outline-none focus:border-indigo-500">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-semibold text-zinc-500 uppercase tracking-wider mb-1">Piste</label>
                            <input type="number" name="trackNumber" min="0" class="edit-input w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-1.5 text-sm text-zinc-100 focus:outline-none focus:border-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-zinc-500 uppercase tracking-wider mb-1">Année</label>
                            <input type="number" name="year" min="0" max="9999" class="edit-input w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-1.5 text-sm text-zinc-100 focus:outline-none focus:border-indigo-500">
                        </div>
                    </div>
                </div>
                {{-- Pochette --}}
                <div class="border-t border-zinc-700/40 pt-3 mt-1">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-[10px] font-semibold text-zinc-500 uppercase tracking-wider">Pochette</span>
                        <button type="button" onclick="searchCover(this)"
                                class="inline-flex items-center gap-1.5 text-xs bg-zinc-800 hover:bg-zinc-700 text-zinc-300 px-2.5 py-1 rounded-lg border border-zinc-700 transition">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <span class="cover-search-label">Rechercher</span>
                        </button>
                        <span class="cover-status text-xs text-zinc-600"></span>
                    </div>
                    <div class="cover-results hidden flex flex-wrap gap-2"></div>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" onclick="saveRow(this)"
                            class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-1.5 rounded-lg transition">
                        <span class="save-label">Enregistrer</span>
                        <svg class="save-spin hidden w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                    </button>
                    <button type="button" onclick="cancelEdit(this)" class="text-sm text-zinc-500 hover:text-zinc-300 transition">Annuler</button>
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="4" class="px-4 py-8 text-center text-zinc-600">Aucun titre trouvé.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

@if($lastPage > 1)
<div class="mt-4 flex items-center justify-center gap-2 text-sm flex-wrap">
    @if($page > 1)
        <a href="?q={{ urlencode($q) }}&page={{ $page - 1 }}"
           class="px-3 py-1.5 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 rounded-lg border border-zinc-700 transition">← Précédent</a>
    @endif
    @php $rangeStart = max(1, $page - 2); $rangeEnd = min($lastPage, $page + 2); @endphp
    @for($p = $rangeStart; $p <= $rangeEnd; $p++)
        <a href="?q={{ urlencode($q) }}&page={{ $p }}"
           class="px-3 py-1.5 rounded-lg border transition {{ $p === $page ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-zinc-800 hover:bg-zinc-700 text-zinc-400 border-zinc-700' }}">{{ $p }}</a>
    @endfor
    @if($page < $lastPage)
        <a href="?q={{ urlencode($q) }}&page={{ $page + 1 }}"
           class="px-3 py-1.5 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 rounded-lg border border-zinc-700 transition">Suivant →</a>
    @endif
</div>
@endif

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

// Field name → data-* attribute mapping
const FIELDS = {
    title: 'title', artist: 'artist', album: 'album',
    albumArtist: 'albumArtist', genre: 'genre',
    trackNumber: 'track', year: 'year',
};

function toggleEdit(btn) {
    const songRow = btn.closest('.song-row');
    const id      = songRow.dataset.id;
    const editRow = document.querySelector(`.edit-row[data-for="${id}"]`);

    // Close any other open edit row
    document.querySelectorAll('.edit-row:not(.hidden)').forEach(r => {
        if (r !== editRow) closeEditRow(r);
    });

    if (editRow.classList.contains('hidden')) {
        // Populate inputs from data attributes
        editRow.querySelector('[name="title"]').value       = songRow.dataset.title ?? '';
        editRow.querySelector('[name="artist"]').value      = songRow.dataset.artist ?? '';
        editRow.querySelector('[name="album"]').value       = songRow.dataset.album ?? '';
        editRow.querySelector('[name="albumArtist"]').value = songRow.dataset.albumArtist ?? '';
        editRow.querySelector('[name="genre"]').value       = songRow.dataset.genre ?? '';
        editRow.querySelector('[name="trackNumber"]').value = songRow.dataset.track ?? '';
        editRow.querySelector('[name="year"]').value        = songRow.dataset.year ?? '';

        editRow.classList.remove('hidden');
        btn.textContent = 'Fermer';
        editRow.querySelector('[name="title"]').focus();
    } else {
        closeEditRow(editRow);
        btn.textContent = 'Modifier';
    }
}

function closeEditRow(editRow) {
    // Reset cover section
    editRow.querySelector('.cover-results').innerHTML = '';
    editRow.querySelector('.cover-results').classList.add('hidden');
    editRow.querySelector('.cover-status').textContent = '';
    editRow.classList.add('hidden');
    const id  = editRow.dataset.for;
    const btn = document.querySelector(`.song-row[data-id="${id}"] .edit-toggle-btn`);
    if (btn) btn.textContent = 'Modifier';
}

function cancelEdit(btn) {
    const editRow = btn.closest('.edit-row');
    closeEditRow(editRow);
}

async function saveRow(btn) {
    const editRow = btn.closest('.edit-row');
    const id      = editRow.dataset.for;
    const songRow = document.querySelector(`.song-row[data-id="${id}"]`);

    const body = new URLSearchParams({ _token: csrfToken });
    editRow.querySelectorAll('.edit-input').forEach(inp => body.append(inp.name, inp.value.trim()));

    btn.querySelector('.save-label').classList.add('opacity-0');
    btn.querySelector('.save-spin').classList.remove('hidden');
    btn.disabled = true;

    try {
        const res  = await fetch(`/admin/metadata/${id}/save`, {
            method: 'POST',
            headers: { Accept: 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString(),
        });
        const json = await res.json();

        if (res.ok && json.success) {
            // Update song row display cells + data attributes
            const title  = editRow.querySelector('[name="title"]').value.trim();
            const artist = editRow.querySelector('[name="artist"]').value.trim();
            const album  = editRow.querySelector('[name="album"]').value.trim();
            songRow.querySelectorAll('td')[0].textContent = title  || '—';
            songRow.querySelectorAll('td')[1].textContent = artist || '—';
            songRow.querySelectorAll('td')[2].textContent = album  || '—';
            songRow.dataset.title       = title;
            songRow.dataset.artist      = artist;
            songRow.dataset.album       = album;
            songRow.dataset.albumArtist = editRow.querySelector('[name="albumArtist"]').value.trim();
            songRow.dataset.genre       = editRow.querySelector('[name="genre"]').value.trim();
            songRow.dataset.track       = editRow.querySelector('[name="trackNumber"]').value.trim();
            songRow.dataset.year        = editRow.querySelector('[name="year"]').value.trim();
            closeEditRow(editRow);
            showNotif('Métadonnées mises à jour.', 'success');
        } else {
            showNotif(json.error || 'Erreur lors de la sauvegarde.', 'error');
        }
    } catch {
        showNotif('Erreur réseau.', 'error');
    } finally {
        btn.querySelector('.save-label').classList.remove('opacity-0');
        btn.querySelector('.save-spin').classList.add('hidden');
        btn.disabled = false;
    }
}

async function searchCover(btn) {
    const editRow = btn.closest('.edit-row');
    const artist  = editRow.querySelector('[name="artist"]').value.trim();
    const album   = editRow.querySelector('[name="album"]').value.trim();
    const title   = editRow.querySelector('[name="title"]').value.trim();
    const q       = [artist, album || title].filter(Boolean).join(' ');

    const statusEl  = editRow.querySelector('.cover-status');
    const resultsEl = editRow.querySelector('.cover-results');
    const labelEl   = btn.querySelector('.cover-search-label');

    labelEl.textContent = 'Recherche…';
    btn.disabled = true;
    statusEl.textContent = '';
    resultsEl.innerHTML = '';
    resultsEl.classList.add('hidden');

    try {
        const res  = await fetch('/admin/metadata/search-artwork?q=' + encodeURIComponent(q), {
            headers: { Accept: 'application/json' }
        });
        const data = await res.json();

        if (!Array.isArray(data) || data.length === 0) {
            statusEl.textContent = 'Aucun résultat.';
        } else {
            data.forEach(item => {
                const img = document.createElement('img');
                img.src = item.thumb;
                img.title = item.label;
                img.className = 'w-14 h-14 rounded-lg object-cover cursor-pointer border-2 border-transparent hover:border-indigo-500 transition cover-thumb';
                img.dataset.full = item.full;
                img.addEventListener('click', () => selectCover(img, editRow));
                resultsEl.appendChild(img);
            });
            resultsEl.classList.remove('hidden');
        }
    } catch {
        statusEl.textContent = 'Erreur réseau.';
    } finally {
        labelEl.textContent = 'Rechercher';
        btn.disabled = false;
    }
}

function selectCover(img, editRow) {
    // Highlight selected
    editRow.querySelectorAll('.cover-thumb').forEach(i => i.classList.remove('border-indigo-500', 'ring-2', 'ring-indigo-500/30'));
    img.classList.add('border-indigo-500', 'ring-2', 'ring-indigo-500/30');
    editRow.dataset.selectedCover = img.dataset.full;

    // Show apply button if not already there
    let applyBtn = editRow.querySelector('.cover-apply-btn');
    if (!applyBtn) {
        applyBtn = document.createElement('button');
        applyBtn.type = 'button';
        applyBtn.className = 'cover-apply-btn inline-flex items-center gap-1.5 text-xs bg-zinc-700 hover:bg-zinc-600 text-zinc-200 px-2.5 py-1 rounded-lg border border-zinc-600 transition mt-2';
        applyBtn.innerHTML = '<span class="apply-label">Appliquer cette pochette</span>'
            + '<svg class="apply-spin hidden w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>';
        applyBtn.addEventListener('click', () => applyCover(applyBtn, editRow));
        editRow.querySelector('.cover-results').after(applyBtn);
    }
}

async function applyCover(btn, editRow) {
    const id  = editRow.dataset.for;
    const url = editRow.dataset.selectedCover;
    if (!url) return;

    btn.querySelector('.apply-label').classList.add('opacity-0');
    btn.querySelector('.apply-spin').classList.remove('hidden');
    btn.disabled = true;

    try {
        const body = new URLSearchParams({ _token: csrfToken, artwork_url: url });
        const res  = await fetch(`/admin/metadata/${id}/cover`, {
            method: 'POST',
            headers: { Accept: 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString(),
        });
        const json = await res.json();

        if (res.ok && json.success) {
            showNotif('Pochette mise à jour.', 'success');
        } else {
            showNotif(json.error || 'Erreur lors de l\'application.', 'error');
        }
    } catch {
        showNotif('Erreur réseau.', 'error');
    } finally {
        btn.querySelector('.apply-label').classList.remove('opacity-0');
        btn.querySelector('.apply-spin').classList.add('hidden');
        btn.disabled = false;
    }
}

// ─── Bulk cover fix ───────────────────────────────────────────────────────────

let bulkSongs = [];
let bulkAbort = false;

function openBulkCovers() {
    bulkAbort = false;
    bulkSongs = [];
    showBulkStep(1);
    document.getElementById('bulkCoverModal').classList.remove('hidden');
}

function closeBulkCovers() {
    bulkAbort = true;
    document.getElementById('bulkCoverModal').classList.add('hidden');
}

function showBulkStep(n) {
    [1,2,3,4].forEach(i => document.getElementById(`bulkStep${i}`).classList.add('hidden'));
    document.getElementById(`bulkStep${n}`).classList.remove('hidden');
}

async function startBulkScan() {
    const btn   = document.getElementById('bulkScanBtn');
    const label = document.getElementById('bulkScanLabel');
    const spin  = document.getElementById('bulkScanSpin');
    label.classList.add('opacity-0');
    spin.classList.remove('hidden');
    btn.disabled = true;

    try {
        const res  = await fetch('/admin/metadata/missing-covers', { headers: { Accept: 'application/json' } });
        const data = await res.json();
        if (!Array.isArray(data)) throw new Error(data.error || 'Erreur serveur');
        bulkSongs = data;
        document.getElementById('bulkFoundCount').textContent = data.length;
        document.getElementById('bulkFixBtn').disabled = data.length === 0;
        showBulkStep(2);
    } catch(e) {
        alert('Erreur lors de l\'analyse : ' + e.message);
    } finally {
        label.classList.remove('opacity-0');
        spin.classList.add('hidden');
        btn.disabled = false;
    }
}

async function startBulkFix() {
    if (!bulkSongs.length) return;
    bulkAbort = false;
    showBulkStep(3);

    let ok = 0, skipped = 0, failed = 0;
    const total = bulkSongs.length;

    const setProgress = (i, title) => {
        const pct = Math.round((i / total) * 100);
        document.getElementById('bulkProgressBar').style.width = pct + '%';
        document.getElementById('bulkProgressCount').textContent = `${i} / ${total}`;
        document.getElementById('bulkCurrentTitle').textContent = title || '';
        document.getElementById('bulkOk').textContent      = ok;
        document.getElementById('bulkSkipped').textContent  = skipped;
        document.getElementById('bulkFailed').textContent   = failed;
    };

    for (let i = 0; i < bulkSongs.length; i++) {
        if (bulkAbort) break;
        const song = bulkSongs[i];
        document.getElementById('bulkProgressLabel').textContent = 'Traitement en cours…';
        setProgress(i, `${song.artist} — ${song.title}`);

        try {
            // 1. Search iTunes
            const q   = encodeURIComponent([song.artist, song.album || song.title].filter(Boolean).join(' '));
            const res = await fetch('/admin/metadata/search-artwork?q=' + q, { headers: { Accept: 'application/json' } });
            const results = await res.json();

            if (!Array.isArray(results) || results.length === 0) {
                skipped++;
                continue;
            }

            // 2. Apply first result
            const artworkUrl = results[0].full;
            const body = new URLSearchParams({ _token: csrfToken, artwork_url: artworkUrl });
            const applyRes = await fetch(`/admin/metadata/${song.id}/cover`, {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString(),
            });
            const applyJson = await applyRes.json();
            if (applyRes.ok && applyJson.success) ok++;
            else failed++;

        } catch {
            failed++;
        }

        setProgress(i + 1, '');
        // Small delay to avoid hammering iTunes API
        await new Promise(r => setTimeout(r, 300));
    }

    document.getElementById('bulkFinalOk').textContent      = ok;
    document.getElementById('bulkFinalSkipped').textContent  = skipped;
    document.getElementById('bulkFinalFailed').textContent   = failed;
    showBulkStep(4);
}

// ──────────────────────────────────────────────────────────────────────────────

function showNotif(msg, type) {
    const el = document.getElementById('saveNotif');
    el.className = type === 'success'
        ? 'mb-4 text-sm px-3 py-2 rounded-lg border bg-emerald-500/10 border-emerald-500/20 text-emerald-400'
        : 'mb-4 text-sm px-3 py-2 rounded-lg border bg-red-500/10 border-red-500/20 text-red-400';
    el.textContent = msg;
    el.classList.remove('hidden');
    clearTimeout(el._t);
    el._t = setTimeout(() => el.classList.add('hidden'), 4000);
}
</script>

@endsection
