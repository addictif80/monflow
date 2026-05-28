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

<div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-x-auto">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-zinc-800">
                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-zinc-500 uppercase tracking-wider min-w-[160px]">Titre</th>
                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-zinc-500 uppercase tracking-wider min-w-[130px]">Artiste</th>
                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-zinc-500 uppercase tracking-wider min-w-[130px]">Album</th>
                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-zinc-500 uppercase tracking-wider min-w-[120px]">Artiste album</th>
                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-zinc-500 uppercase tracking-wider min-w-[90px]">Genre</th>
                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-zinc-500 uppercase tracking-wider w-16">Piste</th>
                <th class="px-3 py-2.5 text-left text-[10px] font-semibold text-zinc-500 uppercase tracking-wider w-16">Année</th>
                <th class="px-3 py-2.5 text-right text-[10px] font-semibold text-zinc-500 uppercase tracking-wider w-36"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-800/50">
        @forelse($songs as $s)
        <tr class="hover:bg-zinc-800/20 transition song-row" data-id="{{ $s['id'] }}">

            @php
                $fields = [
                    'title'       => ['text' => $s['title'] ?? '',       'type' => 'text',   'color' => 'text-zinc-200'],
                    'artist'      => ['text' => $s['artist'] ?? '',      'type' => 'text',   'color' => 'text-zinc-400'],
                    'album'       => ['text' => $s['album'] ?? '',       'type' => 'text',   'color' => 'text-zinc-400'],
                    'albumArtist' => ['text' => $s['albumArtist'] ?? '', 'type' => 'text',   'color' => 'text-zinc-500'],
                    'genre'       => ['text' => $s['genre'] ?? '',       'type' => 'text',   'color' => 'text-zinc-500'],
                    'trackNumber' => ['text' => $s['trackNumber'] ?? '', 'type' => 'number', 'color' => 'text-zinc-500'],
                    'year'        => ['text' => $s['year'] ?? '',        'type' => 'number', 'color' => 'text-zinc-500'],
                ];
            @endphp

            @foreach($fields as $field => $cfg)
            <td class="px-3 py-2 meta-td" data-field="{{ $field }}">
                <span class="meta-display {{ $cfg['color'] }} block truncate max-w-xs">{{ $cfg['text'] }}</span>
                <input type="{{ $cfg['type'] }}"
                       class="meta-input hidden w-full bg-zinc-800 border border-zinc-700 rounded px-2 py-1 text-xs text-zinc-100 focus:outline-none focus:border-indigo-500"
                       value="{{ $cfg['text'] }}"
                       @if($cfg['type'] === 'number') min="0" @if($field === 'year') max="9999" @endif @endif>
            </td>
            @endforeach

            <td class="px-3 py-2 text-right">
                <div class="action-display">
                    <button type="button" onclick="editRow(this)"
                            class="text-xs text-indigo-400 hover:text-indigo-300 transition">Modifier</button>
                </div>
                <div class="action-edit hidden flex items-center justify-end gap-2">
                    <button type="button" onclick="saveRow(this)"
                            class="text-xs bg-indigo-600 hover:bg-indigo-500 text-white px-2.5 py-1 rounded transition">
                        <span class="save-label">Enregistrer</span>
                        <svg class="save-spin hidden w-3 h-3 animate-spin inline" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                    </button>
                    <button type="button" onclick="cancelRow(this)"
                            class="text-xs text-zinc-500 hover:text-zinc-300 transition">Annuler</button>
                </div>
            </td>
        </tr>
        @empty
        <tr><td colspan="8" class="px-4 py-8 text-center text-zinc-600">Aucun titre trouvé.</td></tr>
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

function editRow(btn) {
    const row = btn.closest('tr');
    row.querySelectorAll('.meta-display').forEach(el => el.classList.add('hidden'));
    row.querySelectorAll('.meta-input').forEach(el => el.classList.remove('hidden'));
    row.querySelector('.action-display').classList.add('hidden');
    row.querySelector('.action-edit').classList.remove('hidden');
    row.querySelector('.meta-input')?.focus();
}

function cancelRow(btn) {
    const row = btn.closest('tr');
    // Restore original values
    row.querySelectorAll('.meta-td').forEach(td => {
        const disp  = td.querySelector('.meta-display');
        const input = td.querySelector('.meta-input');
        input.value = disp.textContent.trim();
        disp.classList.remove('hidden');
        input.classList.add('hidden');
    });
    row.querySelector('.action-display').classList.remove('hidden');
    row.querySelector('.action-edit').classList.add('hidden');
}

async function saveRow(btn) {
    const row = btn.closest('tr');
    const id  = row.dataset.id;

    const body = new URLSearchParams({ _token: csrfToken });
    row.querySelectorAll('.meta-td').forEach(td => {
        body.append(td.dataset.field, td.querySelector('.meta-input').value.trim());
    });

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
            // Commit new values to display spans
            row.querySelectorAll('.meta-td').forEach(td => {
                td.querySelector('.meta-display').textContent = td.querySelector('.meta-input').value.trim();
            });
            cancelRow(btn);
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
