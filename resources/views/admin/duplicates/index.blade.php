@extends('layouts.admin')
@section('title', 'Gestion des doublons — Admin MonFlow')
@section('content')
<div class="mb-6">
    <h1 class="text-base font-semibold text-zinc-100">Gestion des doublons</h1>
    <p class="text-sm text-zinc-500 mt-0.5">Détection et suppression des fichiers en double</p>
</div>

{{-- Scan-in-progress banner (shown after a delete, auto-hides when scan finishes) --}}
@if(session('scanning'))
<div id="scanBanner" class="mb-4 flex items-center gap-3 bg-indigo-500/10 border border-indigo-500/20 rounded-xl px-4 py-3 text-indigo-300 text-sm">
    <svg class="w-4 h-4 animate-spin shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
    <span id="scanMsg">Scan Navidrome en cours — patientez avant de rescanner…</span>
    <button onclick="document.getElementById('scanBanner').remove()" class="ml-auto text-indigo-500 hover:text-indigo-300 transition text-xs">Fermer</button>
</div>
<script>
(function pollScan() {
    fetch('/admin/duplicates/scan-status')
        .then(r => r.json())
        .then(d => {
            if (d.scanning) {
                document.getElementById('scanMsg').textContent =
                    'Scan Navidrome en cours (' + (d.count || 0) + ' fichiers traités)…';
                setTimeout(pollScan, 3000);
            } else {
                const b = document.getElementById('scanBanner');
                if (b) {
                    b.className = b.className.replace('indigo', 'emerald');
                    document.getElementById('scanMsg').textContent =
                        'Scan terminé. Cliquez sur "Scanner la bibliothèque" pour voir les doublons restants.';
                    const spin = b.querySelector('svg');
                    if (spin) spin.classList.remove('animate-spin');
                }
            }
        })
        .catch(() => setTimeout(pollScan, 5000));
})();
</script>
@endif

<div class="mb-6 flex items-center gap-4 flex-wrap">
    <form method="GET" class="flex items-center gap-3">
        <input type="hidden" name="scan" value="1">
        <button class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Scanner la bibliotheque</button>
    </form>
    @if($scanned)
    <span class="text-sm text-zinc-500">{{ count($duplicates) }} groupe(s) de doublons detecte(s)</span>
    @endif
</div>

@if($scanned && count($duplicates) === 0)
<div class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-4 text-emerald-400 text-sm">
    Aucun doublon detecte dans la bibliotheque.
</div>
@endif

@if($scanned && $mode === 'cross' && count($crossAlbum) === 0)
<div class="bg-green-900/30 border border-green-700 rounded-lg p-4 text-green-300 text-sm">
    Aucune chanson presente sur plusieurs albums.
</div>
@endif

@if($scanned && $mode === 'exact' && count($duplicates) > 0)
<form method="POST" action="/admin/duplicates/batch-delete" id="batchForm">
    @csrf
    <div class="mb-4 flex items-center gap-3">
        <button type="button" id="deleteBtn"
                class="inline-flex items-center gap-2 bg-red-500/10 hover:bg-red-500/15 text-red-400 text-sm font-medium px-4 py-2 rounded-lg border border-red-500/20 transition disabled:opacity-40 disabled:cursor-not-allowed" disabled>
            <span id="deleteBtnLabel">Supprimer la selection (<span id="selectedCount">0</span>)</span>
            <svg id="deleteBtnSpinner" class="hidden w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
        </button>
        <button type="button" id="selectLowerBtn" class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-400 text-xs px-3 py-1.5 rounded-lg border border-zinc-700 transition">Selectionner les moins bons (bitrate inferieur)</button>
    </div>

    @foreach($duplicates as $i => $group)
    <div class="bg-gray-800 border border-gray-700 rounded-lg mb-4 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-700 flex items-center gap-3">
            <span class="text-sm font-semibold">{{ $group[0]['title'] ?? '—' }}</span>
            <span class="text-xs text-gray-400">{{ $group[0]['artist'] ?? '' }}</span>
            <span class="text-xs text-gray-500">— {{ $group[0]['album'] ?? '' }}</span>
            <span class="text-xs px-2 py-0.5 bg-red-900/50 text-red-300 rounded">{{ count($group) }} copies identiques</span>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-800">
                    <th class="px-4 py-2 w-8"></th>
                    <th class="px-4 py-2 text-left text-xs text-zinc-600">Album</th>
                    <th class="px-4 py-2 text-left text-xs text-zinc-600">Duree</th>
                    <th class="px-4 py-2 text-left text-xs text-zinc-600">Format</th>
                    <th class="px-4 py-2 text-left text-xs text-zinc-600">Bitrate</th>
                    <th class="px-4 py-2 text-left text-xs text-zinc-600">Taille</th>
                    <th class="px-4 py-2 text-left text-xs text-zinc-600">Chemin</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800/50">
            @foreach($group as $j => $s)
                <tr class="hover:bg-zinc-800/30 transition" data-group="{{ $i }}" data-bitrate="{{ $s['bitRate'] ?? 0 }}">
                    <td class="px-4 py-2">
                        <input type="checkbox" class="dup-check accent-red-500 rounded"
                           data-id="{{ $s['id'] }}"
                           data-path="{{ $s['path'] ?? '' }}"
                           data-title="{{ addslashes($s['title'] ?? '') }}">
                    </td>
                    <td class="px-4 py-2 text-zinc-400">{{ $s['album'] ?? '—' }}</td>
                    <td class="px-4 py-2 text-zinc-500">{{ gmdate('i:s', $s['duration'] ?? 0) }}</td>
                    <td class="px-4 py-2 text-zinc-500">{{ strtoupper($s['suffix'] ?? '—') }}</td>
                    <td class="px-4 py-2 text-zinc-500">{{ $s['bitRate'] ?? '—' }} kbps</td>
                    <td class="px-4 py-2 text-zinc-500">{{ number_format(($s['size'] ?? 0) / 1048576, 1) }} Mo</td>
                    <td class="px-4 py-2 text-zinc-600 text-xs max-w-xs truncate" title="{{ $s['path'] ?? '' }}">{{ $s['path'] ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endforeach
</form>

<script>
const checks   = document.querySelectorAll('.dup-check');
const countEl  = document.getElementById('selectedCount');
const deleteBtn = document.getElementById('deleteBtn');
const form     = document.getElementById('batchForm');

function updateCount() {
    const n = document.querySelectorAll('.dup-check:checked').length;
    countEl.textContent = n;
    deleteBtn.disabled = n === 0;
}
checks.forEach(c => c.addEventListener('change', updateCount));

// "Sélectionner les moins bons"
document.getElementById('selectLowerBtn').addEventListener('click', () => {
    const groups = {};
    document.querySelectorAll('tr[data-group]').forEach(tr => {
        const g = tr.dataset.group;
        if (!groups[g]) groups[g] = [];
        groups[g].push(tr);
    });
    Object.values(groups).forEach(rows => {
        let maxBr = 0;
        rows.forEach(r => { maxBr = Math.max(maxBr, parseInt(r.dataset.bitrate) || 0); });
        const best = rows.filter(r => parseInt(r.dataset.bitrate) === maxBr);
        rows.forEach(r => {
            const cb = r.querySelector('.dup-check');
            cb.checked = !best.includes(r) || (best.length === rows.length && rows.indexOf(r) > 0);
        });
    });
    updateCount();
});

// Suppression — injecte id/path/title des cases cochées sans refaire d'appels API
deleteBtn.addEventListener('click', () => {
    const checked = Array.from(document.querySelectorAll('.dup-check:checked'));
    if (!checked.length) return;
    if (!confirm('Supprimer définitivement les ' + checked.length + ' fichier(s) cochés ?')) return;

    // Spinner + désactivation
    document.getElementById('deleteBtnLabel').classList.add('opacity-0');
    document.getElementById('deleteBtnSpinner').classList.remove('hidden');
    deleteBtn.disabled = true;

    // Supprimer les éventuels champs précédents
    form.querySelectorAll('.submit-data').forEach(el => el.remove());

    checked.forEach(cb => {
        const add = (name, value) => {
            const inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = name; inp.value = value; inp.className = 'submit-data';
            form.appendChild(inp);
        };
        add('ids[]',    cb.dataset.id);
        add('paths[]',  cb.dataset.path);
        add('titles[]', cb.dataset.title);
    });

    form.submit();
});
</script>
@endif

@if($scanned && $mode === 'cross' && count($crossAlbum) > 0)
<div class="mb-4 p-3 bg-blue-900/20 border border-blue-800 rounded-lg text-sm text-blue-300">
    Ces chansons apparaissent sur plusieurs albums (compilations, best-of, remasters...). Ce ne sont generalement pas des doublons a supprimer.
</div>
<form method="POST" action="/admin/duplicates/batch-delete" id="batchFormCross">
    @csrf
    <div class="mb-4 flex items-center gap-3">
        <button type="submit" id="deleteBtnCross" class="px-4 py-2 bg-red-700 hover:bg-red-600 rounded-lg text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed" disabled onclick="return confirm('Attention : ces fichiers sont sur des albums differents. Supprimer definitivement les fichiers coches ?')">
            Supprimer la selection (<span id="selectedCountCross">0</span>)
        </button>
    </div>

    @foreach($crossAlbum as $i => $group)
    @php
        $albums = collect($group)->pluck('album')->unique()->filter()->values();
    @endphp
    <div class="bg-gray-800 border border-gray-700 rounded-lg mb-4 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-700 flex items-center gap-3 flex-wrap">
            <span class="text-sm font-semibold">{{ $group[0]['title'] ?? '—' }}</span>
            <span class="text-xs text-gray-400">{{ $group[0]['artist'] ?? '' }}</span>
            <span class="text-xs px-2 py-0.5 bg-blue-900/50 text-blue-300 rounded">{{ count($group) }} versions sur {{ $albums->count() }} album(s)</span>
        </div>
        <table class="w-full text-sm">
            <thead><tr class="text-left text-gray-500 text-xs">
                <th class="px-4 py-2 w-8"></th>
                <th class="px-4 py-2">Album</th>
                <th class="px-4 py-2">Duree</th>
                <th class="px-4 py-2">Format</th>
                <th class="px-4 py-2">Bitrate</th>
                <th class="px-4 py-2">Taille</th>
                <th class="px-4 py-2">Chemin</th>
            </tr></thead>
            <tbody>
            @foreach($group as $j => $s)
                <tr class="border-t border-gray-700/50 hover:bg-gray-700/30">
                    <td class="px-4 py-2">
                        <input type="checkbox" name="ids[]" value="{{ $s['id'] }}" class="cross-check accent-blue-500 rounded">
                    </td>
                    <td class="px-4 py-2 text-gray-300">{{ $s['album'] ?? '—' }}</td>
                    <td class="px-4 py-2 text-gray-400">{{ gmdate('i:s', $s['duration'] ?? 0) }}</td>
                    <td class="px-4 py-2 text-gray-400">{{ strtoupper($s['suffix'] ?? '—') }}</td>
                    <td class="px-4 py-2 text-gray-400">{{ $s['bitRate'] ?? '—' }} kbps</td>
                    <td class="px-4 py-2 text-gray-400">{{ number_format(($s['size'] ?? 0) / 1048576, 1) }} Mo</td>
                    <td class="px-4 py-2 text-gray-500 text-xs max-w-xs truncate" title="{{ $s['path'] ?? '' }}">{{ $s['path'] ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endforeach
</form>

<script>
const crossChecks = document.querySelectorAll('.cross-check');
const crossCountEl = document.getElementById('selectedCountCross');
const crossDeleteBtn = document.getElementById('deleteBtnCross');

function updateCrossCount() {
    const n = document.querySelectorAll('.cross-check:checked').length;
    crossCountEl.textContent = n;
    crossDeleteBtn.disabled = n === 0;
}
crossChecks.forEach(c => c.addEventListener('change', updateCrossCount));
</script>
@endif

@if(!$scanned)
<div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
    <h2 class="text-lg font-semibold mb-4">Comment ca fonctionne</h2>
    <div class="text-sm text-gray-300 space-y-3">
        <p><strong>Mode "Vrais doublons"</strong> — Detecte les fichiers identiques : meme titre, meme artiste ET meme album. Ce sont les copies a supprimer en priorite.</p>
        <p><strong>Mode "Multi-albums"</strong> — Montre les chansons presentes sur plusieurs albums (compilations, best-of, live, remasters). Utile pour nettoyer mais souvent legitime.</p>
        <p class="mt-4"><strong>1. Choisir le mode</strong> — Selectionnez le type de detection souhaite.</p>
        <p><strong>2. Scanner</strong> — Cliquez sur "Scanner la bibliotheque" pour analyser toutes les chansons.</p>
        <p><strong>3. Selectionner</strong> — Cochez les copies a supprimer, ou utilisez "Selectionner les moins bons" pour cocher automatiquement les fichiers de bitrate inferieur.</p>
        <p><strong>4. Supprimer</strong> — Cliquez sur "Supprimer la selection" pour tout supprimer en une fois.</p>
    </div>
</div>
@endif
@endsection
