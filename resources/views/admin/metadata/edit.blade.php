@extends('layouts.admin')
@section('title', 'Modifier metadonnees — Admin MonFlow')
@section('content')
<div class="mb-4">
    <a href="/admin/metadata" class="text-sm text-zinc-500 hover:text-zinc-300">&larr; Retour</a>
</div>

<div class="flex items-center gap-4 mb-6">
    <h1 class="text-base font-semibold text-zinc-100">Modifier les metadonnees</h1>
    <span class="text-zinc-500 text-xs font-mono truncate max-w-xs">{{ $song['path'] ?? '' }}</span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <form method="POST" action="/admin/metadata/{{ $song['id'] }}/save">
            @csrf
            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-zinc-400 mb-1.5">Titre</label>
                        <input type="text" name="title" value="{{ $song['title'] ?? '' }}" required
                               class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-400 mb-1.5">Artiste</label>
                        <input type="text" name="artist" value="{{ $song['artist'] ?? '' }}"
                               class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-400 mb-1.5">Artiste de l'album</label>
                        <input type="text" name="albumArtist" value="{{ $song['albumArtist'] ?? '' }}"
                               class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-400 mb-1.5">Album</label>
                        <input type="text" name="album" value="{{ $song['album'] ?? '' }}"
                               class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-400 mb-1.5">Genre</label>
                        <input type="text" name="genre" value="{{ $song['genre'] ?? '' }}"
                               class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-400 mb-1.5">Annee</label>
                        <input type="number" name="year" value="{{ $song['year'] ?? '' }}" min="0" max="9999"
                               class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-400 mb-1.5">Numero de piste</label>
                        <input type="number" name="trackNumber" value="{{ $song['trackNumber'] ?? '' }}" min="0"
                               class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-400 mb-1.5">Numero de disque</label>
                        <input type="number" name="discNumber" value="{{ $song['discNumber'] ?? '' }}" min="0"
                               class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-400 mb-1.5">Commentaire</label>
                    <textarea name="comment" rows="2" class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">{{ $song['comment'] ?? '' }}</textarea>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Enregistrer</button>
                    <a href="/admin/metadata" class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-sm font-medium px-4 py-2 rounded-lg border border-zinc-700 transition">Annuler</a>
                </div>
            </div>
        </form>
    </div>

    <div>
        <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4 space-y-2 text-sm">
            <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-3">Informations</h3>
            <div><span class="text-zinc-600">ID :</span> <span class="text-zinc-400 font-mono text-xs">{{ $song['id'] }}</span></div>
            <div><span class="text-zinc-600">Duree :</span> <span class="text-zinc-400">{{ gmdate('i:s', $song['duration'] ?? 0) }}</span></div>
            <div><span class="text-zinc-600">Format :</span> <span class="text-zinc-400">{{ strtoupper($song['suffix'] ?? '—') }}</span></div>
            <div><span class="text-zinc-600">Bitrate :</span> <span class="text-zinc-400">{{ $song['bitRate'] ?? '—' }} kbps</span></div>
            <div><span class="text-zinc-600">Taille :</span> <span class="text-zinc-400">{{ number_format(($song['size'] ?? 0) / 1048576, 1) }} Mo</span></div>
            <div><span class="text-zinc-600">Chemin :</span> <span class="text-xs text-zinc-500 break-all">{{ $song['path'] ?? '—' }}</span></div>
        </div>

        @if(count($albumSongs) > 1)
        <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4 mt-4">
            <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-3">Autres pistes de l'album</h3>
            <div class="space-y-1 max-h-64 overflow-y-auto">
                @foreach($albumSongs as $as)
                <a href="/admin/metadata/{{ $as['id'] }}/edit"
                   class="block px-2 py-1 rounded text-xs transition hover:bg-zinc-800 {{ $as['id'] === $song['id'] ? 'bg-zinc-800 text-zinc-200' : 'text-zinc-500' }}">
                    {{ $as['trackNumber'] ?? '?' }}. {{ $as['title'] ?? '—' }}
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
