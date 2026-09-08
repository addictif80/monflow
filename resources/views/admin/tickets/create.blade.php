@extends('layouts.admin')
@section('title', 'Nouveau ticket — Admin MonFlow')
@section('content')
<div class="mb-6">
    <a href="/admin/tickets" class="text-sm text-zinc-500 hover:text-zinc-300">&larr; Retour aux tickets</a>
</div>
<div class="mb-6">
    <h1 class="text-base font-semibold text-zinc-100">Ouvrir un ticket pour un utilisateur</h1>
    <p class="text-sm text-zinc-500 mt-0.5">L'utilisateur sera notifié de l'ouverture du ticket.</p>
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-6 max-w-2xl">
    <form method="POST" action="/admin/tickets/create" id="ticketCreateForm">
        @csrf
        <div class="space-y-4">
            <div class="relative">
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Client</label>
                <input type="text" id="userSearch" autocomplete="off"
                       placeholder="Rechercher par nom d'utilisateur ou email"
                       value="{{ old('user_search') }}"
                       class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
                <input type="hidden" name="user_id" id="userId" value="{{ old('user_id') }}">
                <div id="userResults" class="hidden absolute z-10 mt-1 w-full bg-zinc-900 border border-zinc-800 rounded-lg overflow-hidden shadow-lg max-h-56 overflow-y-auto"></div>
                <p id="userHint" class="text-xs text-zinc-600 mt-1.5">Tapez au moins 2 caractères puis sélectionnez le client dans la liste.</p>
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Sujet</label>
                <input name="subject" value="{{ old('subject') }}" required maxlength="255" class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-zinc-400 mb-1.5">Catégorie</label>
                    <select name="category" required class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
                        <option value="account" {{ old('category') === 'account' ? 'selected' : '' }}>Compte</option>
                        <option value="billing" {{ old('category') === 'billing' ? 'selected' : '' }}>Facturation</option>
                        <option value="technical" {{ old('category') === 'technical' ? 'selected' : '' }}>Technique</option>
                        <option value="other" {{ old('category', 'other') === 'other' ? 'selected' : '' }}>Autre</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-400 mb-1.5">Priorité</label>
                    <select name="priority" required class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
                        <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Basse</option>
                        <option value="medium" {{ old('priority', 'medium') === 'medium' ? 'selected' : '' }}>Moyenne</option>
                        <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>Haute</option>
                        <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>Urgente</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Message</label>
                <textarea name="message" rows="6" required class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">{{ old('message') }}</textarea>
            </div>
            <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Créer le ticket</button>
        </div>
    </form>
</div>

<script>
(function () {
    const searchInput = document.getElementById('userSearch');
    const hiddenInput = document.getElementById('userId');
    const results = document.getElementById('userResults');
    const hint = document.getElementById('userHint');
    let debounce = null;
    let lastQuery = '';

    function hideResults() { results.classList.add('hidden'); results.innerHTML = ''; }

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function render(users) {
        if (!users.length) {
            results.innerHTML = '<div class="px-3 py-2 text-sm text-zinc-500">Aucun utilisateur trouvé.</div>';
            results.classList.remove('hidden');
            return;
        }
        results.innerHTML = users.map(u => `
            <button type="button" data-id="${escapeHtml(u.id)}" data-label="${escapeHtml(u.username)}"
                    class="w-full text-left px-3 py-2 text-sm text-zinc-200 hover:bg-zinc-800 transition block">
                <span class="font-medium">${escapeHtml(u.username)}</span>
                <span class="text-zinc-500 ml-1">${escapeHtml(u.email)}</span>
            </button>
        `).join('');
        results.classList.remove('hidden');
    }

    searchInput.addEventListener('input', function () {
        hiddenInput.value = '';
        hint.classList.remove('hidden');
        const q = this.value.trim();
        if (debounce) clearTimeout(debounce);
        if (q.length < 2) { hideResults(); return; }
        debounce = setTimeout(function () {
            lastQuery = q;
            fetch('/admin/tickets/users-search?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } })
                .then(r => r.json())
                .then(users => { if (q === lastQuery) render(users); })
                .catch(() => hideResults());
        }, 250);
    });

    results.addEventListener('click', function (e) {
        const btn = e.target.closest('button[data-id]');
        if (!btn) return;
        hiddenInput.value = btn.dataset.id;
        searchInput.value = btn.dataset.label;
        hint.textContent = 'Client sélectionné.';
        hint.classList.add('text-emerald-400');
        hideResults();
    });

    document.addEventListener('click', function (e) {
        if (!results.contains(e.target) && e.target !== searchInput) hideResults();
    });

    document.getElementById('ticketCreateForm').addEventListener('submit', function (e) {
        if (!hiddenInput.value) {
            e.preventDefault();
            hint.textContent = 'Veuillez sélectionner un client dans la liste.';
            hint.classList.remove('text-zinc-600');
            hint.classList.add('text-red-400');
            searchInput.focus();
        }
    });
})();
</script>
@endsection
