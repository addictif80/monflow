@extends('layouts.admin')

@section('title', 'Formules — Admin MonFlow')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Formules</h1>
    <a href="/admin/plans/create" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-medium transition">+ Créer une formule</a>
</div>

<div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="border-b border-gray-700 text-left text-gray-400">
                    <th class="px-4 py-3">Nom</th>
                    <th class="px-4 py-3">Prix</th>
                    <th class="px-4 py-3">Cycle</th>
                    <th class="px-4 py-3">Appareils max</th>
                    <th class="px-4 py-3">Statut</th>
                    <th class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($plans as $plan)
                    <tr class="border-b border-gray-700/50 hover:bg-gray-700">
                        <td class="px-4 py-3 font-medium">{{ $plan->name }}</td>
                        <td class="px-4 py-3">{{ number_format($plan->price, 2, ',', ' ') }} &euro;</td>
                        <td class="px-4 py-3 text-gray-400">{{ $plan->billing_cycle }}</td>
                        <td class="px-4 py-3 text-gray-400">{{ $plan->max_devices }}</td>
                        <td class="px-4 py-3">
                            @if($plan->is_active)
                                <span class="px-2 py-0.5 text-xs rounded-full bg-green-900/50 text-green-400 border border-green-700">active</span>
                            @else
                                <span class="px-2 py-0.5 text-xs rounded-full bg-gray-700 text-gray-400 border border-gray-600">inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <a href="/admin/plans/{{ $plan->id }}/edit" class="text-indigo-400 hover:text-indigo-300">Modifier</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">Aucune formule.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
