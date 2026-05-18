<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'MonFlow')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen flex flex-col">
    <nav class="bg-gray-800 border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 flex items-center justify-between h-16">
            <a href="/portal" class="text-xl font-bold text-indigo-400">MonFlow</a>
            <div class="flex items-center gap-4 text-sm">
                <a href="/portal" class="hover:text-indigo-400">Tableau de bord</a>
                <a href="/portal/plans" class="hover:text-indigo-400">Formules</a>
                <a href="/portal/wallet" class="hover:text-indigo-400">Portefeuille</a>
                <a href="/portal/payments" class="hover:text-indigo-400">Paiements</a>
                <a href="/portal/devices" class="hover:text-indigo-400">Appareils</a>
                <a href="/support/tickets" class="hover:text-indigo-400">Support</a>
                <a href="/portal/feedback" class="hover:text-indigo-400">Feedback</a>
                <a href="/player" class="hover:text-indigo-400 text-indigo-300 font-medium">Lecteur</a>
                <a href="/portal/deemix" class="hover:text-indigo-400 text-indigo-300 font-medium">Deemix</a>
                @php $unreadCount = \App\Models\Notification::where('user_id', Auth::id())->whereNull('read_at')->count(); @endphp
                <a href="/portal/notifications" class="relative hover:text-indigo-400">
                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    @if($unreadCount > 0)<span class="absolute -top-1 -right-2 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>@endif
                </a>
                <span class="text-gray-400">|</span>
                <a href="/portal/profile" class="text-gray-300 hover:text-indigo-400">{{ Auth::user()->username }}</a>
                <form action="/logout" method="POST" class="inline">@csrf<button class="hover:text-red-400">Déconnexion</button></form>
            </div>
        </div>
    </nav>
    @if(session('impersonating_admin_id'))
    <div class="bg-amber-500 text-gray-900 text-sm font-medium">
        <div class="max-w-7xl mx-auto px-4 py-2 flex items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                Mode observation — vous naviguez en tant que <span class="font-bold ml-1">{{ Auth::user()->username }}</span>
            </div>
            <form action="/portal/stop-impersonate" method="POST">
                @csrf
                <button type="submit" class="px-3 py-1 bg-gray-900 hover:bg-gray-800 text-amber-400 rounded text-xs font-semibold transition">
                    ← Revenir admin
                </button>
            </form>
        </div>
    </div>
    @endif
    <main class="flex-1 max-w-7xl mx-auto w-full px-4 py-8">
        @if(session('success'))<div class="mb-4 p-3 bg-green-900/50 border border-green-700 rounded text-green-300">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="mb-4 p-3 bg-red-900/50 border border-red-700 rounded text-red-300">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="mb-4 p-3 bg-red-900/50 border border-red-700 rounded text-red-300">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
        @yield('content')
    </main>
    <footer class="bg-gray-800 border-t border-gray-700 py-4 text-center text-gray-500 text-sm">MonFlow &copy; {{ date('Y') }}</footer>
</body>
</html>
