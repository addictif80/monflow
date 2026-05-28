@extends('layouts.admin')
@section('title', 'Template newsletter — Admin MonFlow')
@section('content')
<div class="mb-6">
    <a href="/admin/newsletters" class="text-sm text-zinc-500 hover:text-zinc-300">&larr; Retour aux newsletters</a>
</div>

<div class="mb-6">
    <h1 class="text-base font-semibold text-zinc-100">Template des newsletters</h1>
    <p class="text-sm text-zinc-500 mt-0.5">Ce gabarit HTML entoure le contenu de chaque newsletter envoyée. Utilisez <code class="bg-zinc-800 px-1 rounded text-indigo-300">@verbatim{{ content }}@endverbatim</code> pour indiquer où injecter le corps du mail.</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
        <form method="POST" action="/admin/newsletters/template">
            @csrf
            <div class="mb-4">
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-medium text-zinc-400">HTML du gabarit</label>
                    <span class="text-xs text-zinc-600">Variable obligatoire : <code class="bg-zinc-800 px-1 rounded text-indigo-400">@verbatim{{ content }}@endverbatim</code></span>
                </div>
                <textarea name="html_body" id="tpl-body" rows="28" required
                          class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 font-mono placeholder-zinc-600 px-3 py-2 outline-none transition">{{ old('html_body', $template->html_body ?? '') }}</textarea>
                @verbatim
                <p class="text-xs text-zinc-600 mt-1">Variables disponibles : {{ content }}, {{ sujet }}, {{ site_name }}, {{ site_url }}, {{ username }}, {{ first_name }}</p>
                @endverbatim
            </div>
            <div class="flex gap-3">
                <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Sauvegarder</button>
                <a href="/admin/newsletters" class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-sm font-medium px-4 py-2 rounded-lg border border-zinc-700 transition">Annuler</a>
            </div>
        </form>
    </div>

    <div>
        <div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden sticky top-8">
            <div class="px-4 py-3 border-b border-zinc-800 flex items-center justify-between">
                <h3 class="text-sm font-medium text-zinc-300">Aperçu avec contenu exemple</h3>
            </div>
            <div class="bg-white" style="min-height:400px">
                <iframe id="preview-frame" class="w-full" style="min-height:400px;border:none"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
var sampleVars = {
    username: 'jean.dupont',
    first_name: 'Jean',
    site_name: '{{ config("app.name") }}',
    site_url: '{{ url("/") }}',
    sujet: 'Les nouveautés de la semaine',
    content: '<h1 style="margin:0 0 16px;font-size:22px;font-weight:700;color:#18181b">Titre de la newsletter</h1>'
           + '<p style="margin:0 0 16px;font-size:15px;color:#3f3f46;line-height:1.6">Voici un exemple de contenu pour visualiser le rendu de votre template avec un vrai texte.</p>'
           + '<p style="margin:0 0 16px;font-size:15px;color:#3f3f46;line-height:1.6">Le contenu réel sera celui que vous saisirez lors de la création de chaque newsletter.</p>'
           + '<table cellpadding="0" cellspacing="0" style="margin:24px 0"><tr><td style="background:#6366f1;border-radius:8px;padding:14px 28px"><a href="#" style="color:#fff;text-decoration:none;font-weight:600;font-size:14px">Écouter maintenant</a></td></tr></table>'
};
function renderVars(t) {
    for (var k in sampleVars) {
        t = t.split('{{ ' + k + ' }}').join(sampleVars[k]);
        t = t.split('{{' + k + '}}').join(sampleVars[k]);
    }
    return t;
}
function updatePreview() {
    var f = document.getElementById('preview-frame');
    var d = f.contentDocument || f.contentWindow.document;
    d.open(); d.write(renderVars(document.getElementById('tpl-body').value)); d.close();
    f.style.height = Math.max(400, d.body.scrollHeight + 40) + 'px';
}
var t; function debounced() { clearTimeout(t); t = setTimeout(updatePreview, 150); }
document.getElementById('tpl-body').addEventListener('input', debounced);
updatePreview();
</script>
@endsection
