@extends('layouts.app')

@section('title', 'Portefeuille — MonFlow')

@section('content')
<div class="mb-6">
    <h1 class="text-base font-semibold text-zinc-100">Portefeuille</h1>
    <p class="text-sm text-zinc-500 mt-0.5">Gérez votre solde et consultez vos transactions</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    {{-- Balance --}}
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
        <h2 class="text-sm font-medium text-zinc-400 mb-2">Solde actuel</h2>
        <span class="text-3xl font-semibold text-zinc-100">{{ number_format($wallet->balance, 2, ',', ' ') }} &euro;</span>
    </div>

    {{-- Top-up Form --}}
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
        <h2 class="text-sm font-medium text-zinc-400 mb-4">Recharger</h2>
        <form action="/portal/wallet/topup" method="POST" class="flex gap-3">
            @csrf
            <div class="flex-1">
                <input type="number" name="amount" min="5" step="0.01" placeholder="Montant (min. 5 €)" required
                       class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
            </div>
            <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                Recharger
            </button>
        </form>
    </div>
</div>

{{-- Transactions --}}
<div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
    <div class="px-4 py-3 border-b border-zinc-800">
        <h2 class="text-sm font-medium text-zinc-300">Historique des transactions</h2>
    </div>
    @if($transactions->count())
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-800">
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Montant</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Description</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800/50">
                    @foreach($transactions as $transaction)
                        <tr class="hover:bg-zinc-800/30 transition">
                            <td class="px-4 py-3 text-zinc-300 capitalize">{{ $transaction->type }}</td>
                            <td class="px-4 py-3 font-medium">
                                @if($transaction->amount >= 0)
                                    <span class="text-emerald-400">+{{ number_format($transaction->amount, 2, ',', ' ') }} &euro;</span>
                                @else
                                    <span class="text-red-400">{{ number_format($transaction->amount, 2, ',', ' ') }} &euro;</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-zinc-500">{{ $transaction->description }}</td>
                            <td class="px-4 py-3 text-zinc-500">{{ \Carbon\Carbon::parse($transaction->created_at)->format('d/m/Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="px-4 py-6 text-center text-sm text-zinc-600">Aucune transaction.</div>
    @endif
</div>
@endsection
