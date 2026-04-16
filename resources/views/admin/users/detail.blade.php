@extends('layouts.admin')

@section('title', $user->username . ' — Admin MonFlow')

@section('content')
<div class="mb-6">
    <a href="/admin/users" class="text-gray-400 hover:text-gray-200 text-sm">&larr; Retour aux utilisateurs</a>
</div>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">{{ $user->username }}</h1>
    <div class="flex gap-2">
        <a href="/admin/users/{{ $user->id }}/edit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-medium transition">Modifier</a>

        @if($user->status === 'active')
            <form method="POST" action="/admin/users/{{ $user->id }}/suspend" onsubmit="return confirm('Suspendre cet utilisateur ?')">
                @csrf
                <button type="submit" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-500 rounded-lg text-sm font-medium transition">Suspendre</button>
            </form>
        @elseif($user->status === 'suspended')
            <form method="POST" action="/admin/users/{{ $user->id }}/reactivate">
                @csrf
                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-500 rounded-lg text-sm font-medium transition">Réactiver</button>
            </form>
        @endif

        @if($user->status !== 'deleted')
            <form method="POST" action="/admin/users/{{ $user->id }}/delete" onsubmit="return confirm('Supprimer cet utilisateur ? Cette action est irréversible.')">
                @csrf
                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-500 rounded-lg text-sm font-medium transition">Supprimer</button>
            </form>
        @else
            @if(!str_starts_with($user->email, 'released_'))
                <form method="POST" action="/admin/users/{{ $user->id }}/release-email" onsubmit="return confirm('Libérer {{ $user->email }} ? Cette adresse pourra être réutilisée par un autre compte.')">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-orange-600 hover:bg-orange-500 rounded-lg text-sm font-medium transition">Libérer l'email</button>
                </form>
            @endif
        @endif
    </div>
</div>

{{-- User Info Card --}}
<div class="bg-gray-800 border border-gray-700 rounded-lg p-6 mb-6">
    <h2 class="text-lg font-semibold mb-4">Informations</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
        <div>
            <span class="text-gray-400">Nom d'utilisateur</span>
            <p class="font-medium">{{ $user->username }}</p>
        </div>
        <div>
            <span class="text-gray-400">Email</span>
            <p class="font-medium">{{ $user->email }}</p>
        </div>
        <div>
            <span class="text-gray-400">Nom complet</span>
            <p class="font-medium">{{ $user->full_name ?: '—' }}</p>
        </div>
        <div>
            <span class="text-gray-400">Statut</span>
            <p class="mt-0.5">
                @if($user->status === 'active')
                    <span class="px-2 py-0.5 text-xs rounded-full bg-green-900/50 text-green-400 border border-green-700">active</span>
                @elseif($user->status === 'suspended')
                    <span class="px-2 py-0.5 text-xs rounded-full bg-red-900/50 text-red-400 border border-red-700">suspendu</span>
                @elseif($user->status === 'deleted')
                    <span class="px-2 py-0.5 text-xs rounded-full bg-gray-700 text-gray-400 border border-gray-600">supprimé</span>
                @endif
            </p>
        </div>
        <div>
            <span class="text-gray-400">Navidrome ID</span>
            <p class="font-medium font-mono text-xs">{{ $user->navidrome_id ?: '—' }}</p>
        </div>
        <div>
            <span class="text-gray-400">Stripe Customer ID</span>
            <p class="font-medium font-mono text-xs">{{ $user->stripe_customer_id ?: '—' }}</p>
        </div>
        <div>
            <span class="text-gray-400">Créé le</span>
            <p class="font-medium">{{ $user->created_at->format('d/m/Y H:i') }}</p>
        </div>
    </div>
</div>

{{-- Wallet Section --}}
<div class="bg-gray-800 border border-gray-700 rounded-lg p-6 mb-6">
    <h2 class="text-lg font-semibold mb-4">Portefeuille</h2>
    <div class="flex items-center gap-6 mb-4">
        <div>
            <span class="text-gray-400 text-sm">Solde actuel</span>
            <p class="text-2xl font-bold text-green-400">{{ number_format($user->wallet_balance ?? 0, 2, ',', ' ') }} &euro;</p>
        </div>
    </div>
    <form method="POST" action="/admin/users/{{ $user->id }}/wallet-adjust" class="flex flex-wrap gap-3 items-end">
        @csrf
        <div>
            <label for="amount" class="block text-sm text-gray-400 mb-1">Montant (+/-)</label>
            <input type="number" id="amount" name="amount" step="0.01" required
                   class="w-40 px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
        </div>
        <div class="flex-1 min-w-[200px]">
            <label for="description" class="block text-sm text-gray-400 mb-1">Description</label>
            <input type="text" id="description" name="description" required
                   class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
        </div>
        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-medium transition">Ajuster</button>
    </form>
</div>

{{-- Subscriptions --}}
<div class="bg-gray-800 border border-gray-700 rounded-lg p-6 mb-6">
    <h2 class="text-lg font-semibold mb-4">Abonnements</h2>
    <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="border-b border-gray-700 text-left text-gray-400">
                    <th class="pb-2 pr-4">Formule</th>
                    <th class="pb-2 pr-4">Statut</th>
                    <th class="pb-2 pr-4">Début</th>
                    <th class="pb-2">Fin</th>
                </tr>
            </thead>
            <tbody>
                @forelse($subscriptions as $sub)
                    <tr class="border-b border-gray-700/50 hover:bg-gray-700">
                        <td class="py-2 pr-4">{{ $sub->plan->name }}</td>
                        <td class="py-2 pr-4">
                            @if($sub->status === 'active')
                                <span class="px-2 py-0.5 text-xs rounded-full bg-green-900/50 text-green-400 border border-green-700">active</span>
                            @elseif($sub->status === 'cancelled')
                                <span class="px-2 py-0.5 text-xs rounded-full bg-gray-700 text-gray-400 border border-gray-600">annulé</span>
                            @elseif($sub->status === 'pending')
                                <span class="px-2 py-0.5 text-xs rounded-full bg-yellow-900/50 text-yellow-400 border border-yellow-700">en attente</span>
                            @else
                                <span class="px-2 py-0.5 text-xs rounded-full bg-gray-700 text-gray-400 border border-gray-600">{{ $sub->status }}</span>
                            @endif
                        </td>
                        <td class="py-2 pr-4 text-gray-400">{{ $sub->current_period_start ? $sub->current_period_start->format('d/m/Y') : '—' }}</td>
                        <td class="py-2 text-gray-400">{{ $sub->current_period_end ? $sub->current_period_end->format('d/m/Y') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-4 text-center text-gray-500">Aucun abonnement.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Payments --}}
<div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
    <h2 class="text-lg font-semibold mb-4">Paiements</h2>
    <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="border-b border-gray-700 text-left text-gray-400">
                    <th class="pb-2 pr-4">Montant</th>
                    <th class="pb-2 pr-4">Statut</th>
                    <th class="pb-2 pr-4">Description</th>
                    <th class="pb-2">Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr class="border-b border-gray-700/50 hover:bg-gray-700">
                        <td class="py-2 pr-4">{{ number_format($payment->amount, 2, ',', ' ') }} &euro;</td>
                        <td class="py-2 pr-4">
                            @if($payment->status === 'succeeded')
                                <span class="px-2 py-0.5 text-xs rounded-full bg-green-900/50 text-green-400 border border-green-700">{{ $payment->status }}</span>
                            @elseif($payment->status === 'pending')
                                <span class="px-2 py-0.5 text-xs rounded-full bg-yellow-900/50 text-yellow-400 border border-yellow-700">{{ $payment->status }}</span>
                            @elseif($payment->status === 'failed')
                                <span class="px-2 py-0.5 text-xs rounded-full bg-red-900/50 text-red-400 border border-red-700">{{ $payment->status }}</span>
                            @else
                                <span class="px-2 py-0.5 text-xs rounded-full bg-gray-700 text-gray-400 border border-gray-600">{{ $payment->status }}</span>
                            @endif
                        </td>
                        <td class="py-2 pr-4 text-gray-400">{{ $payment->description ?: '—' }}</td>
                        <td class="py-2 text-gray-400">{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-4 text-center text-gray-500">Aucun paiement.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
