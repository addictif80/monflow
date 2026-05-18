@extends('layouts.app')

@section('title', 'Résilier mon abonnement - MonFlow')

@section('content')
<div class="max-w-lg mx-auto">
    <div class="mb-6">
        <a href="/portal" class="text-sm text-indigo-400 hover:text-indigo-300">&larr; Retour au tableau de bord</a>
    </div>

    <div class="bg-gray-800 rounded-lg border border-yellow-700 p-6">
        <div class="flex items-start gap-4 mb-6">
            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-yellow-900/50 border border-yellow-700 flex items-center justify-center">
                <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-yellow-300">Résilier mon abonnement</h1>
                <p class="text-gray-400 text-sm mt-1">Cette action mettra fin au renouvellement automatique.</p>
            </div>
        </div>

        <div class="bg-gray-900/50 rounded-lg border border-gray-700 p-4 mb-6 space-y-3">
            <div class="flex justify-between text-sm">
                <span class="text-gray-400">Formule actuelle</span>
                <span class="font-medium text-indigo-400">{{ $sub->plan->name }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-400">Accès garanti jusqu'au</span>
                <span class="font-medium">{{ \Carbon\Carbon::parse($sub->current_period_end)->format('d/m/Y') }}</span>
            </div>
        </div>

        <div class="bg-yellow-900/20 border border-yellow-800 rounded-lg p-4 mb-6 text-sm text-yellow-300 space-y-1">
            <p class="font-medium">Ce qui se passe après la résiliation :</p>
            <ul class="list-disc list-inside space-y-1 text-yellow-400/80 mt-2">
                <li>Votre accès reste actif jusqu'à la fin de la période en cours.</li>
                <li>Aucun prélèvement supplémentaire ne sera effectué.</li>
                <li>À l'échéance, votre compte sera suspendu puis supprimé.</li>
            </ul>
        </div>

        <div class="flex gap-3">
            <a href="/portal"
               class="flex-1 text-center px-4 py-2 bg-gray-700 hover:bg-gray-600 text-gray-200 rounded-lg text-sm font-medium transition">
                Annuler
            </a>
            <form action="/portal/cancel-subscription" method="POST" class="flex-1">
                @csrf
                <button type="submit"
                        class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition">
                    Confirmer la résiliation
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
