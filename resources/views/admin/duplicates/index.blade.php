@extends('layouts.admin')
@section('title', 'Gestion des doublons — Admin MonFlow')
@section('content')
<h1 class="text-2xl font-bold mb-6">Gestion des doublons</h1>

<div class="mb-6 flex items-center gap-4">
    <form method="GET">
        <input type="hidden" name="scan" value="1">
        <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-medium">Scanner la bibliotheque</button>
    </form>
    @if($scanned)
    <span class="text-sm text-gray-400">{{ count($duplicates) }} groupe(s) de doublons detecte(s)</span>
    @endif
</div>

@if($scanned && count($duplicates) === 0)
<div class="bg-green-900/30 border border-green-700 rounded-lg p-4 text-green-300 text-sm">
    Aucun doublon detecte dans la bibliotheque.
</div>
@endif

@foreach($duplicates as $i => $group)
<div class="bg-gray-800 border border-gray-700 rounded-lg mb-4 overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-700 flex items-center gap-3">
        <span class="text-sm font-semibold">{{ $group[0]['title'] ?? '—' }}</span>
        <span class="text-xs text-gray-400">{{ $group[0]['artist'] ?? '' }}</span>
        <span class="text-xs px-2 py-0.5 bg-amber-900/50 text-amber-300 rounded">{{ count($group) }} copies</span>
    </div>
    <table class="w-full text-sm">
        <thead><tr class="text-left text-gray-500 text-xs">
            <th class="px-4 py-2">Album</th>
            <th class="px-4 py-2">Duree</th>
            <th class="px-4 py-2">Format</th>
            <th class="px-4 py-2">Bitrate</th>
            <th class="px-4 py-2">Taille</th>
            <th class="px-4 py-2">Chemin</th>
            <th class="px-4 py-2">Actions</th>
        </tr></thead>
        <tbody>
        @foreach($group as $j => $s)
            <tr class="border-t border-gray-700/50 hover:bg-gray-700/30">
                <td class="px-4 py-2 text-gray-300">{{ $s['album'] ?? '—' }}</td>
                <td class="px-4 py-2 text-gray-400">{{ gmdate('i:s', $s['duration'] ?? 0) }}</td>
                <td class="px-4 py-2 text-gray-400">{{ strtoupper($s['suffix'] ?? '—') }}</td>
                <td class="px-4 py-2 text-gray-400">{{ $s['bitRate'] ?? '—' }} kbps</td>
                <td class="px-4 py-2 text-gray-400">{{ number_format(($s['size'] ?? 0) / 1048576, 1) }} Mo</td>
                <td class="px-4 py-2 text-gray-500 text-xs max-w-xs truncate" title="{{ $s['path'] ?? '' }}">{{ $s['path'] ?? '—' }}</td>
                <td class="px-4 py-2">
                    <div class="flex gap-2">
                        <a href="/admin/metadata/{{ $s['id'] }}/edit" class="text-indigo-400 hover:text-indigo-300 text-xs">Modifier</a>
                        <form method="POST" action="/admin/duplicates/{{ $s['id'] }}/delete" onsubmit="return confirm('Supprimer definitivement cette copie ?')">
                            @csrf
                            <button class="text-red-400 hover:text-red-300 text-xs">Supprimer</button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endforeach

@if(!$scanned)
<div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
    <h2 class="text-lg font-semibold mb-4">Comment ca fonctionne</h2>
    <div class="text-sm text-gray-300 space-y-3">
        <p><strong>1. Scanner</strong> — Cliquez sur "Scanner la bibliotheque" pour analyser toutes les chansons.</p>
        <p><strong>2. Identifier</strong> — Les doublons sont detectes par correspondance exacte du titre et de l'artiste.</p>
        <p><strong>3. Comparer</strong> — Pour chaque groupe, comparez le format, le bitrate et la taille pour choisir la meilleure version.</p>
        <p><strong>4. Nettoyer</strong> — Supprimez les copies inferieures. La suppression retire le fichier de Navidrome.</p>
    </div>
</div>
@endif
@endsection
