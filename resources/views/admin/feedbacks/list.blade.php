@extends('layouts.admin')
@section('title', 'Feedbacks — Admin MonFlow')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Feedbacks utilisateurs</h1>
</div>

<div class="mb-4 flex gap-2 flex-wrap">
    @php
        $statuses = ['' => 'Tous', 'new' => 'Nouveaux', 'reviewed' => 'Examinés', 'in_progress' => 'En cours', 'resolved' => 'Résolus', 'dismissed' => 'Rejetés'];
        $types = ['' => 'Tous types', 'bug' => 'Bug', 'suggestion' => 'Suggestion', 'ui' => 'Interface', 'performance' => 'Performance', 'other' => 'Autre'];
    @endphp
    @foreach($statuses as $val => $label)
        <a href="/admin/feedbacks{{ $val ? '?status='.$val.($typeFilter ? '&type='.$typeFilter : '') : ($typeFilter ? '?type='.$typeFilter : '') }}" class="px-3 py-1 rounded text-sm {{ $statusFilter === $val ? 'bg-indigo-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">{{ $label }}</a>
    @endforeach
</div>
<div class="mb-4 flex gap-2 flex-wrap">
    @foreach($types as $val => $label)
        <a href="/admin/feedbacks{{ $val ? '?type='.$val.($statusFilter ? '&status='.$statusFilter : '') : ($statusFilter ? '?status='.$statusFilter : '') }}" class="px-3 py-1 rounded text-sm {{ $typeFilter === $val ? 'bg-yellow-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="border-b border-gray-700 text-left text-gray-400">
                <th class="px-4 py-3">Utilisateur</th>
                <th class="px-4 py-3">Type</th>
                <th class="px-4 py-3">Sujet</th>
                <th class="px-4 py-3">Statut</th>
                <th class="px-4 py-3">Ticket</th>
                <th class="px-4 py-3">Date</th>
                <th class="px-4 py-3">Actions</th>
            </tr></thead>
            <tbody>
            @php
                $typeLabels = ['bug' => 'Bug', 'suggestion' => 'Suggestion', 'ui' => 'Interface', 'performance' => 'Performance', 'other' => 'Autre'];
                $typeColors = ['bug' => 'red', 'suggestion' => 'blue', 'ui' => 'purple', 'performance' => 'yellow', 'other' => 'gray'];
                $statusLabels = ['new' => 'Nouveau', 'reviewed' => 'Examiné', 'in_progress' => 'En cours', 'resolved' => 'Résolu', 'dismissed' => 'Rejeté'];
                $statusColors = ['new' => 'blue', 'reviewed' => 'yellow', 'in_progress' => 'indigo', 'resolved' => 'green', 'dismissed' => 'gray'];
            @endphp
            @forelse($feedbacks as $f)
                <tr class="border-b border-gray-700/50 hover:bg-gray-700">
                    <td class="px-4 py-3"><a href="/admin/users/{{ $f->user_id }}" class="text-indigo-400 hover:text-indigo-300">{{ $f->user?->username ?? '—' }}</a></td>
                    <td class="px-4 py-3">
                        @php $tc = $typeColors[$f->type] ?? 'gray'; @endphp
                        <span class="px-2 py-0.5 text-xs rounded-full bg-{{ $tc }}-900/50 text-{{ $tc }}-400 border border-{{ $tc }}-700">{{ $typeLabels[$f->type] ?? $f->type }}</span>
                    </td>
                    <td class="px-4 py-3">{{ Str::limit($f->subject, 50) }}</td>
                    <td class="px-4 py-3">
                        @php $sc = $statusColors[$f->status] ?? 'gray'; @endphp
                        <span class="px-2 py-0.5 text-xs rounded-full bg-{{ $sc }}-900/50 text-{{ $sc }}-400 border border-{{ $sc }}-700">{{ $statusLabels[$f->status] ?? $f->status }}</span>
                    </td>
                    <td class="px-4 py-3">
                        @if($f->ticket_id)
                            <a href="/admin/tickets/{{ $f->ticket_id }}" class="text-indigo-400 hover:text-indigo-300 text-xs">Ticket</a>
                        @else
                            <span class="text-gray-500 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-400">{{ $f->created_at->format('d/m/Y') }}</td>
                    <td class="px-4 py-3">
                        <a href="/admin/feedbacks/{{ $f->id }}" class="text-indigo-400 hover:text-indigo-300 text-xs">Gérer</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-6 text-center text-gray-500">Aucun feedback.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $feedbacks->appends(request()->query())->links() }}</div>
@endsection
