@extends('layouts.app')

@section('title', 'Supprimer mon compte — MonFlow')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="mb-6">
        <a href="/portal/profile" class="text-sm text-zinc-500 hover:text-zinc-300">&larr; Retour au profil</a>
    </div>

    <div class="bg-zinc-900 border border-red-500/20 rounded-xl p-6">
        <div class="flex items-start gap-4 mb-6">
            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>
            <div>
                <h1 class="text-base font-semibold text-red-400">Supprimer mon compte</h1>
                <p class="text-sm text-zinc-500 mt-0.5">Cette action est irréversible.</p>
            </div>
        </div>

        <div class="bg-red-500/10 border border-red-500/20 rounded-lg p-4 mb-6 text-sm space-y-1">
            <p class="font-medium text-red-400">Ce qui sera supprimé immédiatement :</p>
            <ul class="list-disc list-inside space-y-1 text-red-400/70 mt-2">
                <li>Votre compte et vos informations personnelles</li>
                <li>Votre accès à la musique (compte Navidrome)</li>
                <li>Vos appareils connectés et notifications</li>
                <li>Vos abonnements Stripe actifs (sans remboursement)</li>
            </ul>
            <p class="font-medium text-zinc-500 mt-3">Ce qui est conservé (obligation légale) :</p>
            <ul class="list-disc list-inside space-y-1 text-zinc-600 mt-1">
                <li>L'historique des paiements et factures</li>
            </ul>
        </div>

        <form action="/portal/delete-account" method="POST" class="space-y-5">
            @csrf

            <div>
                <label for="password" class="block text-xs font-medium text-zinc-400 mb-1.5">Mot de passe actuel</label>
                <input type="password" name="password" id="password" required
                       class="w-full bg-zinc-900 border border-zinc-800 focus:border-red-500/50 focus:ring-1 focus:ring-red-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition @error('password') border-red-500/50 @enderror">
                @error('password')
                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="confirm" class="block text-xs font-medium text-zinc-400 mb-1.5">
                    Tapez <span class="font-mono text-red-400 select-all">SUPPRIMER</span> pour confirmer
                </label>
                <input type="text" name="confirm" id="confirm" required autocomplete="off" placeholder="SUPPRIMER"
                       class="w-full bg-zinc-900 border border-zinc-800 focus:border-red-500/50 focus:ring-1 focus:ring-red-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition @error('confirm') border-red-500/50 @enderror">
                @error('confirm')
                    <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3 pt-2">
                <a href="/portal/profile"
                   class="flex-1 text-center inline-flex items-center justify-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-sm font-medium px-4 py-2 rounded-lg border border-zinc-700 transition">
                    Annuler
                </a>
                <button type="submit"
                        class="flex-1 inline-flex items-center justify-center gap-2 bg-red-500/10 hover:bg-red-500/15 text-red-400 text-sm font-medium px-4 py-2 rounded-lg border border-red-500/20 transition">
                    Supprimer définitivement
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
