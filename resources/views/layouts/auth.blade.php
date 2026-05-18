<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'MonFlow')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32.png">
    <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md p-8">
        <h1 class="text-3xl font-bold text-center text-indigo-400 mb-8">MonFlow</h1>
        @if(session('success'))<div class="mb-4 p-3 bg-green-900/50 border border-green-700 rounded text-green-300">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="mb-4 p-3 bg-red-900/50 border border-red-700 rounded text-red-300">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="mb-4 p-3 bg-red-900/50 border border-red-700 rounded text-red-300">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
        @yield('content')
    </div>
</body>
</html>
