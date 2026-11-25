<?php

namespace App\Http\Requests;

class StoreComplaintTicketRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'category' => ['required', 'in:fasilitas,kebersihan,pelayanan,lainnya'],
            'description' => ['required', 'string', 'min:5', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'judul keluhan',
            'category' => 'kategori keluhan',
            'description' => 'deskripsi',
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'description.min' => 'Deskripsi minimal 5 karakter.',
        ]);
    }
}
