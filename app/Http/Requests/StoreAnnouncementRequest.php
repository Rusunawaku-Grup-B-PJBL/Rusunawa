<?php

namespace App\Http\Requests;

class StoreAnnouncementRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'min:1'],
            'published_at' => ['sometimes', 'date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'judul pengumuman',
            'body' => 'isi pengumuman',
            'published_at' => 'waktu publikasi',
        ];
    }
}
