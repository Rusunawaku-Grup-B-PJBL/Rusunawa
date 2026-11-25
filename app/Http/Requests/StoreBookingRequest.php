<?php

namespace App\Http\Requests;

class StoreBookingRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'duration_months' => ['required', 'integer', 'min:1', 'max:24'],
            'occupants' => ['required', 'integer', 'min:1'],
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ];
    }

    public function attributes(): array
    {
        return [
            'room_id' => 'kamar',
            'start_date' => 'tanggal mulai',
            'duration_months' => 'durasi',
            'occupants' => 'jumlah penghuni',
            'payment_proof' => 'bukti pembayaran',
        ];
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'start_date.after_or_equal' => 'Tanggal mulai tidak boleh sebelum hari ini.',
            'duration_months.min' => 'Durasi minimal 1 bulan.',
            'duration_months.max' => 'Durasi maksimal 24 bulan.',
            'occupants.min' => 'Jumlah penghuni minimal 1 orang.',
            'payment_proof.mimes' => 'Bukti pembayaran harus berupa JPG, JPEG, PNG, atau PDF.',
            'payment_proof.max' => 'Ukuran bukti pembayaran maksimal 4 MB.',
        ]);
    }
}
