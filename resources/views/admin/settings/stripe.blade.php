@extends('layouts.admin')
@section('title', 'Intégration Stripe — Admin MonFlow')
@section('content')
<div class="mb-6">
    <h1 class="text-base font-semibold text-zinc-100">Intégration Stripe</h1>
    <p class="text-sm text-zinc-500 mt-0.5">Clés lues depuis le fichier <code class="bg-zinc-800 px-1 rounded">.env</code> du serveur — non modifiables depuis cette page</p>
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 max-w-2xl mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-sm font-medium text-zinc-300">Clés configurées</h2>
        @if($mode === 'test')
            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-500/10 text-yellow-400 border border-yellow-500/20">Mode test</span>
        @elseif($mode === 'live')
            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Mode live</span>
        @endif
    </div>

    <div class="space-y-3 text-sm">
        <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
            <span class="text-zinc-500">Clé publique (STRIPE_PUBLIC_KEY)</span>
            @if($publicKeySet)
                <span class="font-mono text-xs text-zinc-300">{{ $publicKeyMasked }}</span>
            @else
                <span class="text-xs text-red-400">non renseignée</span>
            @endif
        </div>
        <div class="flex items-center justify-between border-b border-zinc-800 pb-3">
            <span class="text-zinc-500">Clé secrète (STRIPE_SECRET_KEY)</span>
            @if($secretKeySet)
                <span class="font-mono text-xs text-zinc-300">{{ $secretKeyMasked }}</span>
            @else
                <span class="text-xs text-red-400">non renseignée</span>
            @endif
        </div>
        <div class="flex items-center justify-between">
            <span class="text-zinc-500">Secret webhook (STRIPE_WEBHOOK_SECRET)</span>
            @if($webhookSecretSet)
                <span class="font-mono text-xs text-zinc-300">{{ $webhookSecretMasked }}</span>
            @else
                <span class="text-xs text-red-400">non renseignée</span>
            @endif
        </div>
    </div>

    @if(!$secretKeySet)
        <p class="text-xs text-zinc-600 mt-4">
            Aucune clé secrète détectée. Renseignez <code class="bg-zinc-800 px-1 rounded">STRIPE_PUBLIC_KEY</code>,
            <code class="bg-zinc-800 px-1 rounded">STRIPE_SECRET_KEY</code> et
            <code class="bg-zinc-800 px-1 rounded">STRIPE_WEBHOOK_SECRET</code> dans le fichier <code class="bg-zinc-800 px-1 rounded">.env</code>
            du serveur puis rechargez cette page.
        </p>
    @endif
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 max-w-2xl mb-6">
    <h2 class="text-sm font-medium text-zinc-300 mb-4">Statut de la connexion</h2>
    <div class="flex items-center gap-3 mb-4">
        <span id="connStatusBadge" class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-800 text-zinc-400 border border-zinc-700">Non vérifié</span>
        <span id="connStatusMessage" class="text-xs text-zinc-500"></span>
    </div>
    <button type="button" id="checkConnBtn" onclick="checkConnection()"
            class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-sm font-medium px-4 py-2 rounded-lg border border-zinc-700 transition disabled:opacity-40">
        Vérifier la connexion
    </button>
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 max-w-2xl">
    <h2 class="text-sm font-medium text-zinc-300 mb-2">Paiement test</h2>
    @if($mode === 'test')
        <p class="text-xs text-zinc-500 mb-4">
            Déclenche un vrai appel à l'API Stripe (conditions réelles) qui crée, confirme puis rembourse
            immédiatement un paiement de 1,00 € avec le moyen de paiement de test officiel de Stripe.
            Aucun argent réel n'est engagé puisque la clé configurée est en mode test.
        </p>
        <button type="button" id="testPaymentBtn" onclick="testPayment()"
                class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition disabled:opacity-40">
            Effectuer un paiement test (1,00 €)
        </button>
    @elseif($mode === 'live')
        <p class="text-xs text-amber-400/80 mb-2">
            Clé en mode live : le paiement test est désactivé ici pour éviter tout débit réel accidentel.
            Utilisez temporairement des clés <code class="bg-zinc-800 px-1 rounded">sk_test_...</code> /
            <code class="bg-zinc-800 px-1 rounded">pk_test_...</code> dans le <code class="bg-zinc-800 px-1 rounded">.env</code>
            pour valider le flux de paiement de bout en bout, puis repassez en mode live une fois validé.
        </p>
    @else
        <p class="text-xs text-zinc-600">Configurez une clé secrète pour activer le paiement test.</p>
    @endif
    <pre id="testPaymentOutput" class="hidden mt-4 text-xs text-zinc-400 whitespace-pre-wrap font-mono bg-zinc-950 border border-zinc-800 rounded-lg p-3"></pre>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

function setBadge(el, ok, label) {
    el.textContent = label;
    el.className = 'inline-flex px-2 py-0.5 rounded-full text-xs font-medium border ' +
        (ok ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-red-500/10 text-red-400 border-red-500/20');
}

async function checkConnection() {
    const btn = document.getElementById('checkConnBtn');
    const badge = document.getElementById('connStatusBadge');
    const msg = document.getElementById('connStatusMessage');
    btn.disabled = true;
    badge.textContent = 'Vérification…';
    badge.className = 'inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-800 text-zinc-400 border border-zinc-700';
    msg.textContent = '';

    try {
        const res = await fetch('/admin/settings/stripe/check-connection', {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
        });
        const data = await res.json();
        setBadge(badge, data.success, data.success ? 'Connecté' : 'Erreur');
        msg.textContent = data.message || '';
    } catch (e) {
        setBadge(badge, false, 'Erreur');
        msg.textContent = e.message;
    } finally {
        btn.disabled = false;
    }
}

async function testPayment() {
    const btn = document.getElementById('testPaymentBtn');
    const out = document.getElementById('testPaymentOutput');
    btn.disabled = true;
    out.classList.remove('hidden');
    out.textContent = 'Paiement test en cours…';

    try {
        const res = await fetch('/admin/settings/stripe/test-payment', {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
        });
        const data = await res.json();
        let text = data.message || (data.success ? 'Terminé.' : 'Erreur.');
        if (data.payment_intent_id) text += `\nPaymentIntent : ${data.payment_intent_id}`;
        if (data.refund_id) text += `\nRemboursement : ${data.refund_id}`;
        out.textContent = text;
    } catch (e) {
        out.textContent = 'Erreur : ' + e.message;
    } finally {
        btn.disabled = false;
    }
}

checkConnection();
</script>
@endsection
