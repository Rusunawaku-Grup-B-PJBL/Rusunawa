<?php

namespace App\Http\Requests;

class RegisterRequest extends ApiFormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->email)) {
            $this->merge(['email' => strtolower(trim($this->email))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'nim' => ['required', 'string', 'max:20', 'unique:users,nim'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['required', 'string', 'regex:/^(\+62|62|0)8[0-9]{8,12}$/'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama lengkap',
            'nim' => 'NIM',
            'email' => 'email',
            'phone' => 'nomor HP',
            'password' => 'kata sandi',
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'phone.regex' => 'Nomor HP tidak valid. Contoh: 081234567890.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);
    }
}