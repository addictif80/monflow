@extends('layouts.admin')
@section('title', ($newsletter ? 'Modifier' : 'Nouvelle') . ' newsletter — Admin MonFlow')
@section('content')
<div class="mb-6"><a href="/admin/newsletters" class="text-gray-400 hover:text-gray-200 text-sm">&larr; Retour</a></div>
<h1 class="text-2xl font-bold mb-6">{{ $newsletter ? 'Modifier la campagne' : 'Nouvelle campagne' }}</h1>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-gray-800 border border-gray-700 rounded-lg p-6">
        <form method="POST" action="{{ $newsletter ? '/admin/newsletters/' . $newsletter->id . '/edit' : '/admin/newsletters/create' }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm text-gray-400 mb-1">Sujet</label>
                <input name="subject" id="nl-subject" value="{{ old('subject', $newsletter->subject ?? '') }}" required class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
            </div>
            <div class="mb-4">
                <label class="block text-sm text-gray-400 mb-1">Corps HTML</label>
                <textarea name="html_body" id="nl-body" rows="22" required class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm font-mono focus:outline-none focus:border-indigo-500">{{ old('html_body', $newsletter->html_body ?? '') }}</textarea>
                @verbatim
                <p class="text-xs text-gray-500 mt-1">Variables : {{ username }}, {{ first_name }}, {{ site_name }}, {{ site_url }}</p>
                @endverbatim
            </div>
            <div class="flex gap-3">
                <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-medium">{{ $newsletter ? 'Mettre à jour' : 'Créer le brouillon' }}</button>
                <a href="/admin/newsletters" class="px-6 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm font-medium">Annuler</a>
            </div>
        </form>
    </div>

    <div>
        <div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden sticky top-8">
            <div class="px-4 py-3 border-b border-gray-700">
                <h3 class="font-semibold text-sm">Aperçu</h3>
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
