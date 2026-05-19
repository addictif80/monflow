@extends('layouts.admin')
@section('title', 'Gestion des paroles — Admin MonFlow')
@section('content')
<div class="mb-6">
    <h1 class="text-base font-semibold text-zinc-100">Gestion des paroles</h1>
    <p class="text-sm text-zinc-500 mt-0.5">Édition des fichiers LRC synchronisés</p>
</div>

<form method="GET" class="mb-6 flex gap-3">
    <input name="q" value="{{ $q ?? '' }}" placeholder="Rechercher un titre..."
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
                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Durée</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-800/50">
        @forelse($songs as $s)
            <tr class="hover:bg-zinc-800/30 transition">
                <td class="px-4 py-3 text-zinc-200">{{ $s['title'] ?? '—' }}</td>
                <td class="px-4 py-3 text-zinc-500">{{ $s['artist'] ?? '—' }}</td>
                <td class="px-4 py-3 text-zinc-500">{{ $s['album'] ?? '—' }}</td>
                <td class="px-4 py-3 text-zinc-500">{{ gmdate('i:s', $s['duration'] ?? 0) }}</td>
                <td class="px-4 py-3">
                    <a href="/admin/lyrics/{{ $s['id'] }}/edit" class="text-indigo-400 hover:text-indigo-300 text-xs">Éditer paroles</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-4 py-6 text-center text-zinc-600">Aucun résultat.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@else
<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
    <h2 class="text-sm font-medium text-zinc-300 mb-4">Guide d'utilisation</h2>
    <div class="text-sm text-zinc-400 space-y-3">
        <p><strong class="text-zinc-300">1. Rechercher</strong> — Tapez le titre d'une chanson dans la barre de recherche.</p>
        <p><strong class="text-zinc-300">2. Éditer</strong> — Cliquez sur "Éditer paroles" pour ouvrir l'éditeur LRC.</p>
        <p><strong class="text-zinc-300">3. Format LRC</strong> — Les paroles synchronisées utilisent le format LRC :</p>
        <pre class="bg-zinc-950 border border-zinc-800 rounded-lg p-3 text-xs text-zinc-500 overflow-x-auto">[00:12.00]Première ligne de paroles
[00:17.50]Deuxième ligne de paroles
[00:23.80]Troisième ligne...</pre>
        <p>Chaque ligne commence par un timestamp <code class="bg-zinc-800 px-1 rounded text-zinc-400">[mm:ss.xx]</code> suivi du texte.</p>
        <p><strong class="text-zinc-300">4. Astuce</strong> — Dans l'éditeur, écoutez la musique et appuyez sur le bouton d'horodatage pour marquer automatiquement le timing de chaque ligne.</p>
        <p><strong class="text-zinc-300">5. Enregistrer</strong> — Les paroles sont sauvées en fichier .lrc à côté du fichier audio. Un scan Navidrome les prendra en compte.</p>
    </div>
</div>
@endif
@endsection
