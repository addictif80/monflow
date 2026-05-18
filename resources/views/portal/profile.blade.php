@extends('layouts.app')

@section('title', 'Mon profil - MonFlow')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold">Mon profil</h1>
    <p class="text-gray-400 mt-1">Gérez vos informations personnelles</p>
</div>

<div class="bg-gray-800 rounded-lg border border-gray-700 p-6 max-w-2xl">
    <form action="/portal/profile" method="POST" class="space-y-5">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label for="first_name" class="block text-sm font-medium text-gray-300 mb-1">Prénom</label>
                <input type="text" name="first_name" id="first_name"
                       value="{{ old('first_name', Auth::user()->first_name) }}"
                       class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
            <div>
                <label for="last_name" class="block text-sm font-medium text-gray-300 mb-1">Nom</label>
                <input type="text" name="last_name" id="last_name"
                       value="{{ old('last_name', Auth::user()->last_name) }}"
                       class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-300 mb-1">Email</label>
            <input type="email" name="email" id="email"
                   value="{{ old('email', Auth::user()->email) }}"
                   class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        </div>

        <div>
            <label for="phone" class="block text-sm font-medium text-gray-300 mb-1">Téléphone</label>
            <input type="text" name="phone" id="phone"
                   value="{{ old('phone', Auth::user()->phone) }}"
                   class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        </div>

        <div class="pt-4 border-t border-gray-700 mb-2">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="hidden" name="newsletter_optin" value="0">
                <input type="checkbox" name="newsletter_optin" value="1" {{ Auth::user()->newsletter_optin ? 'checked' : '' }} class="w-4 h-4 rounded text-indigo-500 focus:ring-indigo-500 bg-gray-700 border-gray-600">
                <div>
                    <span class="text-sm text-gray-300">Recevoir la newsletter</span>
                    <p class="text-xs text-gray-500">Nouveautés, mises à jour et recommandations musicales</p>
                </div>
            </label>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-gray-700">
            <a href="/portal/change-password" class="text-sm text-indigo-400 hover:text-indigo-300">
                Changer le mot de passe
            </a>
            <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">
                Enregistrer
            </button>
        </div>
    </form>
</div>

<div class="max-w-2xl mt-8 space-y-3">
    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Profil public</h2>

    {{-- Avatar --}}
    <div class="bg-gray-800 rounded-lg border border-gray-700 p-5">
        <div class="flex items-center gap-5 mb-4">
            @if(Auth::user()->avatar_path)
                <img src="{{ asset(Auth::user()->avatar_path) }}" alt="Avatar"
                     class="w-16 h-16 rounded-full object-cover ring-2 ring-indigo-500/40">
            @else
                <div class="w-16 h-16 rounded-full bg-indigo-700 flex items-center justify-center text-2xl font-bold text-white">
                    {{ strtoupper(substr(Auth::user()->display_name ?: Auth::user()->username, 0, 1)) }}
                </div>
            @endif
            <div>
                @if(Auth::user()->display_name)
                    <div class="font-semibold text-white">{{ Auth::user()->display_name }}</div>
                    <div class="text-indigo-400 font-mono text-sm">#{{ Auth::user()->display_name }}</div>
                    <a href="/u/{{ Auth::user()->display_name }}" target="_blank"
                       class="text-xs text-gray-400 hover:text-indigo-400 mt-0.5 inline-block">Voir mon profil public →</a>
                @else
                    <div class="text-gray-400 text-sm">Aucun pseudo défini</div>
                @endif
            </div>
        </div>
        <form action="/portal/profile/avatar" method="POST" enctype="multipart/form-data" class="flex items-center gap-3">
            @csrf
            <input type="file" name="avatar" accept="image/*"
                   class="text-sm text-gray-300 file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:bg-gray-700 file:text-gray-200 hover:file:bg-gray-600">
            <button type="submit" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs font-medium transition flex-shrink-0">
                Mettre à jour
            </button>
        </form>
    </div>

    {{-- Display name --}}
    <div class="bg-gray-800 rounded-lg border border-gray-700 p-5">
        <h3 class="text-sm font-medium text-gray-300 mb-1">Pseudo public</h3>
        <p class="text-xs text-gray-500 mb-3">Votre identifiant public. Lettres, chiffres, <code class="text-gray-400">_</code> <code class="text-gray-400">-</code> <code class="text-gray-400">.</code> uniquement. Unique sur MonFlow.</p>
        @if(session('display_name_suggestions'))
        <div class="mb-3 p-3 bg-amber-900/30 border border-amber-700/50 rounded text-xs text-amber-300">
            Ce pseudo est déjà pris. Suggestions :
            <div class="flex flex-wrap gap-2 mt-2">
                @foreach(session('display_name_suggestions') as $s)
                    <button type="button" onclick="document.getElementById('display_name_input').value='{{ $s }}'"
                            class="px-2 py-0.5 bg-gray-700 hover:bg-indigo-700 rounded font-mono transition">{{ $s }}</button>
                @endforeach
            </div>
        </div>
        @endif
        <form action="/portal/profile/display-name" method="POST" class="flex items-center gap-3">
            @csrf
            <div class="relative flex-1">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">#</span>
                <input type="text" name="display_name" id="display_name_input"
                       value="{{ old('display_name', Auth::user()->display_name) }}"
                       placeholder="monpseudo" maxlength="50"
                       class="w-full pl-7 pr-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
            </div>
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition flex-shrink-0">
                Enregistrer
            </button>
        </form>
        @error('display_name')<p class="text-red-400 text-xs mt-2">{{ $message }}</p>@enderror
    </div>
</div>

<div class="max-w-2xl mt-8 space-y-3">
    <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wider">Données &amp; compte</h2>

    <div class="bg-gray-800 rounded-lg border border-gray-700 p-4 flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-200">Exporter mes données</p>
            <p class="text-xs text-gray-500 mt-0.5">Télécharger un fichier JSON de toutes vos données personnelles (RGPD).</p>
        </div>
        <a href="/portal/export-data"
           class="flex-shrink-0 ml-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">
            Télécharger
        </a>
    </div>

    <div class="bg-gray-800 rounded-lg border border-red-900/50 p-4 flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-red-400">Supprimer mon compte</p>
            <p class="text-xs text-gray-500 mt-0.5">Supprime définitivement vos données personnelles et votre accès.</p>
        </div>
        <a href="/portal/delete-account"
           class="flex-shrink-0 ml-4 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition">
            Supprimer
        </a>
    </div>
</div>
@endsection
