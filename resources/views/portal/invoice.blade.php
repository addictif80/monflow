<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture #{{ $payment->id }} — MonFlow</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 700px; margin: 40px auto; color: #1a1a1a; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #6366f1; padding-bottom: 20px; margin-bottom: 30px; }
        .logo { font-size: 24px; font-weight: bold; color: #6366f1; }
        .info { text-align: right; font-size: 13px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #f3f4f6; text-align: left; padding: 10px 12px; font-size: 13px; color: #666; border-bottom: 1px solid #ddd; }
        td { padding: 10px 12px; border-bottom: 1px solid #eee; font-size: 14px; }
        .total { font-size: 18px; font-weight: bold; text-align: right; margin-top: 20px; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #999; text-align: center; }
        .status { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; }
        .status-succeeded { background: #d1fae5; color: #065f46; }
        .status-failed { background: #fee2e2; color: #991b1b; }
        .status-refunded { background: #e5e7eb; color: #374151; }
        @media print { .no-print { display: none; } body { margin: 20px; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:20px">
        <button onclick="window.print()" style="background:#6366f1;color:#fff;border:none;padding:8px 16px;border-radius:6px;cursor:pointer;font-size:14px">Imprimer / PDF</button>
        <a href="/portal/payments" style="margin-left:10px;color:#6366f1;text-decoration:none;font-size:14px">&larr; Retour aux paiements</a>
    </div>
    <div class="header">
        <div>
            <div class="logo">MonFlow</div>
            <div style="margin-top:8px;font-size:13px;color:#666">{{ config('app.url') }}</div>
        </div>
        <div class="info">
            <strong>Facture</strong><br>
            N° {{ strtoupper(substr($payment->id, 0, 8)) }}<br>
            {{ $payment->created_at->format('d/m/Y') }}
        </div>
    </div>
    <div style="margin-bottom:20px">
        <strong>Client :</strong><br>
        {{ $user->first_name }} {{ $user->last_name }}<br>
        {{ $user->email }}<br>
        @if($user->username)<span style="color:#666">@{{ $user->username }}</span>@endif
    </div>
    <table>
        <thead><tr><th>Description</th><th>Méthode</th><th style="text-align:right">Montant</th></tr></thead>
        <tbody>
            <tr>
                <td>{{ $payment->description }}</td>
                <td>{{ ucfirst($payment->payment_method) }}</td>
                <td style="text-align:right">{{ number_format($payment->amount, 2, ',', ' ') }} &euro;</td>
            </tr>
        </tbody>
    </table>
    <div class="total">
        Total : {{ number_format($payment->amount, 2, ',', ' ') }} &euro;
        <span class="status status-{{ $payment->status }}">{{ $payment->status }}</span>
    </div>
    <div class="footer">
        MonFlow &mdash; {{ config('app.url') }}<br>
        Document généré le {{ now()->format('d/m/Y à H:i') }}
    </div>
</body>
</html>
