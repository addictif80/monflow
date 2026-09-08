@extends('layouts.app')

@section('title', 'Offrir un abonnement — MonFlow')

@section('content')
<div class="mb-6">
    <h1 class="text-base font-semibold text-zinc-100">Offrir un abonnement</h1>
    <p class="text-sm text-zinc-500 mt-0.5">Faites plaisir à quelqu'un avec un abonnement MonFlow</p>
</div>

<div class="bg-zinc-900 border border-zinc-800 rounded-xl p-6 max-w-lg">
    <form action="/portal/gift" method="POST" class="space-y-5">
        @csrf

        <div>
            <label for="plan_id" class="block text-xs font-medium text-zinc-400 mb-1.5">Formule</label>
            <select name="plan_id" id="plan_id" required
                    class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 px-3 py-2 outline-none transition">
                <option value="">-- Choisir une formule --</option>
                @foreach($plans as $plan)
                    <option value="{{ $plan->id }}">{{ $plan->name }} - {{ number_format($plan->price, 2, ',', ' ') }} &euro;/{{ $plan->billing_cycle }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="recipient_email" class="block text-xs font-medium text-zinc-400 mb-1.5">Email du destinataire</label>
            <input type="email" name="recipient_email" id="recipient_email" required placeholder="exemple@email.com"
                   value="{{ old('recipient_email') }}"
                   class="w-full bg-zinc-900 border border-zinc-800 focus:border-indigo-500/50 focus:ring-1 focus:ring-indigo-500/20 rounded-lg text-sm text-zinc-100 placeholder-zinc-600 px-3 py-2 outline-none transition">
        </div>

        <div class="pt-4 border-t border-zinc-800">
            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                Offrir
            </button>
        </div>
    </form>
</div>
@endsection
