@extends('layouts.admin')
@section('title', 'Abonnements — Admin MonFlow')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Abonnements</h1>
</div>

<div class="mb-4 flex gap-2 flex-wrap">
    @php $statuses = ['' => 'Tous', 'active' => 'Actifs', 'pending' => 'En attente', 'suspended' => 'Suspendus', 'cancelled' => 'Résiliés']; @endphp
    @foreach($statuses as $val => $label)
        <a href="/admin/subscriptions{{ $val ? '?status='.$val : '' }}" class="px-3 py-1 rounded text-sm {{ ($statusFilter ?? '') === $val ? 'bg-indigo-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="border-b border-gray-700 text-left text-gray-400">
                <th class="px-4 py-3">Utilisateur</th>
                <th class="px-4 py-3">Formule</th>
                <th class="px-4 py-3">Statut</th>
                <th class="px-4 py-3">Fin période</th>
                <th class="px-4 py-3">Actions</th>
            </tr></thead>
            <tbody>
            @forelse($subscriptions as $s)
                <tr class="border-b border-gray-700/50 hover:bg-gray-700">
                    <td class="px-4 py-3">
                        <a href="/admin/users/{{ $s->user_id }}" class="text-indigo-400 hover:text-indigo-300">{{ $s->user?->username ?? '—' }}</a>
                    </td>
                    <td class="px-4 py-3">{{ $s->plan?->name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @php $colors = ['active' => 'green', 'pending' => 'yellow', 'suspended' => 'red', 'cancelled' => 'gray']; $c = $colors[$s->status] ?? 'gray'; @endphp
                        <span class="px-2 py-0.5 text-xs rounded-full bg-{{ $c }}-900/50 text-{{ $c }}-400 border border-{{ $c }}-700">{{ $s->status }}</span>
                        @if($s->is_gift) <span class="text-xs text-emerald-400 ml-1">cadeau</span> @endif
                    </td>
                    <td class="px-4 py-3 text-gray-400">
                        @if($s->current_period_end)
                            {{ $s->current_period_end->format('d/m/Y') }}
                            @if($s->status === 'active' && $s->current_period_end->isPast())
                                <span class="text-red-400 text-xs ml-1">expiré</span>
                            @elseif($s->status === 'active' && $s->current_period_end->diffInDays(now()) <= 7)
                                <span class="text-yellow-400 text-xs ml-1">{{ $s->current_period_end->diffInDays(now()) }}j restants</span>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <a href="/admin/subscriptions/{{ $s->id }}" class="text-indigo-400 hover:text-indigo-300 text-xs">Gérer</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Aucun abonnement.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $subscriptions->appends(request()->query())->links() }}</div>

{{-- Create subscription --}}
<div class="mt-8 bg-gray-800 border border-gray-700 rounded-lg p-6">
    <h2 class="text-lg font-semibold mb-4">Créer un abonnement manuellement</h2>
    <form action="/admin/subscriptions/create" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        @csrf
        <div>
            <label class="block text-sm text-gray-400 mb-1">Utilisateur (ID)</label>
            <input type="text" name="user_id" placeholder="UUID de l'utilisateur" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-sm text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        </div>
        <div>
            <label class="block text-sm text-gray-400 mb-1">Formule</label>
            <select name="plan_id" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-sm text-gray-100">
                @foreach(\App\Models\Plan::where('is_active', true)->orderBy('sort_order')->get() as $p)
                    <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->price }}€)</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm text-gray-400 mb-1">Durée (mois)</label>
            <input type="number" name="months" value="1" min="1" max="24" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-sm text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        </div>
        <div>
            <button type="submit" class="w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium">Créer</button>
        </div>
    </form>
</div>
@endsection
