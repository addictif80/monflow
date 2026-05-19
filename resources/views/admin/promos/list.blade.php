@extends('layouts.admin')

@section('title', 'Codes promo — Admin MonFlow')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-base font-semibold text-zinc-100">Codes promo</h1>
        <p class="text-sm text-zinc-500 mt-0.5">Gestion des codes promotionnels</p>
    </div>
    <a href="/admin/promos/create" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">+ Créer un code promo</a>
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-800">
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Code</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Valeur</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Utilisations</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Valide du</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Valide jusqu'au</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Statut</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800/50">
                @forelse($promos as $promo)
                    <tr class="hover:bg-zinc-800/30 transition">
                        <td class="px-4 py-3 font-mono font-medium text-zinc-200">{{ $promo->code }}</td>
                        <td class="px-4 py-3 text-zinc-500">{{ $promo->discount_type === 'percentage' ? 'Pourcentage' : 'Fixe' }}</td>
                        <td class="px-4 py-3 text-zinc-300">
                            @if($promo->discount_type === 'percentage')
                                {{ $promo->discount_value }}%
                            @else
                                {{ number_format($promo->discount_value, 2, ',', ' ') }} &euro;
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-500">{{ $promo->current_uses }} / {{ $promo->max_uses ?? '&infin;' }}</td>
                        <td class="px-4 py-3 text-zinc-500">{{ $promo->valid_from ? $promo->valid_from->format('d/m/Y') : '—' }}</td>
                        <td class="px-4 py-3 text-zinc-500">{{ $promo->valid_until ? $promo->valid_until->format('d/m/Y') : '—' }}</td>
                        <td class="px-4 py-3">
                            @if($promo->is_active)
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">active</span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-800 text-zinc-500 border border-zinc-700">inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <a href="/admin/promos/{{ $promo->id }}/edit" class="text-indigo-400 hover:text-indigo-300 text-xs">Modifier</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-6 text-center text-zinc-600">Aucun code promo.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
