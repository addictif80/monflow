@extends('layouts.admin')

@section('title', $title . ' — Admin MonFlow')

@section('content')
<div class="mb-6">
    <a href="/admin/plans" class="text-sm text-zinc-500 hover:text-zinc-300">&larr; Retour aux formules</a>
</div>

<div class="mb-6">
    <h1 class="text-base font-semibold text-zinc-100">{{ $title }}</h1>
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-6 max-w-2xl">
    <form method="POST" action="{{ $plan ? '/admin/plans/' . $plan->id . '/edit' : '/admin/plans/create' }}">
        @csrf

        {{-- Name --}}
        <div class="mb-4">
            <label for="name" class="block text-xs font-medium text-zinc-400 mb-1.5">Nom</label>
            <input type="text" id="name" name="name" value="{{ old('name', $plan->name ?? '') }}" required
                   class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
        </div>

        {{-- Description --}}
        <div class="mb-4">
            <label for="description" class="block text-xs font-medium text-zinc-400 mb-1.5">Description</label>
            <textarea id="description" name="description" rows="3"
                      class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">{{ old('description', $plan->description ?? '') }}</textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            {{-- Price --}}
            <div>
                <label for="price" class="block text-xs font-medium text-zinc-400 mb-1.5">Prix (&euro;)</label>
                <input type="number" id="price" name="price" step="0.01" min="0" value="{{ old('price', $plan->price ?? '') }}" required
                       class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
            </div>

            {{-- Billing Cycle --}}
            <div>
                <label for="billing_cycle" class="block text-xs font-medium text-zinc-400 mb-1.5">Cycle de facturation</label>
                <select id="billing_cycle" name="billing_cycle" required
                        class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
                    <option value="monthly" {{ old('billing_cycle', $plan->billing_cycle ?? '') === 'monthly' ? 'selected' : '' }}>Mensuel</option>
                    <option value="quarterly" {{ old('billing_cycle', $plan->billing_cycle ?? '') === 'quarterly' ? 'selected' : '' }}>Trimestriel</option>
                    <option value="yearly" {{ old('billing_cycle', $plan->billing_cycle ?? '') === 'yearly' ? 'selected' : '' }}>Annuel</option>
                </select>
            </div>

            {{-- Stripe Price ID --}}
            <div>
                <label for="stripe_price_id" class="block text-xs font-medium text-zinc-400 mb-1.5">Stripe Price ID</label>
                <input type="text" id="stripe_price_id" name="stripe_price_id" value="{{ old('stripe_price_id', $plan->stripe_price_id ?? '') }}" placeholder="price_1AbCdEf..."
                       class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 font-mono placeholder-zinc-600 px-3 py-2 outline-none transition">
                <p class="text-xs text-zinc-600 mt-1">Commence par <code class="text-indigo-400">price_</code> — pas <code>prod_</code>. Dans Stripe, ouvrez le Produit puis copiez l'ID de la ligne de tarification.</p>
            </div>

            {{-- Max Devices --}}
            <div>
                <label for="max_devices" class="block text-xs font-medium text-zinc-400 mb-1.5">Appareils max</label>
                <input type="number" id="max_devices" name="max_devices" min="1" value="{{ old('max_devices', $plan->max_devices ?? '') }}" required
                       class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
            </div>

            {{-- Sort Order --}}
            <div>
                <label for="sort_order" class="block text-xs font-medium text-zinc-400 mb-1.5">Ordre d'affichage</label>
                <input type="number" id="sort_order" name="sort_order" min="0" value="{{ old('sort_order', $plan->sort_order ?? 0) }}"
                       class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
            </div>
        </div>

        {{-- Is Active (edit only) --}}
        @if($plan)
            <div class="mb-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $plan->is_active) ? 'checked' : '' }}
                           class="w-4 h-4 rounded bg-zinc-900 border-zinc-700 text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-zinc-300">Active</span>
                </label>
            </div>
        @endif

        <div class="flex gap-3">
            <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                {{ $plan ? 'Mettre à jour' : 'Créer' }}
            </button>
            <a href="/admin/plans" class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-sm font-medium px-4 py-2 rounded-lg border border-zinc-700 transition">Annuler</a>
        </div>
    </form>
</div>
@endsection
