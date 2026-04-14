@extends('layouts.admin')
@section('title', 'Remboursements — Admin MonFlow')
@section('content')
<h1 class="text-2xl font-bold mb-6">Remboursements</h1>
<div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="border-b border-gray-700 text-left text-gray-400">
                <th class="px-4 py-3">Utilisateur</th>
                <th class="px-4 py-3">Montant</th>
                <th class="px-4 py-3">Raison</th>
                <th class="px-4 py-3">Destination</th>
                <th class="px-4 py-3">Statut</th>
                <th class="px-4 py-3">Traité par</th>
                <th class="px-4 py-3">Date</th>
            </tr></thead>
            <tbody>
            @forelse($refunds as $r)
                <tr class="border-b border-gray-700/50 hover:bg-gray-700">
                    <td class="px-4 py-3">{{ $r->payment?->user?->username ?? '—' }}</td>
                    <td class="px-4 py-3 font-mono">{{ number_format($r->amount, 2, ',', ' ') }} €</td>
                    <td class="px-4 py-3 text-gray-400">{{ Str::limit($r->reason, 50) }}</td>
                    <td class="px-4 py-3 text-gray-400">{{ $r->refund_to === 'original' ? 'Moyen original' : 'Portefeuille' }}</td>
                    <td class="px-4 py-3">
                        @php $colors = ['processed' => 'green', 'pending' => 'yellow', 'failed' => 'red']; $c = $colors[$r->status] ?? 'gray'; @endphp
                        <span class="px-2 py-0.5 text-xs rounded-full bg-{{ $c }}-900/50 text-{{ $c }}-400 border border-{{ $c }}-700">{{ $r->status }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-400">{{ $r->processedBy?->username ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-400">{{ $r->created_at->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-6 text-center text-gray-500">Aucun remboursement.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $refunds->links() }}</div>
@endsection
