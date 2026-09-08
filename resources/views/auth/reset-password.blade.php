@extends('layouts.auth')
@section('title', 'Réinitialiser le mot de passe — MonFlow')
@section('content')
<h1 class="text-xl font-semibold text-zinc-100 mb-1">Nouveau mot de passe</h1>
<p class="text-sm text-zinc-500 mb-6">Choisissez un nouveau mot de passe pour votre compte.</p>
<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-6">
    <form method="POST" action="/reset-password">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">
        <div class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Nouveau mot de passe</label>
                <input name="password" type="password" required class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Confirmer</label>
                <input name="password_confirmation" type="password" required class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
            </div>
            <button class="w-full inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Réinitialiser</button>
        </div>
    </form>
</div>
@endsection
