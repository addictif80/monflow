@extends('layouts.admin')
@section('title', 'Abonnements — Admin MonFlow')
@section('content')
<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div>
        <h1 class="text-base font-semibold text-zinc-100">Abonnements</h1>
        <p class="text-sm text-zinc-500 mt-0.5">Gestion des abonnements utilisateurs</p>
    </div>
    <div class="flex gap-2 flex-wrap">
        <button type="button" onclick="openReminders()"
                class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-sm font-medium px-4 py-2 rounded-lg border border-zinc-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            Envoyer des rappels
        </button>
        <button type="button" id="processRemindersBtn" onclick="runMaintenance('reminders')"
                class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 text-sm font-medium px-4 py-2 rounded-lg border border-zinc-700 transition disabled:opacity-40">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Traiter les relances maintenant
        </button>
        <button type="button" id="processOverdueBtn" onclick="runMaintenance('overdue')"
                class="inline-flex items-center gap-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 text-sm font-medium px-4 py-2 rounded-lg border border-red-500/20 transition disabled:opacity-40">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            Traiter les suspensions/suppressions
        </button>
        <label class="inline-flex items-center gap-2 text-xs text-zinc-400 px-1">
            <input type="checkbox" id="keepDataCheckbox" class="accent-indigo-500 w-3.5 h-3.5">
            Conserver les données (suspendre seulement, ne jamais supprimer)
        </label>
    </div>
</div>

{{-- ─── Maintenance result modal ───────────────────────────────────────────── --}}
<div id="maintenanceModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.8)">
    <div class="bg-zinc-900 border border-zinc-700 rounded-2xl w-full max-w-lg shadow-2xl flex flex-col" style="max-height:80vh">
        <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-800 flex-shrink-0">
            <h2 class="text-sm font-semibold text-zinc-100" id="maintenanceTitle">Traitement</h2>
            <button onclick="closeMaintenance()" class="text-zinc-600 hover:text-zinc-300 transition text-xl leading-none">✕</button>
        </div>
        <div class="flex-1 overflow-y-auto px-5 py-4">
            <pre id="maintenanceOutput" class="text-xs text-zinc-400 whitespace-pre-wrap font-mono"></pre>
        </div>
        <div class="px-5 py-4 border-t border-zinc-800 flex-shrink-0">
            <button onclick="closeMaintenance()" class="text-sm bg-zinc-800 hover:bg-zinc-700 text-zinc-300 px-4 py-2 rounded-lg border border-zinc-700 transition">Fermer</button>
        </div>
    </div>
</div>

{{-- ─── Reminder modal ─────────────────────────────────────────────────────── --}}
<div id="reminderModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(0,0,0,.8)">
    <div class="bg-zinc-900 border border-zinc-700 rounded-2xl w-full max-w-2xl shadow-2xl flex flex-col" style="max-height:88vh">

        <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-800 flex-shrink-0">
            <h2 class="text-sm font-semibold text-zinc-100">Rappels de paiement</h2>
            <button onclick="closeReminders()" class="text-zinc-600 hover:text-zinc-300 transition text-xl leading-none">✕</button>
        </div>

        {{-- Loading --}}
        <div id="rmStep1" class="flex-1 flex items-center justify-center py-10">
            <svg class="w-6 h-6 animate-spin text-indigo-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
            <span class="ml-3 text-sm text-zinc-400">Chargement des abonnements éligibles…</span>
        </div>

        {{-- Selection --}}
        <div id="rmStep2" class="hidden flex-1 flex flex-col overflow-hidden">
            {{-- Tabs --}}
            <div class="flex gap-1 px-5 pt-4 pb-0 border-b border-zinc-800 flex-shrink-0">
                <button id="tabExpiringBtn" onclick="switchTab('expiring')"
                        class="rm-tab px-4 py-2 text-xs font-medium rounded-t-lg border-b-2 transition">
                    Échéance imminente (<span id="expiringCount">0</span>)
                </button>
                <button id="tabOverdueBtn" onclick="switchTab('overdue')"
                        class="rm-tab px-4 py-2 text-xs font-medium rounded-t-lg border-b-2 transition">
                    Impayés / En attente (<span id="overdueCount">0</span>)
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-5 py-4">
                {{-- Expiring tab --}}
                <div id="tabExpiring">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs text-zinc-500">Abonnements actifs dont l'échéance est dans ≤ 7 jours. Mail envoyé : <span class="text-zinc-300">renewal_reminder</span>.</p>
                        <button onclick="toggleAll('expiring')" class="text-xs text-indigo-400 hover:text-indigo-300">Tout sélectionner</button>
                    </div>
                    <div id="expiringEmpty" class="hidden text-center text-zinc-600 text-sm py-6">Aucun abonnement expirant sous 7 jours.</div>
                    <table id="expiringTable" class="w-full text-xs hidden">
                        <thead><tr class="border-b border-zinc-800">
                            <th class="pb-2 w-8"></th>
                            <th class="pb-2 text-left text-zinc-500 font-medium">Utilisateur</th>
                            <th class="pb-2 text-left text-zinc-500 font-medium">Formule</th>
                            <th class="pb-2 text-left text-zinc-500 font-medium">Échéance</th>
                            <th class="pb-2 text-left text-zinc-500 font-medium">Restant</th>
                        </tr></thead>
                        <tbody id="expiringBody" class="divide-y divide-zinc-800/50"></tbody>
                    </table>
                </div>

                {{-- Overdue tab --}}
                <div id="tabOverdue" class="hidden">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs text-zinc-500">Abonnements expirés non renouvelés ou en attente. Mail envoyé : <span class="text-zinc-300">payment_reminder</span>.</p>
                        <button onclick="toggleAll('overdue')" class="text-xs text-indigo-400 hover:text-indigo-300">Tout sélectionner</button>
                    </div>
                    <div id="overdueEmpty" class="hidden text-center text-zinc-600 text-sm py-6">Aucun abonnement impayé ou en attente.</div>
                    <table id="overdueTable" class="w-full text-xs hidden">
                        <thead><tr class="border-b border-zinc-800">
                            <th class="pb-2 w-8"></th>
                            <th class="pb-2 text-left text-zinc-500 font-medium">Utilisateur</th>
                            <th class="pb-2 text-left text-zinc-500 font-medium">Formule</th>
                            <th class="pb-2 text-left text-zinc-500 font-medium">Statut</th>
                            <th class="pb-2 text-left text-zinc-500 font-medium">Retard</th>
                        </tr></thead>
                        <tbody id="overdueBody" class="divide-y divide-zinc-800/50"></tbody>
                    </table>
                </div>
            </div>

            <div class="px-5 py-4 border-t border-zinc-800 flex items-center gap-3 flex-shrink-0">
                <button onclick="sendReminders()" id="sendBtn"
                        class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition disabled:opacity-40">
                    <span id="sendLabel">Envoyer aux sélectionnés</span>
                    <svg id="sendSpin" class="hidden w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                </button>
                <span id="selectedCount" class="text-xs text-zinc-500">0 sélectionné(s)</span>
                <button onclick="closeReminders()" class="ml-auto text-sm text-zinc-500 hover:text-zinc-300 transition">Annuler</button>
            </div>
        </div>

        {{-- Progress --}}
        <div id="rmStep3" class="hidden flex-1 flex flex-col justify-center px-6 py-6">
            <div class="flex items-center justify-between text-xs text-zinc-500 mb-2">
                <span id="rmProgressLabel">Envoi en cours…</span>
                <span id="rmProgressCount">0 / 0</span>
            </div>
            <div class="w-full bg-zinc-800 rounded-full h-2 mb-4">
                <div id="rmProgressBar" class="bg-indigo-500 h-2 rounded-full transition-all duration-300" style="width:0%"></div>
            </div>
            <div class="text-xs text-zinc-600 truncate mb-3" id="rmCurrentEmail">—</div>
            <div class="flex gap-4 text-xs">
                <span class="text-emerald-400"><span id="rmOk">0</span> envoyé(s)</span>
                <span class="text-red-400"><span id="rmFailed">0</span> erreur(s)</span>
            </div>
        </div>

        {{-- Done --}}
        <div id="rmStep4" class="hidden flex-1 flex flex-col justify-center px-6 py-8">
            <div class="flex items-center gap-2 text-emerald-400 mb-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span class="text-sm font-medium">Envoi terminé</span>
            </div>
            <div class="text-xs text-zinc-500 space-y-1 mb-5">
                <div><span class="text-emerald-400 font-medium" id="rmFinalOk">0</span> mail(s) envoyé(s) avec succès</div>
                <div><span class="text-red-400 font-medium" id="rmFinalFailed">0</span> erreur(s)</div>
            </div>
            <button onclick="closeReminders()" class="self-start text-sm bg-zinc-800 hover:bg-zinc-700 text-zinc-300 px-4 py-2 rounded-lg border border-zinc-700 transition">Fermer</button>
        </div>

    </div>
</div>

<div class="mb-4 flex gap-2 flex-wrap">
    @php $statuses = ['' => 'Tous', 'active' => 'Actifs', 'pending' => 'En attente', 'suspended' => 'Suspendus', 'cancelled' => 'Résiliés']; @endphp
    @foreach($statuses as $val => $label)
        <a href="/admin/subscriptions{{ $val ? '?status='.$val : '' }}"
           class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-medium transition {{ ($statusFilter ?? '') === $val ? 'bg-indigo-600 text-white' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700 border border-zinc-700' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-800">
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Utilisateur</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Formule</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Statut</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Fin période</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800/50">
            @forelse($subscriptions as $s)
                <tr class="hover:bg-zinc-800/30 transition">
                    <td class="px-4 py-3">
                        <a href="/admin/users/{{ $s->user_id }}" class="text-indigo-400 hover:text-indigo-300">{{ $s->user?->username ?? '—' }}</a>
                    </td>
                    <td class="px-4 py-3 text-zinc-300">{{ $s->plan?->name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @if($s->status === 'active')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">{{ $s->status }}</span>
                        @elseif($s->status === 'pending')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-500/10 text-yellow-400 border border-yellow-500/20">{{ $s->status }}</span>
                        @elseif($s->status === 'suspended')
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20">{{ $s->status }}</span>
                        @else
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-800 text-zinc-500 border border-zinc-700">{{ $s->status }}</span>
                        @endif
                        @if($s->is_gift) <span class="text-xs text-emerald-400 ml-1">cadeau</span> @endif
                    </td>
                    <td class="px-4 py-3 text-zinc-500">
                        @if($s->current_period_end)
                            {{ $s->current_period_end->format('d/m/Y') }}
                            @if($s->status === 'active' && $s->current_period_end->isPast())
                                <span class="text-red-400 text-xs ml-1">expiré</span>
                            @elseif($s->status === 'active' && $s->current_period_end->diffInDays(now(), true) <= 7)
                                <span class="text-yellow-400 text-xs ml-1">{{ $s->current_period_end->diffInDays(now(), true) }}j restants</span>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <a href="/admin/subscriptions/{{ $s->id }}" class="text-indigo-400 hover:text-indigo-300 text-xs">Gérer</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-6 text-center text-zinc-600">Aucun abonnement.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $subscriptions->appends(request()->query())->links() }}</div>

{{-- Create subscription --}}
<div class="mt-8 bg-zinc-900 border border-zinc-800 rounded-xl p-5">
    <h2 class="text-sm font-medium text-zinc-300 mb-4">Créer un abonnement manuellement</h2>
    <form action="/admin/subscriptions/create" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        @csrf
        <div>
            <label class="block text-xs font-medium text-zinc-400 mb-1.5">Utilisateur (ID)</label>
            <input type="text" name="user_id" placeholder="UUID de l'utilisateur"
                   class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
        </div>
        <div>
            <label class="block text-xs font-medium text-zinc-400 mb-1.5">Formule</label>
            <select name="plan_id" class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
                @foreach(\App\Models\Plan::where('is_active', true)->orderBy('sort_order')->get() as $p)
                    <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->price }}€)</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-zinc-400 mb-1.5">Durée (mois)</label>
            <input type="number" name="months" value="1" min="1" max="24"
                   class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
        </div>
        <div>
            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Créer</button>
        </div>
    </form>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

let rmData = { expiring: [], overdue: [] };
let currentTab = 'expiring';

function showRmStep(n) {
    [1,2,3,4].forEach(i => {
        const el = document.getElementById('rmStep' + i);
        if (el) el.classList.add('hidden');
    });
    const target = document.getElementById('rmStep' + n);
    if (target) { target.classList.remove('hidden'); target.classList.add('flex'); }
}

async function openReminders() {
    rmData = { expiring: [], overdue: [] };
    document.getElementById('reminderModal').classList.remove('hidden');
    showRmStep(1);

    try {
        const res  = await fetch('/admin/subscriptions/reminders-eligible', { headers: { Accept: 'application/json' } });
        const data = await res.json();
        rmData = data;
        buildTables(data);
        document.getElementById('expiringCount').textContent = data.expiring.length;
        document.getElementById('overdueCount').textContent  = data.overdue.length;
        switchTab('expiring');
        showRmStep(2);
    } catch(e) {
        alert('Erreur lors du chargement : ' + e.message);
        closeReminders();
    }
}

function closeReminders() {
    document.getElementById('reminderModal').classList.add('hidden');
}

function buildTables(data) {
    // Expiring
    const eb = document.getElementById('expiringBody');
    eb.innerHTML = '';
    if (data.expiring.length === 0) {
        document.getElementById('expiringEmpty').classList.remove('hidden');
        document.getElementById('expiringTable').classList.add('hidden');
    } else {
        document.getElementById('expiringEmpty').classList.add('hidden');
        document.getElementById('expiringTable').classList.remove('hidden');
        data.expiring.forEach(s => {
            eb.insertAdjacentHTML('beforeend', `
            <tr class="hover:bg-zinc-800/20">
                <td class="py-2 pr-2"><input type="checkbox" class="rm-check expiring-check accent-indigo-500 w-3.5 h-3.5"
                    data-id="${s.id}" data-type="renewal" onchange="updateCount()"></td>
                <td class="py-2 pr-4"><span class="text-zinc-200">${esc(s.username)}</span><br><span class="text-zinc-600">${esc(s.email)}</span></td>
                <td class="py-2 pr-4 text-zinc-400">${esc(s.plan)}</td>
                <td class="py-2 pr-4 text-zinc-400">${esc(s.ends_at)}</td>
                <td class="py-2 text-yellow-400">${s.days_left}j</td>
            </tr>`);
        });
    }

    // Overdue
    const ob = document.getElementById('overdueBody');
    ob.innerHTML = '';
    if (data.overdue.length === 0) {
        document.getElementById('overdueEmpty').classList.remove('hidden');
        document.getElementById('overdueTable').classList.add('hidden');
    } else {
        document.getElementById('overdueEmpty').classList.add('hidden');
        document.getElementById('overdueTable').classList.remove('hidden');
        data.overdue.forEach(s => {
            const retard = s.days_overdue > 0 ? `${s.days_overdue}j` : (s.status === 'pending' ? 'en attente' : '—');
            ob.insertAdjacentHTML('beforeend', `
            <tr class="hover:bg-zinc-800/20">
                <td class="py-2 pr-2"><input type="checkbox" class="rm-check overdue-check accent-indigo-500 w-3.5 h-3.5"
                    data-id="${s.id}" data-type="payment" onchange="updateCount()"></td>
                <td class="py-2 pr-4"><span class="text-zinc-200">${esc(s.username)}</span><br><span class="text-zinc-600">${esc(s.email)}</span></td>
                <td class="py-2 pr-4 text-zinc-400">${esc(s.plan)}</td>
                <td class="py-2 pr-4">
                    <span class="text-[10px] px-1.5 py-0.5 rounded-full ${s.status === 'pending' ? 'bg-yellow-500/10 text-yellow-400' : 'bg-red-500/10 text-red-400'}">${esc(s.status)}</span>
                </td>
                <td class="py-2 text-red-400">${retard}</td>
            </tr>`);
        });
    }
    updateCount();
}

function switchTab(tab) {
    currentTab = tab;
    document.getElementById('tabExpiring').classList.toggle('hidden', tab !== 'expiring');
    document.getElementById('tabOverdue').classList.toggle('hidden', tab !== 'overdue');

    const activeClass  = 'border-indigo-500 text-indigo-300';
    const inactiveClass = 'border-transparent text-zinc-500 hover:text-zinc-300';
    document.getElementById('tabExpiringBtn').className = 'rm-tab px-4 py-2 text-xs font-medium rounded-t-lg border-b-2 transition ' + (tab === 'expiring' ? activeClass : inactiveClass);
    document.getElementById('tabOverdueBtn').className  = 'rm-tab px-4 py-2 text-xs font-medium rounded-t-lg border-b-2 transition ' + (tab === 'overdue'  ? activeClass : inactiveClass);
}

function toggleAll(tab) {
    const checks = document.querySelectorAll(`.${tab}-check`);
    const allChecked = [...checks].every(c => c.checked);
    checks.forEach(c => c.checked = !allChecked);
    updateCount();
}

function updateCount() {
    const n = document.querySelectorAll('.rm-check:checked').length;
    document.getElementById('selectedCount').textContent = n + ' sélectionné(s)';
    document.getElementById('sendBtn').disabled = n === 0;
}

async function sendReminders() {
    const checks = [...document.querySelectorAll('.rm-check:checked')];
    if (!checks.length) return;

    showRmStep(3);
    let ok = 0, failed = 0;
    const total = checks.length;

    for (let i = 0; i < checks.length; i++) {
        const c     = checks[i];
        const id    = c.dataset.id;
        const type  = c.dataset.type;

        const pct = Math.round((i / total) * 100);
        document.getElementById('rmProgressBar').style.width  = pct + '%';
        document.getElementById('rmProgressCount').textContent = `${i} / ${total}`;
        document.getElementById('rmCurrentEmail').textContent = id;

        try {
            const body = new URLSearchParams({ _token: csrfToken, type });
            const res  = await fetch(`/admin/subscriptions/${id}/send-reminder`, {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString(),
            });
            const json = await res.json();
            if (res.ok && json.success) {
                ok++;
                document.getElementById('rmCurrentEmail').textContent = json.email;
            } else {
                failed++;
            }
        } catch {
            failed++;
        }

        document.getElementById('rmOk').textContent     = ok;
        document.getElementById('rmFailed').textContent = failed;
        await new Promise(r => setTimeout(r, 150));
    }

    document.getElementById('rmProgressBar').style.width   = '100%';
    document.getElementById('rmProgressCount').textContent = `${total} / ${total}`;
    document.getElementById('rmFinalOk').textContent     = ok;
    document.getElementById('rmFinalFailed').textContent  = failed;
    showRmStep(4);
}

const maintenanceConfig = {
    reminders: {
        url: '/admin/subscriptions/process-reminders',
        title: 'Relances des paiements',
        confirm: "Lancer immédiatement l'envoi des rappels de paiement et de renouvellement à tous les abonnements éligibles ?",
    },
    overdue: {
        url: '/admin/subscriptions/process-overdue',
        title: 'Suspensions / suppressions',
        confirm: (keepData) => keepData
            ? "Lancer immédiatement la vérification des abonnements en retard ? Les comptes dépassant le délai de grâce seront suspendus (accès Navidrome bloqué). Aucune suppression ne sera effectuée, même au-delà du délai de suppression — les données de tous les utilisateurs concernés seront conservées."
            : "Lancer immédiatement la vérification des abonnements en retard ? Les comptes dépassant le délai de grâce seront suspendus (accès Navidrome bloqué) et ceux dépassant le délai de suppression seront SUPPRIMÉS (compte Navidrome + données).",
    },
};

async function runMaintenance(kind) {
    const cfg = maintenanceConfig[kind];
    const keepData = kind === 'overdue' && document.getElementById('keepDataCheckbox').checked;
    const confirmMsg = typeof cfg.confirm === 'function' ? cfg.confirm(keepData) : cfg.confirm;
    if (!confirm(confirmMsg)) return;

    const btn = document.getElementById(kind === 'reminders' ? 'processRemindersBtn' : 'processOverdueBtn');
    btn.disabled = true;

    document.getElementById('maintenanceTitle').textContent = cfg.title;
    document.getElementById('maintenanceOutput').textContent = 'Traitement en cours…';
    document.getElementById('maintenanceModal').classList.remove('hidden');

    try {
        const body = kind === 'overdue' ? new URLSearchParams({ keep_data: keepData ? '1' : '0' }) : null;
        const res  = await fetch(cfg.url, {
            method: 'POST',
            headers: Object.assign(
                { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body ? { 'Content-Type': 'application/x-www-form-urlencoded' } : {}
            ),
            body,
        });
        const data = await res.json();
        document.getElementById('maintenanceOutput').textContent = data.output || (data.success ? 'Terminé.' : (data.message || 'Erreur.'));
    } catch (e) {
        document.getElementById('maintenanceOutput').textContent = 'Erreur : ' + e.message;
    } finally {
        btn.disabled = false;
    }
}

function closeMaintenance() {
    document.getElementById('maintenanceModal').classList.add('hidden');
}

document.getElementById('maintenanceModal').addEventListener('click', function (e) {
    if (e.target === this) closeMaintenance();
});

function esc(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.getElementById('reminderModal').addEventListener('click', function(e) {
    if (e.target === this) closeReminders();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeReminders(); });

// Init button state
updateCount();
</script>
@endsection
