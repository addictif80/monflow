@extends('layouts.admin')

@section('title', 'Utilisateurs — Admin MonFlow')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-base font-semibold text-zinc-100">Utilisateurs</h1>
        <p class="text-sm text-zinc-500 mt-0.5">Gestion des comptes utilisateurs</p>
    </div>
    <a href="/admin/users/create" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">+ Créer un utilisateur</a>
</div>

{{-- Search & Filter --}}
<form method="GET" action="/admin/users" class="flex flex-wrap gap-3 mb-6">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher par nom, email..."
           class="flex-1 min-w-[200px] bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
    <select name="status" class="bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none">
        <option value="">Tous les statuts</option>
        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actif</option>
        <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspendu</option>
        <option value="deleted" {{ request('status') === 'deleted' ? 'selected' : '' }}>Supprimé</option>
    </select>
    <button type="submit" class="inline-flex items-center gap-2 bg-zinc-800 hover:bg-zinc-700 text-zinc-200 text-sm font-medium px-4 py-2 rounded-lg border border-zinc-700 transition">Filtrer</button>
</form>

{{-- Users Table --}}
<div class="bg-zinc-900 border border-zinc-800 rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-800">
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Nom d'utilisateur</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Statut</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 uppercase tracking-wider">Créé le</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800/50">
                @forelse($users as $user)
                    <tr class="hover:bg-zinc-800/30 transition cursor-pointer" onclick="window.location='/admin/users/{{ $user->id }}'">
                        <td class="px-4 py-3">
                            <a href="/admin/users/{{ $user->id }}" class="text-indigo-400 hover:text-indigo-300 font-medium">{{ $user->username }}</a>
                        </td>
                        <td class="px-4 py-3 text-zinc-400">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            @if($user->status === 'active')
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">active</span>
                            @elseif($user->status === 'suspended')
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20">suspendu</span>
                            @elseif($user->status === 'deleted')
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-800 text-zinc-500 border border-zinc-700">supprimé</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-500">{{ $user->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-zinc-600">Aucun utilisateur trouvé.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $users->links() }}
</div>
@endsection
