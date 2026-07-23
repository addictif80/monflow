@extends('layouts.admin')
@section('title', 'Frais de restauration — Admin MonFlow')
@section('content')
<div class="mb-6">
    <h1 class="text-base font-semibold text-zinc-100">Frais de restauration</h1>
    <p class="text-sm text-zinc-500 mt-0.5">Montant facturé pour récupérer un compte supprimé avec conservation des données</p>
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 max-w-md">
    <form method="POST" action="/admin/settings/restoration-fee">
        @csrf
        <div class="mb-4">
            <label class="block text-xs font-medium text-zinc-400 mb-1.5">Frais de restauration (€)</label>
            <input name="restoration_fee" type="number" step="0.01" min="0"
                   value="{{ old('restoration_fee', $settings->restoration_fee) }}" required
                   class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
            <p class="text-xs text-zinc-600 mt-1.5">
                Ce montant est indiqué dans le mail envoyé à un utilisateur dont le compte a été
                supprimé manuellement avec l'option "Conserver les données" cochée. Il n'est pas
                facturé automatiquement — la remise en service reste une action manuelle de l'admin
                (bouton "Réactiver" sur la fiche du membre).
            </p>
        </div>
        <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Sauvegarder</button>
    </form>
</div>
@endsection
