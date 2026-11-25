<?php

namespace App\Http\Requests;

class StoreDamageReportRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'category' => ['required', 'in:air,ac,lampu'],
            'description' => ['required', 'string', 'min:5'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:4096'],
        ];
    }

    public function attributes(): array
    {
        return [
            'category' => 'kategori',
            'description' => 'deskripsi',
            'photo' => 'foto',
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'description.min' => 'Deskripsi minimal 5 karakter.',
            'photo.max' => 'Ukuran foto maksimal 4 MB.',
            'photo.mimes' => 'Foto harus berformat JPG atau PNG.',
        ]);
    }
}
