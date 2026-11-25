<?php

namespace App\Http\Resources;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Booking */
class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'room' => [
                'id' => $this->room->id,
                'code' => $this->room->code,
                'name' => 'Kamar '.$this->room->code,
                'deposit' => $this->room->deposit,
            ],
            'user' => $this->whenLoaded('user', fn () => [
                'name' => $this->user->name,
                'nim' => $this->user->nim,
            ]),
            'start_date' => $this->start_date->toDateString(),
            'duration_months' => $this->duration_months,
            'end_date' => $this->end_date->toDateString(),
            'occupants' => $this->occupants,
            'payment_status' => $this->payment_status,
            'payment_status_label' => $this->paymentStatusLabel(),
            'payment_proof_url' => $this->payment_proof_path
                ? Storage::disk('public')->url($this->payment_proof_path)
                : null,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'checkin_done' => $this->handovers->contains('type', 'checkin'),
            'checkout_done' => $this->handovers->contains('type', 'checkout'),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    private function paymentStatusLabel(): string
    {
        return match ($this->payment_status) {
            'terverifikasi' => 'Terverifikasi',
            'ditolak' => 'Ditolak',
            default => 'Menunggu verifikasi',
        };
    }

    private function statusLabel(): string
    {
        return match ($this->status) {
            'selesai' => 'Selesai',
            'dibatalkan' => 'Dibatalkan',
            default => 'Aktif',
        };
    }
}
