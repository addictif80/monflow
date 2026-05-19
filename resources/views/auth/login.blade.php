@extends('layouts.auth')
@section('title', 'Connexion — MonFlow')
@section('content')
@if(request('unverified'))
    <div class="mb-4 bg-yellow-500/10 border border-yellow-500/20 rounded-lg p-3 text-sm text-yellow-400">
        <p class="mb-3">Votre email n'est pas encore confirmé. Vérifiez votre boîte de réception ou renvoyez le mail.</p>
        <form action="/verify-email/resend-public" method="POST">
            @csrf
            <input type="hidden" name="email" value="{{ request('unverified') }}">
            <button type="submit" class="inline-flex items-center gap-2 bg-yellow-500/20 hover:bg-yellow-500/30 text-yellow-300 text-sm font-medium px-4 py-2 rounded-lg border border-yellow-500/30 transition">Renvoyer le mail de confirmation</button>
        </form>
    </div>
@endif
<h1 class="text-xl font-semibold text-zinc-100 mb-1">Connexion</h1>
<p class="text-sm text-zinc-500 mb-6">Accédez à votre espace MonFlow</p>
<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-6">
    <form method="POST" action="/login">
        @csrf
        <div class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Nom d'utilisateur ou email</label>
                <input name="username" value="{{ old('username') }}" required class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Mot de passe</label>
                <input name="password" type="password" required class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
            </div>
            <label class="flex items-center gap-2 text-sm text-zinc-400 cursor-pointer"><input type="checkbox" name="remember" class="rounded border-zinc-700 bg-zinc-800 text-indigo-500"> Se souvenir de moi</label>
            <button class="w-full inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Se connecter</button>
        </div>
    </form>
</div>
<div class="mt-4 flex justify-between text-sm text-zinc-500">
    <a href="/register" class="text-indigo-400 hover:text-indigo-300">Créer un compte</a>
    <a href="/forgot-password" class="text-indigo-400 hover:text-indigo-300">Mot de passe oublié ?</a>
</div>
@endsection
