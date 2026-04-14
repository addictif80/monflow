@extends('layouts.admin')
@section('title', 'Ticket #' . Str::limit($ticket->id, 8) . ' — Admin MonFlow')
@section('content')
<div class="mb-6"><a href="/admin/tickets" class="text-gray-400 hover:text-gray-200 text-sm">&larr; Retour aux tickets</a></div>
<div class="bg-gray-800 border border-gray-700 rounded-lg p-6 mb-6">
    <h1 class="text-2xl font-bold mb-3">{{ $ticket->subject }}</h1>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
        <div><div class="text-gray-400">Utilisateur</div><div>{{ $ticket->user?->username }}</div></div>
        <div><div class="text-gray-400">Catégorie</div><div>{{ $ticket->category }}</div></div>
        <div><div class="text-gray-400">Priorité</div><div>{{ $ticket->priority }}</div></div>
        <div><div class="text-gray-400">Statut</div><div>{{ $ticket->status }}</div></div>
    </div>
</div>

<div class="space-y-3 mb-6">
    @foreach($messages as $m)
        <div class="bg-gray-800 border {{ $m->is_staff_reply ? 'border-indigo-700' : 'border-gray-700' }} rounded-lg p-4">
            <div class="flex items-center justify-between mb-2 text-sm">
                <span class="font-medium {{ $m->is_staff_reply ? 'text-indigo-400' : '' }}">
                    {{ $m->author?->username ?? '—' }}
                    @if($m->is_staff_reply)<span class="ml-2 text-xs px-2 py-0.5 bg-indigo-900/50 text-indigo-300 rounded">Staff</span>@endif
                </span>
                <span class="text-gray-500">{{ $m->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="text-gray-300 whitespace-pre-wrap">{{ $m->body }}</div>
        </div>
    @endforeach
</div>

<div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
    <form method="POST" action="/admin/tickets/{{ $ticket->id }}">
        @csrf
        <div class="mb-4">
            <label class="block text-sm text-gray-400 mb-1">Réponse</label>
            <textarea name="body" rows="5" class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500"></textarea>
        </div>
        <div class="mb-4">
            <label class="block text-sm text-gray-400 mb-1">Statut</label>
            <select name="status" class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                @foreach(['open' => 'Ouvert', 'in_progress' => 'En cours', 'waiting_customer' => 'Attente client', 'resolved' => 'Résolu', 'closed' => 'Fermé'] as $k => $v)
                    <option value="{{ $k }}" {{ $ticket->status === $k ? 'selected' : '' }}>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-medium">Envoyer</button>
    </form>
</div>
@endsection
