@extends('layouts.admin')

@section('title', 'Formules — Admin MonFlow')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-base font-semibold text-zinc-100">Formules</h1>
        <p class="text-sm text-zinc-500 mt-0.5">Gestion des offres d'abonnement</p>
    </div>
    <a href="/admin/plans/create" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">+ Créer une formule</a>
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-800">
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Nom</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Prix</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Cycle</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Appareils max</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Statut</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800/50">
                @forelse($plans as $plan)
                    <tr class="hover:bg-zinc-800/30 transition">
                        <td class="px-4 py-3 font-medium text-zinc-200">{{ $plan->name }}</td>
                        <td class="px-4 py-3 text-zinc-300">{{ number_format($plan->price, 2, ',', ' ') }} &euro;</td>
                        <td class="px-4 py-3 text-zinc-500">{{ $plan->billing_cycle }}</td>
                        <td class="px-4 py-3 text-zinc-500">{{ $plan->max_devices }}</td>
                        <td class="px-4 py-3">
                            @if($plan->is_active)
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">active</span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-800 text-zinc-500 border border-zinc-700">inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <a href="/admin/plans/{{ $plan->id }}/edit" class="text-indigo-400 hover:text-indigo-300 text-xs">Modifier</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-zinc-600">Aucune formule.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
