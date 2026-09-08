@extends('layouts.admin')
@section('title', 'Abonnement — Admin MonFlow')
@section('content')
<div class="mb-6">
    <a href="/admin/subscriptions" class="text-sm text-zinc-500 hover:text-zinc-300">&larr; Retour aux abonnements</a>
</div>

@if(session('success'))
    <div class="mb-4 p-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-lg text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 p-3 bg-red-500/10 border border-red-500/20 text-red-400 rounded-lg text-sm">{{ session('error') }}</div>
@endif

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-base font-semibold text-zinc-100">Abonnement de {{ $sub->user?->username ?? '—' }}</h1>
    </div>
    @if($sub->status === 'active')
        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">{{ $sub->status }}</span>
    @elseif($sub->status === 'pending')
        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-500/10 text-yellow-400 border border-yellow-500/20">{{ $sub->status }}</span>
    @elseif($sub->status === 'suspended')
        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20">{{ $sub->status }}</span>
    @else
        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-800 text-zinc-500 border border-zinc-700">{{ $sub->status }}</span>
    @endif
</div>

{{-- Info --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
        <h2 class="text-sm font-medium text-zinc-300 mb-4">Informations</h2>
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between"><dt class="text-zinc-500">Utilisateur</dt><dd><a href="/admin/users/{{ $sub->user_id }}" class="text-indigo-400 hover:text-indigo-300">{{ $sub->user?->username ?? '—' }}</a></dd></div>
            <div class="flex justify-between"><dt class="text-zinc-500">Formule</dt><dd class="text-zinc-300">{{ $sub->plan?->name ?? '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-zinc-500">Début période</dt><dd class="text-zinc-300">{{ $sub->current_period_start?->format('d/m/Y H:i') ?? '—' }}</dd></div>
            <div class="flex justify-between">
                <dt class="text-zinc-500">Fin période</dt>
                <dd class="text-zinc-300">
                    {{ $sub->current_period_end?->format('d/m/Y H:i') ?? '—' }}
                    @if($sub->current_period_end && $sub->status === 'active')
                        @if($sub->current_period_end->isPast())
                            <span class="text-red-400 text-xs ml-1">expiré</span>
                        @elseif($sub->current_period_end->diffInDays(now(), true) <= 7)
                            <span class="text-yellow-400 text-xs ml-1">{{ $sub->current_period_end->diffInDays(now(), true) }}j restants</span>
                        @endif
                    @endif
                </dd>
            </div>
            <div class="flex justify-between"><dt class="text-zinc-500">Stripe ID</dt><dd class="font-mono text-xs text-zinc-400">{{ $sub->stripe_subscription_id ?? '—' }}</dd></div>
            @if($sub->is_gift)
                <div class="flex justify-between"><dt class="text-zinc-500">Cadeau</dt><dd class="text-emerald-400">Oui — offert par {{ $sub->giftedBy?->username ?? '—' }}</dd></div>
            @endif
            @if($sub->cancelled_at)
                <div class="flex justify-between"><dt class="text-zinc-500">Résilié le</dt><dd class="text-red-400">{{ $sub->cancelled_at->format('d/m/Y H:i') }}</dd></div>
            @endif
            <div class="flex justify-between"><dt class="text-zinc-500">Créé le</dt><dd class="text-zinc-300">{{ $sub->created_at->format('d/m/Y H:i') }}</dd></div>
        </dl>
    </div>

    {{-- Actions --}}
    <div class="space-y-4">
        @if($sub->status !== 'cancelled')
        {{-- Extend --}}
        <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
            <h3 class="text-sm font-medium text-zinc-300 mb-3">Prolonger l'abonnement</h3>
            <form action="/admin/subscriptions/{{ $sub->id }}/extend" method="POST" class="flex gap-3 items-end">
                @csrf
                <div class="flex-1">
                    <label class="block text-xs font-medium text-zinc-400 mb-1.5">Jours</label>
                    <input type="number" name="days" value="30" min="1" max="365"
                           class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
                </div>
                <button type="submit" class="inline-flex items-center gap-2 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 text-sm font-medium px-4 py-2 rounded-lg border border-emerald-500/20 transition">Prolonger</button>
            </form>
        </div>

        {{-- Update dates --}}
        <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
            <h3 class="text-sm font-medium text-zinc-300 mb-3">Modifier les dates</h3>
            <form action="/admin/subscriptions/{{ $sub->id }}/update-dates" method="POST" class="grid grid-cols-2 gap-3 items-end">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-zinc-400 mb-1.5">Début période</label>
                    <input type="datetime-local" name="current_period_start" value="{{ $sub->current_period_start?->format('Y-m-d\TH:i') }}"
                           class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-400 mb-1.5">Fin période</label>
                    <input type="datetime-local" name="current_period_end" value="{{ $sub->current_period_end?->format('Y-m-d\TH:i') }}"
                           class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
                </div>
                <div class="col-span-2">
                    <button type="submit" class="inline-flex items-center gap-2 bg-yellow-500/10 hover:bg-yellow-500/20 text-yellow-400 text-sm font-medium px-4 py-2 rounded-lg border border-yellow-500/20 transition">Mettre à jour</button>
                </div>
            </form>
        </div>

        {{-- Change plan --}}
        <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
            <h3 class="text-sm font-medium text-zinc-300 mb-3">Changer de formule</h3>
            <form action="/admin/subscriptions/{{ $sub->id }}/change-plan" method="POST" class="flex gap-3 items-end">
                @csrf
                <div class="flex-1">
                    <label class="block text-xs font-medium text-zinc-400 mb-1.5">Nouvelle formule</label>
                    <select name="plan_id" class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
                        @foreach($plans as $p)
                            <option value="{{ $p->id }}" {{ $sub->plan_id === $p->id ? 'selected' : '' }}>{{ $p->name }} ({{ $p->price }}€)</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Changer</button>
            </form>
        </div>

        {{-- Cancel --}}
        <div class="bg-zinc-900 border border-red-500/20 rounded-xl p-5">
            <h3 class="text-sm font-medium text-red-400 mb-2">Résilier l'abonnement</h3>
            <p class="text-xs text-zinc-500 mb-3">Cette action annule immédiatement l'abonnement. Si un abonnement Stripe est lié, il sera également annulé.</p>
            <form action="/admin/subscriptions/{{ $sub->id }}/cancel" method="POST" onsubmit="return confirm('Confirmer la résiliation de cet abonnement ?')">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 bg-red-500/10 hover:bg-red-500/15 text-red-400 text-sm font-medium px-4 py-2 rounded-lg border border-red-500/20 transition">Résilier</button>
            </form>
        </div>
        @else
        <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
            <h3 class="text-sm font-medium text-zinc-300 mb-3">Réactiver l'abonnement</h3>
            <p class="text-xs text-zinc-500 mb-3">Cet abonnement est résilié. Vous pouvez le prolonger pour le réactiver.</p>
            <form action="/admin/subscriptions/{{ $sub->id }}/extend" method="POST" class="flex gap-3 items-end">
                @csrf
                <div class="flex-1">
                    <label class="block text-xs font-medium text-zinc-400 mb-1.5">Jours</label>
                    <input type="number" name="days" value="30" min="1" max="365"
                           class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
                </div>
                <button type="submit" class="inline-flex items-center gap-2 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 text-sm font-medium px-4 py-2 rounded-lg border border-emerald-500/20 transition">Réactiver</button>
            </form>
        </div>
        @endif
    </div>
</div>

{{-- Payment history --}}
<div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
    <div class="px-4 py-3 border-b border-zinc-800">
        <h2 class="text-sm font-medium text-zinc-300">Historique des paiements</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-800">
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Montant</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Méthode</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Statut</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800/50">
            @forelse($sub->payments as $payment)
                <tr class="hover:bg-zinc-800/30 transition">
                    <td class="px-4 py-3 text-zinc-500">{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3 text-zinc-300">{{ number_format($payment->amount, 2) }} €</td>
                    <td class="px-4 py-3 text-zinc-500">{{ $payment->payment_method ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @if($payment->status === 'succeeded')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">{{ $payment->status }}</span>
                        @elseif($payment->status === 'pending')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-500/10 text-yellow-400 border border-yellow-500/20">{{ $payment->status }}</span>
                        @elseif($payment->status === 'failed')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20">{{ $payment->status }}</span>
                        @else
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-800 text-zinc-500 border border-zinc-700">{{ $payment->status }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-6 text-center text-zinc-600">Aucun paiement lié.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
