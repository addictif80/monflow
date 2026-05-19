@extends('layouts.app')
@section('title', $feedback->subject . ' — Feedback — MonFlow')
@section('content')
<div class="mb-4">
    <a href="/portal/feedback" class="text-sm text-zinc-500 hover:text-zinc-300">&larr; Retour aux feedbacks</a>
</div>

@php
    $typeLabels = ['bug' => 'Bug', 'suggestion' => 'Suggestion', 'ui' => 'Interface', 'performance' => 'Performance', 'other' => 'Autre'];
    $statusLabels = ['new' => 'Nouveau', 'reviewed' => 'Examiné', 'in_progress' => 'En cours', 'resolved' => 'Résolu', 'dismissed' => 'Rejeté'];
    $statusColors = ['new' => 'indigo', 'reviewed' => 'yellow', 'in_progress' => 'indigo', 'resolved' => 'emerald', 'dismissed' => 'zinc'];
    $sc = $statusColors[$feedback->status] ?? 'zinc';
@endphp

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-base font-semibold text-zinc-100">{{ $feedback->subject }}</h1>
        <p class="text-sm text-zinc-500 mt-0.5">{{ $typeLabels[$feedback->type] ?? $feedback->type }}</p>
    </div>
    @if($sc === 'emerald')
        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">{{ $statusLabels[$feedback->status] ?? $feedback->status }}</span>
    @elseif($sc === 'yellow')
        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-500/10 text-yellow-400 border border-yellow-500/20">{{ $statusLabels[$feedback->status] ?? $feedback->status }}</span>
    @elseif($sc === 'indigo')
        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">{{ $statusLabels[$feedback->status] ?? $feedback->status }}</span>
    @else
        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-800 text-zinc-500 border border-zinc-700">{{ $statusLabels[$feedback->status] ?? $feedback->status }}</span>
    @endif
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-6 max-w-2xl space-y-4">
    <div class="flex gap-4 text-sm text-zinc-500">
        <span>Type : <strong class="text-zinc-300">{{ $typeLabels[$feedback->type] ?? $feedback->type }}</strong></span>
        <span>Envoyé le : <strong class="text-zinc-300">{{ $feedback->created_at->format('d/m/Y H:i') }}</strong></span>
    </div>
    <div class="text-sm text-zinc-300 leading-relaxed whitespace-pre-wrap">{{ $feedback->body }}</div>

    @if($feedback->admin_note)
        <div class="mt-4 p-4 bg-zinc-800 border border-zinc-700 rounded-xl">
            <p class="text-xs text-zinc-500 mb-1">Note de l'administration</p>
            <p class="text-sm text-zinc-300">{{ $feedback->admin_note }}</p>
        </div>
    @endif

    @if($feedback->ticket_id)
        <div class="mt-4 p-3 bg-indigo-500/10 border border-indigo-500/20 rounded-lg text-sm text-indigo-400">
            Un ticket de support a été ouvert pour ce feedback.
            <a href="/support/tickets/{{ $feedback->ticket_id }}" class="text-indigo-300 hover:text-indigo-200 ml-1 underline">Voir le ticket</a>
        </div>
    @endif
</div>
@endsection
