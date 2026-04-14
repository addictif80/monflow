@extends('layouts.app')

@section('title', 'Changer le mot de passe - MonFlow')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold">Changer le mot de passe</h1>
    <p class="text-gray-400 mt-1">Mettez à jour votre mot de passe de connexion</p>
</div>

<div class="bg-gray-800 rounded-lg border border-gray-700 p-6 max-w-md">
    <form action="/portal/change-password" method="POST" class="space-y-5">
        @csrf

        <div>
            <label for="current_password" class="block text-sm font-medium text-gray-300 mb-1">Mot de passe actuel</label>
            <input type="password" name="current_password" id="current_password" required
                   class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-300 mb-1">Nouveau mot de passe</label>
            <input type="password" name="password" id="password" required
                   class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-300 mb-1">Confirmer le mot de passe</label>
            <input type="password" name="password_confirmation" id="password_confirmation" required
                   class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-gray-700">
            <a href="/portal/profile" class="text-sm text-gray-400 hover:text-gray-300">Retour au profil</a>
            <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">
                Mettre à jour
            </button>
        </div>
    </form>
</div>
@endsection
