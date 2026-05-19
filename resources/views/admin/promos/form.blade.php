@extends('layouts.admin')
@section('title', $title . ' — Admin MonFlow')
@section('content')
<div class="mb-6">
    <a href="/admin/promos" class="text-sm text-zinc-500 hover:text-zinc-300">&larr; Retour</a>
</div>

<div class="mb-6">
    <h1 class="text-base font-semibold text-zinc-100">{{ $title }}</h1>
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-6 max-w-2xl">
    <form method="POST" action="{{ $promo ? '/admin/promos/' . $promo->id . '/edit' : '/admin/promos/create' }}">
        @csrf
        <div class="mb-4">
            <label class="block text-xs font-medium text-zinc-400 mb-1.5">Code</label>
            <input name="code" value="{{ old('code', $promo->code ?? '') }}" required
                   class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 font-mono uppercase placeholder-zinc-600 px-3 py-2 outline-none transition">
        </div>
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Type de remise</label>
                <select name="discount_type" required class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
                    <option value="percentage" {{ old('discount_type', $promo->discount_type ?? '') === 'percentage' ? 'selected' : '' }}>Pourcentage (%)</option>
                    <option value="fixed" {{ old('discount_type', $promo->discount_type ?? '') === 'fixed' ? 'selected' : '' }}>Montant fixe (€)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Valeur</label>
                <input name="discount_value" type="number" step="0.01" min="0" value="{{ old('discount_value', $promo->discount_value ?? '') }}" required
                       class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Utilisations max (vide = illimité)</label>
                <input name="max_uses" type="number" min="0" value="{{ old('max_uses', $promo->max_uses ?? '') }}"
                       class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Valide du</label>
                <input name="valid_from" type="datetime-local" value="{{ old('valid_from', $promo ? $promo->valid_from?->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}" required
                       class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Valide jusqu'au</label>
                <input name="valid_until" type="datetime-local" value="{{ old('valid_until', $promo ? $promo->valid_until?->format('Y-m-d\TH:i') : '') }}"
                       class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
            </div>
        </div>
        <div class="mb-4 border-t border-zinc-800 pt-4">
            <label class="flex items-center gap-2 mb-3 cursor-pointer">
                <input type="checkbox" name="is_recurring" value="1" id="is_recurring" {{ old('is_recurring', $promo->is_recurring ?? false) ? 'checked' : '' }} class="w-4 h-4 rounded" onchange="document.getElementById('recurring_months_field').classList.toggle('hidden', !this.checked)">
                <span class="text-sm text-zinc-300">Code récurrent (s'applique sur plusieurs mois)</span>
            </label>
            <div id="recurring_months_field" class="{{ old('is_recurring', $promo->is_recurring ?? false) ? '' : 'hidden' }}">
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Nombre de mois de remise</label>
                <input name="recurring_months" type="number" min="1" max="24" value="{{ old('recurring_months', $promo->recurring_months ?? '') }}" placeholder="Ex: 3"
                       class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
                <p class="text-xs text-zinc-600 mt-1">La remise s'applique automatiquement pendant ce nombre de mois, puis le tarif normal reprend.</p>
            </div>
        </div>
        @if($promo)
            <div class="mb-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $promo->is_active) ? 'checked' : '' }} class="w-4 h-4 rounded">
                    <span class="text-sm text-zinc-300">Actif</span>
                </label>
            </div>
        @endif
        <div class="flex gap-3">
            <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">{{ $promo ? 'Mettre à jour' : 'Créer' }}</button>
            <a href="/admin/promos" class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-sm font-medium px-4 py-2 rounded-lg border border-zinc-700 transition">Annuler</a>
        </div>
    </form>
</div>
@endsection
