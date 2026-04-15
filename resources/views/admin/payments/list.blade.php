@extends('layouts.admin')
@section('title', 'Paiements — Admin MonFlow')
@section('content')
<h1 class="text-2xl font-bold mb-6">Paiements</h1>
<div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="border-b border-gray-700 text-left text-gray-400">
                <th class="px-4 py-3">Utilisateur</th>
                <th class="px-4 py-3">Montant</th>
                <th class="px-4 py-3">Statut</th>
                <th class="px-4 py-3">Méthode</th>
                <th class="px-4 py-3">Description</th>
                <th class="px-4 py-3">Date</th>
                <th class="px-4 py-3">Actions</th>
            </tr></thead>
            <tbody>
            @forelse($payments as $p)
                <tr class="border-b border-gray-700/50 hover:bg-gray-700">
                    <td class="px-4 py-3">{{ $p->user?->username ?? '—' }}</td>
                    <td class="px-4 py-3 font-mono">{{ number_format($p->amount, 2, ',', ' ') }} €</td>
                    <td class="px-4 py-3">
                        @php $colors = ['succeeded' => 'green', 'pending' => 'yellow', 'failed' => 'red', 'refunded' => 'gray', 'partially_refunded' => 'gray']; $c = $colors[$p->status] ?? 'gray'; @endphp
                        <span class="px-2 py-0.5 text-xs rounded-full bg-{{ $c }}-900/50 text-{{ $c }}-400 border border-{{ $c }}-700">{{ $p->status }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-400">{{ $p->payment_method }}</td>
                    <td class="px-4 py-3 text-gray-400">{{ $p->description }}</td>
                    <td class="px-4 py-3 text-gray-400">{{ $p->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3">
                        @if($p->status === 'succeeded')
                            <a href="/admin/payments/{{ $p->id }}/refund" class="text-indigo-400 hover:text-indigo-300">Rembourser</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-6 text-center text-gray-500">Aucun paiement.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $payments->links() }}</div>
@endsection
