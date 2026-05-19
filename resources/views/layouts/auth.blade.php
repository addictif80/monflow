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
            <img src="/icons/icon-192.png" alt="MonFlow" class="w-12 h-12 rounded-xl mb-3">
            <span class="text-lg font-semibold text-zinc-100">MonFlow</span>
        </div>
        @if(session('success'))<div class="mb-4 bg-emerald-500/10 border border-emerald-500/20 rounded-lg p-3 text-sm text-emerald-400">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="mb-4 bg-red-500/10 border border-red-500/20 rounded-lg p-3 text-sm text-red-400">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="mb-4 bg-red-500/10 border border-red-500/20 rounded-lg p-3 text-sm text-red-400 space-y-1">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
        @yield('content')
    </div>
    <footer class="fixed bottom-4 left-0 right-0 text-center text-zinc-700 text-xs">MonFlow &copy; {{ date('Y') }}</footer>
</body>
</html>
