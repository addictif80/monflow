@extends('layouts.auth')
@section('title', 'Inscription — MonFlow')
@section('content')
@if($errors->has('email') && str_contains($errors->first('email'), 'supprimé'))
    <div class="mb-4 bg-yellow-500/10 border border-yellow-500/20 rounded-lg p-3 text-sm text-yellow-400">
        <p class="mb-3">Vous n'avez pas retrouvé l'email « Souscrire à nouveau » ? Vous pouvez vous le faire renvoyer.</p>
        <form action="/resubscribe/resend" method="POST">
            @csrf
            <input type="hidden" name="email" value="{{ old('email') }}">
            <button type="submit" class="inline-flex items-center gap-2 bg-yellow-500/20 hover:bg-yellow-500/30 text-yellow-300 text-sm font-medium px-4 py-2 rounded-lg border border-yellow-500/30 transition">Renvoyer l'email</button>
        </form>
    </div>
@endif
<h1 class="text-xl font-semibold text-zinc-100 mb-1">Créer un compte</h1>
<p class="text-sm text-zinc-500 mb-6">Rejoignez MonFlow</p>
<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-6">
    <form method="POST" action="/register">
        @csrf
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-zinc-400 mb-1.5">Prénom</label>
                    <input name="first_name" value="{{ old('first_name') }}" class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-400 mb-1.5">Nom</label>
                    <input name="last_name" value="{{ old('last_name') }}" class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Nom d'utilisateur</label>
                <input name="username" value="{{ old('username') }}" required class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Email</label>
                <input name="email" type="email" value="{{ old('email') }}" required class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Mot de passe</label>
                <input name="password" type="password" required class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Confirmer le mot de passe</label>
                <input name="password_confirmation" type="password" required class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
            </div>
            <button class="w-full inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">S'inscrire</button>
        </div>
    </form>
</div>
<div class="mt-4 text-center text-sm text-zinc-500">Déjà un compte ? <a href="/login" class="text-indigo-400 hover:text-indigo-300">Se connecter</a></div>
@endsection
