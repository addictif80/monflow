@extends('layouts.admin')
@section('title', 'Gestion des paroles — Admin MonFlow')
@section('content')

<div class="mb-5 flex items-center justify-between gap-4 flex-wrap">
    <div>
        <h1 class="text-base font-semibold text-zinc-100">Gestion des paroles</h1>
        <p class="text-sm text-zinc-500 mt-0.5">{{ number_format($total) }} titre(s) au total</p>
    </div>
    <button type="button" onclick="openBulkLyrics()"
            class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-sm font-medium px-4 py-2 rounded-lg border border-zinc-700 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/></svg>
        Paroles manquantes
    </button>
</div>

{{-- Modal paroles manquantes --}}
<div id="bulkLyricsModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.7)">
    <div class="bg-zinc-900 border border-zinc-700 rounded-2xl w-full max-w-lg p-6 shadow-2xl">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-zinc-100">Téléchargement automatique des paroles</h2>
            <button onclick="closeBulkLyrics()" class="text-zinc-600 hover:text-zinc-300 transition text-lg leading-none">✕</button>
        </div>

        {{-- Étape 1 --}}
        <div id="blStep1">
            <p class="text-sm text-zinc-400 mb-4">Analyse la bibliothèque pour trouver les titres sans fichier <code class="text-zinc-300">.lrc</code>, puis télécharge automatiquement les paroles synchronisées depuis LRCLIB.</p>
            <button onclick="startBulkScan()" id="blScanBtn"
                    class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <span id="blScanLabel">Analyser la bibliothèque</span>
                <svg id="blScanSpin" class="hidden w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
            </button>
        </div>

        {{-- Étape 2 --}}
        <div id="blStep2" class="hidden">
            <div class="mb-4 p-3 bg-zinc-800 rounded-lg text-sm">
                <span class="text-zinc-300 font-medium" id="blFoundCount">0</span>
                <span class="text-zinc-500"> titre(s) sans paroles détecté(s)</span>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="startBulkFix()" id="blFixBtn"
                        class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                    Télécharger les paroles manquantes
                </button>
                <button onclick="closeBulkLyrics()" class="text-sm text-zinc-500 hover:text-zinc-300 transition">Annuler</button>
            </div>
        </div>

        {{-- Étape 3 --}}
        <div id="blStep3" class="hidden">
            <div class="mb-3 flex items-center justify-between text-xs text-zinc-500">
                <span id="blProgressLabel">Initialisation…</span>
                <span id="blProgressCount">0 / 0</span>
            </div>
            <div class="w-full bg-zinc-800 rounded-full h-2 mb-4">
                <div id="blProgressBar" class="bg-indigo-500 h-2 rounded-full transition-all duration-300" style="width:0%"></div>
            </div>
            <div class="text-xs text-zinc-600 mb-3 truncate" id="blCurrentTitle">—</div>
            <div class="flex gap-4 text-xs">
                <span class="text-emerald-400"><span id="blOk">0</span> enregistrées</span>
                <span class="text-zinc-500"><span id="blSkipped">0</span> introuvables</span>
                <span class="text-red-400"><span id="blFailed">0</span> erreurs</span>
            </div>
        </div>

        {{-- Étape 4 --}}
        <div id="blStep4" class="hidden">
            <div class="flex items-center gap-2 text-emerald-400 mb-3">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span class="text-sm font-medium">Téléchargement terminé</span>
            </div>
            <div class="text-xs text-zinc-500 space-y-1 mb-4">
                <div><span class="text-emerald-400 font-medium" id="blFinalOk">0</span> parole(s) enregistrée(s)</div>
                <div><span class="text-zinc-400 font-medium" id="blFinalSkipped">0</span> introuvable(s) sur LRCLIB</div>
                <div><span class="text-red-400 font-medium" id="blFinalFailed">0</span> erreur(s)</div>
            </div>
            <button onclick="closeBulkLyrics()" class="text-sm bg-zinc-800 hover:bg-zinc-700 text-zinc-300 px-4 py-2 rounded-lg border border-zinc-700 transition">Fermer</button>
        </div>
    </div>
</div>

<form method="GET" class="mb-4 flex items-center gap-3 flex-wrap">
    <input name="q" value="{{ $q }}" placeholder="Filtrer par titre, artiste…"
           class="bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-sm text-zinc-200 placeholder-zinc-600 focus:outline-none focus:border-indigo-500 w-72">
    <button class="bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-sm px-4 py-2 rounded-lg border border-zinc-700 transition">Filtrer</button>
    @if($q)
        <a href="/admin/lyrics" class="text-xs text-zinc-500 hover:text-zinc-300">Effacer</a>
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
                <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-zinc-500 uppercase tracking-wider hidden sm:table-cell">Album</th>
                <th class="px-4 py-2.5 text-center text-[10px] font-semibold text-zinc-500 uppercase tracking-wider w-24">Paroles</th>
                <th class="px-4 py-2.5 w-24"></th>
            </tr>
        </thead>
        <tbody>
        @forelse($songs as $s)
        <tr class="song-row border-t border-zinc-800/50 hover:bg-zinc-800/20 transition" data-id="{{ $s['id'] }}"
            data-title="{{ $s['title'] ?? '' }}"
            data-artist="{{ $s['artist'] ?? '' }}"
            data-album="{{ $s['album'] ?? '' }}"
            data-has-lyrics="{{ $s['hasLyrics'] ? '1' : '0' }}">
            <td class="px-4 py-2.5 text-zinc-200 truncate max-w-[220px]">{{ $s['title'] ?? '—' }}</td>
            <td class="px-4 py-2.5 text-zinc-400 truncate max-w-[180px]">{{ $s['artist'] ?? '—' }}</td>
            <td class="px-4 py-2.5 text-zinc-500 truncate max-w-[180px] hidden sm:table-cell">{{ $s['album'] ?? '—' }}</td>
            <td class="px-4 py-2.5 text-center">
                @if($s['hasLyrics'])
                    <span class="lyrics-badge inline-flex items-center gap-1 text-[10px] font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-2 py-0.5 rounded-full">
                        <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
                        LRC
                    </span>
                @else
                    <span class="lyrics-badge inline-flex items-center gap-1 text-[10px] font-medium bg-zinc-700/50 text-zinc-500 border border-zinc-700 px-2 py-0.5 rounded-full">
                        Absent
                    </span>
                @endif
            </td>
            <td class="px-4 py-2.5 text-right">
                <button type="button" onclick="toggleLyrics(this)"
                        class="text-xs text-indigo-400 hover:text-indigo-300 transition lyrics-toggle-btn">Éditer</button>
            </td>
        </tr>
        <tr class="lyrics-row border-t border-indigo-500/20 bg-indigo-500/5 hidden" data-for="{{ $s['id'] }}">
            <td colspan="5" class="px-4 py-4">
                <div class="text-[10px] font-semibold text-zinc-500 uppercase tracking-wider mb-2">
                    Paroles LRC — <span class="text-zinc-400 normal-case font-normal">{{ $s['title'] ?? '' }}</span>
                </div>
                <div class="lyrics-loading text-xs text-zinc-500 mb-2">Chargement…</div>
                <textarea class="lrc-editor hidden w-full bg-zinc-950 border border-zinc-700 rounded-lg px-3 py-2.5 text-xs text-zinc-300 font-mono focus:outline-none focus:border-indigo-500 resize-y"
                          rows="12" placeholder="[00:00.00]Paroles synchronisées…&#10;[00:05.00]Deuxième ligne…&#10;&#10;Ou paroles non synchronisées (sans timestamps)"></textarea>
                <div class="lyrics-status text-xs text-zinc-600 mb-2 hidden"></div>
                <div class="flex items-center gap-2 mt-2 lyrics-actions hidden">
                    <button type="button" onclick="saveLyrics(this)"
                            class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-1.5 rounded-lg transition">
                        <span class="save-label">Enregistrer</span>
                        <svg class="save-spin hidden w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    </button>
                    <button type="button" onclick="downloadLyrics(this)"
                            class="inline-flex items-center gap-1.5 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-sm px-3 py-1.5 rounded-lg border border-zinc-700 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span class="dl-label">Télécharger LRCLIB</span>
                        <svg class="dl-spin hidden w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                    </button>
                    <button type="button" onclick="cancelLyrics(this)" class="text-sm text-zinc-500 hover:text-zinc-300 transition">Annuler</button>
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="5" class="px-4 py-8 text-center text-zinc-600">Aucun titre trouvé.</td></tr>
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

async function toggleLyrics(btn) {
    const songRow   = btn.closest('.song-row');
    const id        = songRow.dataset.id;
    const lyricsRow = document.querySelector(`.lyrics-row[data-for="${id}"]`);

    // Close any other open row
    document.querySelectorAll('.lyrics-row:not(.hidden)').forEach(r => {
        if (r !== lyricsRow) closeLyricsRow(r);
    });

    if (lyricsRow.classList.contains('hidden')) {
        lyricsRow.classList.remove('hidden');
        btn.textContent = 'Fermer';

        const loadingEl = lyricsRow.querySelector('.lyrics-loading');
        const editorEl  = lyricsRow.querySelector('.lrc-editor');
        const actionsEl = lyricsRow.querySelector('.lyrics-actions');

        loadingEl.classList.remove('hidden');
        editorEl.classList.add('hidden');
        actionsEl.classList.add('hidden');

        try {
            const res  = await fetch(`/admin/lyrics/${id}/get`, { headers: { Accept: 'application/json' } });
            const data = await res.json();
            editorEl.value = data.lrc || '';
        } catch {
            editorEl.value = '';
        } finally {
            loadingEl.classList.add('hidden');
            editorEl.classList.remove('hidden');
            actionsEl.classList.remove('hidden');
            editorEl.focus();
        }
    } else {
        closeLyricsRow(lyricsRow);
    }
}

function closeLyricsRow(lyricsRow) {
    lyricsRow.classList.add('hidden');
    const id  = lyricsRow.dataset.for;
    const btn = document.querySelector(`.song-row[data-id="${id}"] .lyrics-toggle-btn`);
    if (btn) btn.textContent = 'Éditer';
    lyricsRow.querySelector('.lyrics-status').textContent = '';
    lyricsRow.querySelector('.lyrics-status').classList.add('hidden');
}

function cancelLyrics(btn) {
    closeLyricsRow(btn.closest('.lyrics-row'));
}

async function saveLyrics(btn) {
    const lyricsRow = btn.closest('.lyrics-row');
    const id        = lyricsRow.dataset.for;
    const content   = lyricsRow.querySelector('.lrc-editor').value;
    const statusEl  = lyricsRow.querySelector('.lyrics-status');

    btn.querySelector('.save-label').classList.add('opacity-0');
    btn.querySelector('.save-spin').classList.remove('hidden');
    btn.disabled = true;

    try {
        const body = new URLSearchParams({ _token: csrfToken, lrc_content: content });
        const res  = await fetch(`/admin/lyrics/${id}/save`, {
            method: 'POST',
            headers: { Accept: 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString(),
        });
        const json = await res.json();

        if (res.ok && json.success) {
            // Update badge
            const songRow  = document.querySelector(`.song-row[data-id="${id}"]`);
            const badge    = songRow.querySelector('.lyrics-badge');
            const hasLyrics = content.trim().length > 0;
            if (badge) {
                badge.outerHTML = hasLyrics
                    ? `<span class="lyrics-badge inline-flex items-center gap-1 text-[10px] font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-2 py-0.5 rounded-full"><svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>LRC</span>`
                    : `<span class="lyrics-badge inline-flex items-center gap-1 text-[10px] font-medium bg-zinc-700/50 text-zinc-500 border border-zinc-700 px-2 py-0.5 rounded-full">Absent</span>`;
            }
            showNotif('Paroles enregistrées.', 'success');
            closeLyricsRow(lyricsRow);
        } else {
            statusEl.textContent = json.error || 'Erreur lors de la sauvegarde.';
            statusEl.className = 'lyrics-status text-xs text-red-400 mb-2';
            statusEl.classList.remove('hidden');
        }
    } catch {
        statusEl.textContent = 'Erreur réseau.';
        statusEl.className = 'lyrics-status text-xs text-red-400 mb-2';
        statusEl.classList.remove('hidden');
    } finally {
        btn.querySelector('.save-label').classList.remove('opacity-0');
        btn.querySelector('.save-spin').classList.add('hidden');
        btn.disabled = false;
    }
}

async function downloadLyrics(btn) {
    const lyricsRow = btn.closest('.lyrics-row');
    const id        = lyricsRow.dataset.for;
    const editorEl  = lyricsRow.querySelector('.lrc-editor');
    const statusEl  = lyricsRow.querySelector('.lyrics-status');

    btn.querySelector('.dl-label').classList.add('opacity-0');
    btn.querySelector('.dl-spin').classList.remove('hidden');
    btn.disabled = true;
    statusEl.classList.add('hidden');

    try {
        const res  = await fetch(`/admin/lyrics/${id}/download`, { headers: { Accept: 'application/json' } });
        const json = await res.json();

        if (res.ok && json.lrc) {
            editorEl.value = json.lrc;
            const type = json.synced ? 'synchronisées' : 'non synchronisées';
            statusEl.textContent = `Paroles ${type} chargées depuis LRCLIB. Cliquez sur Enregistrer pour sauvegarder.`;
            statusEl.className = 'lyrics-status text-xs text-emerald-400 mb-2';
            statusEl.classList.remove('hidden');
        } else {
            statusEl.textContent = json.error || 'Paroles introuvables sur LRCLIB.';
            statusEl.className = 'lyrics-status text-xs text-zinc-500 mb-2';
            statusEl.classList.remove('hidden');
        }
    } catch {
        statusEl.textContent = 'Erreur réseau.';
        statusEl.className = 'lyrics-status text-xs text-red-400 mb-2';
        statusEl.classList.remove('hidden');
    } finally {
        btn.querySelector('.dl-label').classList.remove('opacity-0');
        btn.querySelector('.dl-spin').classList.add('hidden');
        btn.disabled = false;
    }
}

// ─── Bulk lyrics ─────────────────────────────────────────────────────────────

let bulkSongs = [];
let bulkAbort = false;

function openBulkLyrics() {
    bulkAbort = false;
    bulkSongs = [];
    showBlStep(1);
    document.getElementById('bulkLyricsModal').classList.remove('hidden');
}

function closeBulkLyrics() {
    bulkAbort = true;
    document.getElementById('bulkLyricsModal').classList.add('hidden');
}

function showBlStep(n) {
    [1,2,3,4].forEach(i => document.getElementById(`blStep${i}`).classList.add('hidden'));
    document.getElementById(`blStep${n}`).classList.remove('hidden');
}

async function startBulkScan() {
    const btn   = document.getElementById('blScanBtn');
    const label = document.getElementById('blScanLabel');
    const spin  = document.getElementById('blScanSpin');
    label.classList.add('opacity-0');
    spin.classList.remove('hidden');
    btn.disabled = true;

    try {
        const res  = await fetch('/admin/lyrics/missing', { headers: { Accept: 'application/json' } });
        const data = await res.json();
        if (!Array.isArray(data)) throw new Error(data.error || 'Erreur serveur');
        bulkSongs = data;
        document.getElementById('blFoundCount').textContent = data.length;
        document.getElementById('blFixBtn').disabled = data.length === 0;
        showBlStep(2);
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
    showBlStep(3);

    let ok = 0, skipped = 0, failed = 0;
    const total = bulkSongs.length;

    const setProgress = (i, title) => {
        const pct = Math.round((i / total) * 100);
        document.getElementById('blProgressBar').style.width = pct + '%';
        document.getElementById('blProgressCount').textContent = `${i} / ${total}`;
        document.getElementById('blCurrentTitle').textContent = title || '';
        document.getElementById('blOk').textContent      = ok;
        document.getElementById('blSkipped').textContent  = skipped;
        document.getElementById('blFailed').textContent   = failed;
    };

    for (let i = 0; i < bulkSongs.length; i++) {
        if (bulkAbort) break;
        const song = bulkSongs[i];
        document.getElementById('blProgressLabel').textContent = 'Téléchargement en cours…';
        setProgress(i, `${song.artist} — ${song.title}`);

        try {
            // 1. Download from LRCLIB
            const dlRes  = await fetch(`/admin/lyrics/${song.id}/download`, { headers: { Accept: 'application/json' } });
            const dlData = await dlRes.json();

            if (!dlRes.ok || !dlData.lrc) {
                skipped++;
                continue;
            }

            // 2. Save
            const body = new URLSearchParams({ _token: csrfToken, lrc_content: dlData.lrc });
            const saveRes = await fetch(`/admin/lyrics/${song.id}/save`, {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString(),
            });
            const saveData = await saveRes.json();
            if (saveRes.ok && saveData.success) ok++;
            else failed++;

        } catch {
            failed++;
        }

        setProgress(i + 1, '');
        await new Promise(r => setTimeout(r, 200));
    }

    document.getElementById('blFinalOk').textContent      = ok;
    document.getElementById('blFinalSkipped').textContent  = skipped;
    document.getElementById('blFinalFailed').textContent   = failed;
    showBlStep(4);
}

// ─────────────────────────────────────────────────────────────────────────────

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
