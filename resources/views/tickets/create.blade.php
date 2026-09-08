@extends('layouts.app')
@section('title', 'Nouveau ticket — MonFlow')
@section('content')
<div class="mb-4"><a href="/support/tickets" class="text-sm text-zinc-500 hover:text-zinc-300">&larr; Retour aux tickets</a></div>
<div class="mb-6">
    <h1 class="text-base font-semibold text-zinc-100">Nouveau ticket support</h1>
    <p class="text-sm text-zinc-500 mt-0.5">Décrivez votre problème ou demande</p>
</div>
<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-6 max-w-2xl">
    <form method="POST" action="/support/tickets/create">
        @csrf
        <div class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Sujet</label>
                <input name="subject" value="{{ old('subject') }}" required maxlength="255" class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-zinc-400 mb-1.5">Catégorie</label>
                    <select name="category" required class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
                        <option value="general">Général</option>
                        <option value="billing">Facturation</option>
                        <option value="technical">Technique</option>
                        <option value="other">Autre</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-400 mb-1.5">Priorité</label>
                    <select name="priority" required class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
                        <option value="low">Basse</option>
                        <option value="medium" selected>Moyenne</option>
                        <option value="high">Haute</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Message</label>
                <textarea name="message" rows="6" required class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">{{ old('message') }}</textarea>
            </div>
            <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Créer le ticket</button>
        </div>
    </form>
</div>
@endsection
