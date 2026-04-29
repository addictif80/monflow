@extends('layouts.admin')
@section('title', 'Gestion des metadonnees — Admin MonFlow')
@section('content')
<h1 class="text-2xl font-bold mb-6">Gestion des metadonnees</h1>

<form method="GET" class="mb-6 flex gap-3">
    <input name="q" value="{{ $q ?? '' }}" placeholder="Rechercher un titre, artiste..." class="flex-1 px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
    <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-medium">Rechercher</button>
</form>

@if($q)
<div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
    <table class="w-full text-sm">
        <thead><tr class="border-b border-gray-700 text-left text-gray-400">
            <th class="px-4 py-3">Titre</th>
            <th class="px-4 py-3">Artiste</th>
            <th class="px-4 py-3">Album</th>
            <th class="px-4 py-3">Genre</th>
            <th class="px-4 py-3">Piste</th>
            <th class="px-4 py-3">Annee</th>
            <th class="px-4 py-3">Actions</th>
        </tr></thead>
        <tbody>
        @forelse($songs as $s)
            <tr class="border-b border-gray-700/50 hover:bg-gray-700">
                <td class="px-4 py-3">{{ $s['title'] ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-400">{{ $s['artist'] ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-400">{{ $s['album'] ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-400">{{ $s['genre'] ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-400">{{ $s['trackNumber'] ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-400">{{ $s['year'] ?? '—' }}</td>
                <td class="px-4 py-3">
                    <a href="/admin/metadata/{{ $s['id'] }}/edit" class="text-indigo-400 hover:text-indigo-300 text-xs">Modifier</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="px-4 py-6 text-center text-gray-500">Aucun resultat.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@else
<div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
    <h2 class="text-lg font-semibold mb-4">Guide d'utilisation</h2>
    <div class="text-sm text-gray-300 space-y-3">
        <p><strong>1. Rechercher</strong> — Tapez le titre ou l'artiste d'une chanson.</p>
        <p><strong>2. Modifier</strong> — Cliquez sur "Modifier" pour editer les metadonnees (titre, artiste, album, genre, piste, annee...).</p>
        <p><strong>3. Enregistrer</strong> — Les modifications sont appliquees directement dans Navidrome.</p>
    </div>
</div>
@endif
@endsection
