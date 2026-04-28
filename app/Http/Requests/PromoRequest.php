<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PromoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        if ($this->isMethod('get')) return [];

        $id = $this->route('id');
        return [
            'code' => 'required|unique:promo_codes,code' . ($id ? ",{$id}" : ''),
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'max_uses' => 'nullable|integer|min:0',
            'valid_from' => 'required|date',
            'valid_until' => 'nullable|date|after:valid_from',
            'is_active' => 'nullable|boolean',
        ];
    }
}
