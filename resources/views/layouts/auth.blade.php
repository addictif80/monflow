<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'MonFlow')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/css/monflow.css">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
</head>
<body class="bg-zinc-950 text-zinc-100 min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm">
        <div class="flex flex-col items-center mb-8">
            <div class="relative mb-4">
                <div class="w-14 h-14 rounded-2xl overflow-hidden" style="box-shadow:0 0 40px rgba(99,102,241,.35),0 8px 32px rgba(0,0,0,.5)">
                    <img src="/icons/icon-192.png" alt="MonFlow" class="w-full h-full object-cover">
                </div>
                <div class="absolute inset-0 rounded-2xl" style="box-shadow:inset 0 0 0 1px rgba(255,255,255,.12)"></div>
            </div>
            <span class="text-xl font-bold text-gradient tracking-tight">MonFlow</span>
            <span class="text-xs text-zinc-600 mt-1">Votre musique, librement</span>
        </div>
        @if(session('success'))<div class="mb-4 bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-3 text-sm text-emerald-400">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="mb-4 bg-red-500/10 border border-red-500/20 rounded-xl p-3 text-sm text-red-400">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="mb-4 bg-red-500/10 border border-red-500/20 rounded-xl p-3 text-sm text-red-400 space-y-1">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
        <div style="background:rgba(9,9,20,.75);border:1px solid rgba(255,255,255,.08);backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);border-radius:1rem;padding:1.5rem;box-shadow:0 8px 40px rgba(0,0,0,.5),0 0 0 1px rgba(99,102,241,.06)">
            @yield('content')
        </div>
    </div>
    <footer class="fixed bottom-4 left-0 right-0 text-center text-zinc-700 text-xs">MonFlow &copy; {{ date('Y') }}</footer>
</body>
</html>
