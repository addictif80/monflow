@extends('layouts.admin')
@section('title', 'Tickets — Admin MonFlow')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Tickets support</h1>
    <form method="GET" class="flex gap-2">
        <select name="status" onchange="this.form.submit()" class="px-3 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm">
            <option value="">Tous les statuts</option>
            @foreach(['open' => 'Ouvert', 'in_progress' => 'En cours', 'waiting_customer' => 'Attente client', 'resolved' => 'Résolu', 'closed' => 'Fermé'] as $k => $v)
                <option value="{{ $k }}" {{ $statusFilter === $k ? 'selected' : '' }}>{{ $v }}</option>
            @endforeach
        </select>
    </form>
</div>
<div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="border-b border-gray-700 text-left text-gray-400">
                <th class="px-4 py-3">Utilisateur</th>
                <th class="px-4 py-3">Sujet</th>
                <th class="px-4 py-3">Catégorie</th>
                <th class="px-4 py-3">Priorité</th>
                <th class="px-4 py-3">Statut</th>
                <th class="px-4 py-3">Créé le</th>
            </tr></thead>
            <tbody>
            @forelse($tickets as $t)
                <tr class="border-b border-gray-700/50 hover:bg-gray-700 cursor-pointer" onclick="window.location='/admin/tickets/{{ $t->id }}'">
                    <td class="px-4 py-3">{{ $t->user?->username ?? '—' }}</td>
                    <td class="px-4 py-3 font-medium">{{ Str::limit($t->subject, 50) }}</td>
                    <td class="px-4 py-3 text-gray-400">{{ $t->category }}</td>
                    <td class="px-4 py-3">
                        @php $pc = ['low' => 'gray', 'medium' => 'yellow', 'high' => 'red']; $pColor = $pc[$t->priority] ?? 'gray'; @endphp
                        <span class="px-2 py-0.5 text-xs rounded-full bg-{{ $pColor }}-900/50 text-{{ $pColor }}-400 border border-{{ $pColor }}-700">{{ $t->priority }}</span>
                    </td>
                    <td class="px-4 py-3">
                        @php $sc = ['open' => 'blue', 'in_progress' => 'yellow', 'waiting_customer' => 'purple', 'resolved' => 'green', 'closed' => 'gray']; $sColor = $sc[$t->status] ?? 'gray'; @endphp
                        <span class="px-2 py-0.5 text-xs rounded-full bg-{{ $sColor }}-900/50 text-{{ $sColor }}-400 border border-{{ $sColor }}-700">{{ $t->status }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-400">{{ $t->created_at->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">Aucun ticket.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $tickets->links() }}</div>
@endsection
