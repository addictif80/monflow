@extends('layouts.app')

@section('title', 'Mon profil - MonFlow')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold">Mon profil</h1>
    <p class="text-gray-400 mt-1">Gérez vos informations personnelles</p>
</div>

<div class="bg-gray-800 rounded-lg border border-gray-700 p-6 max-w-2xl">
    <form action="/portal/profile" method="POST" class="space-y-5">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label for="first_name" class="block text-sm font-medium text-gray-300 mb-1">Prénom</label>
                <input type="text" name="first_name" id="first_name"
                       value="{{ old('first_name', Auth::user()->first_name) }}"
                       class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
            <div>
                <label for="last_name" class="block text-sm font-medium text-gray-300 mb-1">Nom</label>
                <input type="text" name="last_name" id="last_name"
                       value="{{ old('last_name', Auth::user()->last_name) }}"
                       class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-300 mb-1">Email</label>
            <input type="email" name="email" id="email"
                   value="{{ old('email', Auth::user()->email) }}"
                   class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        </div>

        <div>
            <label for="phone" class="block text-sm font-medium text-gray-300 mb-1">Téléphone</label>
            <input type="text" name="phone" id="phone"
                   value="{{ old('phone', Auth::user()->phone) }}"
                   class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-gray-700">
            <a href="/portal/change-password" class="text-sm text-indigo-400 hover:text-indigo-300">
                Changer le mot de passe
            </a>
            <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">
                Enregistrer
            </button>
        </div>
    </form>
</div>
@endsection
