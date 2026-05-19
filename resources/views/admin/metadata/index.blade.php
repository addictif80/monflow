@extends('layouts.admin')
@section('title', 'Gestion des metadonnees — Admin MonFlow')
@section('content')
<div class="mb-6">
    <h1 class="text-base font-semibold text-zinc-100">Gestion des metadonnees</h1>
    <p class="text-sm text-zinc-500 mt-0.5">Édition des tags audio via Navidrome</p>
</div>

<form method="GET" class="mb-6 flex gap-3">
    <input name="q" value="{{ $q ?? '' }}" placeholder="Rechercher un titre, artiste..."
           class="flex-1 bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
    <button class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Rechercher</button>
</form>

@if($q)
<div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-zinc-800">
                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Titre</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Artiste</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Album</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Genre</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Piste</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Annee</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-800/50">
        @forelse($songs as $s)
            <tr class="hover:bg-zinc-800/30 transition">
                <td class="px-4 py-3 text-zinc-200">{{ $s['title'] ?? '—' }}</td>
                <td class="px-4 py-3 text-zinc-500">{{ $s['artist'] ?? '—' }}</td>
                <td class="px-4 py-3 text-zinc-500">{{ $s['album'] ?? '—' }}</td>
                <td class="px-4 py-3 text-zinc-500">{{ $s['genre'] ?? '—' }}</td>
                <td class="px-4 py-3 text-zinc-500">{{ $s['trackNumber'] ?? '—' }}</td>
                <td class="px-4 py-3 text-zinc-500">{{ $s['year'] ?? '—' }}</td>
                <td class="px-4 py-3">
                    <a href="/admin/metadata/{{ $s['id'] }}/edit" class="text-indigo-400 hover:text-indigo-300 text-xs">Modifier</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="px-4 py-6 text-center text-zinc-600">Aucun resultat.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@else
<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
    <h2 class="text-sm font-medium text-zinc-300 mb-4">Guide d'utilisation</h2>
    <div class="text-sm text-zinc-400 space-y-3">
        <p><strong class="text-zinc-300">1. Rechercher</strong> — Tapez le titre ou l'artiste d'une chanson.</p>
        <p><strong class="text-zinc-300">2. Modifier</strong> — Cliquez sur "Modifier" pour editer les metadonnees (titre, artiste, album, genre, piste, annee...).</p>
        <p><strong class="text-zinc-300">3. Enregistrer</strong> — Les modifications sont appliquees directement dans Navidrome.</p>
    </div>
</div>
@endif
@endsection
