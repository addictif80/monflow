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
    <div class="flex justify-between text-sm text-gray-400">
        <a href="/register" class="hover:text-indigo-400">Créer un compte</a>
        <a href="/forgot-password" class="hover:text-indigo-400">Mot de passe oublié ?</a>
    </div>
</form>
@endsection
