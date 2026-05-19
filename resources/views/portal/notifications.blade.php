@extends('layouts.app')
@section('title', 'Notifications — MonFlow')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-base font-semibold text-zinc-100">Notifications</h1>
        <p class="text-sm text-zinc-500 mt-0.5">Vos dernières notifications</p>
    </div>
    @if($notifications->where('read_at', null)->count() > 0)
        <form action="/portal/notifications/read" method="POST">@csrf
            <button class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-sm font-medium px-4 py-2 rounded-lg border border-zinc-700 transition">Tout marquer comme lu</button>
        </form>
    @endif
</div>
<div class="space-y-2">
    @forelse($notifications as $n)
        <a href="{{ $n->link ?? '#' }}" class="block bg-zinc-900 border rounded-xl p-4 hover:bg-zinc-800/50 transition {{ $n->read_at ? 'border-zinc-800 opacity-60' : 'border-indigo-500/30' }}">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium {{ $n->read_at ? 'text-zinc-500' : 'text-zinc-100' }}">{{ $n->title }}</p>
                    <p class="text-xs text-zinc-500 mt-1">{{ $n->body }}</p>
                </div>
                <span class="text-xs text-zinc-600 whitespace-nowrap ml-4">{{ $n->created_at->diffForHumans() }}</span>
            </div>
        </a>
    @empty
        <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-8 text-center text-sm text-zinc-600">Aucune notification.</div>
    @endforelse
</div>
<div class="mt-4">{{ $notifications->links() }}</div>
@endsection
