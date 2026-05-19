@extends('layouts.admin')
@section('title', ($newsletter ? 'Modifier' : 'Nouvelle') . ' newsletter — Admin MonFlow')
@section('content')
<div class="mb-6">
    <a href="/admin/newsletters" class="text-sm text-zinc-500 hover:text-zinc-300">&larr; Retour</a>
</div>

<div class="mb-6">
    <h1 class="text-base font-semibold text-zinc-100">{{ $newsletter ? 'Modifier la campagne' : 'Nouvelle campagne' }}</h1>
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
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Corps HTML</label>
                <textarea name="html_body" id="nl-body" rows="22" required
                          class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 font-mono placeholder-zinc-600 px-3 py-2 outline-none transition">{{ old('html_body', $newsletter->html_body ?? '') }}</textarea>
                @verbatim
                <p class="text-xs text-zinc-600 mt-1">Variables : {{ username }}, {{ first_name }}, {{ site_name }}, {{ site_url }}</p>
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
                <h3 class="text-sm font-medium text-zinc-300">Aperçu</h3>
            </div>
            <div class="bg-white" style="min-height:400px">
                <iframe id="preview-frame" class="w-full" style="min-height:400px;border:none"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
var sampleVars = { username: 'jean.dupont', first_name: 'Jean', site_name: 'MonFlow', site_url: '{{ url("/") }}' };
function renderVars(t) { for (var k in sampleVars) { t = t.split('{{ ' + k + ' }}').join(sampleVars[k]); t = t.split('{{' + k + '}}').join(sampleVars[k]); } return t; }
function updatePreview() {
    var f = document.getElementById('preview-frame');
    var d = f.contentDocument || f.contentWindow.document;
    d.open(); d.write(renderVars(document.getElementById('nl-body').value)); d.close();
    f.style.height = Math.max(400, d.body.scrollHeight + 40) + 'px';
}
var t; function debounced() { clearTimeout(t); t = setTimeout(updatePreview, 150); }
document.getElementById('nl-body').addEventListener('input', debounced);
updatePreview();
</script>
@endsection
