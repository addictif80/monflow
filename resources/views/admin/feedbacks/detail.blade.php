@extends('layouts.admin')
@section('title', 'Feedback — Admin MonFlow')
@section('content')
<div class="mb-6">
    <a href="/admin/feedbacks" class="text-gray-400 hover:text-gray-200 text-sm">&larr; Retour aux feedbacks</a>
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

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Content --}}
    <div class="space-y-4">
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <dl class="space-y-3 text-sm mb-4">
                <div class="flex justify-between"><dt class="text-gray-400">Utilisateur</dt><dd><a href="/admin/users/{{ $feedback->user_id }}" class="text-indigo-400 hover:text-indigo-300">{{ $feedback->user?->username ?? '—' }}</a></dd></div>
                <div class="flex justify-between"><dt class="text-gray-400">Type</dt><dd>{{ $typeLabels[$feedback->type] ?? $feedback->type }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-400">Date</dt><dd>{{ $feedback->created_at->format('d/m/Y H:i') }}</dd></div>
                @if($feedback->ticket_id)
                    <div class="flex justify-between"><dt class="text-gray-400">Ticket lié</dt><dd><a href="/admin/tickets/{{ $feedback->ticket_id }}" class="text-indigo-400 hover:text-indigo-300">Voir le ticket</a></dd></div>
                @endif
            </dl>
            <div class="border-t border-gray-700 pt-4">
                <p class="text-xs text-gray-400 mb-2">Description</p>
                <div class="text-sm leading-relaxed whitespace-pre-wrap">{{ $feedback->body }}</div>
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="space-y-4">
        {{-- Update status & note --}}
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <h3 class="font-semibold mb-3">Traitement</h3>
            <form action="/admin/feedbacks/{{ $feedback->id }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Statut</label>
                    <select name="status" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-sm text-gray-100">
                        @foreach($statusLabels as $val => $label)
                            <option value="{{ $val }}" {{ $feedback->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Note interne</label>
                    <textarea name="admin_note" rows="3" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-sm text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">{{ $feedback->admin_note }}</textarea>
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-sm font-medium transition">Mettre à jour</button>
            </form>
        </div>

        {{-- Propagate to ticket --}}
        @if(!$feedback->ticket_id)
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <h3 class="font-semibold mb-3">Propager au support</h3>
            <p class="text-sm text-gray-400 mb-3">Crée un ticket de support au nom de l'utilisateur avec le contenu de ce feedback. L'utilisateur sera notifié.</p>
            <form action="/admin/feedbacks/{{ $feedback->id }}/to-ticket" method="POST" onsubmit="return confirm('Créer un ticket à partir de ce feedback ?')">
                @csrf
                <button type="submit" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-500 text-white rounded-lg text-sm font-medium transition">Propager au support</button>
            </form>
        </div>
        @else
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <h3 class="font-semibold mb-3 text-green-400">Ticket lié</h3>
            <p class="text-sm text-gray-400 mb-3">Ce feedback a déjà été propagé au support.</p>
            <a href="/admin/tickets/{{ $feedback->ticket_id }}" class="px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg text-sm font-medium transition inline-block">Voir le ticket</a>
        </div>
        @endif
    </div>
</div>
@endsection
