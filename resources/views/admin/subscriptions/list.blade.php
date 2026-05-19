@extends('layouts.admin')
@section('title', 'Abonnements — Admin MonFlow')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-base font-semibold text-zinc-100">Abonnements</h1>
        <p class="text-sm text-zinc-500 mt-0.5">Gestion des abonnements utilisateurs</p>
    </div>
</div>

<div class="mb-4 flex gap-2 flex-wrap">
    @php $statuses = ['' => 'Tous', 'active' => 'Actifs', 'pending' => 'En attente', 'suspended' => 'Suspendus', 'cancelled' => 'Résiliés']; @endphp
    @foreach($statuses as $val => $label)
        <a href="/admin/subscriptions{{ $val ? '?status='.$val : '' }}"
           class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-medium transition {{ ($statusFilter ?? '') === $val ? 'bg-indigo-600 text-white' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700 border border-zinc-700' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-800">
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Utilisateur</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Formule</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Statut</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Fin période</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800/50">
            @forelse($subscriptions as $s)
                <tr class="hover:bg-zinc-800/30 transition">
                    <td class="px-4 py-3">
                        <a href="/admin/users/{{ $s->user_id }}" class="text-indigo-400 hover:text-indigo-300">{{ $s->user?->username ?? '—' }}</a>
                    </td>
                    <td class="px-4 py-3 text-zinc-300">{{ $s->plan?->name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @if($s->status === 'active')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">{{ $s->status }}</span>
                        @elseif($s->status === 'pending')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-500/10 text-yellow-400 border border-yellow-500/20">{{ $s->status }}</span>
                        @elseif($s->status === 'suspended')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20">{{ $s->status }}</span>
                        @else
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-800 text-zinc-500 border border-zinc-700">{{ $s->status }}</span>
                        @endif
                        @if($s->is_gift) <span class="text-xs text-emerald-400 ml-1">cadeau</span> @endif
                    </td>
                    <td class="px-4 py-3 text-zinc-500">
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
                <tr><td colspan="5" class="px-4 py-6 text-center text-zinc-600">Aucun abonnement.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $subscriptions->appends(request()->query())->links() }}</div>

{{-- Create subscription --}}
<div class="mt-8 bg-zinc-900 border border-zinc-800 rounded-xl p-5">
    <h2 class="text-sm font-medium text-zinc-300 mb-4">Créer un abonnement manuellement</h2>
    <form action="/admin/subscriptions/create" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        @csrf
        <div>
            <label class="block text-xs font-medium text-zinc-400 mb-1.5">Utilisateur (ID)</label>
            <input type="text" name="user_id" placeholder="UUID de l'utilisateur"
                   class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
        </div>
        <div>
            <label class="block text-xs font-medium text-zinc-400 mb-1.5">Formule</label>
            <select name="plan_id" class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
                @foreach(\App\Models\Plan::where('is_active', true)->orderBy('sort_order')->get() as $p)
                    <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->price }}€)</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-zinc-400 mb-1.5">Durée (mois)</label>
            <input type="number" name="months" value="1" min="1" max="24"
                   class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
        </div>
        <div>
            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Créer</button>
        </div>
    </form>
</div>
@endsection
