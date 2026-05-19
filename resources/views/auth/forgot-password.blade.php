@extends('layouts.auth')
@section('title', 'Mot de passe oublié — MonFlow')
@section('content')
<h1 class="text-xl font-semibold text-zinc-100 mb-1">Mot de passe oublié</h1>
<p class="text-sm text-zinc-500 mb-6">Entrez votre email pour recevoir un lien de réinitialisation.</p>
<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-6">
    <form method="POST" action="/forgot-password">
        @csrf
        <div class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Email</label>
                <input name="email" type="email" required class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
            </div>
            <button class="w-full inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Envoyer le lien</button>
        </div>
    </form>
</div>
<div class="mt-4 text-center text-sm text-zinc-500"><a href="/login" class="text-indigo-400 hover:text-indigo-300">Retour à la connexion</a></div>
@endsection
