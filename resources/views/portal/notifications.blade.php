@extends('layouts.app')
@section('title', 'Notifications — MonFlow')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Notifications</h1>
    @if($notifications->where('read_at', null)->count() > 0)
        <form action="/portal/notifications/read" method="POST">@csrf
            <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm">Tout marquer comme lu</button>
        </form>
    @endif
</div>
<div class="space-y-2">
    @forelse($notifications as $n)
        <a href="{{ $n->link ?? '#' }}" class="block bg-gray-800 border rounded-lg p-4 hover:bg-gray-750 transition {{ $n->read_at ? 'border-gray-700 opacity-60' : 'border-indigo-500/50' }}">
            <div class="flex items-start justify-between">
                <div>
                    <p class="font-medium {{ $n->read_at ? 'text-gray-400' : 'text-gray-100' }}">{{ $n->title }}</p>
                    <p class="text-sm text-gray-400 mt-1">{{ $n->body }}</p>
                </div>
                <span class="text-xs text-gray-500 whitespace-nowrap ml-4">{{ $n->created_at->diffForHumans() }}</span>
            </div>
        </a>
    @empty
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-8 text-center text-gray-500">Aucune notification.</div>
    @endforelse
</div>
<div class="mt-4">{{ $notifications->links() }}</div>
@endsection
