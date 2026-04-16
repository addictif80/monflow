@extends('layouts.app')

@section('title', 'Tableau de bord - MonFlow')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold">Bienvenue, {{ Auth::user()->username }}</h1>
    <p class="text-gray-400 mt-1">Votre espace client MonFlow</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    {{-- Subscription Card --}}
    <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
        <h2 class="text-lg font-semibold mb-4">Abonnement</h2>
        @if($activeSub)
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-gray-400">Formule</span>
                    <span class="font-medium text-indigo-400">{{ $activeSub->plan->name }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-400">Statut</span>
                    @if($activeSub->status === 'active')
                        <span class="px-2 py-1 text-xs rounded-full bg-green-900/50 text-green-400 border border-green-700">Actif</span>
                    @elseif($activeSub->status === 'suspended')
                        <span class="px-2 py-1 text-xs rounded-full bg-red-900/50 text-red-400 border border-red-700">Suspendu</span>
                    @elseif($activeSub->status === 'pending')
                        <span class="px-2 py-1 text-xs rounded-full bg-yellow-900/50 text-yellow-400 border border-yellow-700">En attente</span>
                    @else
                        <span class="px-2 py-1 text-xs rounded-full bg-gray-700 text-gray-400 border border-gray-600">{{ ucfirst($activeSub->status) }}</span>
                    @endif
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-400">Fin de période</span>
                    <span>{{ \Carbon\Carbon::parse($activeSub->current_period_end)->format('d/m/Y') }}</span>
                </div>
                <div class="pt-3 border-t border-gray-700">
                    <form action="/portal/cancel-subscription" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler votre abonnement ?')">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition">
                            Annuler l'abonnement
                        </button>
                    </form>
                </div>
            </div>
        @elseif($pendingSub)
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-gray-400">Formule</span>
                    <span class="font-medium text-indigo-400">{{ $pendingSub->plan->name }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-400">Statut</span>
                    <span class="px-2 py-1 text-xs rounded-full bg-yellow-900/50 text-yellow-400 border border-yellow-700">Paiement en attente</span>
                </div>
                <p class="text-sm text-gray-400">Votre paiement n'a pas été finalisé. Vous pouvez le reprendre maintenant.</p>
                <form action="/portal/resume-payment" method="POST" class="space-y-2 pt-2 border-t border-gray-700">
                    @csrf
                    <label class="block text-sm text-gray-400">Durée</label>
                    <select name="months" class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm">
                        <option value="1">1 mois (abonnement récurrent)</option>
                        <option value="3">3 mois prépayés</option>
                        <option value="6">6 mois prépayés</option>
                        <option value="12">12 mois prépayés</option>
                    </select>
                    <button type="submit" class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-medium transition">Payer maintenant</button>
                </form>
            </div>
        @else
            <p class="text-gray-400 mb-4">Aucun abonnement actif.</p>
            <a href="/portal/plans" class="inline-block px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">
                Voir les formules
            </a>
        @endif
    </div>

    {{-- Wallet Card --}}
    <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
        <h2 class="text-lg font-semibold mb-4">Portefeuille</h2>
        <div class="mb-4">
            <span class="text-3xl font-bold text-indigo-400">{{ number_format($wallet->balance, 2, ',', ' ') }} &euro;</span>
        </div>
        <a href="/portal/wallet" class="inline-block px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">
            Gérer le portefeuille
        </a>
    </div>
</div>

{{-- Recent Payments --}}
<div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold">Paiements récents</h2>
        <a href="/portal/payments" class="text-sm text-indigo-400 hover:text-indigo-300">Voir tout</a>
    </div>
    @if($recentPayments->count())
        <ul class="divide-y divide-gray-700">
            @foreach($recentPayments as $payment)
                <li class="py-3 flex items-center justify-between">
                    <div>
                        <span class="font-medium">{{ number_format($payment->amount, 2, ',', ' ') }} &euro;</span>
                        <span class="text-gray-400 text-sm ml-2">{{ \Carbon\Carbon::parse($payment->created_at)->format('d/m/Y') }}</span>
                    </div>
                    @if($payment->status === 'succeeded')
                        <span class="px-2 py-1 text-xs rounded-full bg-green-900/50 text-green-400 border border-green-700">Réussi</span>
                    @elseif($payment->status === 'failed')
                        <span class="px-2 py-1 text-xs rounded-full bg-red-900/50 text-red-400 border border-red-700">Échoué</span>
                    @elseif($payment->status === 'pending')
                        <span class="px-2 py-1 text-xs rounded-full bg-yellow-900/50 text-yellow-400 border border-yellow-700">En attente</span>
                    @else
                        <span class="px-2 py-1 text-xs rounded-full bg-gray-700 text-gray-400 border border-gray-600">{{ ucfirst($payment->status) }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    @else
        <p class="text-gray-400">Aucun paiement récent.</p>
    @endif
</div>
@endsection
