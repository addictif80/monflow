@extends('layouts.auth')
@section('title', 'Connexion — MonFlow')
@section('content')
<form method="POST" action="/login" class="bg-gray-800 rounded-lg p-6 space-y-4">
    @csrf
    <h2 class="text-xl font-semibold text-center">Connexion</h2>
    <div>
        <label class="block text-sm text-gray-400 mb-1">Nom d'utilisateur ou email</label>
        <input name="username" value="{{ old('username') }}" required class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 focus:outline-none focus:border-indigo-500">
    </div>
    <div>
        <label class="block text-sm text-gray-400 mb-1">Mot de passe</label>
        <input name="password" type="password" required class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 focus:outline-none focus:border-indigo-500">
    </div>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" class="rounded"> Se souvenir de moi</label>
    <button class="w-full bg-indigo-600 hover:bg-indigo-700 py-2 rounded font-medium">Se connecter</button>
    @if(session('unverified_email'))
        <div class="p-3 bg-yellow-900/50 border border-yellow-700 rounded text-yellow-300 text-sm">
            <p class="mb-2">Votre email n'est pas encore confirmé.</p>
            <form action="/verify-email/resend-public" method="POST">
                @csrf
                <input type="hidden" name="email" value="{{ session('unverified_email') }}">
                <button type="submit" class="px-4 py-1.5 bg-yellow-600 hover:bg-yellow-500 text-white rounded text-sm font-medium transition">Renvoyer le mail de confirmation</button>
            </form>
        </div>
    @endif
    <div class="flex justify-between text-sm text-gray-400">
        <a href="/register" class="hover:text-indigo-400">Créer un compte</a>
        <a href="/forgot-password" class="hover:text-indigo-400">Mot de passe oublié ?</a>
    </div>
</form>
@endsection
