@extends('layouts.admin')
@section('title', 'Déclaration URSSAF — Admin MonFlow')
@section('content')
<div class="mb-6">
    <h1 class="text-base font-semibold text-zinc-100">Déclaration URSSAF</h1>
    <p class="text-sm text-zinc-500 mt-0.5">Rapport mensuel automatique du chiffre d'affaires à déclarer (auto-entrepreneur)</p>
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 max-w-md mb-6">
    <form method="POST" action="/admin/settings/urssaf-report">
        @csrf
        <div class="mb-4">
            <label class="block text-xs font-medium text-zinc-400 mb-1.5">Jour du mois d'envoi</label>
            <input name="urssaf_report_day" type="number" min="1" max="28" step="1"
                   value="{{ old('urssaf_report_day', $settings->urssaf_report_day) }}" required
                   class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
            <p class="text-xs text-zinc-600 mt-1.5">Le rapport du mois précédent est généré et envoyé automatiquement ce jour-là (limité au 28 pour être valide tous les mois).</p>
        </div>
        <div class="mb-4">
            <label class="block text-xs font-medium text-zinc-400 mb-1.5">Adresse email de destination</label>
            <input name="urssaf_report_email" type="email"
                   value="{{ old('urssaf_report_email', $settings->urssaf_report_email) }}" required
                   class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
        </div>
        <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Sauvegarder</button>
    </form>
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5 max-w-md mb-6">
    <h2 class="text-sm font-semibold text-zinc-200 mb-2">Générer manuellement</h2>
    <p class="text-xs text-zinc-600 mb-3">Génère et envoie immédiatement le rapport du mois précédent, sans attendre la date programmée.</p>
    <form method="POST" action="/admin/settings/urssaf-report/generate">
        @csrf
        <button type="submit" class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-100 text-sm font-medium px-4 py-2 rounded-lg transition">Générer et envoyer maintenant</button>
    </form>
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
    <div class="p-5 border-b border-zinc-800">
        <h2 class="text-sm font-semibold text-zinc-200">Historique des rapports</h2>
    </div>
    <table class="w-full text-sm">
        <thead>
            <tr class="text-left text-xs text-zinc-500 border-b border-zinc-800">
                <th class="px-5 py-2.5 font-medium">Période</th>
                <th class="px-5 py-2.5 font-medium">Transactions</th>
                <th class="px-5 py-2.5 font-medium">Chiffre d'affaires</th>
                <th class="px-5 py-2.5 font-medium">Statut</th>
                <th class="px-5 py-2.5 font-medium">Envoyé à</th>
                <th class="px-5 py-2.5 font-medium"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($reports as $r)
                <tr class="border-b border-zinc-800/50">
                    <td class="px-5 py-2.5 text-zinc-300">{{ $r->period_month->format('m/Y') }}</td>
                    <td class="px-5 py-2.5 text-zinc-400">{{ $r->transaction_count }}</td>
                    <td class="px-5 py-2.5 text-zinc-300">{{ number_format($r->total_amount, 2, ',', ' ') }} &euro;</td>
                    <td class="px-5 py-2.5">
                        @php $colors = ['sent' => 'emerald', 'failed' => 'red', 'pending' => 'yellow']; $c = $colors[$r->status] ?? 'zinc'; @endphp
                        <span class="text-xs px-2 py-0.5 rounded-full bg-{{ $c }}-500/10 text-{{ $c }}-400">{{ $r->status }}</span>
                    </td>
                    <td class="px-5 py-2.5 text-zinc-500 text-xs">{{ $r->sent_to ?? '—' }}</td>
                    <td class="px-5 py-2.5 text-right">
                        <a href="/admin/settings/urssaf-report/{{ $r->id }}/download" class="text-indigo-400 hover:text-indigo-300 text-xs">Télécharger</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-5 py-6 text-center text-zinc-600 text-sm">Aucun rapport généré pour l'instant.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
