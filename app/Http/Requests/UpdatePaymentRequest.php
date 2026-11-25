<?php

namespace App\Http\Requests;

class UpdatePaymentRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'payment_status' => ['required', 'in:terverifikasi,ditolak'],
        ];
    }

    public function attributes(): array
    {
        return ['payment_status' => 'status pembayaran'];
    }
}
