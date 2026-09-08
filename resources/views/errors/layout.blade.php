<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Erreur') — MonFlow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/css/monflow.css">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
</head>
<body class="bg-zinc-950 text-zinc-100 min-h-screen flex items-center justify-center px-4">
    <div class="text-center max-w-md w-full">
        <div class="mb-8">
            <a href="/" class="inline-flex items-center gap-2 justify-center">
                <img src="/icons/icon-192.png" alt="MonFlow" class="w-8 h-8 rounded-xl">
                <span class="text-lg font-bold text-gradient">MonFlow</span>
            </a>
        </div>

        <div class="mb-6">
            <span class="text-6xl font-black text-gradient block mb-4">@yield('code', '?')</span>
            <h1 class="text-xl font-semibold text-zinc-100 mb-2">@yield('heading', 'Une erreur est survenue')</h1>
            <p class="text-sm text-zinc-500 leading-relaxed">@yield('message', 'Veuillez réessayer ou contacter le support si le problème persiste.')</p>
        </div>

        <div class="flex items-center justify-center gap-3 flex-wrap">
            @yield('actions')
        </div>
    </div>
    <footer class="fixed bottom-4 left-0 right-0 text-center text-zinc-700 text-xs">MonFlow &copy; {{ date('Y') }}</footer>
</body>
</html>
