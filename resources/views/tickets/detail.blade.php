@extends('layouts.app')
@section('title', $ticket->subject . ' — MonFlow')
@section('content')
<div class="mb-4"><a href="/support/tickets" class="text-sm text-zinc-500 hover:text-zinc-300">&larr; Retour aux tickets</a></div>
<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 mb-4">
    <h1 class="text-base font-semibold text-zinc-100 mb-3">{{ $ticket->subject }}</h1>
    <div class="flex flex-wrap gap-4 text-sm">
        <div><span class="text-zinc-600">Catégorie :</span> <span class="text-zinc-400">{{ $ticket->category }}</span></div>
        <div><span class="text-zinc-600">Priorité :</span> <span class="text-zinc-400">{{ $ticket->priority }}</span></div>
        <div><span class="text-zinc-600">Statut :</span>
            @php $sc = ['open' => 'indigo', 'in_progress' => 'yellow', 'waiting_customer' => 'yellow', 'resolved' => 'emerald', 'closed' => 'zinc']; $sColor = $sc[$ticket->status] ?? 'zinc'; @endphp
            @if($sColor === 'emerald')
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">{{ $ticket->status }}</span>
            @elseif($sColor === 'yellow')
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-500/10 text-yellow-400 border border-yellow-500/20">{{ $ticket->status }}</span>
            @elseif($sColor === 'indigo')
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">{{ $ticket->status }}</span>
            @else
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-800 text-zinc-500 border border-zinc-700">{{ $ticket->status }}</span>
            @endif
        </div>
        <div><span class="text-zinc-600">Créé :</span> <span class="text-zinc-400">{{ $ticket->created_at->format('d/m/Y H:i') }}</span></div>
    </div>
</div>

<div class="space-y-3 mb-4">
    @foreach($messages as $m)
        <div class="bg-zinc-900 border {{ $m->is_staff_reply ? 'border-indigo-500/30' : 'border-zinc-800' }} rounded-xl p-4">
            <div class="flex items-center justify-between mb-2 text-sm">
                <span class="font-medium {{ $m->is_staff_reply ? 'text-indigo-400' : 'text-zinc-300' }}">
                    {{ $m->author?->username ?? '—' }}
                    @if($m->is_staff_reply)<span class="ml-2 inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">Support</span>@endif
                </span>
                <span class="text-zinc-600">{{ $m->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="text-sm text-zinc-400 whitespace-pre-wrap">{{ $m->body }}</div>
        </div>
    @endforeach
</div>

@if(!in_array($ticket->status, ['resolved', 'closed']))
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
        <form method="POST" action="/support/tickets/{{ $ticket->id }}">
            @csrf
            <label class="block text-xs font-medium text-zinc-400 mb-1.5">Votre réponse</label>
            <textarea name="body" rows="4" required class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition mb-4"></textarea>
            <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Envoyer</button>
        </form>
    </div>
@else
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4 text-center text-sm text-zinc-600">Ce ticket est {{ $ticket->status === 'resolved' ? 'résolu' : 'fermé' }}. Créez un nouveau ticket si besoin.</div>
@endif
@endsection
