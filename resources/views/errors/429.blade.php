@extends('errors.layout')
@section('title', 'Trop de tentatives')
@section('code', '429')
@section('heading', 'Trop de tentatives')
@section('message', 'Vous avez effectué trop de requêtes en peu de temps. Attendez quelques instants avant de réessayer.')
@section('actions')
    <a href="javascript:history.back()" class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-sm font-medium px-4 py-2 rounded-lg border border-zinc-700 transition">← Retour</a>
@endsection
