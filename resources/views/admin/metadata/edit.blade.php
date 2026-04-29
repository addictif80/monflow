@extends('layouts.admin')
@section('title', 'Modifier metadonnees — Admin MonFlow')
@section('content')
<div class="mb-4"><a href="/admin/metadata" class="text-gray-400 hover:text-gray-200 text-sm">&larr; Retour</a></div>

<div class="flex items-center gap-4 mb-6">
    <h1 class="text-2xl font-bold">Modifier les metadonnees</h1>
    <span class="text-gray-400 text-sm">{{ $song['path'] ?? '' }}</span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <form method="POST" action="/admin/metadata/{{ $song['id'] }}/save">
            @csrf
            <div class="bg-gray-800 border border-gray-700 rounded-lg p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Titre</label>
                        <input type="text" name="title" value="{{ $song['title'] ?? '' }}" class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500" required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Artiste</label>
                        <input type="text" name="artist" value="{{ $song['artist'] ?? '' }}" class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Artiste de l'album</label>
                        <input type="text" name="albumArtist" value="{{ $song['albumArtist'] ?? '' }}" class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Album</label>
                        <input type="text" name="album" value="{{ $song['album'] ?? '' }}" class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Genre</label>
                        <input type="text" name="genre" value="{{ $song['genre'] ?? '' }}" class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Annee</label>
                        <input type="number" name="year" value="{{ $song['year'] ?? '' }}" min="0" max="9999" class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Numero de piste</label>
                        <input type="number" name="trackNumber" value="{{ $song['trackNumber'] ?? '' }}" min="0" class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-1">Numero de disque</label>
                        <input type="number" name="discNumber" value="{{ $song['discNumber'] ?? '' }}" min="0" class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1">Commentaire</label>
                    <textarea name="comment" rows="2" class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">{{ $song['comment'] ?? '' }}</textarea>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-medium">Enregistrer</button>
                    <a href="/admin/metadata" class="px-6 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm font-medium">Annuler</a>
                </div>
            </div>
        </form>
    </div>

    <div>
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-4 space-y-3 text-sm">
            <h3 class="font-semibold text-gray-300">Informations</h3>
            <div><span class="text-gray-500">ID :</span> <span class="text-gray-300 font-mono text-xs">{{ $song['id'] }}</span></div>
            <div><span class="text-gray-500">Duree :</span> {{ gmdate('i:s', $song['duration'] ?? 0) }}</div>
            <div><span class="text-gray-500">Format :</span> {{ strtoupper($song['suffix'] ?? '—') }}</div>
            <div><span class="text-gray-500">Bitrate :</span> {{ $song['bitRate'] ?? '—' }} kbps</div>
            <div><span class="text-gray-500">Taille :</span> {{ number_format(($song['size'] ?? 0) / 1048576, 1) }} Mo</div>
            <div><span class="text-gray-500">Chemin :</span> <span class="text-xs text-gray-400 break-all">{{ $song['path'] ?? '—' }}</span></div>
        </div>

        @if(count($albumSongs) > 1)
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-4 mt-4">
            <h3 class="font-semibold text-gray-300 text-sm mb-3">Autres pistes de l'album</h3>
            <div class="space-y-1 max-h-64 overflow-y-auto">
                @foreach($albumSongs as $as)
                <a href="/admin/metadata/{{ $as['id'] }}/edit" class="block px-2 py-1 rounded text-xs hover:bg-gray-700 {{ $as['id'] === $song['id'] ? 'bg-gray-700 text-white' : 'text-gray-400' }}">
                    {{ $as['trackNumber'] ?? '?' }}. {{ $as['title'] ?? '—' }}
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
