@extends('layouts.admin')
@section('title', 'Ticket #' . Str::limit($ticket->id, 8) . ' — Admin MonFlow')
@section('content')
<div class="mb-6">
    <a href="/admin/tickets" class="text-sm text-zinc-500 hover:text-zinc-300">&larr; Retour aux tickets</a>
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 mb-6">
    <h1 class="text-base font-semibold text-zinc-100 mb-3">{{ $ticket->subject }}</h1>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
        <div><div class="text-xs text-zinc-500">Utilisateur</div><div class="text-zinc-300 mt-0.5">{{ $ticket->user?->username }}</div></div>
        <div><div class="text-xs text-zinc-500">Catégorie</div><div class="text-zinc-300 mt-0.5">{{ $ticket->category }}</div></div>
        <div><div class="text-xs text-zinc-500">Priorité</div><div class="text-zinc-300 mt-0.5">{{ $ticket->priority }}</div></div>
        <div><div class="text-xs text-zinc-500">Statut</div><div class="text-zinc-300 mt-0.5">{{ $ticket->status }}</div></div>
    </div>
</div>

<div class="space-y-3 mb-6">
    @foreach($messages as $m)
        <div class="bg-zinc-900 border {{ $m->is_staff_reply ? 'border-indigo-500/30' : 'border-zinc-800' }} rounded-xl p-4">
            <div class="flex items-center justify-between mb-2 text-sm">
                <span class="font-medium {{ $m->is_staff_reply ? 'text-indigo-400' : 'text-zinc-300' }}">
                    {{ $m->author?->username ?? '—' }}
                    @if($m->is_staff_reply)<span class="ml-2 inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">Staff</span>@endif
                </span>
                <span class="text-zinc-600">{{ $m->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="text-sm text-zinc-400 whitespace-pre-wrap">{{ $m->body }}</div>
        </div>
    @endforeach
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
    <form method="POST" action="/admin/tickets/{{ $ticket->id }}">
        @csrf
        <div class="mb-4">
            <label class="block text-xs font-medium text-zinc-400 mb-1.5">Réponse</label>
            <textarea name="body" rows="5" class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition"></textarea>
        </div>
        <div class="mb-4">
            <label class="block text-xs font-medium text-zinc-400 mb-1.5">Statut</label>
            <select name="status" class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
                @foreach(['open' => 'Ouvert', 'in_progress' => 'En cours', 'waiting_customer' => 'Attente client', 'resolved' => 'Résolu', 'closed' => 'Fermé'] as $k => $v)
                    <option value="{{ $k }}" {{ $ticket->status === $k ? 'selected' : '' }}>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Envoyer</button>
    </form>
</div>
@endsection
