@extends('layouts.admin')

@section('title', $title . ' — Admin MonFlow')

@section('content')
<div class="mb-6">
    <a href="/admin/plans" class="text-gray-400 hover:text-gray-200 text-sm">&larr; Retour aux formules</a>
</div>

<h1 class="text-2xl font-bold mb-6">{{ $title }}</h1>

<div class="bg-gray-800 border border-gray-700 rounded-lg p-6 max-w-2xl">
    <form method="POST" action="{{ $plan ? '/admin/plans/' . $plan->id . '/edit' : '/admin/plans/create' }}">
        @csrf

        {{-- Name --}}
        <div class="mb-4">
            <label for="name" class="block text-sm text-gray-400 mb-1">Nom</label>
            <input type="text" id="name" name="name" value="{{ old('name', $plan->name ?? '') }}" required
                   class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
        </div>

        {{-- Description --}}
        <div class="mb-4">
            <label for="description" class="block text-sm text-gray-400 mb-1">Description</label>
            <textarea id="description" name="description" rows="3"
                      class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">{{ old('description', $plan->description ?? '') }}</textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            {{-- Price --}}
            <div>
                <label for="price" class="block text-sm text-gray-400 mb-1">Prix (&euro;)</label>
                <input type="number" id="price" name="price" step="0.01" min="0" value="{{ old('price', $plan->price ?? '') }}" required
                       class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
            </div>

            {{-- Billing Cycle --}}
            <div>
                <label for="billing_cycle" class="block text-sm text-gray-400 mb-1">Cycle de facturation</label>
                <select id="billing_cycle" name="billing_cycle" required
                        class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                    <option value="monthly" {{ old('billing_cycle', $plan->billing_cycle ?? '') === 'monthly' ? 'selected' : '' }}>Mensuel</option>
                    <option value="quarterly" {{ old('billing_cycle', $plan->billing_cycle ?? '') === 'quarterly' ? 'selected' : '' }}>Trimestriel</option>
                    <option value="yearly" {{ old('billing_cycle', $plan->billing_cycle ?? '') === 'yearly' ? 'selected' : '' }}>Annuel</option>
                </select>
            </div>

            {{-- Stripe Price ID --}}
            <div>
                <label for="stripe_price_id" class="block text-sm text-gray-400 mb-1">Stripe Price ID</label>
                <input type="text" id="stripe_price_id" name="stripe_price_id" value="{{ old('stripe_price_id', $plan->stripe_price_id ?? '') }}" placeholder="price_1AbCdEf..."
                       class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500 font-mono">
                <p class="text-xs text-gray-500 mt-1">Commence par <code class="text-indigo-400">price_</code> — pas <code>prod_</code>. Dans Stripe, ouvrez le Produit puis copiez l'ID de la ligne de tarification.</p>
            </div>

            {{-- Max Devices --}}
            <div>
                <label for="max_devices" class="block text-sm text-gray-400 mb-1">Appareils max</label>
                <input type="number" id="max_devices" name="max_devices" min="1" value="{{ old('max_devices', $plan->max_devices ?? '') }}" required
                       class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
            </div>

            {{-- Sort Order --}}
            <div>
                <label for="sort_order" class="block text-sm text-gray-400 mb-1">Ordre d'affichage</label>
                <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order', $plan->sort_order ?? 0) }}"
                       class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
            </div>
        </div>

        {{-- Is Active (edit only) --}}
        @if($plan)
            <div class="mb-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $plan->is_active) ? 'checked' : '' }}
                           class="w-4 h-4 rounded bg-gray-900 border-gray-700 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-gray-300">Active</span>
                </label>
            </div>
        @endif

        <div class="flex gap-3">
            <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-medium transition">
                {{ $plan ? 'Mettre à jour' : 'Créer' }}
            </button>
            <a href="/admin/plans" class="px-6 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm font-medium transition">Annuler</a>
        </div>
    </form>
</div>
@endsection
