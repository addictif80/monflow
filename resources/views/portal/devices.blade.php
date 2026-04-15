@extends('layouts.app')

@section('title', 'Mes appareils - MonFlow')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold">Mes appareils</h1>
    <p class="text-gray-400 mt-1">Gérez les appareils connectés à votre compte</p>
</div>

@if($devices->count())
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($devices as $device)
            <div class="bg-gray-800 rounded-lg border border-gray-700 p-5">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h3 class="font-semibold text-gray-100">{{ $device->device_name }}</h3>
                        <p class="text-sm text-gray-400">{{ $device->device_type }}</p>
                    </div>
                    <div class="text-gray-500">
                        @if(strtolower($device->device_type) === 'mobile')
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        @elseif(strtolower($device->device_type) === 'tablet')
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        @else
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        @endif
                    </div>
                </div>
                <p class="text-xs text-gray-500 mb-4">
                    Dernière activité : {{ $device->last_active_at ? \Carbon\Carbon::parse($device->last_active_at)->format('d/m/Y H:i') : 'Inconnue' }}
                </p>
                <form action="/portal/devices/{{ $device->id }}" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir révoquer cet appareil ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full px-3 py-2 bg-red-600/20 hover:bg-red-600/40 text-red-400 border border-red-700 rounded-lg text-sm font-medium transition">
                        Révoquer
                    </button>
                </form>
            </div>
        @endforeach
    </div>
@else
    <div class="bg-gray-800 rounded-lg border border-gray-700 p-8 text-center">
        <svg class="w-12 h-12 text-gray-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        <p class="text-gray-400">Aucun appareil connecté.</p>
    </div>
@endif
@endsection
