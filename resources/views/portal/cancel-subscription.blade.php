@extends('layouts.app')

@section('title', 'Résilier mon abonnement — MonFlow')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="mb-6">
        <a href="/portal" class="text-sm text-zinc-500 hover:text-zinc-300">&larr; Retour au tableau de bord</a>
    </div>

    <div class="bg-zinc-900 border border-yellow-500/20 rounded-xl p-6">
        <div class="flex items-start gap-4 mb-6">
            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-yellow-500/10 border border-yellow-500/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-base font-semibold text-yellow-400">Résilier mon abonnement</h1>
                <p class="text-sm text-zinc-500 mt-0.5">Cette action mettra fin au renouvellement automatique.</p>
            </div>
        </div>

        <div class="bg-zinc-800 rounded-xl border border-zinc-700 p-4 mb-6 space-y-3">
            <div class="flex justify-between text-sm">
                <span class="text-zinc-500">Formule actuelle</span>
                <span class="font-medium text-indigo-400">{{ $sub->plan->name }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-zinc-500">Accès garanti jusqu'au</span>
                <span class="font-medium text-zinc-300">{{ \Carbon\Carbon::parse($sub->current_period_end)->format('d/m/Y') }}</span>
            </div>
        </div>

        <div class="bg-yellow-500/10 border border-yellow-500/20 rounded-lg p-4 mb-6 text-sm text-yellow-400 space-y-1">
            <p class="font-medium">Ce qui se passe après la résiliation :</p>
            <ul class="list-disc list-inside space-y-1 text-yellow-400/70 mt-2">
                <li>Votre accès reste actif jusqu'à la fin de la période en cours.</li>
                <li>Aucun prélèvement supplémentaire ne sera effectué.</li>
                <li>À l'échéance, votre compte sera suspendu puis supprimé.</li>
            </ul>
        </div>

        <div class="flex gap-3">
            <a href="/portal"
               class="flex-1 text-center inline-flex items-center justify-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-sm font-medium px-4 py-2 rounded-lg border border-zinc-700 transition">
                Annuler
            </a>
            <form action="/portal/cancel-subscription" method="POST" class="flex-1">
                @csrf
                <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 bg-red-500/10 hover:bg-red-500/15 text-red-400 text-sm font-medium px-4 py-2 rounded-lg border border-red-500/20 transition">
                    Confirmer la résiliation
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
