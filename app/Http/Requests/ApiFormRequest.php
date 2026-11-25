<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Dasar semua FormRequest API: pesan validasi umum berbahasa Indonesia.
 * Kelas turunan cukup mengisi rules() dan attributes().
 */
abstract class ApiFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'required' => ':Attribute wajib diisi.',
            'string' => ':Attribute harus berupa teks.',
            'email' => 'Format :attribute tidak valid.',
            'unique' => ':Attribute sudah terdaftar.',
            'min' => ':Attribute minimal :min karakter.',
            'max' => ':Attribute maksimal :max karakter.',
            'confirmed' => 'Konfirmasi :attribute tidak cocok.',
            'regex' => 'Format :attribute tidak valid.',
            'integer' => ':Attribute harus berupa angka bulat.',
            'date' => ':Attribute bukan tanggal yang valid.',
            'in' => ':Attribute yang dipilih tidak valid.',
            'exists' => ':Attribute tidak ditemukan.',
            'file' => ':Attribute harus berupa berkas.',
            'image' => ':Attribute harus berupa gambar.',
            'mimes' => 'Format :attribute harus berupa :values.',
        ];
    }
}