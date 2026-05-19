@extends('layouts.admin')
@section('title', 'Nouveau remboursement — Admin MonFlow')
@section('content')
<div class="mb-6">
    <a href="/admin/payments" class="text-sm text-zinc-500 hover:text-zinc-300">&larr; Retour aux paiements</a>
</div>

<div class="mb-6">
    <h1 class="text-base font-semibold text-zinc-100">Rembourser un paiement</h1>
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 mb-6 max-w-2xl">
    <div class="grid grid-cols-2 gap-4 text-sm">
        <div>
            <span class="text-xs text-zinc-500">Utilisateur</span>
            <p class="font-medium text-zinc-200 mt-0.5">{{ $payment->user->username }} ({{ $payment->user->email }})</p>
        </div>
        <div>
            <span class="text-xs text-zinc-500">Montant payé</span>
            <p class="font-mono text-lg font-semibold text-zinc-100 mt-0.5">{{ number_format($payment->amount, 2, ',', ' ') }} €</p>
        </div>
        <div>
            <span class="text-xs text-zinc-500">Description</span>
            <p class="text-zinc-300 mt-0.5">{{ $payment->description }}</p>
        </div>
        <div>
            <span class="text-xs text-zinc-500">Date</span>
            <p class="text-zinc-300 mt-0.5">{{ $payment->created_at->format('d/m/Y H:i') }}</p>
        </div>
    </div>
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 max-w-2xl">
    <form method="POST" action="/admin/payments/{{ $payment->id }}/refund">
        @csrf
        <div class="mb-4">
            <label class="block text-xs font-medium text-zinc-400 mb-1.5">Montant à rembourser (€)</label>
            <input name="amount" type="number" step="0.01" min="0" max="{{ $payment->amount }}" value="{{ $payment->amount }}" required
                   class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
        </div>
        <div class="mb-4">
            <label class="block text-xs font-medium text-zinc-400 mb-1.5">Destination</label>
            <select name="refund_to" class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
                <option value="original">Moyen de paiement original (Stripe)</option>
                <option value="wallet">Portefeuille utilisateur</option>
            </select>
        </div>
        <div class="mb-6">
            <label class="block text-xs font-medium text-zinc-400 mb-1.5">Raison</label>
            <textarea name="reason" rows="3" class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition"></textarea>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Effectuer le remboursement</button>
            <a href="/admin/payments" class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-sm font-medium px-4 py-2 rounded-lg border border-zinc-700 transition">Annuler</a>
        </div>
    </form>
</div>
@endsection
