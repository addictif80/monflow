@extends('layouts.admin')
@section('title', 'Remboursements — Admin MonFlow')
@section('content')
<div class="mb-6">
    <h1 class="text-base font-semibold text-zinc-100">Remboursements</h1>
    <p class="text-sm text-zinc-500 mt-0.5">Historique des remboursements traités</p>
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-800">
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Utilisateur</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Montant</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Raison</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Destination</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Statut</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Traité par</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800/50">
            @forelse($refunds as $r)
                <tr class="hover:bg-zinc-800/30 transition">
                    <td class="px-4 py-3 text-zinc-300">{{ $r->payment?->user?->username ?? '—' }}</td>
                    <td class="px-4 py-3 font-mono text-zinc-300">{{ number_format($r->amount, 2, ',', ' ') }} €</td>
                    <td class="px-4 py-3 text-zinc-500">{{ Str::limit($r->reason, 50) }}</td>
                    <td class="px-4 py-3 text-zinc-500">{{ $r->refund_to === 'original' ? 'Moyen original' : 'Portefeuille' }}</td>
                    <td class="px-4 py-3">
                        @if($r->status === 'processed')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">{{ $r->status }}</span>
                        @elseif($r->status === 'pending')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-500/10 text-yellow-400 border border-yellow-500/20">{{ $r->status }}</span>
                        @elseif($r->status === 'failed')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20">{{ $r->status }}</span>
                        @else
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-800 text-zinc-500 border border-zinc-700">{{ $r->status }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-zinc-500">{{ $r->processedBy?->username ?? '—' }}</td>
                    <td class="px-4 py-3 text-zinc-500">{{ $r->created_at->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-6 text-center text-zinc-600">Aucun remboursement.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $refunds->links() }}</div>
@endsection
