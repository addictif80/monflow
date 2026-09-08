@extends('layouts.admin')
@section('title', 'Feedback — Admin MonFlow')
@section('content')
<div class="mb-6">
    <a href="/admin/feedbacks" class="text-sm text-zinc-500 hover:text-zinc-300">&larr; Retour aux feedbacks</a>
</div>

@php
    $typeLabels = ['bug' => 'Bug', 'suggestion' => 'Suggestion', 'ui' => 'Interface', 'performance' => 'Performance', 'other' => 'Autre'];
    $statusLabels = ['new' => 'Nouveau', 'reviewed' => 'Examiné', 'in_progress' => 'En cours', 'resolved' => 'Résolu', 'dismissed' => 'Rejeté'];
@endphp

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-base font-semibold text-zinc-100">{{ $feedback->subject }}</h1>
    </div>
    @if($feedback->status === 'new' || $feedback->status === 'in_progress')
        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">{{ $statusLabels[$feedback->status] ?? $feedback->status }}</span>
    @elseif($feedback->status === 'reviewed')
        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-500/10 text-yellow-400 border border-yellow-500/20">{{ $statusLabels[$feedback->status] ?? $feedback->status }}</span>
    @elseif($feedback->status === 'resolved')
        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">{{ $statusLabels[$feedback->status] ?? $feedback->status }}</span>
    @else
        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-800 text-zinc-500 border border-zinc-700">{{ $statusLabels[$feedback->status] ?? $feedback->status }}</span>
    @endif
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Content --}}
    <div class="space-y-4">
        <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
            <dl class="space-y-3 text-sm mb-4">
                <div class="flex justify-between"><dt class="text-zinc-500">Utilisateur</dt><dd><a href="/admin/users/{{ $feedback->user_id }}" class="text-indigo-400 hover:text-indigo-300">{{ $feedback->user?->username ?? '—' }}</a></dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">Type</dt><dd class="text-zinc-300">{{ $typeLabels[$feedback->type] ?? $feedback->type }}</dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">Date</dt><dd class="text-zinc-300">{{ $feedback->created_at->format('d/m/Y H:i') }}</dd></div>
                @if($feedback->ticket_id)
                    <div class="flex justify-between"><dt class="text-zinc-500">Ticket lié</dt><dd><a href="/admin/tickets/{{ $feedback->ticket_id }}" class="text-indigo-400 hover:text-indigo-300">Voir le ticket</a></dd></div>
                @endif
            </dl>
            <div class="border-t border-zinc-800 pt-4">
                <p class="text-xs text-zinc-500 mb-2">Description</p>
                <div class="text-sm text-zinc-400 leading-relaxed whitespace-pre-wrap">{{ $feedback->body }}</div>
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="space-y-4">
        {{-- Update status & note --}}
        <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
            <h3 class="text-sm font-medium text-zinc-300 mb-3">Traitement</h3>
            <form action="/admin/feedbacks/{{ $feedback->id }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-zinc-400 mb-1.5">Statut</label>
                    <select name="status" class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
                        @foreach($statusLabels as $val => $label)
                            <option value="{{ $val }}" {{ $feedback->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-400 mb-1.5">Note interne</label>
                    <textarea name="admin_note" rows="3" class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">{{ $feedback->admin_note }}</textarea>
                </div>
                <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Mettre à jour</button>
            </form>
        </div>

        {{-- Propagate to ticket --}}
        @if(!$feedback->ticket_id)
        <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
            <h3 class="text-sm font-medium text-zinc-300 mb-2">Propager au support</h3>
            <p class="text-xs text-zinc-500 mb-3">Crée un ticket de support au nom de l'utilisateur avec le contenu de ce feedback. L'utilisateur sera notifié.</p>
            <form action="/admin/feedbacks/{{ $feedback->id }}/to-ticket" method="POST" onsubmit="return confirm('Créer un ticket à partir de ce feedback ?')">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 bg-yellow-500/10 hover:bg-yellow-500/20 text-yellow-400 text-sm font-medium px-4 py-2 rounded-lg border border-yellow-500/20 transition">Propager au support</button>
            </form>
        </div>
        @else
        <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
            <h3 class="text-sm font-medium text-emerald-400 mb-2">Ticket lié</h3>
            <p class="text-xs text-zinc-500 mb-3">Ce feedback a déjà été propagé au support.</p>
            <a href="/admin/tickets/{{ $feedback->ticket_id }}" class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-sm font-medium px-4 py-2 rounded-lg border border-zinc-700 transition">Voir le ticket</a>
        </div>
        @endif
    </div>
</div>
@endsection
