@extends('layouts.app')
@section('title', 'Mes tickets — MonFlow')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Mes tickets support</h1>
    <a href="/support/tickets/create" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-medium">+ Nouveau ticket</a>
</div>
<div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
    <table class="w-full text-sm">
        <thead><tr class="border-b border-gray-700 text-left text-gray-400">
            <th class="px-4 py-3">Sujet</th>
            <th class="px-4 py-3">Catégorie</th>
            <th class="px-4 py-3">Priorité</th>
            <th class="px-4 py-3">Statut</th>
            <th class="px-4 py-3">Créé le</th>
        </tr></thead>
        <tbody>
        @forelse($tickets as $t)
            <tr class="border-b border-gray-700/50 hover:bg-gray-700 cursor-pointer" onclick="window.location='/support/tickets/{{ $t->id }}'">
                <td class="px-4 py-3 font-medium">{{ Str::limit($t->subject, 50) }}</td>
                <td class="px-4 py-3 text-gray-400">{{ $t->category }}</td>
                <td class="px-4 py-3 text-gray-400">{{ $t->priority }}</td>
                <td class="px-4 py-3">
                    @php $sc = ['open' => 'blue', 'in_progress' => 'yellow', 'waiting_customer' => 'purple', 'resolved' => 'green', 'closed' => 'gray']; $sColor = $sc[$t->status] ?? 'gray'; @endphp
                    <span class="px-2 py-0.5 text-xs rounded-full bg-{{ $sColor }}-900/50 text-{{ $sColor }}-400 border border-{{ $sColor }}-700">{{ $t->status }}</span>
                </td>
                <td class="px-4 py-3 text-gray-400">{{ $t->created_at->format('d/m/Y H:i') }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Aucun ticket. <a href="/support/tickets/create" class="text-indigo-400">Créer votre premier ticket</a>.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
