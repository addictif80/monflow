@extends('layouts.admin')
@section('title', 'Gestion des paroles — Admin MonFlow')
@section('content')
<h1 class="text-2xl font-bold mb-6">Gestion des paroles</h1>

<form method="GET" class="mb-6 flex gap-3">
    <input name="q" value="{{ $q ?? '' }}" placeholder="Rechercher un titre..." class="flex-1 px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
    <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-medium">Rechercher</button>
</form>

@if($q)
<div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
    <table class="w-full text-sm">
        <thead><tr class="border-b border-gray-700 text-left text-gray-400">
            <th class="px-4 py-3">Titre</th>
            <th class="px-4 py-3">Artiste</th>
            <th class="px-4 py-3">Album</th>
            <th class="px-4 py-3">Durée</th>
            <th class="px-4 py-3">Actions</th>
        </tr></thead>
        <tbody>
        @forelse($songs as $s)
            <tr class="border-b border-gray-700/50 hover:bg-gray-700">
                <td class="px-4 py-3">{{ $s['title'] ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-400">{{ $s['artist'] ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-400">{{ $s['album'] ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-400">{{ gmdate('i:s', $s['duration'] ?? 0) }}</td>
                <td class="px-4 py-3">
                    <a href="/admin/lyrics/{{ $s['id'] }}/edit" class="text-indigo-400 hover:text-indigo-300 text-xs">Éditer paroles</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">Aucun résultat.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@else
<div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
    <h2 class="text-lg font-semibold mb-4">Guide d'utilisation</h2>
    <div class="text-sm text-gray-300 space-y-3">
        <p><strong>1. Rechercher</strong> — Tapez le titre d'une chanson dans la barre de recherche.</p>
        <p><strong>2. Éditer</strong> — Cliquez sur "Éditer paroles" pour ouvrir l'éditeur LRC.</p>
        <p><strong>3. Format LRC</strong> — Les paroles synchronisées utilisent le format LRC :</p>
        <pre class="bg-gray-900 rounded p-3 text-xs text-gray-400 overflow-x-auto">[00:12.00]Première ligne de paroles
[00:17.50]Deuxième ligne de paroles
[00:23.80]Troisième ligne...</pre>
        <p>Chaque ligne commence par un timestamp <code class="bg-gray-900 px-1 rounded">[mm:ss.xx]</code> suivi du texte.</p>
        <p><strong>4. Astuce</strong> — Dans l'éditeur, écoutez la musique et appuyez sur le bouton d'horodatage pour marquer automatiquement le timing de chaque ligne.</p>
        <p><strong>5. Enregistrer</strong> — Les paroles sont sauvées en fichier .lrc à côté du fichier audio. Un scan Navidrome les prendra en compte.</p>
    </div>
</div>
@endif
@endsection
