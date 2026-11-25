<?php

namespace App\Http\Requests;

class UpdateComplaintTicketRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', 'in:dikirim,diproses,selesai'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'status' => 'status keluhan',
            'note' => 'catatan pengelola',
        ];
    }
}
