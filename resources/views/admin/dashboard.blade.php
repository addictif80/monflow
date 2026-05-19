@extends('layouts.admin')

@section('title', 'Tableau de bord — Admin MonFlow')

@section('content')
<div class="mb-6">
    <h1 class="text-base font-semibold text-zinc-100">Tableau de bord</h1>
    <p class="text-sm text-zinc-500 mt-0.5">Vue d'ensemble de la plateforme</p>
</div>

{{-- Stats Grid --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
        <p class="text-xs font-medium text-zinc-500 uppercase tracking-wider">Utilisateurs totaux</p>
        <p class="text-2xl font-semibold text-zinc-100 mt-1">{{ $totalUsers }}</p>
        <p class="text-xs text-zinc-600 mt-1">+{{ $newUsersMonth }} ce mois</p>
    </div>
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
        <p class="text-xs font-medium text-zinc-500 uppercase tracking-wider">Utilisateurs actifs</p>
        <p class="text-2xl font-semibold text-emerald-400 mt-1">{{ $activeUsers }}</p>
        <p class="text-xs text-zinc-600 mt-1">{{ $suspendedUsers }} suspendus / {{ $deletedUsers }} supprimés</p>
    </div>
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
        <p class="text-xs font-medium text-zinc-500 uppercase tracking-wider">Abonnements actifs</p>
        <p class="text-2xl font-semibold text-indigo-400 mt-1">{{ $activeSubs }}</p>
        <p class="text-xs mt-1 {{ $expiringSoon > 0 ? 'text-yellow-400' : 'text-zinc-600' }}">{{ $expiringSoon }} expirent dans 7j</p>
    </div>
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
        <p class="text-xs font-medium text-zinc-500 uppercase tracking-wider">Revenus du mois</p>
        <p class="text-2xl font-semibold text-emerald-400 mt-1">{{ number_format($revenueMonth, 2, ',', ' ') }} &euro;</p>
        @php $delta = $revenueMonth - $revenueLastMonth; @endphp
        <p class="text-xs mt-1 {{ $delta >= 0 ? 'text-emerald-500' : 'text-red-400' }}">
            {{ $delta >= 0 ? '+' : '' }}{{ number_format($delta, 2, ',', ' ') }} &euro; vs mois dernier
        </p>
    </div>
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
        <p class="text-xs font-medium text-zinc-500 uppercase tracking-wider">Churn du mois</p>
        <p class="text-2xl font-semibold text-red-400 mt-1">{{ $churnMonth }}</p>
        <p class="text-xs text-zinc-600 mt-1">abonnements résiliés</p>
    </div>
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
        <p class="text-xs font-medium text-zinc-500 uppercase tracking-wider">Tickets ouverts</p>
        <p class="text-2xl font-semibold text-yellow-400 mt-1">{{ $openTickets }}</p>
    </div>
</div>

{{-- Revenue Chart --}}
@if($monthlyRevenue->isNotEmpty())
<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 mb-6">
    <h2 class="text-sm font-medium text-zinc-300 mb-4">Revenus des 6 derniers mois</h2>
    <canvas id="revenueChart" height="80"></canvas>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: {
        labels: @json($monthlyRevenue->keys()),
        datasets: [{
            label: 'Revenus (€)',
            data: @json($monthlyRevenue->values()),
            backgroundColor: 'rgba(99,102,241,0.3)',
            borderColor: 'rgb(99,102,241)',
            borderWidth: 1,
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true, ticks: { color: '#71717a', callback: v => v + ' €' }, grid: { color: '#27272a' } },
            x: { ticks: { color: '#71717a' }, grid: { display: false } }
        },
        plugins: { legend: { display: false } }
    }
});
</script>
@endif

{{-- Recent Payments --}}
<div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden mb-6">
    <div class="px-4 py-3 border-b border-zinc-800">
        <h2 class="text-sm font-medium text-zinc-300">Paiements récents</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-800">
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Utilisateur</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Montant</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Statut</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800/50">
                @forelse($recentPayments as $payment)
                    <tr class="hover:bg-zinc-800/30 transition">
                        <td class="px-4 py-3 text-zinc-300">{{ $payment->user->username ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-300">{{ number_format($payment->amount, 2, ',', ' ') }} &euro;</td>
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
                        <td class="px-4 py-3 text-zinc-500">{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-zinc-600">Aucun paiement récent.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Recent Tickets --}}
<div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
    <div class="px-4 py-3 border-b border-zinc-800">
        <h2 class="text-sm font-medium text-zinc-300">Tickets récents</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-800">
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Utilisateur</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Sujet</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Statut</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800/50">
                @forelse($recentTickets as $ticket)
                    <tr class="hover:bg-zinc-800/30 transition">
                        <td class="px-4 py-3 text-zinc-400">{{ $ticket->user->username ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <a href="/admin/tickets/{{ $ticket->id }}" class="text-indigo-400 hover:text-indigo-300">{{ $ticket->subject }}</a>
                        </td>
                        <td class="px-4 py-3">
                            @if(in_array($ticket->status, ['open', 'in_progress']))
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">{{ $ticket->status }}</span>
                            @elseif($ticket->status === 'waiting_customer')
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-500/10 text-yellow-400 border border-yellow-500/20">{{ $ticket->status }}</span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-800 text-zinc-500 border border-zinc-700">{{ $ticket->status }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-6 text-center text-zinc-600">Aucun ticket récent.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
