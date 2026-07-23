@extends('layouts.admin')

@section('title', $user->username . ' — Admin MonFlow')

@section('content')
<div class="mb-6">
    <a href="/admin/users" class="text-sm text-zinc-500 hover:text-zinc-300">&larr; Retour aux utilisateurs</a>
</div>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-base font-semibold text-zinc-100">{{ $user->username }}</h1>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="/admin/users/{{ $user->id }}/edit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Modifier</a>

        @if(!$user->is_admin && $user->status !== 'deleted')
            <form method="POST" action="/admin/users/{{ $user->id }}/impersonate" onsubmit="return confirm('Se connecter en tant que {{ $user->username }} ?')">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 text-sm font-medium px-4 py-2 rounded-lg border border-amber-500/20 transition">Impersonate</button>
            </form>
        @endif

        @if($user->status === 'active')
            <form method="POST" action="/admin/users/{{ $user->id }}/suspend" onsubmit="return confirm('Suspendre cet utilisateur ?')">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 bg-yellow-500/10 hover:bg-yellow-500/20 text-yellow-400 text-sm font-medium px-4 py-2 rounded-lg border border-yellow-500/20 transition">Suspendre</button>
            </form>
        @elseif($user->status === 'suspended')
            <form method="POST" action="/admin/users/{{ $user->id }}/reactivate">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 text-sm font-medium px-4 py-2 rounded-lg border border-emerald-500/20 transition">Réactiver</button>
            </form>
        @elseif($user->status === 'deleted' && $user->deleted_with_data_kept)
            <form method="POST" action="/admin/users/{{ $user->id }}/reactivate" onsubmit="return confirm('Réactiver ce compte ? Le mot de passe Navidrome original sera restauré et l\'accès rétabli. Pensez à facturer les frais de restauration si applicable.')">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 text-sm font-medium px-4 py-2 rounded-lg border border-emerald-500/20 transition">Réactiver (données conservées)</button>
            </form>
        @endif

        @if($user->status !== 'deleted')
            <form method="POST" action="/admin/users/{{ $user->id }}/delete" onsubmit="return confirmDelete(this)" class="inline-flex items-center gap-2">
                @csrf
                <label class="inline-flex items-center gap-1.5 text-xs text-zinc-500 cursor-pointer whitespace-nowrap">
                    <input type="checkbox" name="keep_data" value="1" class="accent-indigo-500 w-3.5 h-3.5">
                    Conserver les données
                </label>
                <button type="submit" class="inline-flex items-center gap-2 bg-red-500/10 hover:bg-red-500/15 text-red-400 text-sm font-medium px-4 py-2 rounded-lg border border-red-500/20 transition">Supprimer</button>
            </form>
        @else
            @if(!str_starts_with($user->email, 'released_'))
                <form method="POST" action="/admin/users/{{ $user->id }}/release-email" onsubmit="return confirm('Libérer {{ $user->email }} ? Cette adresse pourra être réutilisée par un autre compte.')">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 bg-orange-500/10 hover:bg-orange-500/20 text-orange-400 text-sm font-medium px-4 py-2 rounded-lg border border-orange-500/20 transition">Libérer l'email</button>
                </form>
            @endif
        @endif
    </div>
</div>

{{-- User Info Card --}}
<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 mb-6">
    <h2 class="text-sm font-medium text-zinc-300 mb-4">Informations</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
        <div>
            <span class="text-xs text-zinc-500">Nom d'utilisateur</span>
            <p class="font-medium text-zinc-200 mt-0.5">{{ $user->username }}</p>
        </div>
        <div>
            <span class="text-xs text-zinc-500">Email</span>
            <p class="font-medium text-zinc-200 mt-0.5">{{ $user->email }}</p>
        </div>
        <div>
            <span class="text-xs text-zinc-500">Nom complet</span>
            <p class="font-medium text-zinc-200 mt-0.5">{{ $user->full_name ?: '—' }}</p>
        </div>
        <div>
            <span class="text-xs text-zinc-500">Statut</span>
            <p class="mt-1">
                @if($user->status === 'active')
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">active</span>
                @elseif($user->status === 'suspended')
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20">suspendu</span>
                @elseif($user->status === 'deleted')
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-800 text-zinc-500 border border-zinc-700">supprimé</span>
                    @if($user->deleted_with_data_kept)
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-amber-500/10 text-amber-400 border border-amber-500/20 ml-1">données conservées</span>
                    @endif
                @endif
            </p>
        </div>
        <div>
            <span class="text-xs text-zinc-500">Navidrome ID</span>
            <p class="font-medium font-mono text-xs text-zinc-400 mt-0.5">{{ $user->navidrome_id ?: '—' }}</p>
        </div>
        <div>
            <span class="text-xs text-zinc-500">Stripe Customer ID</span>
            <p class="font-medium font-mono text-xs text-zinc-400 mt-0.5">{{ $user->stripe_customer_id ?: '—' }}</p>
        </div>
        <div>
            <span class="text-xs text-zinc-500">Créé le</span>
            <p class="font-medium text-zinc-200 mt-0.5">{{ $user->created_at->format('d/m/Y H:i') }}</p>
        </div>
        <div>
            <span class="text-xs text-zinc-500">Mot de passe Navidrome</span>
            <div class="mt-0.5 flex items-center gap-2">
                <p id="password-value" class="font-medium font-mono text-xs text-zinc-400">••••••••</p>
                <button type="button" id="reveal-password-btn"
                        data-url="/admin/users/{{ $user->id }}/reveal-password"
                        class="text-xs text-indigo-400 hover:text-indigo-300 underline">Afficher</button>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(form) {
    const keepData = form.querySelector('[name=keep_data]').checked;
    const msg = keepData
        ? "Supprimer cet utilisateur en conservant ses données ? Son compte Navidrome et ses playlists ne seront PAS supprimés, et il recevra un mail l'informant qu'il peut récupérer son compte moyennant les frais de restauration configurés."
        : "Supprimer cet utilisateur ? Cette action est irréversible : son compte Navidrome et toutes ses données (playlists, historique) seront définitivement supprimés.";
    return confirm(msg);
}

document.getElementById('reveal-password-btn')?.addEventListener('click', async function () {
    const btn = this;
    const valueEl = document.getElementById('password-value');
    if (btn.dataset.revealed === '1') {
        valueEl.textContent = '••••••••';
        btn.textContent = 'Afficher';
        btn.dataset.revealed = '0';
        return;
    }
    btn.disabled = true;
    try {
        const res = await fetch(btn.dataset.url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
        });
        const data = await res.json();
        if (data.success) {
            valueEl.textContent = data.password;
            btn.textContent = 'Masquer';
            btn.dataset.revealed = '1';
        } else {
            valueEl.textContent = data.message || 'Indisponible';
        }
    } catch (e) {
        valueEl.textContent = 'Erreur';
    } finally {
        btn.disabled = false;
    }
});
</script>

{{-- Wallet Section --}}
<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 mb-6">
    <h2 class="text-sm font-medium text-zinc-300 mb-4">Portefeuille</h2>
    <div class="flex items-center gap-6 mb-4">
        <div>
            <span class="text-xs text-zinc-500">Solde actuel</span>
            <p class="text-2xl font-semibold text-emerald-400 mt-0.5">{{ number_format($user->wallet_balance ?? 0, 2, ',', ' ') }} &euro;</p>
        </div>
    </div>
    <form method="POST" action="/admin/users/{{ $user->id }}/wallet-adjust" class="flex flex-wrap gap-3 items-end">
        @csrf
        <div>
            <label for="amount" class="block text-xs font-medium text-zinc-400 mb-1.5">Montant (+/-)</label>
            <input type="number" id="amount" name="amount" step="0.01" required
                   class="w-40 bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
        </div>
        <div class="flex-1 min-w-[200px]">
            <label for="description" class="block text-xs font-medium text-zinc-400 mb-1.5">Description</label>
            <input type="text" id="description" name="description" required
                   class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
        </div>
        <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Ajuster</button>
    </form>
</div>

{{-- Subscriptions --}}
<div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden mb-6">
    <div class="px-4 py-3 border-b border-zinc-800">
        <h2 class="text-sm font-medium text-zinc-300">Abonnements</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-800">
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Formule</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Statut</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Début</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Fin</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800/50">
                @forelse($subscriptions as $sub)
                    <tr class="hover:bg-zinc-800/30 transition">
                        <td class="px-4 py-3 text-zinc-300">{{ $sub->plan->name }}</td>
                        <td class="px-4 py-3">
                            @if($sub->status === 'active')
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">active</span>
                            @elseif($sub->status === 'cancelled')
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-800 text-zinc-500 border border-zinc-700">annulé</span>
                            @elseif($sub->status === 'pending')
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-500/10 text-yellow-400 border border-yellow-500/20">en attente</span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-800 text-zinc-500 border border-zinc-700">{{ $sub->status }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-500">{{ $sub->current_period_start ? $sub->current_period_start->format('d/m/Y') : '—' }}</td>
                        <td class="px-4 py-3 text-zinc-500">{{ $sub->current_period_end ? $sub->current_period_end->format('d/m/Y') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-zinc-600">Aucun abonnement.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Payments --}}
<div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
    <div class="px-4 py-3 border-b border-zinc-800">
        <h2 class="text-sm font-medium text-zinc-300">Paiements</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-800">
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Montant</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Statut</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Description</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800/50">
                @forelse($payments as $payment)
                    <tr class="hover:bg-zinc-800/30 transition">
                        <td class="px-4 py-3 text-zinc-300">{{ number_format($payment->amount, 2, ',', ' ') }} &euro;</td>
                        <td class="px-4 py-3">
                            @if($payment->status === 'succeeded')
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">{{ $payment->status }}</span>
                            @elseif($payment->status === 'pending')
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-500/10 text-yellow-400 border border-yellow-500/20">{{ $payment->status }}</span>
                            @elseif($payment->status === 'failed')
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20">{{ $payment->status }}</span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-800 text-zinc-500 border border-zinc-700">{{ $payment->status }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-500">{{ $payment->description ?: '—' }}</td>
                        <td class="px-4 py-3 text-zinc-500">{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-zinc-600">Aucun paiement.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
