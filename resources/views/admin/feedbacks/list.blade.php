@extends('layouts.admin')
@section('title', 'Feedbacks — Admin MonFlow')
@section('content')
<div class="mb-6">
    <h1 class="text-base font-semibold text-zinc-100">Feedbacks utilisateurs</h1>
    <p class="text-sm text-zinc-500 mt-0.5">Retours et suggestions des utilisateurs</p>
</div>

<div class="mb-3 flex gap-2 flex-wrap">
    @php
        $statuses = ['' => 'Tous', 'new' => 'Nouveaux', 'reviewed' => 'Examinés', 'in_progress' => 'En cours', 'resolved' => 'Résolus', 'dismissed' => 'Rejetés'];
        $types = ['' => 'Tous types', 'bug' => 'Bug', 'suggestion' => 'Suggestion', 'ui' => 'Interface', 'performance' => 'Performance', 'other' => 'Autre'];
    @endphp
    @foreach($statuses as $val => $label)
        <a href="/admin/feedbacks{{ $val ? '?status='.$val.($typeFilter ? '&type='.$typeFilter : '') : ($typeFilter ? '?type='.$typeFilter : '') }}"
           class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-medium transition {{ $statusFilter === $val ? 'bg-indigo-600 text-white' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700 border border-zinc-700' }}">{{ $label }}</a>
    @endforeach
</div>
<div class="mb-4 flex gap-2 flex-wrap">
    @foreach($types as $val => $label)
        <a href="/admin/feedbacks{{ $val ? '?type='.$val.($statusFilter ? '&status='.$statusFilter : '') : ($statusFilter ? '?status='.$statusFilter : '') }}"
           class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-medium transition {{ $typeFilter === $val ? 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700 border border-zinc-700' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-800">
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Utilisateur</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Sujet</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Statut</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Ticket</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800/50">
            @php
                $typeLabels = ['bug' => 'Bug', 'suggestion' => 'Suggestion', 'ui' => 'Interface', 'performance' => 'Performance', 'other' => 'Autre'];
                $statusLabels = ['new' => 'Nouveau', 'reviewed' => 'Examiné', 'in_progress' => 'En cours', 'resolved' => 'Résolu', 'dismissed' => 'Rejeté'];
            @endphp
            @forelse($feedbacks as $f)
                <tr class="hover:bg-zinc-800/30 transition">
                    <td class="px-4 py-3"><a href="/admin/users/{{ $f->user_id }}" class="text-indigo-400 hover:text-indigo-300">{{ $f->user?->username ?? '—' }}</a></td>
                    <td class="px-4 py-3">
                        @if($f->type === 'bug')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20">{{ $typeLabels[$f->type] ?? $f->type }}</span>
                        @elseif($f->type === 'suggestion')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">{{ $typeLabels[$f->type] ?? $f->type }}</span>
                        @elseif($f->type === 'performance')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-500/10 text-yellow-400 border border-yellow-500/20">{{ $typeLabels[$f->type] ?? $f->type }}</span>
                        @else
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-800 text-zinc-500 border border-zinc-700">{{ $typeLabels[$f->type] ?? $f->type }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-zinc-300">{{ Str::limit($f->subject, 50) }}</td>
                    <td class="px-4 py-3">
                        @if($f->status === 'new')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">{{ $statusLabels[$f->status] ?? $f->status }}</span>
                        @elseif($f->status === 'reviewed')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-500/10 text-yellow-400 border border-yellow-500/20">{{ $statusLabels[$f->status] ?? $f->status }}</span>
                        @elseif($f->status === 'in_progress')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">{{ $statusLabels[$f->status] ?? $f->status }}</span>
                        @elseif($f->status === 'resolved')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">{{ $statusLabels[$f->status] ?? $f->status }}</span>
                        @else
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-800 text-zinc-500 border border-zinc-700">{{ $statusLabels[$f->status] ?? $f->status }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($f->ticket_id)
                            <a href="/admin/tickets/{{ $f->ticket_id }}" class="text-indigo-400 hover:text-indigo-300 text-xs">Ticket</a>
                        @else
                            <span class="text-zinc-600 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-zinc-500">{{ $f->created_at->format('d/m/Y') }}</td>
                    <td class="px-4 py-3">
                        <a href="/admin/feedbacks/{{ $f->id }}" class="text-indigo-400 hover:text-indigo-300 text-xs">Gérer</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-6 text-center text-zinc-600">Aucun feedback.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $feedbacks->appends(request()->query())->links() }}</div>
@endsection
