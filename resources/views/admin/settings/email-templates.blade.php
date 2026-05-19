@extends('layouts.admin')
@section('title', 'Templates email — Admin MonFlow')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-base font-semibold text-zinc-100">Templates email</h1>
        <p class="text-sm text-zinc-500 mt-0.5">Personnalisation des emails transactionnels</p>
    </div>
    <a href="/admin/settings/email-templates/create" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">+ Nouveau template</a>
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-zinc-800">
                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Type</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Sujet</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Statut</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-800/50">
        @forelse($templates as $t)
            <tr class="hover:bg-zinc-800/30 transition">
                <td class="px-4 py-3 font-mono text-zinc-400 text-xs">{{ $t->template_type }}</td>
                <td class="px-4 py-3 text-zinc-300">{{ $t->subject }}</td>
                <td class="px-4 py-3">
                    @if($t->is_active)
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">active</span>
                    @else
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-800 text-zinc-500 border border-zinc-700">inactive</span>
                    @endif
                </td>
                <td class="px-4 py-3"><a href="/admin/settings/email-templates/{{ $t->id }}" class="text-indigo-400 hover:text-indigo-300 text-xs">Modifier</a></td>
            </tr>
        @empty
            <tr><td colspan="4" class="px-4 py-6 text-center text-zinc-600">Aucun template. <a href="/admin/settings/email-templates/create" class="text-indigo-400 hover:text-indigo-300">Créer</a> ou exécuter <code class="bg-zinc-800 px-1 rounded text-zinc-400">php artisan setup:email-templates</code>.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
