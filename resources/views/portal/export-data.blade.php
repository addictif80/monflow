<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Export de mes données — MonFlow</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f172a; color: #e2e8f0; line-height: 1.6; }
  .page { max-width: 860px; margin: 0 auto; padding: 40px 24px 80px; }

  /* Header */
  .header { display: flex; align-items: center; justify-content: space-between; padding-bottom: 24px; border-bottom: 2px solid #1e3a5f; margin-bottom: 32px; }
  .header-brand { font-size: 24px; font-weight: 800; color: #818cf8; letter-spacing: -0.5px; }
  .header-meta { text-align: right; font-size: 12px; color: #64748b; line-height: 1.8; }
  .header-meta strong { color: #94a3b8; display: block; font-size: 13px; }

  /* Section */
  .section { margin-bottom: 40px; }
  .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.2px; color: #6366f1; margin-bottom: 14px; padding-bottom: 6px; border-bottom: 1px solid #1e293b; }

  /* Card */
  .card { background: #1e293b; border: 1px solid #334155; border-radius: 10px; overflow: hidden; }
  .card + .card { margin-top: 12px; }

  /* Profile grid */
  .profile-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0; }
  .profile-item { padding: 14px 18px; border-bottom: 1px solid #0f172a; border-right: 1px solid #0f172a; }
  .profile-item:last-child { border-right: none; }
  .label { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 3px; }
  .value { font-size: 14px; color: #f1f5f9; font-weight: 500; word-break: break-all; }
  .value.mono { font-family: 'SF Mono', 'Fira Code', monospace; font-size: 13px; }

  /* Badge */
  .badge { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; }
  .badge-green  { background: #14532d; color: #4ade80; border: 1px solid #166534; }
  .badge-yellow { background: #713f12; color: #fbbf24; border: 1px solid #92400e; }
  .badge-red    { background: #450a0a; color: #f87171; border: 1px solid #7f1d1d; }
  .badge-gray   { background: #1e293b; color: #94a3b8; border: 1px solid #334155; }
  .badge-blue   { background: #1e3a5f; color: #60a5fa; border: 1px solid #1d4ed8; }

  /* Table */
  table { width: 100%; border-collapse: collapse; font-size: 13px; }
  thead { background: #0f172a; }
  th { padding: 10px 16px; text-align: left; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.7px; color: #64748b; }
  td { padding: 11px 16px; border-top: 1px solid #0f172a; color: #cbd5e1; vertical-align: top; }
  tr:last-child td { border-bottom: none; }
  .amount-pos { color: #4ade80; font-weight: 600; }
  .amount-neg { color: #f87171; font-weight: 600; }
  .amount-neutral { color: #e2e8f0; font-weight: 600; }
  .text-muted { color: #64748b; font-size: 12px; }
  .text-right { text-align: right; }

  /* Ticket thread */
  .ticket-header { padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
  .ticket-subject { font-weight: 600; color: #f1f5f9; }
  .ticket-messages { border-top: 1px solid #0f172a; }
  .message { padding: 12px 18px; border-bottom: 1px solid #0f172a; }
  .message:last-child { border-bottom: none; }
  .message-meta { font-size: 11px; color: #64748b; margin-bottom: 4px; }
  .message-staff { background: #1a2744; }
  .message-body { font-size: 13px; color: #cbd5e1; white-space: pre-wrap; word-break: break-word; }
  .no-messages { padding: 12px 18px; font-size: 12px; color: #475569; font-style: italic; }

  /* Empty state */
  .empty { padding: 24px 18px; text-align: center; color: #475569; font-size: 13px; font-style: italic; }

  /* Footer */
  .footer { margin-top: 60px; padding-top: 20px; border-top: 1px solid #1e293b; font-size: 11px; color: #334155; text-align: center; line-height: 2; }

  @media print {
    body { background: #fff; color: #1e293b; }
    .card { border-color: #e2e8f0; background: #f8fafc; }
    .header { border-color: #e2e8f0; }
    .section-title { color: #6366f1; border-color: #e2e8f0; }
    thead { background: #f1f5f9; }
    td, th { border-color: #e2e8f0; }
    .message-staff { background: #eff6ff; }
    .ticket-messages, .message { border-color: #e2e8f0; }
    .profile-item { border-color: #e2e8f0; }
    .label { color: #94a3b8; }
    .value { color: #0f172a; }
    td { color: #334155; }
    .text-muted { color: #94a3b8; }
    .footer { color: #94a3b8; border-color: #e2e8f0; }
  }
</style>
</head>
<body>
<div class="page">

  {{-- Header --}}
  <div class="header">
    <div class="header-brand">MonFlow</div>
    <div class="header-meta">
      <strong>Export de données personnelles</strong>
      Généré le {{ now()->format('d/m/Y à H:i') }}<br>
      Conformément au Règlement Général sur la Protection des Données (RGPD)
    </div>
  </div>

  {{-- Profil --}}
  <div class="section">
    <div class="section-title">Profil</div>
    <div class="card">
      <div class="profile-grid">
        <div class="profile-item">
          <div class="label">Nom d'utilisateur</div>
          <div class="value mono">{{ $user->username }}</div>
        </div>
        <div class="profile-item">
          <div class="label">Adresse email</div>
          <div class="value">{{ $user->email }}</div>
        </div>
        <div class="profile-item">
          <div class="label">Prénom</div>
          <div class="value">{{ $user->first_name ?: '—' }}</div>
        </div>
        <div class="profile-item">
          <div class="label">Nom</div>
          <div class="value">{{ $user->last_name ?: '—' }}</div>
        </div>
        <div class="profile-item">
          <div class="label">Téléphone</div>
          <div class="value">{{ $user->phone ?: '—' }}</div>
        </div>
        <div class="profile-item">
          <div class="label">Statut</div>
          <div class="value">
            @if($user->status === 'active') <span class="badge badge-green">Actif</span>
            @elseif($user->status === 'suspended') <span class="badge badge-yellow">Suspendu</span>
            @elseif($user->status === 'deleted') <span class="badge badge-red">Supprimé</span>
            @else <span class="badge badge-gray">{{ $user->status }}</span>
            @endif
          </div>
        </div>
        <div class="profile-item">
          <div class="label">Newsletter</div>
          <div class="value">{{ $user->newsletter_optin ? 'Abonné' : 'Non abonné' }}</div>
        </div>
        <div class="profile-item">
          <div class="label">Membre depuis</div>
          <div class="value">{{ $user->created_at?->format('d/m/Y') ?? '—' }}</div>
        </div>
      </div>
    </div>
  </div>

  {{-- Abonnements --}}
  <div class="section">
    <div class="section-title">Abonnements ({{ $subscriptions->count() }})</div>
    @if($subscriptions->isEmpty())
      <div class="card"><div class="empty">Aucun abonnement</div></div>
    @else
      <div class="card">
        <table>
          <thead>
            <tr>
              <th>Formule</th>
              <th>Statut</th>
              <th>Début</th>
              <th>Fin de période</th>
              <th>Résiliation</th>
            </tr>
          </thead>
          <tbody>
            @foreach($subscriptions as $sub)
            <tr>
              <td><strong>{{ $sub->plan?->name ?? '—' }}</strong><br><span class="text-muted">{{ number_format($sub->plan?->price ?? 0, 2, ',', ' ') }} €</span></td>
              <td>
                @if($sub->status === 'active') <span class="badge badge-green">Actif</span>
                @elseif($sub->status === 'suspended') <span class="badge badge-yellow">Suspendu</span>
                @elseif($sub->status === 'cancelled') <span class="badge badge-red">Résilié</span>
                @elseif($sub->status === 'expired') <span class="badge badge-gray">Expiré</span>
                @else <span class="badge badge-gray">{{ $sub->status }}</span>
                @endif
              </td>
              <td>{{ $sub->current_period_start?->format('d/m/Y') ?? '—' }}</td>
              <td>{{ $sub->current_period_end?->format('d/m/Y') ?? '—' }}</td>
              <td>{{ $sub->cancelled_at?->format('d/m/Y') ?? '—' }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  {{-- Paiements --}}
  <div class="section">
    <div class="section-title">Historique des paiements ({{ $payments->count() }})</div>
    @if($payments->isEmpty())
      <div class="card"><div class="empty">Aucun paiement</div></div>
    @else
      <div class="card">
        <table>
          <thead>
            <tr>
              <th>Date</th>
              <th>Description</th>
              <th>Moyen</th>
              <th>Statut</th>
              <th class="text-right">Montant</th>
            </tr>
          </thead>
          <tbody>
            @foreach($payments as $p)
            <tr>
              <td class="text-muted">{{ $p->created_at?->format('d/m/Y') }}</td>
              <td>{{ $p->description ?: '—' }}</td>
              <td>
                @if($p->payment_method === 'stripe') <span class="badge badge-blue">Stripe</span>
                @elseif($p->payment_method === 'wallet') <span class="badge badge-gray">Portefeuille</span>
                @elseif($p->payment_method === 'mixed') <span class="badge badge-gray">Mixte</span>
                @else <span class="text-muted">{{ $p->payment_method }}</span>
                @endif
              </td>
              <td>
                @if($p->status === 'succeeded') <span class="badge badge-green">Réussi</span>
                @elseif($p->status === 'failed') <span class="badge badge-red">Échoué</span>
                @else <span class="badge badge-yellow">{{ $p->status }}</span>
                @endif
              </td>
              <td class="text-right amount-neutral">{{ number_format($p->amount, 2, ',', ' ') }} €</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  {{-- Portefeuille --}}
  <div class="section">
    <div class="section-title">Portefeuille</div>
    <div class="card">
      @if($wallet)
        <div class="profile-grid" style="grid-template-columns: repeat(2, 1fr)">
          <div class="profile-item">
            <div class="label">Solde actuel</div>
            <div class="value" style="font-size:20px; color:#818cf8; font-weight:700">{{ number_format($wallet->balance, 2, ',', ' ') }} €</div>
          </div>
          <div class="profile-item">
            <div class="label">Nombre de transactions</div>
            <div class="value">{{ $transactions->count() }}</div>
          </div>
        </div>
        @if($transactions->isNotEmpty())
          <table>
            <thead>
              <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Description</th>
                <th class="text-right">Montant</th>
              </tr>
            </thead>
            <tbody>
              @foreach($transactions as $t)
              <tr>
                <td class="text-muted">{{ $t->created_at?->format('d/m/Y') }}</td>
                <td>
                  @if($t->type === 'topup') <span class="badge badge-green">Rechargement</span>
                  @elseif($t->type === 'payment') <span class="badge badge-blue">Paiement</span>
                  @elseif($t->type === 'refund') <span class="badge badge-yellow">Remboursement</span>
                  @elseif($t->type === 'gift') <span class="badge badge-blue">Cadeau</span>
                  @elseif($t->type === 'adjustment') <span class="badge badge-gray">Ajustement</span>
                  @else <span class="text-muted">{{ $t->type }}</span>
                  @endif
                </td>
                <td>{{ $t->description ?: '—' }}</td>
                <td class="text-right {{ $t->amount >= 0 ? 'amount-pos' : 'amount-neg' }}">
                  {{ $t->amount >= 0 ? '+' : '' }}{{ number_format($t->amount, 2, ',', ' ') }} €
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        @endif
      @else
        <div class="empty">Aucun portefeuille créé</div>
      @endif
    </div>
  </div>

  {{-- Tickets --}}
  <div class="section">
    <div class="section-title">Tickets de support ({{ $tickets->count() }})</div>
    @if($tickets->isEmpty())
      <div class="card"><div class="empty">Aucun ticket</div></div>
    @else
      @foreach($tickets as $ticket)
        <div class="card">
          <div class="ticket-header">
            <span class="ticket-subject">{{ $ticket->subject }}</span>
            <div style="display:flex;align-items:center;gap:10px;flex-shrink:0">
              @if($ticket->status === 'open') <span class="badge badge-blue">Ouvert</span>
              @elseif($ticket->status === 'in_progress') <span class="badge badge-yellow">En cours</span>
              @elseif($ticket->status === 'waiting_customer') <span class="badge badge-yellow">En attente</span>
              @elseif($ticket->status === 'resolved') <span class="badge badge-green">Résolu</span>
              @elseif($ticket->status === 'closed') <span class="badge badge-gray">Fermé</span>
              @else <span class="badge badge-gray">{{ $ticket->status }}</span>
              @endif
              <span class="text-muted">{{ $ticket->created_at?->format('d/m/Y') }}</span>
            </div>
          </div>
          <div class="ticket-messages">
            @forelse($ticket->messages as $msg)
              <div class="message {{ $msg->is_staff_reply ? 'message-staff' : '' }}">
                <div class="message-meta">
                  {{ $msg->is_staff_reply ? 'Support MonFlow' : $user->username }}
                  · {{ $msg->created_at?->format('d/m/Y H:i') }}
                </div>
                <div class="message-body">{{ $msg->body }}</div>
              </div>
            @empty
              <div class="no-messages">Aucun message</div>
            @endforelse
          </div>
        </div>
      @endforeach
    @endif
  </div>

  {{-- Feedbacks --}}
  <div class="section">
    <div class="section-title">Feedbacks ({{ $feedbacks->count() }})</div>
    @if($feedbacks->isEmpty())
      <div class="card"><div class="empty">Aucun feedback</div></div>
    @else
      <div class="card">
        <table>
          <thead>
            <tr>
              <th>Date</th>
              <th>Type</th>
              <th>Sujet</th>
              <th>Message</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            @foreach($feedbacks as $fb)
            <tr>
              <td class="text-muted">{{ $fb->created_at?->format('d/m/Y') }}</td>
              <td><span class="badge badge-gray">{{ $fb->type }}</span></td>
              <td><strong>{{ $fb->subject }}</strong></td>
              <td style="max-width:300px;white-space:pre-wrap;word-break:break-word">{{ $fb->body }}</td>
              <td><span class="badge badge-gray">{{ $fb->status }}</span></td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  {{-- Appareils --}}
  <div class="section">
    <div class="section-title">Appareils connectés ({{ $devices->count() }})</div>
    @if($devices->isEmpty())
      <div class="card"><div class="empty">Aucun appareil enregistré</div></div>
    @else
      <div class="card">
        <table>
          <thead>
            <tr>
              <th>Appareil</th>
              <th>Type</th>
              <th>Adresse IP</th>
              <th>Dernière activité</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            @foreach($devices as $d)
            <tr>
              <td><strong>{{ $d->device_name ?: '—' }}</strong></td>
              <td class="text-muted">{{ $d->device_type ?: '—' }}</td>
              <td class="mono text-muted">{{ $d->ip_address ?: '—' }}</td>
              <td class="text-muted">{{ $d->last_active ? \Carbon\Carbon::parse($d->last_active)->format('d/m/Y H:i') : '—' }}</td>
              <td>
                @if($d->is_active) <span class="badge badge-green">Actif</span>
                @else <span class="badge badge-gray">Révoqué</span>
                @endif
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  <div class="footer">
    Document généré automatiquement par MonFlow le {{ now()->format('d/m/Y à H:i:s') }}<br>
    Conformément au RGPD (Règlement UE 2016/679), vous avez le droit d'accès, de rectification et d'effacement de vos données personnelles.<br>
    Pour toute demande : <strong>support via l'espace client</strong>
  </div>

</div>
</body>
</html>
