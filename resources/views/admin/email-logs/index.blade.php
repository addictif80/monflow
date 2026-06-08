@extends('layouts.admin')
@section('title', 'Journal des mails — Admin MonFlow')
@section('content')

<div class="mb-5 flex items-center justify-between gap-4 flex-wrap">
    <div>
        <h1 class="text-base font-semibold text-zinc-100">Journal des mails</h1>
        <p class="text-sm text-zinc-500 mt-0.5">{{ number_format($logs->total()) }} mail(s) enregistré(s)</p>
    </div>
</div>

<form method="GET" class="mb-4 flex items-center gap-3 flex-wrap">
    <input name="q" value="{{ $q }}" placeholder="Destinataire, sujet…"
           class="bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-sm text-zinc-200 placeholder-zinc-600 focus:outline-none focus:border-indigo-500 w-64">

    <select name="status" class="bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-sm text-zinc-200 focus:outline-none focus:border-indigo-500">
        <option value="">Tous les statuts</option>
        <option value="sent"   {{ $status === 'sent'   ? 'selected' : '' }}>Envoyé</option>
        <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>Échec</option>
    </select>

    <select name="type" class="bg-zinc-900 border border-zinc-700 rounded-lg px-3 py-2 text-sm text-zinc-200 focus:outline-none focus:border-indigo-500">
        <option value="">Tous les types</option>
        @foreach($types as $t)
            <option value="{{ $t }}" {{ $type === $t ? 'selected' : '' }}>{{ $t }}</option>
        @endforeach
    </select>

    <button class="bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-sm px-4 py-2 rounded-lg border border-zinc-700 transition">Filtrer</button>
    @if($q || $status || $type)
        <a href="/admin/email-logs" class="text-xs text-zinc-500 hover:text-zinc-300">Effacer</a>
    @endif
</form>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-zinc-800">
                <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-zinc-500 uppercase tracking-wider">Date</th>
                <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-zinc-500 uppercase tracking-wider">Destinataire</th>
                <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-zinc-500 uppercase tracking-wider">Sujet</th>
                <th class="px-4 py-2.5 text-left text-[10px] font-semibold text-zinc-500 uppercase tracking-wider hidden md:table-cell">Type</th>
                <th class="px-4 py-2.5 text-center text-[10px] font-semibold text-zinc-500 uppercase tracking-wider w-20">Statut</th>
                <th class="px-4 py-2.5 w-20"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-800/50">
        @forelse($logs as $log)
            <tr class="hover:bg-zinc-800/20 transition">
                <td class="px-4 py-2.5 text-zinc-500 text-xs whitespace-nowrap">
                    {{ $log->created_at->format('d/m/Y') }}<br>
                    <span class="text-zinc-600">{{ $log->created_at->format('H:i:s') }}</span>
                </td>
                <td class="px-4 py-2.5 text-zinc-300 text-xs truncate max-w-[180px]">{{ $log->to }}</td>
                <td class="px-4 py-2.5 text-zinc-400 text-xs truncate max-w-[280px]">{{ $log->subject }}</td>
                <td class="px-4 py-2.5 hidden md:table-cell">
                    @if($log->template_type)
                        <span class="inline-block text-[10px] bg-zinc-800 text-zinc-400 border border-zinc-700 px-2 py-0.5 rounded-full font-mono">{{ $log->template_type }}</span>
                    @else
                        <span class="text-zinc-600 text-xs">—</span>
                    @endif
                </td>
                <td class="px-4 py-2.5 text-center">
                    @if($log->status === 'sent')
                        <span class="inline-flex items-center gap-1 text-[10px] font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-2 py-0.5 rounded-full">
                            <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
                            Envoyé
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 text-[10px] font-medium bg-red-500/10 text-red-400 border border-red-500/20 px-2 py-0.5 rounded-full" title="{{ $log->error }}">
                            <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                            Échec
                        </span>
                    @endif
                </td>
                <td class="px-4 py-2.5 text-right">
                    <button type="button"
                            onclick="openPreview({{ $log->id }}, {{ json_encode($log->to) }}, {{ json_encode($log->subject) }})"
                            class="text-xs text-indigo-400 hover:text-indigo-300 transition">Aperçu</button>
                </td>
            </tr>
            @if($log->status === 'failed' && $log->error)
            <tr class="bg-red-500/5">
                <td colspan="6" class="px-4 py-1.5">
                    <span class="text-[10px] text-red-400 font-mono">{{ $log->error }}</span>
                </td>
            </tr>
            @endif
        @empty
            <tr><td colspan="6" class="px-4 py-8 text-center text-zinc-600">Aucun mail enregistré.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

@if($logs->lastPage() > 1)
<div class="mt-4 flex items-center justify-center gap-2 text-sm flex-wrap">
    @if($logs->currentPage() > 1)
        <a href="{{ $logs->previousPageUrl() }}"
           class="px-3 py-1.5 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 rounded-lg border border-zinc-700 transition">← Précédent</a>
    @endif
    @php $rangeStart = max(1, $logs->currentPage() - 2); $rangeEnd = min($logs->lastPage(), $logs->currentPage() + 2); @endphp
    @for($p = $rangeStart; $p <= $rangeEnd; $p++)
        <a href="{{ $logs->url($p) }}"
           class="px-3 py-1.5 rounded-lg border transition {{ $p === $logs->currentPage() ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-zinc-800 hover:bg-zinc-700 text-zinc-400 border-zinc-700' }}">{{ $p }}</a>
    @endfor
    @if($logs->currentPage() < $logs->lastPage())
        <a href="{{ $logs->nextPageUrl() }}"
           class="px-3 py-1.5 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 rounded-lg border border-zinc-700 transition">Suivant →</a>
    @endif
</div>
@endif

{{-- Preview modal --}}
<div id="previewModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.8)">
    <div class="bg-zinc-900 border border-zinc-700 rounded-2xl w-full max-w-3xl shadow-2xl flex flex-col" style="max-height:90vh">
        <div class="flex items-start justify-between gap-4 px-5 py-4 border-b border-zinc-800 flex-shrink-0">
            <div class="min-w-0">
                <div id="previewTo" class="text-xs text-zinc-500 truncate"></div>
                <div id="previewSubject" class="text-sm font-medium text-zinc-200 truncate mt-0.5"></div>
            </div>
            <button onclick="closePreview()" class="text-zinc-500 hover:text-zinc-300 transition text-xl leading-none flex-shrink-0">✕</button>
        </div>
        <div class="flex-1 overflow-hidden rounded-b-2xl">
            <iframe id="previewFrame" src="about:blank"
                    class="w-full h-full border-0 bg-white rounded-b-2xl"
                    style="min-height:500px"></iframe>
        </div>
    </div>
</div>

<script>
function openPreview(id, to, subject) {
    document.getElementById('previewTo').textContent = 'À : ' + to;
    document.getElementById('previewSubject').textContent = subject;
    document.getElementById('previewFrame').src = '/admin/email-logs/' + id + '/preview';
    document.getElementById('previewModal').classList.remove('hidden');
}

function closePreview() {
    document.getElementById('previewModal').classList.add('hidden');
    document.getElementById('previewFrame').src = 'about:blank';
}

document.getElementById('previewModal').addEventListener('click', function(e) {
    if (e.target === this) closePreview();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closePreview();
});
</script>

@endsection
