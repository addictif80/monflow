@extends('layouts.app')
@section('title', 'Nouveau feedback — MonFlow')
@section('content')
<div class="mb-4">
    <a href="/portal/feedback" class="text-sm text-zinc-500 hover:text-zinc-300">&larr; Retour aux feedbacks</a>
</div>

<div class="mb-6">
    <h1 class="text-base font-semibold text-zinc-100">Envoyer un feedback</h1>
    <p class="text-sm text-zinc-500 mt-0.5">Partagez vos observations ou suggestions</p>
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-6 max-w-2xl">
    <form action="/portal/feedback/create" method="POST" class="space-y-4">
        @csrf
        <div>
            <label class="block text-xs font-medium text-zinc-400 mb-1.5">Type</label>
            <select name="type" class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
                <option value="bug" {{ old('type') === 'bug' ? 'selected' : '' }}>Bug</option>
                <option value="suggestion" {{ old('type') === 'suggestion' ? 'selected' : '' }}>Suggestion</option>
                <option value="ui" {{ old('type') === 'ui' ? 'selected' : '' }}>Interface</option>
                <option value="performance" {{ old('type') === 'performance' ? 'selected' : '' }}>Performance</option>
                <option value="other" {{ old('type') === 'other' ? 'selected' : '' }}>Autre</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-zinc-400 mb-1.5">Sujet</label>
            <input type="text" name="subject" value="{{ old('subject') }}" placeholder="Résumez votre feedback en une phrase" class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
        </div>
        <div>
            <label class="block text-xs font-medium text-zinc-400 mb-1.5">Description</label>
            <textarea name="body" rows="6" placeholder="Décrivez en détail ce que vous avez observé ou ce que vous suggérez..." class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">{{ old('body') }}</textarea>
        </div>
        <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Envoyer</button>
    </form>
</div>
@endsection
