@extends('layouts.app')

@section('title', 'Changer le mot de passe — MonFlow')

@section('content')
<div class="mb-6">
    <h1 class="text-base font-semibold text-zinc-100">Changer le mot de passe</h1>
    <p class="text-sm text-zinc-500 mt-0.5">Mettez à jour votre mot de passe de connexion</p>
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-6 max-w-md">
    <form action="/portal/change-password" method="POST" class="space-y-5">
        @csrf

        <div>
            <label for="current_password" class="block text-xs font-medium text-zinc-400 mb-1.5">Mot de passe actuel</label>
            <input type="password" name="current_password" id="current_password" required
                   class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
        </div>

        <div>
            <label for="password" class="block text-xs font-medium text-zinc-400 mb-1.5">Nouveau mot de passe</label>
            <input type="password" name="password" id="password" required
                   class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
        </div>

        <div>
            <label for="password_confirmation" class="block text-xs font-medium text-zinc-400 mb-1.5">Confirmer le mot de passe</label>
            <input type="password" name="password_confirmation" id="password_confirmation" required
                   class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-zinc-800">
            <a href="/portal/profile" class="text-sm text-zinc-500 hover:text-zinc-300">Retour au profil</a>
            <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                Mettre à jour
            </button>
        </div>
    </form>
</div>
@endsection
