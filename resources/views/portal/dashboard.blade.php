@extends('layouts.app')

@section('title', 'Tableau de bord — MonFlow')

@section('content')
<div class="mb-6">
    <h1 class="text-base font-semibold text-zinc-100">Bienvenue, {{ Auth::user()->username }}</h1>
    <p class="text-sm text-zinc-500 mt-0.5">Votre espace client MonFlow</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    {{-- Subscription Card --}}
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
        <h2 class="text-sm font-medium text-zinc-400 mb-4">Abonnement</h2>
        @if($activeSub)
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-zinc-500">Formule</span>
                    <span class="text-sm font-medium text-indigo-400">{{ $activeSub->plan->name }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-zinc-500">Statut</span>
                    @if($activeSub->status === 'active')
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Actif</span>
                    @elseif($activeSub->status === 'suspended')
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20">Suspendu</span>
                    @elseif($activeSub->status === 'pending')
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-500/10 text-yellow-400 border border-yellow-500/20">En attente</span>
                    @else
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-800 text-zinc-500 border border-zinc-700">{{ ucfirst($activeSub->status) }}</span>
                    @endif
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-zinc-500">Fin de période</span>
                    <span class="text-sm text-zinc-300">{{ \Carbon\Carbon::parse($activeSub->current_period_end)->format('d/m/Y') }}</span>
                </div>
                <div class="pt-3 border-t border-zinc-800">
                    <a href="/portal/cancel-subscription"
                       class="inline-flex items-center gap-2 bg-red-500/10 hover:bg-red-500/15 text-red-400 text-sm font-medium px-4 py-2 rounded-lg border border-red-500/20 transition w-full justify-center">
                        Résilier l'abonnement
                    </a>
                </div>
            </div>
        @elseif($pendingSub)
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-zinc-500">Formule</span>
                    <span class="text-sm font-medium text-indigo-400">{{ $pendingSub->plan->name }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-zinc-500">Statut</span>
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-500/10 text-yellow-400 border border-yellow-500/20">Paiement en attente</span>
                </div>
                <p class="text-sm text-zinc-500">Votre paiement n'a pas été finalisé. Vous pouvez le reprendre maintenant.</p>
                <form action="/portal/resume-payment" method="POST" class="space-y-2 pt-2 border-t border-zinc-800">
                    @csrf
                    <label class="block text-xs font-medium text-zinc-400 mb-1.5">Durée</label>
                    <select name="months" class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
                        <option value="1">1 mois (abonnement récurrent)</option>
                        <option value="3">3 mois prépayés</option>
                        <option value="6">6 mois prépayés</option>
                        <option value="12">12 mois prépayés</option>
                    </select>
                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Payer maintenant</button>
                </form>
            </div>
        @else
            <p class="text-sm text-zinc-500 mb-4">Aucun abonnement actif.</p>
            <a href="/portal/plans" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                Voir les formules
            </a>
        @endif
    </div>

    {{-- Wallet Card --}}
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
        <h2 class="text-sm font-medium text-zinc-400 mb-4">Portefeuille</h2>
        <div class="mb-4">
            <span class="text-3xl font-semibold text-zinc-100">{{ number_format($wallet->balance, 2, ',', ' ') }} &euro;</span>
        </div>
        <a href="/portal/wallet" class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-sm font-medium px-4 py-2 rounded-lg border border-zinc-700 transition">
            Gérer le portefeuille
        </a>
    </div>
</div>

{{-- Recent Payments --}}
<div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
    <div class="flex items-center justify-between px-4 py-3 border-b border-zinc-800">
        <h2 class="text-sm font-medium text-zinc-300">Paiements récents</h2>
        <a href="/portal/payments" class="text-xs text-indigo-400 hover:text-indigo-300">Voir tout</a>
    </div>
    @if($recentPayments->count())
        <table class="w-full text-sm">
            <tbody class="divide-y divide-zinc-800/50">
            @foreach($recentPayments as $payment)
                <tr class="hover:bg-zinc-800/30 transition">
                    <td class="px-4 py-3 text-zinc-300 font-medium">{{ number_format($payment->amount, 2, ',', ' ') }} &euro;</td>
                    <td class="px-4 py-3 text-zinc-500">{{ \Carbon\Carbon::parse($payment->created_at)->format('d/m/Y') }}</td>
                    <td class="px-4 py-3">
                        @if($payment->status === 'succeeded')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Réussi</span>
                        @elseif($payment->status === 'failed')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20">Échoué</span>
                        @elseif($payment->status === 'pending')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-500/10 text-yellow-400 border border-yellow-500/20">En attente</span>
                        @else
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-800 text-zinc-500 border border-zinc-700">{{ ucfirst($payment->status) }}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @else
        <div class="px-4 py-6 text-center text-sm text-zinc-600">Aucun paiement récent.</div>
    @endif
</div>
@endsection
