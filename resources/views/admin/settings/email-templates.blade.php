@extends('layouts.admin')
@section('title', 'Templates email — Admin MonFlow')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Templates email</h1>
    <a href="/admin/settings/email-templates/create" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-medium">+ Nouveau template</a>
</div>
<div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
    <table class="w-full text-sm">
        <thead><tr class="border-b border-gray-700 text-left text-gray-400">
            <th class="px-4 py-3">Type</th>
            <th class="px-4 py-3">Sujet</th>
            <th class="px-4 py-3">Statut</th>
            <th class="px-4 py-3">Actions</th>
        </tr></thead>
        <tbody>
        @forelse($templates as $t)
            <tr class="border-b border-gray-700/50 hover:bg-gray-700">
                <td class="px-4 py-3 font-mono">{{ $t->template_type }}</td>
                <td class="px-4 py-3">{{ $t->subject }}</td>
                <td class="px-4 py-3">
                    @if($t->is_active)
                        <span class="px-2 py-0.5 text-xs rounded-full bg-green-900/50 text-green-400 border border-green-700">active</span>
                    @else
                        <span class="px-2 py-0.5 text-xs rounded-full bg-gray-700 text-gray-400">inactive</span>
                    @endif
                </td>
                <td class="px-4 py-3"><a href="/admin/settings/email-templates/{{ $t->id }}" class="text-indigo-400 hover:text-indigo-300">Modifier</a></td>
            </tr>
        @empty
            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">Aucun template. <a href="/admin/settings/email-templates/create" class="text-indigo-400">Créer</a> ou exécuter <code class="bg-gray-700 px-1 rounded">php artisan setup:email-templates</code>.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
