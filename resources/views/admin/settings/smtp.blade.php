@extends('layouts.admin')
@section('title', 'Configuration SMTP — Admin MonFlow')
@section('content')
<div class="mb-6">
    <h1 class="text-base font-semibold text-zinc-100">Configuration SMTP</h1>
    <p class="text-sm text-zinc-500 mt-0.5">Paramètres du serveur d'envoi d'emails</p>
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 max-w-2xl mb-6">
    <form method="POST" action="/admin/settings/smtp">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Nom de la config</label>
                <input name="name" value="{{ old('name', $config->name ?? 'Default') }}" required
                       class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Hôte SMTP</label>
                <input name="host" value="{{ old('host', $config->host ?? '') }}" required placeholder="smtp.example.com"
                       class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Port</label>
                <input name="port" type="number" value="{{ old('port', $config->port ?? 587) }}" required
                       class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Nom d'utilisateur</label>
                <input name="username" value="{{ old('username', $config->username ?? '') }}"
                       class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Mot de passe</label>
                <input name="password" type="password" value="{{ old('password', $config->password ?? '') }}"
                       class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Email expéditeur</label>
                <input name="from_email" type="email" value="{{ old('from_email', $config->from_email ?? '') }}" required
                       class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Nom expéditeur</label>
                <input name="from_name" value="{{ old('from_name', $config->from_name ?? '') }}" required
                       class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
            </div>
            <div class="md:col-span-2 flex gap-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="use_tls" value="1" {{ old('use_tls', $config->use_tls ?? false) ? 'checked' : '' }} class="w-4 h-4 rounded">
                    <span class="text-sm text-zinc-300">Utiliser TLS</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="use_ssl" value="1" {{ old('use_ssl', $config->use_ssl ?? false) ? 'checked' : '' }} class="w-4 h-4 rounded">
                    <span class="text-sm text-zinc-300">Utiliser SSL</span>
                </label>
            </div>
        </div>
        <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Sauvegarder</button>
    </form>
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 max-w-2xl">
    <h2 class="text-sm font-medium text-zinc-300 mb-4">Tester la configuration</h2>
    <form method="POST" action="/admin/settings/smtp" class="flex gap-3">
        @csrf
        <input type="hidden" name="test_email" value="1">
        <input name="test_email_address" type="email" required placeholder="destinataire@example.com"
               class="flex-1 bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
        <button type="submit" class="inline-flex items-center gap-2 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 text-sm font-medium px-4 py-2 rounded-lg border border-emerald-500/20 transition">Envoyer un test</button>
    </form>
</div>
@endsection
