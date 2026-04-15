@extends('layouts.app')
@section('title', 'Nouveau ticket — MonFlow')
@section('content')
<div class="mb-6"><a href="/support/tickets" class="text-gray-400 hover:text-gray-200 text-sm">&larr; Retour aux tickets</a></div>
<h1 class="text-2xl font-bold mb-6">Nouveau ticket support</h1>
<div class="bg-gray-800 border border-gray-700 rounded-lg p-6 max-w-2xl">
    <form method="POST" action="/support/tickets/create">
        @csrf
        <div class="mb-4">
            <label class="block text-sm text-gray-400 mb-1">Sujet</label>
            <input name="subject" value="{{ old('subject') }}" required maxlength="255" class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
        </div>
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm text-gray-400 mb-1">Catégorie</label>
                <select name="category" required class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                    <option value="general">Général</option>
                    <option value="billing">Facturation</option>
                    <option value="technical">Technique</option>
                    <option value="other">Autre</option>
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1">Priorité</label>
                <select name="priority" required class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                    <option value="low">Basse</option>
                    <option value="medium" selected>Moyenne</option>
                    <option value="high">Haute</option>
                </select>
            </div>
        </div>
        <div class="mb-6">
            <label class="block text-sm text-gray-400 mb-1">Message</label>
            <textarea name="message" rows="6" required class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">{{ old('message') }}</textarea>
        </div>
        <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-medium">Créer le ticket</button>
    </form>
</div>
@endsection
