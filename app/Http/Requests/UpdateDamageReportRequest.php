<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;

class UpdateDamageReportRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'required', 'in:dilaporkan,diproses,selesai'],
            'technician_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'note' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->hasAny(['status', 'technician_name', 'note'])) {
                $validator->errors()->add('status', 'Minimal satu perubahan harus dikirim.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'status' => 'status laporan',
            'technician_name' => 'nama teknisi',
            'note' => 'catatan',
        ];
    }
}
