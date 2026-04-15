@extends('layouts.admin')
@section('title', 'Nouveau remboursement — Admin MonFlow')
@section('content')
<div class="mb-6"><a href="/admin/payments" class="text-gray-400 hover:text-gray-200 text-sm">&larr; Retour aux paiements</a></div>
<h1 class="text-2xl font-bold mb-6">Rembourser un paiement</h1>
<div class="bg-gray-800 border border-gray-700 rounded-lg p-6 mb-6 max-w-2xl">
    <div class="text-sm text-gray-400 mb-1">Utilisateur</div>
    <div class="mb-3 font-medium">{{ $payment->user->username }} ({{ $payment->user->email }})</div>
    <div class="text-sm text-gray-400 mb-1">Montant payé</div>
    <div class="mb-3 font-mono text-lg">{{ number_format($payment->amount, 2, ',', ' ') }} €</div>
    <div class="text-sm text-gray-400 mb-1">Description</div>
    <div class="mb-3">{{ $payment->description }}</div>
    <div class="text-sm text-gray-400 mb-1">Date</div>
    <div>{{ $payment->created_at->format('d/m/Y H:i') }}</div>
</div>
<div class="bg-gray-800 border border-gray-700 rounded-lg p-6 max-w-2xl">
    <form method="POST" action="/admin/payments/{{ $payment->id }}/refund">
        @csrf
        <div class="mb-4">
            <label class="block text-sm text-gray-400 mb-1">Montant à rembourser (€)</label>
            <input name="amount" type="number" step="0.01" min="0" max="{{ $payment->amount }}" value="{{ $payment->amount }}" required class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
        </div>
        <div class="mb-4">
            <label class="block text-sm text-gray-400 mb-1">Destination</label>
            <select name="refund_to" class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                <option value="original">Moyen de paiement original (Stripe)</option>
                <option value="wallet">Portefeuille utilisateur</option>
            </select>
        </div>
        <div class="mb-6">
            <label class="block text-sm text-gray-400 mb-1">Raison</label>
            <textarea name="reason" rows="3" class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500"></textarea>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-medium">Effectuer le remboursement</button>
            <a href="/admin/payments" class="px-6 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm font-medium">Annuler</a>
        </div>
    </form>
</div>
@endsection
