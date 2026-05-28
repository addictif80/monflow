@extends('layouts.admin')
@section('title', 'Logs serveur — Admin MonFlow')
@section('content')

<div class="mb-6 flex items-center justify-between gap-4 flex-wrap">
    <div>
        <h1 class="text-base font-semibold text-zinc-100">Logs serveur</h1>
        <p class="text-sm text-zinc-500 mt-0.5 font-mono text-xs">{{ $logFile }}</p>
    </div>
    <div class="flex items-center gap-2">
        <button id="copyBtn" onclick="copyLogs()"
            class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-sm font-medium px-4 py-2 rounded-lg border border-zinc-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            <span id="copyLabel">Copier</span>
        </button>
        <a href="{{ request()->fullUrlWithQuery(['_' => time()]) }}"
            class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Actualiser
        </a>
    </div>
</div>

{{-- Filtres --}}
<form method="GET" class="mb-4 flex items-center gap-3 flex-wrap">
    <input type="text" name="filter" value="{{ $filter }}" placeholder="Filtrer (ex: ERROR, QueryException…)"
        class="bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-sm text-zinc-200 placeholder-zinc-600 focus:outline-none focus:border-indigo-500 w-72">
    <select name="lines" class="bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-sm text-zinc-200 focus:outline-none focus:border-indigo-500">
        @foreach([50, 100, 200, 500, 1000] as $n)
            <option value="{{ $n }}" {{ $lines == $n ? 'selected' : '' }}>{{ $n }} entrées</option>
        @endforeach
    </select>
    <button class="bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-sm px-4 py-2 rounded-lg border border-zinc-700 transition">Filtrer</button>
    @if($filter)
        <a href="{{ route('admin') }}/logs?lines={{ $lines }}" class="text-xs text-zinc-500 hover:text-zinc-300">Effacer le filtre</a>
    @endif
</form>

{{-- Stats rapides --}}
@php
    $counts = ['emergency'=>0,'alert'=>0,'critical'=>0,'error'=>0,'warning'=>0,'notice'=>0,'info'=>0,'debug'=>0];
    foreach ($entries as $e) { if (isset($counts[$e['level']])) $counts[$e['level']]++; }
@endphp
<div class="mb-4 flex items-center gap-3 flex-wrap text-xs">
    @if($counts['error'] + $counts['critical'] + $counts['emergency'] + $counts['alert'] > 0)
    <span class="px-2 py-1 rounded-full bg-red-500/10 text-red-400 border border-red-500/20">
        {{ $counts['error'] + $counts['critical'] + $counts['emergency'] + $counts['alert'] }} erreur(s)
    </span>
    @endif
    @if($counts['warning'] > 0)
    <span class="px-2 py-1 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20">
        {{ $counts['warning'] }} avertissement(s)
    </span>
    @endif
    @if($counts['info'] + $counts['notice'] > 0)
    <span class="px-2 py-1 rounded-full bg-blue-500/10 text-blue-400 border border-blue-500/20">
        {{ $counts['info'] + $counts['notice'] }} info(s)
    </span>
    @endif
    <span class="text-zinc-600">{{ count($entries) }} entrée(s) affichée(s)</span>
</div>

@if(empty($entries))
<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-8 text-center text-zinc-500 text-sm">
    Aucune entrée de log{{ $filter ? ' correspondant au filtre' : '' }}.
</div>
@else

{{-- Log entries --}}
<div class="space-y-1 font-mono text-xs" id="logContainer">
    @foreach($entries as $entry)
    @php
        $level = $entry['level'];
        $isError = in_array($level, ['error','critical','emergency','alert']);
        $isWarn  = $level === 'warning';
        $isInfo  = in_array($level, ['info','notice']);
        $rowBg   = $isError ? 'bg-red-500/5 border-red-500/20' : ($isWarn ? 'bg-amber-500/5 border-amber-500/20' : 'bg-zinc-900 border-zinc-800');
        $badge   = $isError ? 'bg-red-500/15 text-red-400' : ($isWarn ? 'bg-amber-500/15 text-amber-400' : ($isInfo ? 'bg-blue-500/15 text-blue-400' : 'bg-zinc-700/50 text-zinc-500'));
    @endphp
    <div class="border rounded-lg overflow-hidden {{ $rowBg }}" data-raw="{{ htmlspecialchars($entry['raw']) }}">
        {{-- Header ligne --}}
        <div class="flex items-start gap-3 px-3 py-2 cursor-pointer select-none"
             onclick="toggleEntry(this)">
            <span class="shrink-0 text-zinc-600 w-36">{{ $entry['datetime'] }}</span>
            <span class="shrink-0 px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase {{ $badge }}">{{ $level }}</span>
            <span class="flex-1 text-zinc-300 truncate">{{ $entry['message'] }}</span>
            @if(trim($entry['context']))
            <svg class="w-3.5 h-3.5 shrink-0 text-zinc-600 chevron transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
            @endif
        </div>
        {{-- Contexte / stack trace (caché par défaut) --}}
        @if(trim($entry['context']))
        <div class="entry-body hidden px-3 pb-3 border-t border-zinc-800/50">
            <pre class="text-zinc-500 whitespace-pre-wrap break-all text-[11px] leading-relaxed mt-2 max-h-96 overflow-y-auto">{{ trim($entry['context']) }}</pre>
        </div>
        @endif
    </div>
    @endforeach
</div>
@endif

<script>
function toggleEntry(header) {
    const body = header.parentElement.querySelector('.entry-body');
    const chevron = header.querySelector('.chevron');
    if (!body) return;
    body.classList.toggle('hidden');
    if (chevron) chevron.style.transform = body.classList.contains('hidden') ? '' : 'rotate(180deg)';
}

function copyLogs() {
    const entries = document.querySelectorAll('#logContainer [data-raw]');
    const text = Array.from(entries).map(e => e.dataset.raw).reverse().join('\n\n');
    navigator.clipboard.writeText(text).then(() => {
        const label = document.getElementById('copyLabel');
        label.textContent = 'Copié !';
        setTimeout(() => label.textContent = 'Copier', 2000);
    });
}

// Expand automatiquement les erreurs
document.querySelectorAll('#logContainer .entry-body').forEach(body => {
    const badge = body.closest('[data-raw]')?.querySelector('.shrink-0.px-1\\.5');
    if (badge && (badge.textContent.trim().toLowerCase() === 'error' ||
                  badge.textContent.trim().toLowerCase() === 'critical')) {
        body.classList.remove('hidden');
        const chevron = body.previousElementSibling?.querySelector('.chevron');
        if (chevron) chevron.style.transform = 'rotate(180deg)';
    }
});
</script>

@endsection
