@extends('layouts.app')
@section('title', 'Nouveau feedback — MonFlow')
@section('content')
<div class="mb-6">
    <a href="/portal/feedback" class="text-gray-400 hover:text-gray-200 text-sm">&larr; Retour aux feedbacks</a>
</div>

<h1 class="text-2xl font-bold mb-6">Envoyer un feedback</h1>

<div class="bg-gray-800 border border-gray-700 rounded-lg p-6 max-w-2xl">
    <form action="/portal/feedback/create" method="POST" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm text-gray-400 mb-1">Type</label>
            <select name="type" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-sm text-gray-100">
                <option value="bug" {{ old('type') === 'bug' ? 'selected' : '' }}>Bug</option>
                <option value="suggestion" {{ old('type') === 'suggestion' ? 'selected' : '' }}>Suggestion</option>
                <option value="ui" {{ old('type') === 'ui' ? 'selected' : '' }}>Interface</option>
                <option value="performance" {{ old('type') === 'performance' ? 'selected' : '' }}>Performance</option>
                <option value="other" {{ old('type') === 'other' ? 'selected' : '' }}>Autre</option>
            </select>
        </div>
        <div>
            <label class="block text-sm text-gray-400 mb-1">Sujet</label>
            <input type="text" name="subject" value="{{ old('subject') }}" placeholder="Résumez votre feedback en une phrase" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-sm text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        </div>
        <div>
            <label class="block text-sm text-gray-400 mb-1">Description</label>
            <textarea name="body" rows="6" placeholder="Décrivez en détail ce que vous avez observé ou ce que vous suggérez..." class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-sm text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">{{ old('body') }}</textarea>
        </div>
        <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-sm font-medium transition">Envoyer</button>
    </form>
</div>
@endsection
