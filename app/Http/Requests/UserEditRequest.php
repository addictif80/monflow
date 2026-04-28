<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserEditRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        if ($this->isMethod('get')) return [];

        $id = $this->route('id');
        return [
            'username' => "required|unique:users,username,{$id}|min:3|max:50",
            'email' => "required|email|unique:users,email,{$id}",
            'first_name' => 'nullable|max:100',
            'last_name' => 'nullable|max:100',
            'phone' => 'nullable|max:20',
            'status' => 'required|in:active,suspended,deleted',
            'password' => 'nullable|min:6',
            'is_admin' => 'nullable|boolean',
        ];
    }
}
