@extends('layouts.app')
@section('title', 'Mes feedbacks — MonFlow')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Mes feedbacks</h1>
    <a href="/portal/feedback/create" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-medium transition">Nouveau feedback</a>
</div>

<div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
    <table class="w-full text-sm">
        <thead><tr class="border-b border-gray-700 text-left text-gray-400">
            <th class="px-4 py-3">Type</th>
            <th class="px-4 py-3">Sujet</th>
            <th class="px-4 py-3">Statut</th>
            <th class="px-4 py-3">Date</th>
        </tr></thead>
        <tbody>
        @php
            $typeLabels = ['bug' => 'Bug', 'suggestion' => 'Suggestion', 'ui' => 'Interface', 'performance' => 'Performance', 'other' => 'Autre'];
            $statusLabels = ['new' => 'Nouveau', 'reviewed' => 'Examiné', 'in_progress' => 'En cours', 'resolved' => 'Résolu', 'dismissed' => 'Rejeté'];
            $statusColors = ['new' => 'blue', 'reviewed' => 'yellow', 'in_progress' => 'indigo', 'resolved' => 'green', 'dismissed' => 'gray'];
        @endphp
        @forelse($feedbacks as $f)
            <tr class="border-b border-gray-700/50 hover:bg-gray-700">
                <td class="px-4 py-3 text-gray-400">{{ $typeLabels[$f->type] ?? $f->type }}</td>
                <td class="px-4 py-3"><a href="/portal/feedback/{{ $f->id }}" class="text-indigo-400 hover:text-indigo-300">{{ $f->subject }}</a></td>
                <td class="px-4 py-3">
                    @php $sc = $statusColors[$f->status] ?? 'gray'; @endphp
                    <span class="px-2 py-0.5 text-xs rounded-full bg-{{ $sc }}-900/50 text-{{ $sc }}-400 border border-{{ $sc }}-700">{{ $statusLabels[$f->status] ?? $f->status }}</span>
                </td>
                <td class="px-4 py-3 text-gray-400">{{ $f->created_at->format('d/m/Y H:i') }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">Aucun feedback envoyé.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
