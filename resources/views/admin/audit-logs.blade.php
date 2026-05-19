@extends('layouts.admin')

@section('title', 'Journal d\'audit — Admin MonFlow')

@section('content')
<div class="mb-6">
    <h1 class="text-base font-semibold text-zinc-100">Journal d'audit</h1>
    <p class="text-sm text-zinc-500 mt-0.5">Historique des actions administratives</p>
</div>

<div class="mb-4 flex gap-2 flex-wrap">
    @php $filters = ['user' => 'Utilisateurs', 'plan' => 'Formules', 'promo' => 'Promos', 'refund' => 'Remboursements', 'wallet' => 'Portefeuille']; @endphp
    <a href="/admin/audit-logs"
       class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-medium transition {{ !request('action') ? 'bg-indigo-600 text-white' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700 border border-zinc-700' }}">Tout</a>
    @foreach($filters as $key => $label)
        <a href="/admin/audit-logs?action={{ $key }}"
           class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-medium transition {{ request('action') === $key ? 'bg-indigo-600 text-white' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700 border border-zinc-700' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-800">
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Admin</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Action</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Cible</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Détails</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800/50">
            @forelse($logs as $log)
                <tr class="hover:bg-zinc-800/30 transition">
                    <td class="px-4 py-3 text-zinc-500 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3 text-zinc-300">{{ $log->admin->username ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @php
                            $parts = explode('.', $log->action);
                            $verb = $parts[1] ?? $parts[0];
                        @endphp
                        @if(in_array($verb, ['create', 'reactivate']))
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">{{ $log->action }}</span>
                        @elseif(in_array($verb, ['edit', 'adjust']))
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">{{ $log->action }}</span>
                        @elseif(in_array($verb, ['delete']))
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20">{{ $log->action }}</span>
                        @elseif(in_array($verb, ['suspend']))
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-500/10 text-yellow-400 border border-yellow-500/20">{{ $log->action }}</span>
                        @else
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-800 text-zinc-500 border border-zinc-700">{{ $log->action }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-zinc-500">
                        @if($log->target_id)
                            {{ class_basename($log->target_type ?? '') }} #{{ substr($log->target_id, 0, 8) }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3 text-zinc-600 text-xs">
                        @if($log->details)
                            {{ json_encode($log->details, JSON_UNESCAPED_UNICODE) }}
                        @endif
                    </td>
                    <td class="px-4 py-3 text-zinc-600 text-xs">{{ $log->ip_address }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-center text-zinc-600">Aucun log.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $logs->appends(request()->query())->links() }}</div>
@endsection
