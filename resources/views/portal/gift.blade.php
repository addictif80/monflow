@extends('layouts.app')

@section('title', 'Offrir un abonnement - MonFlow')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold">Offrir un abonnement</h1>
    <p class="text-gray-400 mt-1">Faites plaisir à quelqu'un avec un abonnement MonFlow</p>
</div>

<div class="bg-gray-800 rounded-lg border border-gray-700 p-6 max-w-lg">
    <form action="/portal/gift" method="POST" class="space-y-5">
        @csrf

        <div>
            <label for="plan_id" class="block text-sm font-medium text-gray-300 mb-1">Formule</label>
            <select name="plan_id" id="plan_id" required
                    class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <option value="">-- Choisir une formule --</option>
                @foreach($plans as $plan)
                    <option value="{{ $plan->id }}">{{ $plan->name }} - {{ number_format($plan->price, 2, ',', ' ') }} &euro;/{{ $plan->billing_cycle }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="recipient_email" class="block text-sm font-medium text-gray-300 mb-1">Email du destinataire</label>
            <input type="email" name="recipient_email" id="recipient_email" required placeholder="exemple@email.com"
                   value="{{ old('recipient_email') }}"
                   class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        </div>

        <div class="pt-4 border-t border-gray-700">
            <button type="submit" class="w-full px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">
                Offrir
            </button>
        </div>
    </form>
</div>
@endsection
