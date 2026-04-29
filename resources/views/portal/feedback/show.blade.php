@extends('layouts.app')
@section('title', $feedback->subject . ' — Feedback — MonFlow')
@section('content')
<div class="mb-6">
    <a href="/portal/feedback" class="text-gray-400 hover:text-gray-200 text-sm">&larr; Retour aux feedbacks</a>
</div>

@php
    $typeLabels = ['bug' => 'Bug', 'suggestion' => 'Suggestion', 'ui' => 'Interface', 'performance' => 'Performance', 'other' => 'Autre'];
    $statusLabels = ['new' => 'Nouveau', 'reviewed' => 'Examiné', 'in_progress' => 'En cours', 'resolved' => 'Résolu', 'dismissed' => 'Rejeté'];
    $statusColors = ['new' => 'blue', 'reviewed' => 'yellow', 'in_progress' => 'indigo', 'resolved' => 'green', 'dismissed' => 'gray'];
    $sc = $statusColors[$feedback->status] ?? 'gray';
@endphp

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">{{ $feedback->subject }}</h1>
    <span class="px-3 py-1 text-sm rounded-full bg-{{ $sc }}-900/50 text-{{ $sc }}-400 border border-{{ $sc }}-700">{{ $statusLabels[$feedback->status] ?? $feedback->status }}</span>
</div>

<div class="bg-gray-800 border border-gray-700 rounded-lg p-6 max-w-2xl space-y-4">
    <div class="flex gap-4 text-sm text-gray-400">
        <span>Type : <strong class="text-gray-200">{{ $typeLabels[$feedback->type] ?? $feedback->type }}</strong></span>
        <span>Envoyé le : <strong class="text-gray-200">{{ $feedback->created_at->format('d/m/Y H:i') }}</strong></span>
    </div>
    <div class="text-sm leading-relaxed whitespace-pre-wrap">{{ $feedback->body }}</div>

    @if($feedback->admin_note)
        <div class="mt-4 p-4 bg-gray-700/50 border border-gray-600 rounded-lg">
            <p class="text-xs text-gray-400 mb-1">Note de l'administration</p>
            <p class="text-sm">{{ $feedback->admin_note }}</p>
        </div>
    @endif

    @if($feedback->ticket_id)
        <div class="mt-4 p-3 bg-indigo-900/30 border border-indigo-700 rounded-lg text-sm">
            Un ticket de support a été ouvert pour ce feedback.
            <a href="/support/tickets/{{ $feedback->ticket_id }}" class="text-indigo-400 hover:text-indigo-300 ml-1">Voir le ticket</a>
        </div>
    @endif
</div>
@endsection
