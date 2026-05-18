@extends('layouts.app')

@section('title', 'Supprimer mon compte - MonFlow')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="mb-6">
        <a href="/portal/profile" class="text-sm text-indigo-400 hover:text-indigo-300">&larr; Retour au profil</a>
    </div>

    <div class="bg-gray-800 rounded-lg border border-red-800 p-6">
        <div class="flex items-start gap-4 mb-6">
            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-900/50 border border-red-700 flex items-center justify-center">
                <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-red-300">Supprimer mon compte</h1>
                <p class="text-gray-400 text-sm mt-1">Cette action est irréversible.</p>
            </div>
        </div>

        <div class="bg-red-900/20 border border-red-800 rounded-lg p-4 mb-6 text-sm space-y-1">
            <p class="font-medium text-red-300">Ce qui sera supprimé immédiatement :</p>
            <ul class="list-disc list-inside space-y-1 text-red-400/80 mt-2">
                <li>Votre compte et vos informations personnelles</li>
                <li>Votre accès à la musique (compte Navidrome)</li>
                <li>Vos appareils connectés et notifications</li>
                <li>Vos abonnements Stripe actifs (sans remboursement)</li>
            </ul>
            <p class="font-medium text-gray-400 mt-3">Ce qui est conservé (obligation légale) :</p>
            <ul class="list-disc list-inside space-y-1 text-gray-500 mt-1">
                <li>L'historique des paiements et factures</li>
            </ul>
        </div>

        <form action="/portal/delete-account" method="POST" class="space-y-5">
            @csrf

            <div>
                <label for="password" class="block text-sm font-medium text-gray-300 mb-1">Mot de passe actuel</label>
                <input type="password" name="password" id="password" required
                       class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent @error('password') border-red-500 @enderror">
                @error('password')
                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="confirm" class="block text-sm font-medium text-gray-300 mb-1">
                    Tapez <span class="font-mono text-red-400 select-all">SUPPRIMER</span> pour confirmer
                </label>
                <input type="text" name="confirm" id="confirm" required autocomplete="off" placeholder="SUPPRIMER"
                       class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent @error('confirm') border-red-500 @enderror">
                @error('confirm')
                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3 pt-2">
                <a href="/portal/profile"
                   class="flex-1 text-center px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-200 rounded-lg text-sm font-medium transition">
                    Annuler
                </a>
                <button type="submit"
                        class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition">
                    Supprimer définitivement
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
