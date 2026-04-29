@extends('layouts.admin')
@section('title', $title . ' — Admin MonFlow')
@section('content')
<div class="mb-6"><a href="/admin/promos" class="text-gray-400 hover:text-gray-200 text-sm">&larr; Retour</a></div>
<h1 class="text-2xl font-bold mb-6">{{ $title }}</h1>
<div class="bg-gray-800 border border-gray-700 rounded-lg p-6 max-w-2xl">
    <form method="POST" action="{{ $promo ? '/admin/promos/' . $promo->id . '/edit' : '/admin/promos/create' }}">
        @csrf
        <div class="mb-4">
            <label class="block text-sm text-gray-400 mb-1">Code</label>
            <input name="code" value="{{ old('code', $promo->code ?? '') }}" required class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm font-mono uppercase focus:outline-none focus:border-indigo-500">
        </div>
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm text-gray-400 mb-1">Type de remise</label>
                <select name="discount_type" required class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                    <option value="percentage" {{ old('discount_type', $promo->discount_type ?? '') === 'percentage' ? 'selected' : '' }}>Pourcentage (%)</option>
                    <option value="fixed" {{ old('discount_type', $promo->discount_type ?? '') === 'fixed' ? 'selected' : '' }}>Montant fixe (€)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1">Valeur</label>
                <input name="discount_value" type="number" step="0.01" min="0" value="{{ old('discount_value', $promo->discount_value ?? '') }}" required class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1">Utilisations max (vide = illimité)</label>
                <input name="max_uses" type="number" min="0" value="{{ old('max_uses', $promo->max_uses ?? '') }}" class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1">Valide du</label>
                <input name="valid_from" type="datetime-local" value="{{ old('valid_from', $promo ? $promo->valid_from?->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}" required class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1">Valide jusqu'au</label>
                <input name="valid_until" type="datetime-local" value="{{ old('valid_until', $promo ? $promo->valid_until?->format('Y-m-d\TH:i') : '') }}" class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
            </div>
        </div>
        <div class="mb-4 border-t border-gray-700 pt-4">
            <label class="flex items-center gap-2 mb-3">
                <input type="checkbox" name="is_recurring" value="1" id="is_recurring" {{ old('is_recurring', $promo->is_recurring ?? false) ? 'checked' : '' }} class="w-4 h-4 rounded" onchange="document.getElementById('recurring_months_field').classList.toggle('hidden', !this.checked)">
                <span class="text-sm text-gray-300">Code récurrent (s'applique sur plusieurs mois)</span>
            </label>
            <div id="recurring_months_field" class="{{ old('is_recurring', $promo->is_recurring ?? false) ? '' : 'hidden' }}">
                <label class="block text-sm text-gray-400 mb-1">Nombre de mois de remise</label>
                <input name="recurring_months" type="number" min="1" max="24" value="{{ old('recurring_months', $promo->recurring_months ?? '') }}" placeholder="Ex: 3" class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                <p class="text-xs text-gray-500 mt-1">La remise s'applique automatiquement pendant ce nombre de mois, puis le tarif normal reprend.</p>
            </div>
        </div>
        @if($promo)
            <div class="mb-6"><label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $promo->is_active) ? 'checked' : '' }} class="w-4 h-4 rounded"><span class="text-sm text-gray-300">Actif</span></label></div>
        @endif
        <div class="flex gap-3">
            <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-medium">{{ $promo ? 'Mettre à jour' : 'Créer' }}</button>
            <a href="/admin/promos" class="px-6 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm font-medium">Annuler</a>
        </div>
    </form>
</div>
@endsection
