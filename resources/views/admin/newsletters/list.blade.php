@extends('layouts.admin')
@section('title', 'Newsletters — Admin MonFlow')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Newsletters</h1>
    <a href="/admin/newsletters/create" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-medium">+ Nouvelle campagne</a>
</div>

<p class="text-sm text-gray-400 mb-4">{{ \App\Models\User::where('is_admin', false)->where('status', '!=', 'deleted')->where('newsletter_optin', true)->whereNotNull('email_verified_at')->count() }} abonnés actifs</p>

<div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
    <table class="w-full text-sm">
        <thead><tr class="border-b border-gray-700 text-left text-gray-400">
            <th class="px-4 py-3">Sujet</th>
            <th class="px-4 py-3">Statut</th>
            <th class="px-4 py-3">Destinataires</th>
            <th class="px-4 py-3">Date</th>
            <th class="px-4 py-3">Actions</th>
        </tr></thead>
        <tbody>
        @forelse($newsletters as $nl)
            <tr class="border-b border-gray-700/50 hover:bg-gray-700">
                <td class="px-4 py-3">{{ Str::limit($nl->subject, 60) }}</td>
                <td class="px-4 py-3">
                    @php $sc = ['draft' => 'yellow', 'sending' => 'blue', 'sent' => 'green'][$nl->status] ?? 'gray'; @endphp
                    <span class="px-2 py-0.5 text-xs rounded-full bg-{{ $sc }}-900/50 text-{{ $sc }}-400 border border-{{ $sc }}-700">{{ ['draft' => 'Brouillon', 'sending' => 'En cours', 'sent' => 'Envoyée'][$nl->status] ?? $nl->status }}</span>
                </td>
                <td class="px-4 py-3 text-gray-400">{{ $nl->recipients_count }}</td>
                <td class="px-4 py-3 text-gray-400">{{ $nl->sent_at?->format('d/m/Y H:i') ?? $nl->created_at->format('d/m/Y') }}</td>
                <td class="px-4 py-3 flex gap-2">
                    <a href="/admin/newsletters/{{ $nl->id }}/preview" target="_blank" class="text-gray-400 hover:text-gray-200 text-xs">Aperçu</a>
                    @if($nl->status === 'draft')
                        <a href="/admin/newsletters/{{ $nl->id }}/edit" class="text-indigo-400 hover:text-indigo-300 text-xs">Modifier</a>
                        <form action="/admin/newsletters/{{ $nl->id }}/send" method="POST" onsubmit="return confirm('Envoyer cette campagne à tous les abonnés ?')" class="inline">
                            @csrf
                            <button class="text-green-400 hover:text-green-300 text-xs">Envoyer</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Aucune newsletter.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $newsletters->links() }}</div>
@endsection
