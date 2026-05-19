@extends('layouts.admin')
@section('title', 'Gestion des doublons — Admin MonFlow')
@section('content')
<div class="mb-6">
    <h1 class="text-base font-semibold text-zinc-100">Gestion des doublons</h1>
    <p class="text-sm text-zinc-500 mt-0.5">Détection et suppression des fichiers en double</p>
</div>

<div class="mb-6 flex items-center gap-4">
    <form method="GET">
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

@if($scanned && count($duplicates) > 0)
<form method="POST" action="/admin/duplicates/batch-delete" id="batchForm">
    @csrf
    <div class="mb-4 flex items-center gap-3">
        <button type="submit" id="deleteBtn" class="inline-flex items-center gap-2 bg-red-500/10 hover:bg-red-500/15 text-red-400 text-sm font-medium px-4 py-2 rounded-lg border border-red-500/20 transition disabled:opacity-40 disabled:cursor-not-allowed" disabled onclick="return confirm('Supprimer definitivement les fichiers coches ?')">
            Supprimer la selection (<span id="selectedCount">0</span>)
        </button>
        <button type="button" id="selectLowerBtn" class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-400 text-xs px-3 py-1.5 rounded-lg border border-zinc-700 transition">Selectionner les moins bons (bitrate inferieur)</button>
    </div>

    @foreach($duplicates as $i => $group)
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl mb-4 overflow-hidden">
        <div class="px-4 py-3 border-b border-zinc-800 flex items-center gap-3">
            <span class="text-sm font-semibold text-zinc-200">{{ $group[0]['title'] ?? '—' }}</span>
            <span class="text-xs text-zinc-500">{{ $group[0]['artist'] ?? '' }}</span>
            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-amber-500/10 text-amber-400 border border-amber-500/20">{{ count($group) }} copies</span>
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
                        <input type="checkbox" name="ids[]" value="{{ $s['id'] }}" class="dup-check accent-red-500 rounded">
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
const checks = document.querySelectorAll('.dup-check');
const countEl = document.getElementById('selectedCount');
const deleteBtn = document.getElementById('deleteBtn');

function updateCount() {
    const n = document.querySelectorAll('.dup-check:checked').length;
    countEl.textContent = n;
    deleteBtn.disabled = n === 0;
}
checks.forEach(c => c.addEventListener('change', updateCount));

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
</script>
@endif

@if(!$scanned)
<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
    <h2 class="text-sm font-medium text-zinc-300 mb-4">Comment ca fonctionne</h2>
    <div class="text-sm text-zinc-400 space-y-3">
        <p><strong class="text-zinc-300">1. Scanner</strong> — Cliquez sur "Scanner la bibliotheque" pour analyser toutes les chansons.</p>
        <p><strong class="text-zinc-300">2. Identifier</strong> — Les doublons sont detectes par correspondance exacte du titre et de l'artiste.</p>
        <p><strong class="text-zinc-300">3. Selectionner</strong> — Cochez les copies a supprimer, ou utilisez "Selectionner les moins bons" pour cocher automatiquement les fichiers de bitrate inferieur.</p>
        <p><strong class="text-zinc-300">4. Supprimer</strong> — Cliquez sur "Supprimer la selection" pour tout supprimer en une fois.</p>
    </div>
</div>
@endif
@endsection
