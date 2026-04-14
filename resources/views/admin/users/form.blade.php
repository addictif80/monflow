@extends('layouts.admin')

@section('title', $title . ' — Admin MonFlow')

@section('content')
<div class="mb-6">
    <a href="/admin/users" class="text-gray-400 hover:text-gray-200 text-sm">&larr; Retour aux utilisateurs</a>
</div>

<h1 class="text-2xl font-bold mb-6">{{ $title }}</h1>

<div class="bg-gray-800 border border-gray-700 rounded-lg p-6 max-w-2xl">
    <form method="POST" action="{{ $user ? '/admin/users/' . $user->id . '/edit' : '/admin/users/create' }}">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            {{-- Username --}}
            <div>
                <label for="username" class="block text-sm text-gray-400 mb-1">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" value="{{ old('username', $user->username ?? '') }}" required
                       class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm text-gray-400 mb-1">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email ?? '') }}" required
                       class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
            </div>

            {{-- First Name --}}
            <div>
                <label for="first_name" class="block text-sm text-gray-400 mb-1">Prénom</label>
                <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $user->first_name ?? '') }}"
                       class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
            </div>

            {{-- Last Name --}}
            <div>
                <label for="last_name" class="block text-sm text-gray-400 mb-1">Nom</label>
                <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $user->last_name ?? '') }}"
                       class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
            </div>

            {{-- Phone --}}
            <div>
                <label for="phone" class="block text-sm text-gray-400 mb-1">Téléphone</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone ?? '') }}"
                       class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
            </div>

            {{-- Password --}}
            <div>
                <label for="password" class="block text-sm text-gray-400 mb-1">Mot de passe @if($user)<span class="text-gray-500">(laisser vide pour ne pas changer)</span>@endif</label>
                <input type="password" id="password" name="password" {{ $user ? '' : 'required' }}
                       class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
            </div>
        </div>

        {{-- Status (edit only) --}}
        @if($user)
            <div class="mb-4">
                <label for="status" class="block text-sm text-gray-400 mb-1">Statut</label>
                <select id="status" name="status"
                        class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                    <option value="active" {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>Actif</option>
                    <option value="suspended" {{ old('status', $user->status) === 'suspended' ? 'selected' : '' }}>Suspendu</option>
                    <option value="deleted" {{ old('status', $user->status) === 'deleted' ? 'selected' : '' }}>Supprimé</option>
                </select>
            </div>
        @endif

        {{-- Is Admin --}}
        <div class="mb-6">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_admin" value="1" {{ old('is_admin', $user->is_admin ?? false) ? 'checked' : '' }}
                       class="w-4 h-4 rounded bg-gray-900 border-gray-700 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-gray-300">Administrateur</span>
            </label>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-medium transition">
                {{ $user ? 'Mettre à jour' : 'Créer' }}
            </button>
            <a href="/admin/users" class="px-6 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm font-medium transition">Annuler</a>
        </div>
    </form>
</div>
@endsection
