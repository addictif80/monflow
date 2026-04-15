@extends('layouts.auth')
@section('title', 'Mot de passe oublié — MonFlow')
@section('content')
<form method="POST" action="/forgot-password" class="bg-gray-800 rounded-lg p-6 space-y-4">
    @csrf
    <h2 class="text-xl font-semibold text-center">Mot de passe oublié</h2>
    <p class="text-sm text-gray-400 text-center">Entrez votre email pour recevoir un lien de réinitialisation.</p>
    <div><label class="block text-sm text-gray-400 mb-1">Email</label><input name="email" type="email" required class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 focus:outline-none focus:border-indigo-500"></div>
    <button class="w-full bg-indigo-600 hover:bg-indigo-700 py-2 rounded font-medium">Envoyer le lien</button>
    <p class="text-center text-sm"><a href="/login" class="text-indigo-400 hover:underline">Retour à la connexion</a></p>
</form>
@endsection
