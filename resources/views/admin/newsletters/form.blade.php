@extends('layouts.admin')
@section('title', ($newsletter ? 'Modifier' : 'Nouvelle') . ' newsletter — Admin MonFlow')
@section('content')
<div class="mb-6">
    <a href="/admin/newsletters" class="text-sm text-zinc-500 hover:text-zinc-300">&larr; Retour</a>
</div>

<div class="mb-6 flex items-center justify-between">
    <h1 class="text-base font-semibold text-zinc-100">{{ $newsletter ? 'Modifier la campagne' : 'Nouvelle campagne' }}</h1>
    <a href="/admin/newsletters/template" class="text-xs text-indigo-400 hover:text-indigo-300">Modifier le template →</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
        <form method="POST" action="{{ $newsletter ? '/admin/newsletters/' . $newsletter->id . '/edit' : '/admin/newsletters/create' }}">
            @csrf
            <div class="mb-4">
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Sujet</label>
                <input name="subject" id="nl-subject" value="{{ old('subject', $newsletter->subject ?? '') }}" required
                       class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
            </div>
            <div class="mb-4">
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Contenu du mail</label>
                <textarea name="html_body" id="nl-body" rows="22" required
                          class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 font-mono placeholder-zinc-600 px-3 py-2 outline-none transition"
                          placeholder="<h1 style=&quot;...&quot;>Titre</h1>&#10;<p style=&quot;...&quot;>Votre texte...</p>">{{ old('html_body', $newsletter->html_body ?? '') }}</textarea>
                @verbatim
                <p class="text-xs text-zinc-600 mt-1">Ce contenu est injecté dans le template. Variables : {{ sujet }}, {{ username }}, {{ first_name }}, {{ site_name }}, {{ site_url }}</p>
                @endverbatim
            </div>
            <div class="flex gap-3">
                <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">{{ $newsletter ? 'Mettre à jour' : 'Créer le brouillon' }}</button>
                <a href="/admin/newsletters" class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-sm font-medium px-4 py-2 rounded-lg border border-zinc-700 transition">Annuler</a>
            </div>
        </form>
    </div>

    <div>
        <div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden sticky top-8">
            <div class="px-4 py-3 border-b border-zinc-800">
                <h3 class="text-sm font-medium text-zinc-300">Aperçu (avec template)</h3>
            </div>
            <div class="bg-white" style="min-height:400px">
                <iframe id="preview-frame" class="w-full" style="min-height:400px;border:none"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
var newsletterLayout = @json($layout);
var sampleVars = {
    username: 'jean.dupont',
    first_name: 'Jean',
    site_name: '{{ config("app.name") }}',
    site_url: '{{ url("/") }}',
    sujet: ''
};
function renderVars(t) {
    sampleVars.sujet = document.getElementById('nl-subject').value || '(sujet)';
    for (var k in sampleVars) {
        t = t.split('{{ ' + k + ' }}').join(sampleVars[k]);
        t = t.split('{{' + k + '}}').join(sampleVars[k]);
    }
    return t;
}
function updatePreview() {
    var content = document.getElementById('nl-body').value;
    var full = newsletterLayout.replace('@{{ content }}', content);
    full = renderVars(full);
    var f = document.getElementById('preview-frame');
    var d = f.contentDocument || f.contentWindow.document;
    d.open(); d.write(full); d.close();
    f.style.height = Math.max(400, d.body.scrollHeight + 40) + 'px';
}
var t; function debounced() { clearTimeout(t); t = setTimeout(updatePreview, 150); }
document.getElementById('nl-body').addEventListener('input', debounced);
document.getElementById('nl-subject').addEventListener('input', debounced);
updatePreview();
</script>
@endsection
