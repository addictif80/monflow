@extends('layouts.app')

@section('title', 'Formules - MonFlow')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold">Nos formules</h1>
    <p class="text-gray-400 mt-1">Choisissez la formule qui vous convient</p>
</div>

{{-- Promo Code --}}
<div class="bg-gray-800 rounded-lg border border-gray-700 p-4 mb-8 max-w-md">
    <label for="promo_code" class="block text-sm font-medium text-gray-300 mb-2">Code promo</label>
    <div class="flex gap-2">
        <input type="text" id="promo_code" placeholder="Entrez votre code promo"
               class="flex-1 px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm">
        <button type="button" onclick="applyPromo()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">
            Appliquer
        </button>
    </div>
</div>

{{-- Plans Grid --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @foreach($plans as $plan)
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6 flex flex-col relative">
            @if($activeSub && $activeSub->plan_id === $plan->id)
                <div class="absolute top-4 right-4">
                    <span class="px-3 py-1 text-xs rounded-full bg-green-900/50 text-green-400 border border-green-700 font-medium">
                        Abonnement actif
                    </span>
                </div>
            @endif

            <h3 class="text-xl font-bold text-indigo-400 mb-2">{{ $plan->name }}</h3>
            <p class="text-gray-400 text-sm mb-4 flex-1">{{ $plan->description }}</p>

            <div class="mb-4">
                <span class="text-3xl font-bold">{{ number_format($plan->price, 2, ',', ' ') }}&euro;</span>
                <span class="text-gray-400 text-sm">/{{ $plan->billing_cycle }}</span>
            </div>

            <ul class="space-y-2 mb-6 text-sm">
                <li class="flex items-center text-gray-300">
                    <svg class="w-4 h-4 text-indigo-400 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    {{ $plan->max_devices }} appareil(s) maximum
                </li>
            </ul>

            @if($activeSub && $activeSub->plan_id === $plan->id)
                <button disabled class="w-full px-4 py-2 bg-gray-600 text-gray-400 rounded-lg text-sm font-medium cursor-not-allowed">
                    Formule actuelle
                </button>
            @elseif($activeSub)
                <button disabled class="w-full px-4 py-2 bg-gray-600 text-gray-400 rounded-lg text-sm font-medium cursor-not-allowed">
                    Abonnement déjà actif
                </button>
            @else
                <div class="space-y-2">
                    <label class="block text-xs text-gray-400">Durée du paiement</label>
                    <select class="prepay-duration w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm" data-plan="{{ $plan->id }}">
                        <option value="1">1 mois — {{ number_format($plan->price, 2, ',', ' ') }} € (récurrent)</option>
                        <option value="3">3 mois — {{ number_format($plan->price * 3, 2, ',', ' ') }} € (prépayé)</option>
                        <option value="6">6 mois — {{ number_format($plan->price * 6, 2, ',', ' ') }} € (prépayé)</option>
                        <option value="12">12 mois — {{ number_format($plan->price * 12, 2, ',', ' ') }} € (prépayé)</option>
                    </select>
                    <a href="/portal/subscribe/{{ $plan->id }}" class="subscribe-link w-full px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition text-center block"
                       data-base-url="/portal/subscribe/{{ $plan->id }}" data-plan="{{ $plan->id }}">
                        S'abonner
                    </a>
                </div>
            @endif
        </div>
    @endforeach
</div>

<script>
    function buildUrl(link) {
        const baseUrl = link.getAttribute('data-base-url');
        const plan = link.getAttribute('data-plan');
        const code = document.getElementById('promo_code').value.trim();
        const sel = document.querySelector('.prepay-duration[data-plan="' + plan + '"]');
        const months = sel ? sel.value : '1';
        const params = new URLSearchParams();
        if (months && months !== '1') params.set('months', months);
        if (code) params.set('promo', code);
        const qs = params.toString();
        link.href = baseUrl + (qs ? '?' + qs : '');
    }
    function applyPromo() {
        document.querySelectorAll('.subscribe-link').forEach(buildUrl);
    }
    document.querySelectorAll('.prepay-duration').forEach(function(sel) {
        sel.addEventListener('change', function() {
            const plan = sel.getAttribute('data-plan');
            const link = document.querySelector('.subscribe-link[data-plan="' + plan + '"]');
            if (link) buildUrl(link);
        });
    });
</script>
@endsection
