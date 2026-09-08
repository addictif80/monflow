@extends('layouts.app')
@section('title', 'Cadeau envoyé — MonFlow')
@section('content')
<div class="max-w-md mx-auto text-center bg-zinc-900 border border-zinc-800 rounded-xl p-8 mt-12">
    <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-3xl">🎁</div>
    <h1 class="text-base font-semibold text-zinc-100 mb-2">Cadeau envoyé !</h1>
    <p class="text-sm text-zinc-500 mb-6">Le destinataire va recevoir un email avec ses identifiants.</p>
    <a href="/portal" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Retour au tableau de bord</a>
</div>
@endsection
