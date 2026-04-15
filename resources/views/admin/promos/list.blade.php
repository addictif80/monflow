@extends('layouts.admin')

@section('title', 'Codes promo — Admin MonFlow')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Codes promo</h1>
    <a href="/admin/promos/create" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-medium transition">+ Créer un code promo</a>
</div>

<div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="border-b border-gray-700 text-left text-gray-400">
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Valeur</th>
                    <th class="px-4 py-3">Utilisations</th>
                    <th class="px-4 py-3">Valide du</th>
                    <th class="px-4 py-3">Valide jusqu'au</th>
                    <th class="px-4 py-3">Statut</th>
                    <th class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($promos as $promo)
                    <tr class="border-b border-gray-700/50 hover:bg-gray-700">
                        <td class="px-4 py-3 font-mono font-medium">{{ $promo->code }}</td>
                        <td class="px-4 py-3 text-gray-400">{{ $promo->discount_type === 'percentage' ? 'Pourcentage' : 'Fixe' }}</td>
                        <td class="px-4 py-3">
                            @if($promo->discount_type === 'percentage')
                                {{ $promo->discount_value }}%
                            @else
                                {{ number_format($promo->discount_value, 2, ',', ' ') }} &euro;
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-400">{{ $promo->current_uses }} / {{ $promo->max_uses ?? '&infin;' }}</td>
                        <td class="px-4 py-3 text-gray-400">{{ $promo->valid_from ? $promo->valid_from->format('d/m/Y') : '—' }}</td>
                        <td class="px-4 py-3 text-gray-400">{{ $promo->valid_until ? $promo->valid_until->format('d/m/Y') : '—' }}</td>
                        <td class="px-4 py-3">
                            @if($promo->is_active)
                                <span class="px-2 py-0.5 text-xs rounded-full bg-green-900/50 text-green-400 border border-green-700">active</span>
                            @else
                                <span class="px-2 py-0.5 text-xs rounded-full bg-gray-700 text-gray-400 border border-gray-600">inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <a href="/admin/promos/{{ $promo->id }}/edit" class="text-indigo-400 hover:text-indigo-300">Modifier</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-6 text-center text-gray-500">Aucun code promo.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
