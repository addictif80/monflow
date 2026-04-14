@extends('layouts.app')
@section('title', $ticket->subject . ' — MonFlow')
@section('content')
<div class="mb-6"><a href="/support/tickets" class="text-gray-400 hover:text-gray-200 text-sm">&larr; Retour aux tickets</a></div>
<div class="bg-gray-800 border border-gray-700 rounded-lg p-6 mb-6">
    <h1 class="text-2xl font-bold mb-3">{{ $ticket->subject }}</h1>
    <div class="flex flex-wrap gap-4 text-sm">
        <div><span class="text-gray-400">Catégorie :</span> {{ $ticket->category }}</div>
        <div><span class="text-gray-400">Priorité :</span> {{ $ticket->priority }}</div>
        <div><span class="text-gray-400">Statut :</span>
            @php $sc = ['open' => 'blue', 'in_progress' => 'yellow', 'waiting_customer' => 'purple', 'resolved' => 'green', 'closed' => 'gray']; $sColor = $sc[$ticket->status] ?? 'gray'; @endphp
            <span class="px-2 py-0.5 text-xs rounded-full bg-{{ $sColor }}-900/50 text-{{ $sColor }}-400 border border-{{ $sColor }}-700">{{ $ticket->status }}</span>
        </div>
        <div><span class="text-gray-400">Créé :</span> {{ $ticket->created_at->format('d/m/Y H:i') }}</div>
    </div>
</div>

<div class="space-y-3 mb-6">
    @foreach($messages as $m)
        <div class="bg-gray-800 border {{ $m->is_staff_reply ? 'border-indigo-700' : 'border-gray-700' }} rounded-lg p-4">
            <div class="flex items-center justify-between mb-2 text-sm">
                <span class="font-medium {{ $m->is_staff_reply ? 'text-indigo-400' : '' }}">
                    {{ $m->author?->username ?? '—' }}
                    @if($m->is_staff_reply)<span class="ml-2 text-xs px-2 py-0.5 bg-indigo-900/50 text-indigo-300 rounded">Support</span>@endif
                </span>
                <span class="text-gray-500">{{ $m->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="text-gray-300 whitespace-pre-wrap">{{ $m->body }}</div>
        </div>
    @endforeach
</div>

@if(!in_array($ticket->status, ['resolved', 'closed']))
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        <form method="POST" action="/support/tickets/{{ $ticket->id }}">
            @csrf
            <label class="block text-sm text-gray-400 mb-1">Votre réponse</label>
            <textarea name="body" rows="4" required class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500 mb-4"></textarea>
            <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-medium">Envoyer</button>
        </form>
    </div>
@else
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-4 text-center text-gray-400 text-sm">Ce ticket est {{ $ticket->status === 'resolved' ? 'résolu' : 'fermé' }}. Créez un nouveau ticket si besoin.</div>
@endif
@endsection
