@extends('layouts.auth')
@section('title', 'Inscription — MonFlow')
@section('content')
<form method="POST" action="/register" class="bg-gray-800 rounded-lg p-6 space-y-4">
    @csrf
    <h2 class="text-xl font-semibold text-center">Créer un compte</h2>
    <div class="grid grid-cols-2 gap-3">
        <div><label class="block text-sm text-gray-400 mb-1">Prénom</label><input name="first_name" value="{{ old('first_name') }}" class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 focus:outline-none focus:border-indigo-500"></div>
        <div><label class="block text-sm text-gray-400 mb-1">Nom</label><input name="last_name" value="{{ old('last_name') }}" class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 focus:outline-none focus:border-indigo-500"></div>
    </div>
    <div><label class="block text-sm text-gray-400 mb-1">Nom d'utilisateur</label><input name="username" value="{{ old('username') }}" required class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 focus:outline-none focus:border-indigo-500"></div>
    <div><label class="block text-sm text-gray-400 mb-1">Email</label><input name="email" type="email" value="{{ old('email') }}" required class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 focus:outline-none focus:border-indigo-500"></div>
    <div><label class="block text-sm text-gray-400 mb-1">Mot de passe</label><input name="password" type="password" required class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 focus:outline-none focus:border-indigo-500"></div>
    <div><label class="block text-sm text-gray-400 mb-1">Confirmer le mot de passe</label><input name="password_confirmation" type="password" required class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 focus:outline-none focus:border-indigo-500"></div>
    <button class="w-full bg-indigo-600 hover:bg-indigo-700 py-2 rounded font-medium">S'inscrire</button>
    <p class="text-center text-sm text-gray-400">Déjà un compte ? <a href="/login" class="text-indigo-400 hover:underline">Se connecter</a></p>
</form>
@endsection
