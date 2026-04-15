@extends('layouts.app')

@section('title', 'Portefeuille - MonFlow')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold">Portefeuille</h1>
    <p class="text-gray-400 mt-1">Gérez votre solde et consultez vos transactions</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    {{-- Balance --}}
    <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
        <h2 class="text-lg font-semibold mb-2">Solde actuel</h2>
        <span class="text-4xl font-bold text-indigo-400">{{ number_format($wallet->balance, 2, ',', ' ') }} &euro;</span>
    </div>

    {{-- Top-up Form --}}
    <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
        <h2 class="text-lg font-semibold mb-4">Recharger</h2>
        <form action="/portal/wallet/topup" method="POST" class="flex gap-3">
            @csrf
            <div class="flex-1">
                <input type="number" name="amount" min="5" step="0.01" placeholder="Montant (min. 5 €)" required
                       class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
            <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">
                Recharger
            </button>
        </form>
    </div>
</div>

{{-- Transactions --}}
<div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
    <div class="p-6 border-b border-gray-700">
        <h2 class="text-lg font-semibold">Historique des transactions</h2>
    </div>
    @if($transactions->count())
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Montant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-700">
                    @foreach($transactions as $transaction)
                        <tr class="hover:bg-gray-700 transition">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="capitalize">{{ $transaction->type }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-medium">
                                @if($transaction->amount >= 0)
                                    <span class="text-green-400">+{{ number_format($transaction->amount, 2, ',', ' ') }} &euro;</span>
                                @else
                                    <span class="text-red-400">{{ number_format($transaction->amount, 2, ',', ' ') }} &euro;</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-400">{{ $transaction->description }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-400">{{ \Carbon\Carbon::parse($transaction->created_at)->format('d/m/Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="p-6 text-gray-400 text-center">Aucune transaction.</div>
    @endif
</div>
@endsection
