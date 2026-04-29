@extends('layouts.admin')
@section('title', 'Éditer paroles — Admin MonFlow')
@section('content')
<div class="mb-4"><a href="/admin/lyrics" class="text-gray-400 hover:text-gray-200 text-sm">&larr; Retour</a></div>
<div class="flex items-center gap-4 mb-6">
    <h1 class="text-2xl font-bold">{{ $song['title'] ?? 'Sans titre' }}</h1>
    <span class="text-gray-400 text-sm">{{ $song['artist'] ?? '' }} — {{ $song['album'] ?? '' }} ({{ gmdate('i:s', $song['duration'] ?? 0) }})</span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="space-y-4">
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-4">
            <div class="flex items-center gap-3 mb-3">
                <button id="playBtn" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 rounded text-sm font-medium">▶ Écouter</button>
                <span id="curTime" class="text-xs text-gray-400">0:00</span>
                <input id="progress" type="range" min="0" max="100" value="0" class="flex-1 accent-indigo-500">
                <span id="totTime" class="text-xs text-gray-400">{{ gmdate('i:s', $song['duration'] ?? 0) }}</span>
            </div>
            <div class="flex gap-2">
                <button id="stampBtn" class="px-3 py-1.5 bg-green-700 hover:bg-green-600 rounded text-xs font-medium" title="Insère le timestamp au curseur">⏱ Horodater (Ctrl+Entrée)</button>
                <button id="stampAllBtn" class="px-3 py-1.5 bg-gray-700 hover:bg-gray-600 rounded text-xs" title="Ajoute un timestamp vide sur chaque ligne sans timestamp">Horodater toutes les lignes</button>
            </div>
        </div>

        <form method="POST" action="/admin/lyrics/{{ $song['id'] }}/save">
            @csrf
            <div class="bg-gray-800 border border-gray-700 rounded-lg p-4">
                <label class="block text-sm text-gray-400 mb-2">Paroles LRC</label>
                <textarea id="lrcEditor" name="lrc_content" rows="20" class="w-full px-3 py-2 bg-gray-900 border border-gray-700 rounded-lg text-sm font-mono focus:outline-none focus:border-indigo-500 leading-relaxed" placeholder="[00:12.00]Première ligne&#10;[00:17.50]Deuxième ligne&#10;[00:23.80]...">{{ $lrcContent }}</textarea>
                <p class="text-xs text-gray-500 mt-2">Format : <code>[mm:ss.xx]texte</code> — Utilisez le bouton ⏱ pendant l'écoute pour ajouter les timestamps.</p>
                <button type="submit" class="mt-3 px-6 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-medium">Enregistrer</button>
            </div>
        </form>
    </div>

    <div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden sticky top-8" style="max-height:calc(100vh - 120px)">
        <div class="px-4 py-3 border-b border-gray-700 flex items-center justify-between">
            <h3 class="font-semibold text-sm">Aperçu en temps réel</h3>
            <span id="previewStatus" class="text-xs text-gray-500">En pause</span>
        </div>
        <div id="previewArea" class="p-4 overflow-y-auto scroll" style="max-height:calc(100vh - 200px)"></div>
    </div>
</div>

<audio id="audio" src="/admin/lyrics/{{ $song['id'] }}/stream" preload="metadata"></audio>

<script>
const audio = document.getElementById('audio');
const editor = document.getElementById('lrcEditor');
const preview = document.getElementById('previewArea');
const playBtn = document.getElementById('playBtn');
const progress = document.getElementById('progress');
const curTime = document.getElementById('curTime');
const previewStatus = document.getElementById('previewStatus');

function fmt(s) { const m = Math.floor(s/60); const ss = (s%60).toFixed(2).padStart(5,'0'); return String(m).padStart(2,'0') + ':' + ss; }
function fmtShort(s) { s=Math.floor(s); return Math.floor(s/60)+':'+(s%60).toString().padStart(2,'0'); }

playBtn.onclick = () => {
    if (audio.paused) { audio.play(); playBtn.textContent = '⏸ Pause'; previewStatus.textContent = 'Lecture'; }
    else { audio.pause(); playBtn.textContent = '▶ Écouter'; previewStatus.textContent = 'En pause'; }
};
audio.addEventListener('timeupdate', () => {
    if (audio.duration) progress.value = (audio.currentTime / audio.duration) * 100;
    curTime.textContent = fmtShort(audio.currentTime);
    syncPreview();
});
progress.oninput = (e) => { if (audio.duration) audio.currentTime = (e.target.value/100)*audio.duration; };

// Stamp button — insert timestamp at cursor
document.getElementById('stampBtn').onclick = stamp;
document.addEventListener('keydown', (e) => {
    if (e.ctrlKey && e.key === 'Enter') { e.preventDefault(); stamp(); }
});

function stamp() {
    const t = fmt(audio.currentTime);
    const pos = editor.selectionStart;
    const text = editor.value;
    const lineStart = text.lastIndexOf('\n', pos - 1) + 1;
    const existing = text.substring(lineStart).match(/^\[\d{2}:\d{2}\.\d{2}\]/);
    if (existing) {
        editor.value = text.substring(0, lineStart) + `[${t}]` + text.substring(lineStart + existing[0].length);
    } else {
        editor.value = text.substring(0, lineStart) + `[${t}]` + text.substring(lineStart);
    }
    const nextLine = text.indexOf('\n', pos);
    editor.selectionStart = editor.selectionEnd = nextLine >= 0 ? nextLine + 1 : editor.value.length;
    editor.focus();
    updatePreview();
}

document.getElementById('stampAllBtn').onclick = () => {
    const lines = editor.value.split('\n');
    editor.value = lines.map(l => l.match(/^\[/) ? l : '[00:00.00]' + l).join('\n');
    updatePreview();
};

// Parse & preview
function parseLrc(text) {
    const lines = [];
    text.split('\n').forEach(l => {
        const m = l.match(/^\[(\d{2}):(\d{2})\.(\d{2,3})\](.*)/);
        if (m) {
            const time = parseInt(m[1])*60 + parseInt(m[2]) + parseInt(m[3].padEnd(3,'0'))/1000;
            lines.push({ time, text: m[4] });
        }
    });
    return lines.sort((a,b) => a.time - b.time);
}

function updatePreview() {
    const lines = parseLrc(editor.value);
    preview.innerHTML = lines.map((l, i) =>
        `<p class="lyrics-line py-2 px-2 rounded cursor-pointer text-sm text-gray-500 transition-all" data-time="${l.time}" data-idx="${i}"><span class="text-xs text-gray-600 mr-2">[${fmt(l.time)}]</span>${l.text ? l.text.replace(/</g,'&lt;') : '♪'}</p>`
    ).join('') || '<p class="text-gray-500 text-center py-8">Ajoutez des paroles au format LRC pour voir l\'aperçu</p>';
    preview.querySelectorAll('.lyrics-line').forEach(el => {
        el.onclick = () => { audio.currentTime = parseFloat(el.dataset.time); if(audio.paused) { audio.play(); playBtn.textContent='⏸ Pause'; previewStatus.textContent='Lecture'; }};
    });
}

function syncPreview() {
    const lines = preview.querySelectorAll('.lyrics-line');
    if (!lines.length) return;
    const t = audio.currentTime;
    let active = -1;
    lines.forEach((el, i) => { if (t >= parseFloat(el.dataset.time)) active = i; });
    lines.forEach((el, i) => {
        if (i === active) {
            el.classList.add('text-white', 'font-semibold', 'bg-gray-700/50');
            el.classList.remove('text-gray-500');
            if (!audio.paused) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            el.classList.remove('text-white', 'font-semibold', 'bg-gray-700/50');
            el.classList.add('text-gray-500');
        }
    });
}

editor.addEventListener('input', updatePreview);
updatePreview();
</script>
@endsection
