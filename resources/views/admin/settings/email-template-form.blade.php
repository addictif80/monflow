@extends('layouts.admin')
@section('title', ($template ? 'Modifier' : 'Nouveau') . ' template — Admin MonFlow')
@section('content')
<div class="mb-6">
    <a href="/admin/settings/email-templates" class="text-sm text-zinc-500 hover:text-zinc-300">&larr; Retour</a>
</div>

<div class="mb-6">
    <h1 class="text-base font-semibold text-zinc-100">{{ $template ? 'Modifier le template' : 'Nouveau template' }}</h1>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
        <form method="POST" action="{{ $template ? '/admin/settings/email-templates/' . $template->id : '/admin/settings/email-templates/create' }}">
            @csrf
            <div class="mb-4">
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Type</label>
                <select name="template_type" required class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
                    @foreach(['welcome', 'email_verification', 'payment_reminder', 'password_reset', 'account_suspended', 'account_deleted', 'gift_received', 'refund_processed', 'subscription_renewed', 'renewal_reminder'] as $type)
                        <option value="{{ $type }}" {{ old('template_type', $template->template_type ?? '') === $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Sujet</label>
                <input name="subject" id="tpl-subject" value="{{ old('subject', $template->subject ?? '') }}" required
                       class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
            </div>
            <div class="mb-4">
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Corps HTML</label>
                <textarea name="html_body" id="tpl-body" rows="20" required
                          class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 font-mono placeholder-zinc-600 px-3 py-2 outline-none transition">{{ old('html_body', $template->html_body ?? '') }}</textarea>
                @verbatim
                <p class="text-xs text-zinc-600 mt-1">Variables : {{ username }}, {{ first_name }}, {{ site_name }}, {{ site_url }}, {{ reset_url }}, {{ verify_url }}, {{ plan }}, {{ plan_name }}, {{ price }}, {{ days_overdue }}, {{ promo_ending }}</p>
                @endverbatim
            </div>
            <div class="mb-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $template->is_active ?? true) ? 'checked' : '' }} class="w-4 h-4 rounded">
                    <span class="text-sm text-zinc-300">Actif</span>
                </label>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">{{ $template ? 'Mettre à jour' : 'Créer' }}</button>
                <a href="/admin/settings/email-templates" class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-sm font-medium px-4 py-2 rounded-lg border border-zinc-700 transition">Annuler</a>
            </div>
        </form>
    </div>

    <div>
        <div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden sticky top-8">
            <div class="px-4 py-3 border-b border-zinc-800 flex items-center justify-between">
                <h3 class="text-sm font-medium text-zinc-300">Aperçu en direct</h3>
                <span id="preview-subject" class="text-xs text-zinc-500 truncate ml-4"></span>
            </div>
            <div class="bg-white" style="min-height: 400px;">
                <iframe id="preview-frame" class="w-full" style="min-height: 400px; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
var sampleVars = {
    username: 'jean.dupont',
    first_name: 'Jean',
    site_name: 'MonFlow',
    site_url: '{{ url("/") }}',
    reset_url: '{{ url("/reset-password/example-token") }}',
    verify_url: '{{ url("/verify-email/example-token?email=jean@example.com") }}',
    plan: 'Premium',
    plan_name: 'Premium',
    price: '9.99',
    days_overdue: '3',
    promo_ending: 'true'
};

function renderTemplate(tpl) {
    for (var k in sampleVars) {
        tpl = tpl.split('{{ ' + k + ' }}').join(sampleVars[k]);
        tpl = tpl.split('{{' + k + '}}').join(sampleVars[k]);
    }
    return tpl;
}

function updatePreview() {
    var body = document.getElementById('tpl-body').value;
    var subject = document.getElementById('tpl-subject').value;
    document.getElementById('preview-subject').textContent = 'Sujet : ' + renderTemplate(subject);

    var frame = document.getElementById('preview-frame');
    var doc = frame.contentDocument || frame.contentWindow.document;
    doc.open();
    doc.write(renderTemplate(body));
    doc.close();

    frame.style.height = Math.max(400, doc.body.scrollHeight + 40) + 'px';
}

var debounceTimer;
function debouncedPreview() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(updatePreview, 150);
}

document.getElementById('tpl-body').addEventListener('input', debouncedPreview);
document.getElementById('tpl-subject').addEventListener('input', debouncedPreview);

updatePreview();
</script>
@endsection
