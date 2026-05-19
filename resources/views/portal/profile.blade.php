@extends('layouts.app')

@section('title', 'Mon profil — MonFlow')

@section('content')
<div class="mb-6">
    <h1 class="text-base font-semibold text-zinc-100">Mon profil</h1>
    <p class="text-sm text-zinc-500 mt-0.5">Gérez vos informations personnelles</p>
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-6 max-w-2xl mb-6">
    <form action="/portal/profile" method="POST" class="space-y-5">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label for="first_name" class="block text-xs font-medium text-zinc-400 mb-1.5">Prénom</label>
                <input type="text" name="first_name" id="first_name"
                       value="{{ old('first_name', Auth::user()->first_name) }}"
                       class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
            </div>
            <div>
                <label for="last_name" class="block text-xs font-medium text-zinc-400 mb-1.5">Nom</label>
                <input type="text" name="last_name" id="last_name"
                       value="{{ old('last_name', Auth::user()->last_name) }}"
                       class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
            </div>
        </div>

        <div>
            <label for="email" class="block text-xs font-medium text-zinc-400 mb-1.5">Email</label>
            <input type="email" name="email" id="email"
                   value="{{ old('email', Auth::user()->email) }}"
                   class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
        </div>

        <div>
            <label for="phone" class="block text-xs font-medium text-zinc-400 mb-1.5">Téléphone</label>
            <input type="text" name="phone" id="phone"
                   value="{{ old('phone', Auth::user()->phone) }}"
                   class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
        </div>

        <div class="pt-4 border-t border-zinc-800">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="hidden" name="newsletter_optin" value="0">
                <input type="checkbox" name="newsletter_optin" value="1" {{ Auth::user()->newsletter_optin ? 'checked' : '' }} class="w-4 h-4 rounded text-indigo-500 bg-zinc-800 border-zinc-700">
                <div>
                    <span class="text-sm text-zinc-300">Recevoir la newsletter</span>
                    <p class="text-xs text-zinc-600 mt-0.5">Nouveautés, mises à jour et recommandations musicales</p>
                </div>
            </label>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-zinc-800">
            <a href="/portal/change-password" class="text-sm text-indigo-400 hover:text-indigo-300">
                Changer le mot de passe
            </a>
            <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                Enregistrer
            </button>
        </div>
    </form>
</div>

<div class="max-w-2xl space-y-3 mb-6">
    <h2 class="text-xs font-semibold text-zinc-600 uppercase tracking-wider">Profil public</h2>

    {{-- Avatar --}}
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
        <div class="flex items-center gap-5 mb-4">
            @if(Auth::user()->avatar_path)
                <img src="{{ asset(Auth::user()->avatar_path) }}" alt="Avatar"
                     class="w-16 h-16 rounded-full object-cover ring-2 ring-indigo-500/40">
            @else
                <div class="w-16 h-16 rounded-full bg-indigo-600/20 border border-indigo-500/20 flex items-center justify-center text-2xl font-bold text-indigo-400">
                    {{ strtoupper(substr(Auth::user()->display_name ?: Auth::user()->username, 0, 1)) }}
                </div>
            @endif
            <div>
                @if(Auth::user()->display_name)
                    <div class="font-semibold text-zinc-100">{{ Auth::user()->display_name }}</div>
                    <div class="text-indigo-400 font-mono text-sm">#{{ Auth::user()->display_name }}</div>
                    <a href="/u/{{ Auth::user()->display_name }}" target="_blank"
                       class="text-xs text-zinc-500 hover:text-indigo-400 mt-0.5 inline-block">Voir mon profil public →</a>
                @else
                    <div class="text-zinc-500 text-sm">Aucun pseudo défini</div>
                @endif
            </div>
        </div>
        <form action="/portal/profile/avatar" method="POST" enctype="multipart/form-data" class="flex items-center gap-3">
            @csrf
            <input type="file" name="avatar" accept="image/*"
                   class="text-sm text-zinc-400 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-zinc-800 file:text-zinc-200 hover:file:bg-zinc-700">
            <button type="submit" class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-sm font-medium px-3 py-1.5 rounded-lg border border-zinc-700 transition flex-shrink-0">
                Mettre à jour
            </button>
        </form>
    </div>

    {{-- Display name --}}
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
        <h3 class="text-sm font-medium text-zinc-300 mb-1">Pseudo public</h3>
        <p class="text-xs text-zinc-600 mb-3">Votre identifiant public. Lettres, chiffres, <code class="text-zinc-500">_</code> <code class="text-zinc-500">-</code> <code class="text-zinc-500">.</code> uniquement. Unique sur MonFlow.</p>
        @if(session('display_name_suggestions'))
        <div class="mb-3 p-3 bg-amber-500/10 border border-amber-500/20 rounded-lg text-xs text-amber-400">
            Ce pseudo est déjà pris. Suggestions :
            <div class="flex flex-wrap gap-2 mt-2">
                @foreach(session('display_name_suggestions') as $s)
                    <button type="button" onclick="document.getElementById('display_name_input').value='{{ $s }}'"
                            class="px-2 py-0.5 bg-zinc-800 hover:bg-indigo-600 rounded font-mono transition text-zinc-300">{{ $s }}</button>
                @endforeach
            </div>
        </div>
        @endif
        <form action="/portal/profile/display-name" method="POST" class="flex items-center gap-3">
            @csrf
            <div class="relative flex-1">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-600 text-sm">#</span>
                <input type="text" name="display_name" id="display_name_input"
                       value="{{ old('display_name', Auth::user()->display_name) }}"
                       placeholder="monpseudo" maxlength="50"
                       class="w-full pl-7 pr-3 bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 py-2 outline-none transition">
            </div>
            <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition flex-shrink-0">
                Enregistrer
            </button>
        </form>
        @error('display_name')<p class="text-red-400 text-xs mt-2">{{ $message }}</p>@enderror
    </div>
</div>

<div class="max-w-2xl space-y-3">
    <h2 class="text-xs font-semibold text-zinc-600 uppercase tracking-wider">Données &amp; compte</h2>

    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-4 flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-zinc-200">Exporter mes données</p>
            <p class="text-xs text-zinc-600 mt-0.5">Télécharger un fichier JSON de toutes vos données personnelles (RGPD).</p>
        </div>
        <a href="/portal/export-data"
           class="flex-shrink-0 ml-4 inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-sm font-medium px-4 py-2 rounded-lg border border-zinc-700 transition">
            Télécharger
        </a>
    </div>

    <div class="bg-zinc-900 border border-red-500/20 rounded-xl p-4 flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-red-400">Supprimer mon compte</p>
            <p class="text-xs text-zinc-600 mt-0.5">Supprime définitivement vos données personnelles et votre accès.</p>
        </div>
        <a href="/portal/delete-account"
           class="flex-shrink-0 ml-4 inline-flex items-center gap-2 bg-red-500/10 hover:bg-red-500/15 text-red-400 text-sm font-medium px-4 py-2 rounded-lg border border-red-500/20 transition">
            Supprimer
        </a>
    </div>
</div>
@endsection
