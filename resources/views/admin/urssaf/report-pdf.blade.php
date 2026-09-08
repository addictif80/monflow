<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Déclaration URSSAF — {{ $periodLabel }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #1a1a1a; font-size: 12px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #6366f1; padding-bottom: 16px; margin-bottom: 24px; }
        .logo { font-size: 20px; font-weight: bold; color: #6366f1; }
        .info { text-align: right; font-size: 11px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        th { background: #f3f4f6; text-align: left; padding: 8px 10px; font-size: 10px; color: #666; border-bottom: 1px solid #ddd; text-transform: uppercase; }
        td { padding: 7px 10px; border-bottom: 1px solid #eee; font-size: 11px; }
        .amount { text-align: right; white-space: nowrap; }
        .summary { margin-top: 24px; border-top: 2px solid #18181b; padding-top: 12px; }
        .summary-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 13px; }
        .total-row { font-size: 17px; font-weight: bold; margin-top: 8px; }
        .footer { margin-top: 30px; padding-top: 14px; border-top: 1px solid #eee; font-size: 10px; color: #999; }
        .note { margin-top: 16px; font-size: 10px; color: #71717a; background: #fafafa; border: 1px solid #eee; padding: 10px 12px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <div class="logo">{{ config('app.name') }}</div>
            <div style="margin-top:6px;color:#666">Récapitulatif des revenus perçus</div>
        </div>
        <div class="info">
            <strong>Déclaration URSSAF</strong><br>
            Période : {{ $periodLabel }}<br>
            Du {{ $start->format('d/m/Y') }} au {{ $end->format('d/m/Y') }}<br>
            Généré le {{ now()->format('d/m/Y à H:i') }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Client</th>
                <th>Description</th>
                <th>Moyen</th>
                <th class="amount">Montant</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $p)
                <tr>
                    <td>{{ $p->created_at->format('d/m/Y') }}</td>
                    <td>{{ $p->user->email ?? '—' }}</td>
                    <td>{{ $p->description }}</td>
                    <td>{{ ucfirst($p->payment_method) }}</td>
                    <td class="amount">{{ number_format($p->amount, 2, ',', ' ') }} &euro;</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center;color:#999">Aucune transaction sur cette période</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary">
        <div class="summary-row"><span>Nombre de transactions</span><span>{{ $payments->count() }}</span></div>
        <div class="summary-row total-row"><span>Chiffre d'affaires à déclarer</span><span>{{ number_format($total, 2, ',', ' ') }} &euro;</span></div>
    </div>

    <div class="note">
        Ce document est un récapitulatif interne généré automatiquement à partir des paiements
        enregistrés comme réussis sur {{ config('app.name') }} pour la période indiquée. Il ne
        constitue pas un document comptable officiel. Vérifiez ces montants avant de les reporter
        dans votre déclaration de chiffre d'affaires auto-entrepreneur sur le site de l'URSSAF.
    </div>

    <div class="footer">
        {{ config('app.name') }} &mdash; {{ config('app.url') }}
    </div>
</body>
</html>
