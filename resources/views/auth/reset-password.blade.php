@extends('layouts.auth')
@section('title', 'Réinitialiser le mot de passe — MonFlow')
@section('content')
<form method="POST" action="/reset-password" class="bg-gray-800 rounded-lg p-6 space-y-4">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <input type="hidden" name="email" value="{{ $email }}">
    <h2 class="text-xl font-semibold text-center">Nouveau mot de passe</h2>
    <div><label class="block text-sm text-gray-400 mb-1">Nouveau mot de passe</label><input name="password" type="password" required class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 focus:outline-none focus:border-indigo-500"></div>
    <div><label class="block text-sm text-gray-400 mb-1">Confirmer</label><input name="password_confirmation" type="password" required class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 focus:outline-none focus:border-indigo-500"></div>
    <button class="w-full bg-indigo-600 hover:bg-indigo-700 py-2 rounded font-medium">Réinitialiser</button>
</form>
@endsection
