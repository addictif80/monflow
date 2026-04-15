@extends('layouts.app')
@section('title', 'Cadeau envoyé — MonFlow')
@section('content')
<div class="max-w-md mx-auto text-center bg-gray-800 border border-gray-700 rounded-lg p-8 mt-12">
    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-green-900/50 border border-green-700 flex items-center justify-center text-4xl">🎁</div>
    <h1 class="text-2xl font-bold mb-2">Cadeau envoyé !</h1>
    <p class="text-gray-400 mb-6">Le destinataire va recevoir un email avec ses identifiants.</p>
    <a href="/portal" class="inline-block px-6 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg font-medium">Retour au tableau de bord</a>
</div>
@endsection
