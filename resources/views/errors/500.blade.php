@extends('errors.layout')
@section('title', 'Erreur serveur')
@section('code', '500')
@section('heading', 'Une erreur est survenue')
@section('message', 'Quelque chose s\'est mal passé de notre côté. L\'équipe a été notifiée. Réessayez dans quelques instants.')
@section('actions')
    <a href="javascript:history.back()" class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-sm font-medium px-4 py-2 rounded-lg border border-zinc-700 transition">← Retour</a>
    <a href="/portal" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Mon espace</a>
@endsection
