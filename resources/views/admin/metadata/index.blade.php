@extends('layouts.admin')
@section('title', 'Gestion des métadonnées — Admin MonFlow')
@section('content')

<div class="mb-5 flex items-center justify-between gap-4 flex-wrap">
    <div>
        <h1 class="text-base font-semibold text-zinc-100">Gestion des métadonnées</h1>
        <p class="text-sm text-zinc-500 mt-0.5">{{ number_format($total) }} titre(s) au total</p>
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
