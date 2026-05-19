@extends('layouts.app')
@section('title', 'Mes paiements — MonFlow')
@section('content')
<div class="mb-6">
    <h1 class="text-base font-semibold text-zinc-100">Historique des paiements</h1>
    <p class="text-sm text-zinc-500 mt-0.5">Tous vos paiements et factures</p>
</div>
<div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-800">
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Description</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Montant</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Méthode</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Statut</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Facture</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800/50">
            @forelse($payments as $p)
                <tr class="hover:bg-zinc-800/30 transition">
                    <td class="px-4 py-3 text-zinc-500">{{ $p->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3 text-zinc-300">{{ $p->description }}</td>
                    <td class="px-4 py-3 font-mono text-zinc-300">{{ number_format($p->amount, 2, ',', ' ') }} €</td>
                    <td class="px-4 py-3 text-zinc-500">{{ $p->payment_method }}</td>
                    <td class="px-4 py-3">
                        @php $colors = ['succeeded' => 'emerald', 'pending' => 'yellow', 'failed' => 'red', 'refunded' => 'zinc', 'partially_refunded' => 'zinc']; $c = $colors[$p->status] ?? 'zinc'; @endphp
                        @if($c === 'emerald')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">{{ $p->status }}</span>
                        @elseif($c === 'yellow')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-500/10 text-yellow-400 border border-yellow-500/20">{{ $p->status }}</span>
                        @elseif($c === 'red')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20">{{ $p->status }}</span>
                        @else
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-800 text-zinc-500 border border-zinc-700">{{ $p->status }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($p->status === 'succeeded')
                            <a href="/portal/payments/{{ $p->id }}/invoice" target="_blank" class="text-indigo-400 hover:text-indigo-300 text-xs">Facture</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-center text-zinc-600">Aucun paiement.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $payments->links() }}</div>
@endsection
