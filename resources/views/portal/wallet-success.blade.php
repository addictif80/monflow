@extends('layouts.app')
@section('title', 'Rechargement réussi — MonFlow')
@section('content')
<div class="max-w-md mx-auto text-center bg-zinc-900 border border-zinc-800 rounded-xl p-8 mt-12">
    <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center">
        <svg class="w-7 h-7 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
    </div>
    <h1 class="text-base font-semibold text-zinc-100 mb-2">Rechargement réussi !</h1>
    <p class="text-sm text-zinc-500 mb-6">Votre portefeuille a été crédité.</p>
    <a href="/portal/wallet" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Voir mon portefeuille</a>
</div>
@endsection
