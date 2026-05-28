@extends('errors.layout')
@section('title', 'Page introuvable')
@section('code', '404')
@section('heading', 'Page introuvable')
@section('message', 'La page que vous cherchez n\'existe pas ou a été déplacée.')
@section('actions')
    <a href="javascript:history.back()" class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-sm font-medium px-4 py-2 rounded-lg border border-zinc-700 transition">← Retour</a>
    <a href="/" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Accueil</a>
@endsection
