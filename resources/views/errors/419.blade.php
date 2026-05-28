@extends('errors.layout')
@section('title', 'Session expirée')
@section('code', '419')
@section('heading', 'Session expirée')
@section('message', 'Votre session a expiré. Rechargez la page et réessayez.')
@section('actions')
    <a href="javascript:location.reload()" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">Recharger la page</a>
@endsection
