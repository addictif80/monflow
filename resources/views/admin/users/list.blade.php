@extends('layouts.admin')

@section('title', 'Utilisateurs — Admin MonFlow')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Utilisateurs</h1>
    <a href="/admin/users/create" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-medium transition">+ Créer un utilisateur</a>
</div>

{{-- Search & Filter --}}
<form method="GET" action="/admin/users" class="flex flex-wrap gap-3 mb-6">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Rechercher par nom, email..."
           class="flex-1 min-w-[200px] px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
    <select name="status" class="px-4 py-2 bg-gray-800 border border-gray-700 rounded-lg text-sm focus:outline-none focus:border-indigo-500">
        <option value="">Tous les statuts</option>
        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actif</option>
        <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspendu</option>
        <option value="deleted" {{ request('status') === 'deleted' ? 'selected' : '' }}>Supprimé</option>
    </select>
    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 rounded-lg text-sm font-medium transition">Filtrer</button>
</form>

{{-- Users Table --}}
<div class="bg-gray-800 border border-gray-700 rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="border-b border-gray-700 text-left text-gray-400">
                    <th class="px-4 py-3">Nom d'utilisateur</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Statut</th>
                    <th class="px-4 py-3">Créé le</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr class="border-b border-gray-700/50 hover:bg-gray-700 cursor-pointer" onclick="window.location='/admin/users/{{ $user->id }}'">
                        <td class="px-4 py-3">
                            <a href="/admin/users/{{ $user->id }}" class="text-indigo-400 hover:text-indigo-300 font-medium">{{ $user->username }}</a>
                        </td>
                        <td class="px-4 py-3 text-gray-300">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            @if($user->status === 'active')
                                <span class="px-2 py-0.5 text-xs rounded-full bg-green-900/50 text-green-400 border border-green-700">active</span>
                            @elseif($user->status === 'suspended')
                                <span class="px-2 py-0.5 text-xs rounded-full bg-red-900/50 text-red-400 border border-red-700">suspendu</span>
                            @elseif($user->status === 'deleted')
                                <span class="px-2 py-0.5 text-xs rounded-full bg-gray-700 text-gray-400 border border-gray-600">supprimé</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-400">{{ $user->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">Aucun utilisateur trouvé.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $users->links() }}
</div>
@endsection
