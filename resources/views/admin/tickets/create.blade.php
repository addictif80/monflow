@extends('layouts.admin')
@section('title', 'Nouveau ticket — Admin MonFlow')
@section('content')
<div class="mb-6">
    <a href="/admin/tickets" class="text-sm text-zinc-500 hover:text-zinc-300">&larr; Retour aux tickets</a>
</div>
<div class="mb-6">
    <h1 class="text-base font-semibold text-zinc-100">Ouvrir un ticket pour un utilisateur</h1>
    <p class="text-sm text-zinc-500 mt-0.5">L'utilisateur sera notifié de l'ouverture du ticket.</p>
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-6 max-w-2xl">
    <form method="POST" action="/admin/tickets/create">
        @csrf
        <div class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Utilisateur (nom d'utilisateur ou email)</label>
                <input name="user" value="{{ old('user') }}" required class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition" placeholder="jdupont ou jdupont@example.com">
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Sujet</label>
                <input name="subject" value="{{ old('subject') }}" required maxlength="255" class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-zinc-400 mb-1.5">Catégorie</label>
                    <select name="category" required class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
                        <option value="general" {{ old('category') === 'general' ? 'selected' : '' }}>Général</option>
                        <option value="billing" {{ old('category') === 'billing' ? 'selected' : '' }}>Facturation</option>
                        <option value="technical" {{ old('category') === 'technical' ? 'selected' : '' }}>Technique</option>
                        <option value="other" {{ old('category') === 'other' ? 'selected' : '' }}>Autre</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-400 mb-1.5">Priorité</label>
                    <select name="priority" required class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
                        <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Basse</option>
                        <option value="medium" {{ old('priority', 'medium') === 'medium' ? 'selected' : '' }}>Moyenne</option>
                        <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>Haute</option>
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
