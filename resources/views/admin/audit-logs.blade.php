@extends('layouts.admin')

@section('title', 'Journal d\'audit — Admin MonFlow')

@section('content')
<h1 class="text-2xl font-bold mb-6">Journal d'audit</h1>

<div class="mb-4 flex gap-2 flex-wrap">
    @php $filters = ['user' => 'Utilisateurs', 'plan' => 'Formules', 'promo' => 'Promos', 'refund' => 'Remboursements', 'wallet' => 'Portefeuille']; @endphp
    <a href="/admin/audit-logs" class="px-3 py-1 rounded text-sm {{ !request('action') ? 'bg-indigo-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">Tout</a>
    @foreach($filters as $key => $label)
        <a href="/admin/audit-logs?action={{ $key }}" class="px-3 py-1 rounded text-sm {{ request('action') === $key ? 'bg-indigo-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="border-b border-gray-700 text-left text-gray-400">
                <th class="px-4 py-3">Date</th>
                <th class="px-4 py-3">Admin</th>
                <th class="px-4 py-3">Action</th>
                <th class="px-4 py-3">Cible</th>
                <th class="px-4 py-3">Détails</th>
                <th class="px-4 py-3">IP</th>
            </tr></thead>
            <tbody>
            @forelse($logs as $log)
                <tr class="border-b border-gray-700/50 hover:bg-gray-700">
                    <td class="px-4 py-3 text-gray-400 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3">{{ $log->admin->username ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @php
                            $colors = ['create' => 'green', 'edit' => 'blue', 'delete' => 'red', 'suspend' => 'yellow', 'reactivate' => 'green', 'release_email' => 'orange', 'adjust' => 'purple'];
                            $parts = explode('.', $log->action);
                            $verb = $parts[1] ?? $parts[0];
                            $c = $colors[$verb] ?? 'gray';
                        @endphp
                        <span class="px-2 py-0.5 text-xs rounded-full bg-{{ $c }}-900/50 text-{{ $c }}-400 border border-{{ $c }}-700">{{ $log->action }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-400">
                        @if($log->target_id)
                            {{ class_basename($log->target_type ?? '') }} #{{ substr($log->target_id, 0, 8) }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-400 text-xs">
                        @if($log->details)
                            {{ json_encode($log->details, JSON_UNESCAPED_UNICODE) }}
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $log->ip_address }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">Aucun log.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $logs->appends(request()->query())->links() }}</div>
@endsection
