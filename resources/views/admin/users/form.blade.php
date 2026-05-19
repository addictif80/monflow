@extends('layouts.admin')

@section('title', $title . ' — Admin MonFlow')

@section('content')
<div class="mb-6">
    <a href="/admin/users" class="text-sm text-zinc-500 hover:text-zinc-300">&larr; Retour aux utilisateurs</a>
</div>

<div class="mb-6">
    <h1 class="text-base font-semibold text-zinc-100">{{ $title }}</h1>
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-6 max-w-2xl">
    <form method="POST" action="{{ $user ? '/admin/users/' . $user->id . '/edit' : '/admin/users/create' }}">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            {{-- Username --}}
            <div>
                <label for="username" class="block text-xs font-medium text-zinc-400 mb-1.5">Nom d'utilisateur</label>
                <input type="text" id="username" name="username" value="{{ old('username', $user->username ?? '') }}" required
                       class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="block text-xs font-medium text-zinc-400 mb-1.5">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email ?? '') }}" required
                       class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
            </div>

            {{-- First Name --}}
            <div>
                <label for="first_name" class="block text-xs font-medium text-zinc-400 mb-1.5">Prénom</label>
                <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $user->first_name ?? '') }}"
                       class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
            </div>

            {{-- Last Name --}}
            <div>
                <label for="last_name" class="block text-xs font-medium text-zinc-400 mb-1.5">Nom</label>
                <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $user->last_name ?? '') }}"
                       class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
            </div>

            {{-- Phone --}}
            <div>
                <label for="phone" class="block text-xs font-medium text-zinc-400 mb-1.5">Téléphone</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone ?? '') }}"
                       class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
            </div>

            {{-- Password --}}
            <div>
                <label for="password" class="block text-xs font-medium text-zinc-400 mb-1.5">Mot de passe @if($user)<span class="text-zinc-600">(laisser vide pour ne pas changer)</span>@endif</label>
                <input type="password" id="password" name="password" {{ $user ? '' : 'required' }}
                       class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
            </div>
        </div>

        {{-- Status (edit only) --}}
        @if($user)
            <div class="mb-4">
                <label for="status" class="block text-xs font-medium text-zinc-400 mb-1.5">Statut</label>
                <select id="status" name="status"
                        class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
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
                       class="w-4 h-4 rounded bg-zinc-900 border-zinc-700 text-indigo-600 focus:ring-indigo-500">
                <span class="text-sm text-zinc-300">Administrateur</span>
            </label>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                {{ $user ? 'Mettre à jour' : 'Créer' }}
            </button>
            <a href="/admin/users" class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-sm font-medium px-4 py-2 rounded-lg border border-zinc-700 transition">Annuler</a>
        </div>
    </form>
</div>
@endsection
