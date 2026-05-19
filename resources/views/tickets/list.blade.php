@extends('layouts.app')
@section('title', 'Mes tickets — MonFlow')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-base font-semibold text-zinc-100">Mes tickets support</h1>
        <p class="text-sm text-zinc-500 mt-0.5">Vos demandes d'assistance</p>
    </div>
    <a href="/support/tickets/create" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">+ Nouveau ticket</a>
</div>
<div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-zinc-800">
                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Sujet</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Catégorie</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Priorité</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Statut</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Créé le</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-800/50">
        @forelse($tickets as $t)
            <tr class="hover:bg-zinc-800/30 transition cursor-pointer" onclick="window.location='/support/tickets/{{ $t->id }}'">
                <td class="px-4 py-3 font-medium text-zinc-300">{{ Str::limit($t->subject, 50) }}</td>
                <td class="px-4 py-3 text-zinc-500">{{ $t->category }}</td>
                <td class="px-4 py-3 text-zinc-500">{{ $t->priority }}</td>
                <td class="px-4 py-3">
                    @php $sc = ['open' => 'indigo', 'in_progress' => 'yellow', 'waiting_customer' => 'yellow', 'resolved' => 'emerald', 'closed' => 'zinc']; $sColor = $sc[$t->status] ?? 'zinc'; @endphp
                    @if($sColor === 'emerald')
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">{{ $t->status }}</span>
                    @elseif($sColor === 'yellow')
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-500/10 text-yellow-400 border border-yellow-500/20">{{ $t->status }}</span>
                    @elseif($sColor === 'indigo')
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">{{ $t->status }}</span>
                    @else
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-800 text-zinc-500 border border-zinc-700">{{ $t->status }}</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-zinc-500">{{ $t->created_at->format('d/m/Y H:i') }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-4 py-6 text-center text-zinc-600">Aucun ticket. <a href="/support/tickets/create" class="text-indigo-400 hover:text-indigo-300">Créer votre premier ticket</a>.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
