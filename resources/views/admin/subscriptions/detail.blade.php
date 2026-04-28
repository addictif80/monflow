@extends('layouts.admin')
@section('title', 'Abonnement — Admin MonFlow')
@section('content')
<div class="mb-6">
    <a href="/admin/subscriptions" class="text-gray-400 hover:text-gray-200 text-sm">&larr; Retour aux abonnements</a>
</div>

@if(session('success'))
    <div class="mb-4 p-3 bg-green-900/50 border border-green-700 text-green-300 rounded-lg text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-3 bg-red-900/50 border border-red-700 text-red-300 rounded-lg text-sm">{{ session('error') }}</div>
@endif

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Abonnement de {{ $sub->user?->username ?? '—' }}</h1>
    @php $colors = ['active' => 'green', 'pending' => 'yellow', 'suspended' => 'red', 'cancelled' => 'gray']; $c = $colors[$sub->status] ?? 'gray'; @endphp
    <span class="px-3 py-1 text-sm rounded-full bg-{{ $c }}-900/50 text-{{ $c }}-400 border border-{{ $c }}-700">{{ $sub->status }}</span>
</div>

{{-- Info --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        <h2 class="text-lg font-semibold mb-4">Informations</h2>
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between"><dt class="text-gray-400">Utilisateur</dt><dd><a href="/admin/users/{{ $sub->user_id }}" class="text-indigo-400 hover:text-indigo-300">{{ $sub->user?->username ?? '—' }}</a></dd></div>
            <div class="flex justify-between"><dt class="text-gray-400">Formule</dt><dd>{{ $sub->plan?->name ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-gray-400">Début période</dt><dd>{{ $sub->current_period_start?->format('d/m/Y H:i') ?? '—' }}</dd></div>
            <div class="flex justify-between">
                <dt class="text-gray-400">Fin période</dt>
                <dd>
                    {{ $sub->current_period_end?->format('d/m/Y H:i') ?? '—' }}
                    @if($sub->current_period_end && $sub->status === 'active')
                        @if($sub->current_period_end->isPast())
                            <span class="text-red-400 text-xs ml-1">expiré</span>
                        @elseif($sub->current_period_end->diffInDays(now()) <= 7)
                            <span class="text-yellow-400 text-xs ml-1">{{ $sub->current_period_end->diffInDays(now()) }}j restants</span>
                        @endif
                    @endif
                </dd>
            </div>
            <div class="flex justify-between"><dt class="text-gray-400">Stripe ID</dt><dd class="font-mono text-xs">{{ $sub->stripe_subscription_id ?? '—' }}</dd></div>
            @if($sub->is_gift)
                <div class="flex justify-between"><dt class="text-gray-400">Cadeau</dt><dd class="text-emerald-400">Oui — offert par {{ $sub->giftedBy?->username ?? '—' }}</dd></div>
            @endif
            @if($sub->cancelled_at)
                <div class="flex justify-between"><dt class="text-gray-400">Résilié le</dt><dd class="text-red-400">{{ $sub->cancelled_at->format('d/m/Y H:i') }}</dd></div>
            @endif
            <div class="flex justify-between"><dt class="text-gray-400">Créé le</dt><dd>{{ $sub->created_at->format('d/m/Y H:i') }}</dd></div>
        </dl>
    </div>

    {{-- Actions --}}
    <div class="space-y-4">
        @if($sub->status !== 'cancelled')
        {{-- Extend --}}
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <h3 class="font-semibold mb-3">Prolonger l'abonnement</h3>
            <form action="/admin/subscriptions/{{ $sub->id }}/extend" method="POST" class="flex gap-3 items-end">
                @csrf
                <div class="flex-1">
                    <label class="block text-sm text-gray-400 mb-1">Jours</label>
                    <input type="number" name="days" value="30" min="1" max="365" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-sm text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg text-sm font-medium transition">Prolonger</button>
            </form>
        </div>

        {{-- Update dates --}}
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <h3 class="font-semibold mb-3">Modifier les dates</h3>
            <form action="/admin/subscriptions/{{ $sub->id }}/update-dates" method="POST" class="grid grid-cols-2 gap-3 items-end">
                @csrf
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Début période</label>
                    <input type="datetime-local" name="current_period_start" value="{{ $sub->current_period_start?->format('Y-m-d\TH:i') }}" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-sm text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Fin période</label>
                    <input type="datetime-local" name="current_period_end" value="{{ $sub->current_period_end?->format('Y-m-d\TH:i') }}" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-sm text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                <div class="col-span-2">
                    <button type="submit" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-500 text-white rounded-lg text-sm font-medium transition">Mettre à jour</button>
                </div>
            </form>
        </div>

        {{-- Change plan --}}
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <h3 class="font-semibold mb-3">Changer de formule</h3>
            <form action="/admin/subscriptions/{{ $sub->id }}/change-plan" method="POST" class="flex gap-3 items-end">
                @csrf
                <div class="flex-1">
                    <label class="block text-sm text-gray-400 mb-1">Nouvelle formule</label>
                    <select name="plan_id" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-sm text-gray-100">
                        @foreach($plans as $p)
                            <option value="{{ $p->id }}" {{ $sub->plan_id === $p->id ? 'selected' : '' }}>{{ $p->name }} ({{ $p->price }}€)</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-sm font-medium transition">Changer</button>
            </form>
        </div>

        {{-- Cancel --}}
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <h3 class="font-semibold mb-3 text-red-400">Résilier l'abonnement</h3>
            <p class="text-sm text-gray-400 mb-3">Cette action annule immédiatement l'abonnement. Si un abonnement Stripe est lié, il sera également annulé.</p>
            <form action="/admin/subscriptions/{{ $sub->id }}/cancel" method="POST" onsubmit="return confirm('Confirmer la résiliation de cet abonnement ?')">
                @csrf
                <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-500 text-white rounded-lg text-sm font-medium transition">Résilier</button>
            </form>
        </div>
        @else
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
            <h3 class="font-semibold mb-3">Réactiver l'abonnement</h3>
            <p class="text-sm text-gray-400 mb-3">Cet abonnement est résilié. Vous pouvez le prolonger pour le réactiver.</p>
            <form action="/admin/subscriptions/{{ $sub->id }}/extend" method="POST" class="flex gap-3 items-end">
                @csrf
                <div class="flex-1">
                    <label class="block text-sm text-gray-400 mb-1">Jours</label>
                    <input type="number" name="days" value="30" min="1" max="365" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-sm text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg text-sm font-medium transition">Réactiver</button>
            </form>
        </div>
        @endif
    </div>
</div>

{{-- Payment history --}}
<div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-700">
        <h2 class="text-lg font-semibold">Historique des paiements</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="border-b border-gray-700 text-left text-gray-400">
                <th class="px-4 py-3">Date</th>
                <th class="px-4 py-3">Montant</th>
                <th class="px-4 py-3">Méthode</th>
                <th class="px-4 py-3">Statut</th>
            </tr></thead>
            <tbody>
            @forelse($sub->payments as $payment)
                <tr class="border-b border-gray-700/50 hover:bg-gray-700">
                    <td class="px-4 py-3 text-gray-400">{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3">{{ number_format($payment->amount, 2) }} €</td>
                    <td class="px-4 py-3 text-gray-400">{{ $payment->payment_method ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @php $pc = ['succeeded' => 'green', 'pending' => 'yellow', 'failed' => 'red']; $pcolor = $pc[$payment->status] ?? 'gray'; @endphp
                        <span class="px-2 py-0.5 text-xs rounded-full bg-{{ $pcolor }}-900/50 text-{{ $pcolor }}-400 border border-{{ $pcolor }}-700">{{ $payment->status }}</span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">Aucun paiement lié.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
