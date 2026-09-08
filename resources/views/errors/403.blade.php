@extends('errors.layout')
@section('title', 'Accès refusé')
@section('code', '403')
@section('heading', 'Accès refusé')
@section('message', 'Vous n\'avez pas les autorisations nécessaires pour accéder à cette page.')
@section('actions')
    <a href="javascript:history.back()" class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-sm font-medium px-4 py-2 rounded-lg border border-zinc-700 transition">← Retour</a>
    <a href="/portal" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Mon espace</a>
@endsection
