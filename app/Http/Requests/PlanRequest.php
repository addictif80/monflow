<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlanRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'required|max:100',
            'description' => 'nullable|max:500',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,quarterly,yearly',
            'stripe_price_id' => 'nullable|starts_with:price_',
            'max_devices' => 'required|integer|min:1',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'stripe_price_id.starts_with' => 'Le Stripe Price ID doit commencer par "price_" (pas "prod_"). Dans Stripe, ouvrez le Produit → section Tarification → copiez l\'ID qui commence par price_.',
        ];
    }
}
