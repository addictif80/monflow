@extends('layouts.app')
@section('title', 'Mes feedbacks — MonFlow')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-base font-semibold text-zinc-100">Mes feedbacks</h1>
        <p class="text-sm text-zinc-500 mt-0.5">Vos retours et suggestions</p>
    </div>
    <a href="/portal/feedback/create" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Nouveau feedback</a>
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-zinc-800">
                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Type</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Sujet</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Statut</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Date</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-800/50">
        @php
            $typeLabels = ['bug' => 'Bug', 'suggestion' => 'Suggestion', 'ui' => 'Interface', 'performance' => 'Performance', 'other' => 'Autre'];
            $statusLabels = ['new' => 'Nouveau', 'reviewed' => 'Examiné', 'in_progress' => 'En cours', 'resolved' => 'Résolu', 'dismissed' => 'Rejeté'];
        @endphp
        @forelse($feedbacks as $f)
            <tr class="hover:bg-zinc-800/30 transition">
                <td class="px-4 py-3 text-zinc-500">{{ $typeLabels[$f->type] ?? $f->type }}</td>
                <td class="px-4 py-3"><a href="/portal/feedback/{{ $f->id }}" class="text-indigo-400 hover:text-indigo-300">{{ $f->subject }}</a></td>
                <td class="px-4 py-3">
                    @php $s = $f->status; @endphp
                    @if($s === 'resolved')
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">{{ $statusLabels[$s] ?? $s }}</span>
                    @elseif($s === 'in_progress')
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">{{ $statusLabels[$s] ?? $s }}</span>
                    @elseif($s === 'reviewed')
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-500/10 text-yellow-400 border border-yellow-500/20">{{ $statusLabels[$s] ?? $s }}</span>
                    @elseif($s === 'new')
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">{{ $statusLabels[$s] ?? $s }}</span>
                    @else
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-800 text-zinc-500 border border-zinc-700">{{ $statusLabels[$s] ?? $s }}</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-zinc-500">{{ $f->created_at->format('d/m/Y H:i') }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="px-4 py-6 text-center text-zinc-600">Aucun feedback envoyé.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
