@extends('layouts.admin')

@section('title', 'Tableau de bord — Admin MonFlow')

@section('content')
<h1 class="text-2xl font-bold mb-6">Tableau de bord</h1>

{{-- Stats Grid --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-5">
        <p class="text-gray-400 text-sm">Utilisateurs totaux</p>
        <p class="text-3xl font-bold mt-1">{{ $totalUsers }}</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-5">
        <p class="text-gray-400 text-sm">Utilisateurs actifs</p>
        <p class="text-3xl font-bold mt-1 text-green-400">{{ $activeUsers }}</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-5">
        <p class="text-gray-400 text-sm">Utilisateurs suspendus</p>
        <p class="text-3xl font-bold mt-1 text-red-400">{{ $suspendedUsers }}</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-5">
        <p class="text-gray-400 text-sm">Abonnements actifs</p>
        <p class="text-3xl font-bold mt-1 text-indigo-400">{{ $activeSubs }}</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-5">
        <p class="text-gray-400 text-sm">Revenus du mois</p>
        <p class="text-3xl font-bold mt-1 text-green-400">{{ number_format($revenueMonth, 2, ',', ' ') }} &euro;</p>
    </div>
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-5">
        <p class="text-gray-400 text-sm">Tickets ouverts</p>
        <p class="text-3xl font-bold mt-1 text-yellow-400">{{ $openTickets }}</p>
    </div>
</div>

{{-- Recent Payments --}}
<div class="bg-gray-800 border border-gray-700 rounded-lg p-5 mb-8">
    <h2 class="text-lg font-semibold mb-4">Paiements récents</h2>
    <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="border-b border-gray-700 text-left text-gray-400">
                    <th class="pb-2 pr-4">Utilisateur</th>
                    <th class="pb-2 pr-4">Montant</th>
                    <th class="pb-2 pr-4">Statut</th>
                    <th class="pb-2">Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentPayments as $payment)
                    <tr class="border-b border-gray-700/50 hover:bg-gray-700">
                        <td class="py-2 pr-4">{{ $payment->user->username }}</td>
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
                        <td class="py-2">{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-4 text-center text-gray-500">Aucun paiement récent.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Recent Tickets --}}
<div class="bg-gray-800 border border-gray-700 rounded-lg p-5">
    <h2 class="text-lg font-semibold mb-4">Tickets récents</h2>
    <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="border-b border-gray-700 text-left text-gray-400">
                    <th class="pb-2 pr-4">Utilisateur</th>
                    <th class="pb-2 pr-4">Sujet</th>
                    <th class="pb-2">Statut</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentTickets as $ticket)
                    <tr class="border-b border-gray-700/50 hover:bg-gray-700">
                        <td class="py-2 pr-4">{{ $ticket->user->username }}</td>
                        <td class="py-2 pr-4">
                            <a href="/admin/tickets/{{ $ticket->id }}" class="text-indigo-400 hover:text-indigo-300">{{ $ticket->subject }}</a>
                        </td>
                        <td class="py-2">
                            @if(in_array($ticket->status, ['open', 'in_progress']))
                                <span class="px-2 py-0.5 text-xs rounded-full bg-green-900/50 text-green-400 border border-green-700">{{ $ticket->status }}</span>
                            @elseif($ticket->status === 'waiting_customer')
                                <span class="px-2 py-0.5 text-xs rounded-full bg-yellow-900/50 text-yellow-400 border border-yellow-700">{{ $ticket->status }}</span>
                            @else
                                <span class="px-2 py-0.5 text-xs rounded-full bg-gray-700 text-gray-400 border border-gray-600">{{ $ticket->status }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="py-4 text-center text-gray-500">Aucun ticket récent.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
