<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'username' => 'required|unique:users|min:3|max:50',
            'email' => 'required|email|unique:users',
            'first_name' => 'nullable|max:100',
            'last_name' => 'nullable|max:100',
            'password' => 'required|min:6|confirmed',
        ];
    }
}
