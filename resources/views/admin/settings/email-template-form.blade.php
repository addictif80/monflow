@extends('layouts.admin')
@section('title', ($template ? 'Modifier' : 'Nouveau') . ' template — Admin MonFlow')
@section('content')
<div class="mb-6"><a href="/admin/settings/email-templates" class="text-gray-400 hover:text-gray-200 text-sm">&larr; Retour</a></div>
<h1 class="text-2xl font-bold mb-6">{{ $template ? 'Modifier le template' : 'Nouveau template' }}</h1>
<div class="bg-gray-800 border border-gray-700 rounded-lg p-6 max-w-4xl">
    <form method="POST" action="{{ $template ? '/admin/settings/email-templates/' . $template->id : '/admin/settings/email-templates/create' }}">
        @csrf
        <div class="mb-4">
            <label class="block text-sm text-gray-400 mb-1">Type</label>
            <select name="template_type" required class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                @foreach(['welcome', 'payment_reminder', 'password_reset', 'account_suspended', 'account_deleted', 'gift_received', 'refund_processed', 'subscription_renewed'] as $type)
                    <option value="{{ $type }}" {{ old('template_type', $template->template_type ?? '') === $type ? 'selected' : '' }}>{{ $type }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-4">
            <label class="block text-sm text-gray-400 mb-1">Sujet</label>
            <input name="subject" value="{{ old('subject', $template->subject ?? '') }}" required class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
        </div>
        <div class="mb-4">
            <label class="block text-sm text-gray-400 mb-1">Corps HTML</label>
            <textarea name="html_body" rows="16" required class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm font-mono focus:outline-none focus:border-indigo-500">{{ old('html_body', $template->html_body ?? '') }}</textarea>
            <p class="text-xs text-gray-500 mt-1">Variables disponibles : {{ '{{ username }}' }}, {{ '{{ first_name }}' }}, {{ '{{ site_name }}' }}, {{ '{{ site_url }}' }}, {{ '{{ reset_url }}' }}, {{ '{{ plan }}' }}, {{ '{{ days_overdue }}' }}</p>
        </div>
        <div class="mb-6">
            <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $template->is_active ?? true) ? 'checked' : '' }} class="w-4 h-4 rounded"><span class="text-sm">Actif</span></label>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-medium">{{ $template ? 'Mettre à jour' : 'Créer' }}</button>
            <a href="/admin/settings/email-templates" class="px-6 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm font-medium">Annuler</a>
        </div>
    </form>
</div>
@endsection
