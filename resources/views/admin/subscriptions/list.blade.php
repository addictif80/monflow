@extends('layouts.admin')
@section('title', 'Abonnements — Admin MonFlow')
@section('content')
<h1 class="text-2xl font-bold mb-6">Abonnements</h1>
<div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="border-b border-gray-700 text-left text-gray-400">
                <th class="px-4 py-3">Utilisateur</th>
                <th class="px-4 py-3">Formule</th>
                <th class="px-4 py-3">Statut</th>
                <th class="px-4 py-3">Début période</th>
                <th class="px-4 py-3">Fin période</th>
                <th class="px-4 py-3">Stripe ID</th>
                <th class="px-4 py-3">Cadeau ?</th>
            </tr></thead>
            <tbody>
            @forelse($subscriptions as $s)
                <tr class="border-b border-gray-700/50 hover:bg-gray-700">
                    <td class="px-4 py-3">{{ $s->user?->username ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $s->plan?->name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @php $colors = ['active' => 'green', 'pending' => 'yellow', 'suspended' => 'red', 'cancelled' => 'gray']; $c = $colors[$s->status] ?? 'gray'; @endphp
                        <span class="px-2 py-0.5 text-xs rounded-full bg-{{ $c }}-900/50 text-{{ $c }}-400 border border-{{ $c }}-700">{{ $s->status }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-400">{{ $s->current_period_start?->format('d/m/Y') ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-400">{{ $s->current_period_end?->format('d/m/Y') ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ Str::limit($s->stripe_subscription_id, 20) ?: '—' }}</td>
                    <td class="px-4 py-3">{{ $s->is_gift ? '🎁' : '' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-6 text-center text-gray-500">Aucun abonnement.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $subscriptions->links() }}</div>
@endsection
