<?php

namespace App\Http\Resources;

use App\Models\ComplaintTicket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ComplaintTicket */
class ComplaintTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'category' => $this->category,
            'category_label' => match ($this->category) {
                'kebersihan' => 'Kebersihan',
                'pelayanan' => 'Pelayanan',
                'lainnya' => 'Lainnya',
                default => 'Fasilitas',
            },
            'description' => $this->description,
            'status' => $this->status,
            'status_label' => match ($this->status) {
                'diproses' => 'Diproses',
                'selesai' => 'Selesai',
                default => 'Dikirim',
            },
            'logs' => $this->whenLoaded('logs', fn () => $this->logs->map(fn ($log) => [
                'status' => $log->status,
                'status_label' => match ($log->status) {
                    'diproses' => 'Diproses',
                    'selesai' => 'Selesai',
                    default => 'Dikirim',
                },
                'note' => $log->note,
                'created_at' => $log->created_at?->toISOString(),
            ])->values()),
            'user' => $this->whenLoaded('user', fn () => [
                'name' => $this->user->name,
                'nim' => $this->user->nim,
            ]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
