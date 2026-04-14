@extends('layouts.admin')
@section('title', 'Configuration SMTP — Admin MonFlow')
@section('content')
<h1 class="text-2xl font-bold mb-6">Configuration SMTP</h1>

<div class="bg-gray-800 border border-gray-700 rounded-lg p-6 max-w-2xl mb-6">
    <form method="POST" action="/admin/settings/smtp">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div class="md:col-span-2"><label class="block text-sm text-gray-400 mb-1">Nom de la config</label><input name="name" value="{{ old('name', $config->name ?? 'Default') }}" required class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500"></div>
            <div><label class="block text-sm text-gray-400 mb-1">Hôte SMTP</label><input name="host" value="{{ old('host', $config->host ?? '') }}" required placeholder="smtp.example.com" class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500"></div>
            <div><label class="block text-sm text-gray-400 mb-1">Port</label><input name="port" type="number" value="{{ old('port', $config->port ?? 587) }}" required class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500"></div>
            <div><label class="block text-sm text-gray-400 mb-1">Nom d'utilisateur</label><input name="username" value="{{ old('username', $config->username ?? '') }}" class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500"></div>
            <div><label class="block text-sm text-gray-400 mb-1">Mot de passe</label><input name="password" type="password" value="{{ old('password', $config->password ?? '') }}" class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500"></div>
            <div><label class="block text-sm text-gray-400 mb-1">Email expéditeur</label><input name="from_email" type="email" value="{{ old('from_email', $config->from_email ?? '') }}" required class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500"></div>
            <div><label class="block text-sm text-gray-400 mb-1">Nom expéditeur</label><input name="from_name" value="{{ old('from_name', $config->from_name ?? '') }}" required class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500"></div>
            <div class="md:col-span-2 flex gap-4">
                <label class="flex items-center gap-2"><input type="checkbox" name="use_tls" value="1" {{ old('use_tls', $config->use_tls ?? false) ? 'checked' : '' }} class="w-4 h-4 rounded"><span class="text-sm">Utiliser TLS</span></label>
                <label class="flex items-center gap-2"><input type="checkbox" name="use_ssl" value="1" {{ old('use_ssl', $config->use_ssl ?? false) ? 'checked' : '' }} class="w-4 h-4 rounded"><span class="text-sm">Utiliser SSL</span></label>
            </div>
        </div>
        <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-medium">Sauvegarder</button>
    </form>
</div>

<div class="bg-gray-800 border border-gray-700 rounded-lg p-6 max-w-2xl">
    <h2 class="text-lg font-semibold mb-4">Tester la configuration</h2>
    <form method="POST" action="/admin/settings/smtp" class="flex gap-3">
        @csrf
        <input type="hidden" name="test_email" value="1">
        <input name="test_email_address" type="email" required placeholder="destinataire@example.com" class="flex-1 px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
        <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-500 rounded-lg text-sm font-medium">Envoyer un test</button>
    </form>
</div>
@endsection
