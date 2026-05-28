@extends('layouts.admin')
@section('title', ($newsletter ? 'Modifier' : 'Nouvelle') . ' newsletter — Admin MonFlow')
@section('content')
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<style>
.ql-toolbar { background:#1c1c2e; border-color:rgba(255,255,255,.08) !important; border-radius:.5rem .5rem 0 0; }
.ql-toolbar .ql-stroke { stroke:#a1a1aa; }
.ql-toolbar .ql-fill  { fill:#a1a1aa; }
.ql-toolbar .ql-picker-label { color:#a1a1aa; }
.ql-toolbar button:hover .ql-stroke,
.ql-toolbar button.ql-active .ql-stroke { stroke:#818cf8; }
.ql-toolbar button:hover .ql-fill,
.ql-toolbar button.ql-active .ql-fill  { fill:#818cf8; }
.ql-toolbar .ql-picker-label:hover,
.ql-toolbar .ql-picker-item:hover  { color:#818cf8; }
.ql-container { background:#fff; border-color:rgba(255,255,255,.08) !important; border-radius:0 0 .5rem .5rem; font-family:inherit; font-size:14px; }
.ql-editor { min-height:340px; color:#18181b; line-height:1.65; }
.ql-editor p { margin-bottom:.75em; }
.ql-picker-options { background:#1c1c2e !important; border-color:rgba(255,255,255,.1) !important; }
.ql-picker-item { color:#a1a1aa !important; }
/* Source toggle */
#nl-source { display:none; }
#nl-source.visible { display:block; }
#nl-editor-wrap.hidden { display:none; }
</style>

<div class="mb-6">
    <a href="/admin/newsletters" class="text-sm text-zinc-500 hover:text-zinc-300">&larr; Retour</a>
</div>

<div class="mb-6 flex items-center justify-between">
    <h1 class="text-base font-semibold text-zinc-100">{{ $newsletter ? 'Modifier la campagne' : 'Nouvelle campagne' }}</h1>
    <a href="/admin/newsletters/template" class="text-xs text-indigo-400 hover:text-indigo-300">Modifier le template →</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
        <form method="POST" id="nl-form" action="{{ $newsletter ? '/admin/newsletters/' . $newsletter->id . '/edit' : '/admin/newsletters/create' }}">
            @csrf
            <div class="mb-4">
                <label class="block text-xs font-medium text-zinc-400 mb-1.5">Sujet</label>
                <input name="subject" id="nl-subject" value="{{ old('subject', $newsletter->subject ?? '') }}" required
                       class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
            </div>

            <div class="mb-4">
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-medium text-zinc-400">Contenu du mail</label>
                    <button type="button" id="toggle-source"
                            class="text-xs text-zinc-500 hover:text-zinc-300 flex items-center gap-1 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                        Source HTML
                    </button>
                </div>

                {{-- WYSIWYG --}}
                <div id="nl-editor-wrap">
                    <div id="nl-editor"></div>
                </div>

                {{-- Source HTML (hidden by default) --}}
                <textarea id="nl-source"
                          class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 font-mono placeholder-zinc-600 px-3 py-2 outline-none transition"
                          rows="18" placeholder="<h1>Titre</h1>&#10;<p>Votre texte…</p>"></textarea>

                {{-- Hidden field submitted --}}
                <textarea name="html_body" id="nl-body" style="display:none" required>{{ old('html_body', $newsletter->html_body ?? '') }}</textarea>

                @verbatim
                <p class="text-xs text-zinc-600 mt-1.5">Variables : {{ sujet }}, {{ username }}, {{ first_name }}, {{ site_name }}, {{ site_url }}</p>
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

<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
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

var debTimer;
function debounced() { clearTimeout(debTimer); debTimer = setTimeout(updatePreview, 200); }

// ── Quill setup ──────────────────────────────────────────────────────────────
var quill = new Quill('#nl-editor', {
    theme: 'snow',
    modules: {
        toolbar: [
            [{ header: [1, 2, 3, false] }],
            ['bold', 'italic', 'underline'],
            [{ color: [] }, { background: [] }],
            [{ align: [] }],
            [{ list: 'ordered' }, { list: 'bullet' }],
            ['link', 'blockquote'],
            ['clean']
        ]
    }
});

// Load existing content into Quill
var initial = document.getElementById('nl-body').value;
if (initial.trim()) quill.clipboard.dangerouslyPasteHTML(initial);

quill.on('text-change', function() {
    document.getElementById('nl-body').value = quill.root.innerHTML;
    document.getElementById('nl-source').value  = quill.root.innerHTML;
    debounced();
});

// ── Source toggle ─────────────────────────────────────────────────────────────
var sourceMode = false;
document.getElementById('toggle-source').addEventListener('click', function() {
    sourceMode = !sourceMode;
    var editorWrap = document.getElementById('nl-editor-wrap');
    var sourceEl   = document.getElementById('nl-source');
    if (sourceMode) {
        sourceEl.value = document.getElementById('nl-body').value;
        editorWrap.classList.add('hidden');
        sourceEl.classList.add('visible');
        this.classList.add('text-indigo-400');
    } else {
        var html = sourceEl.value;
        document.getElementById('nl-body').value = html;
        quill.clipboard.dangerouslyPasteHTML(html);
        editorWrap.classList.remove('hidden');
        sourceEl.classList.remove('visible');
        this.classList.remove('text-indigo-400');
    }
});

document.getElementById('nl-source').addEventListener('input', function() {
    document.getElementById('nl-body').value = this.value;
    debounced();
});

// ── Subject input ─────────────────────────────────────────────────────────────
document.getElementById('nl-subject').addEventListener('input', debounced);

// ── Sync on submit ────────────────────────────────────────────────────────────
document.getElementById('nl-form').addEventListener('submit', function() {
    if (!sourceMode) {
        document.getElementById('nl-body').value = quill.root.innerHTML;
    }
});

updatePreview();
</script>
@endsection
