@extends('layouts.admin')
@section('title', ($template ? 'Modifier' : 'Nouveau') . ' template — Admin MonFlow')
@section('content')
<div class="mb-6"><a href="/admin/settings/email-templates" class="text-gray-400 hover:text-gray-200 text-sm">&larr; Retour</a></div>
<h1 class="text-2xl font-bold mb-6">{{ $template ? 'Modifier le template' : 'Nouveau template' }}</h1>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        <form method="POST" action="{{ $template ? '/admin/settings/email-templates/' . $template->id : '/admin/settings/email-templates/create' }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm text-gray-400 mb-1">Type</label>
                <select name="template_type" required class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
                    @foreach(['welcome', 'email_verification', 'payment_reminder', 'password_reset', 'account_suspended', 'account_deleted', 'gift_received', 'refund_processed', 'subscription_renewed', 'renewal_reminder'] as $type)
                        <option value="{{ $type }}" {{ old('template_type', $template->template_type ?? '') === $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm text-gray-400 mb-1">Sujet</label>
                <input name="subject" id="tpl-subject" value="{{ old('subject', $template->subject ?? '') }}" required class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
            </div>
            <div class="mb-4">
                <label class="block text-sm text-gray-400 mb-1">Corps HTML</label>
                <textarea name="html_body" id="tpl-body" rows="20" required class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm font-mono focus:outline-none focus:border-indigo-500">{{ old('html_body', $template->html_body ?? '') }}</textarea>
                @verbatim
                <p class="text-xs text-gray-500 mt-1">Variables : {{ username }}, {{ first_name }}, {{ site_name }}, {{ site_url }}, {{ reset_url }}, {{ verify_url }}, {{ plan }}, {{ plan_name }}, {{ price }}, {{ days_overdue }}, {{ promo_ending }}</p>
                @endverbatim
            </div>
            <div class="mb-6">
                <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $template->is_active ?? true) ? 'checked' : '' }} class="w-4 h-4 rounded"><span class="text-sm">Actif</span></label>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-medium">{{ $template ? 'Mettre à jour' : 'Créer' }}</button>
                <a href="/admin/settings/email-templates" class="px-6 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm font-medium">Annuler</a>
            </div>
        </form>
    </div>

    <div>
        <div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden sticky top-8">
            <div class="px-4 py-3 border-b border-gray-700 flex items-center justify-between">
                <h3 class="font-semibold text-sm">Aperçu en direct</h3>
                <span id="preview-subject" class="text-xs text-gray-400 truncate ml-4"></span>
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
