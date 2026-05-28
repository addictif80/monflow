@extends('layouts.admin')
@section('title', 'Newsletters — Admin MonFlow')
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-base font-semibold text-zinc-100">Newsletters</h1>
        <p class="text-sm text-zinc-500 mt-0.5">{{ \App\Models\User::where('is_admin', false)->where('status', '!=', 'deleted')->where('newsletter_optin', true)->whereNotNull('email_verified_at')->count() }} abonnés actifs</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="/admin/newsletters/weekly-preview" target="_blank" class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-sm font-medium px-4 py-2 rounded-lg transition border border-zinc-700">Aperçu de la prochaine</a>
        <a href="/admin/newsletters/create" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">+ Nouvelle campagne</a>
    </div>
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-zinc-800">
                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Sujet</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Statut</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Destinataires</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-800/50">
        @forelse($newsletters as $nl)
            <tr class="hover:bg-zinc-800/30 transition">
                <td class="px-4 py-3 text-zinc-300">{{ Str::limit($nl->subject, 60) }}</td>
                <td class="px-4 py-3">
                    @if($nl->status === 'draft')
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-500/10 text-yellow-400 border border-yellow-500/20">Brouillon</span>
                    @elseif($nl->status === 'sending')
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">En cours</span>
                    @elseif($nl->status === 'sent')
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Envoyée</span>
                    @else
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-800 text-zinc-500 border border-zinc-700">{{ $nl->status }}</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-zinc-500">{{ $nl->recipients_count }}</td>
                <td class="px-4 py-3 text-zinc-500">{{ $nl->sent_at?->format('d/m/Y H:i') ?? $nl->created_at->format('d/m/Y') }}</td>
                <td class="px-4 py-3 flex gap-3">
                    <a href="/admin/newsletters/{{ $nl->id }}/preview" target="_blank" class="text-zinc-500 hover:text-zinc-300 text-xs">Aperçu</a>
                    @if($nl->status === 'draft')
                        <a href="/admin/newsletters/{{ $nl->id }}/edit" class="text-indigo-400 hover:text-indigo-300 text-xs">Modifier</a>
                        <form action="/admin/newsletters/{{ $nl->id }}/send" method="POST" onsubmit="return confirm('Envoyer cette campagne à tous les abonnés ?')" class="inline">
                            @csrf
                            <button class="text-emerald-400 hover:text-emerald-300 text-xs">Envoyer</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-4 py-6 text-center text-zinc-600">Aucune newsletter.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $newsletters->links() }}</div>
@endsection
