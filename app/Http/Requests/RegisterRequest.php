<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // L'unicité de l'email est vérifiée manuellement ci-dessous pour
            // pouvoir distinguer "déjà pris" de "compte supprimé" et donner
            // un message adapté (voir withValidator()).
            'username' => 'required|unique:users|min:3|max:50',
            'email' => 'required|email',
            'first_name' => 'nullable|max:100',
            'last_name' => 'nullable|max:100',
            'password' => 'required|min:6|confirmed',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $existing = User::where('email', $this->input('email'))->first();
            if (!$existing) {
                return;
            }
            if ($existing->status === 'deleted') {
                $validator->errors()->add('email', 'Ce compte a été supprimé. Consultez l\'email de confirmation de suppression et cliquez sur « Souscrire à nouveau » pour réutiliser cette adresse.');
            } else {
                $validator->errors()->add('email', 'Cette adresse email est déjà utilisée.');
            }
        });
    }
}
