<?php

namespace App\Http\Requests;

class StoreSupportMessageRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:1', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return ['body' => 'pesan'];
    }
}
