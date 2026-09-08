@extends('layouts.admin')
@section('title', 'Tickets — Admin MonFlow')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-base font-semibold text-zinc-100">Tickets support</h1>
        <p class="text-sm text-zinc-500 mt-0.5">Demandes d'assistance des utilisateurs</p>
    </div>
    <form method="GET" class="flex gap-2">
        <select name="status" onchange="this.form.submit()"
                class="bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
            <option value="">Tous les statuts</option>
            @foreach(['open' => 'Ouvert', 'in_progress' => 'En cours', 'waiting_customer' => 'Attente client', 'resolved' => 'Résolu', 'closed' => 'Fermé'] as $k => $v)
                <option value="{{ $k }}" {{ $statusFilter === $k ? 'selected' : '' }}>{{ $v }}</option>
            @endforeach
        </select>
    </form>
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-800">
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Utilisateur</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Sujet</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Catégorie</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Priorité</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Statut</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Créé le</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800/50">
            @forelse($tickets as $t)
                <tr class="hover:bg-zinc-800/30 transition cursor-pointer" onclick="window.location='/admin/tickets/{{ $t->id }}'">
                    <td class="px-4 py-3 text-zinc-400">{{ $t->user?->username ?? '—' }}</td>
                    <td class="px-4 py-3 font-medium text-zinc-200">{{ Str::limit($t->subject, 50) }}</td>
                    <td class="px-4 py-3 text-zinc-500">{{ $t->category }}</td>
                    <td class="px-4 py-3">
                        @if($t->priority === 'low')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-800 text-zinc-500 border border-zinc-700">{{ $t->priority }}</span>
                        @elseif($t->priority === 'medium')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-500/10 text-yellow-400 border border-yellow-500/20">{{ $t->priority }}</span>
                        @elseif($t->priority === 'high')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20">{{ $t->priority }}</span>
                        @else
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-800 text-zinc-500 border border-zinc-700">{{ $t->priority }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if(in_array($t->status, ['open', 'in_progress']))
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">{{ $t->status }}</span>
                        @elseif(in_array($t->status, ['waiting_customer']))
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-500/10 text-yellow-400 border border-yellow-500/20">{{ $t->status }}</span>
                        @elseif($t->status === 'resolved')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">{{ $t->status }}</span>
                        @else
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-800 text-zinc-500 border border-zinc-700">{{ $t->status }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-zinc-500">{{ $t->created_at->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-center text-zinc-600">Aucun ticket.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $tickets->links() }}</div>
@endsection
