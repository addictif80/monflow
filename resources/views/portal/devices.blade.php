@extends('layouts.app')

@section('title', 'Mes appareils — MonFlow')

@section('content')
<div class="mb-6">
    <h1 class="text-base font-semibold text-zinc-100">Mes appareils</h1>
    <p class="text-sm text-zinc-500 mt-0.5">Gérez les appareils connectés à votre compte</p>
</div>

@if($devices->count())
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($devices as $device)
            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h3 class="font-medium text-zinc-100">{{ $device->device_name }}</h3>
                        <p class="text-xs text-zinc-500 mt-0.5">{{ $device->device_type }}</p>
                    </div>
                    <div class="text-zinc-600">
                        @if(strtolower($device->device_type) === 'mobile')
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        @elseif(strtolower($device->device_type) === 'tablet')
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        @else
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        @endif
                    </div>
                </div>
                <p class="text-xs text-zinc-600 mb-4">
                    Dernière activité : {{ $device->last_active_at ? \Carbon\Carbon::parse($device->last_active_at)->format('d/m/Y H:i') : 'Inconnue' }}
                </p>
                <form action="/portal/devices/{{ $device->id }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir révoquer cet appareil ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 bg-red-500/10 hover:bg-red-500/15 text-red-400 text-sm font-medium px-4 py-2 rounded-lg border border-red-500/20 transition">
                        Révoquer
                    </button>
                </form>
            </div>
        @endforeach
    </div>
@else
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-8 text-center">
        <svg class="w-10 h-10 text-zinc-700 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        <p class="text-sm text-zinc-600">Aucun appareil connecté.</p>
    </div>
@endif
@endsection
