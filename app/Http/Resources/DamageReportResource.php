<?php

namespace App\Http\Resources;

use App\Models\DamageReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin DamageReport */
class DamageReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'room_code' => $this->room->code,
            'category' => $this->category,
            'category_label' => match ($this->category) {
                'ac' => 'AC',
                'lampu' => 'Lampu',
                default => 'Air',
            },
            'description' => $this->description,
            'photo_url' => $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null,
            'status' => $this->status,
            'status_label' => match ($this->status) {
                'diproses' => 'Diproses',
                'selesai' => 'Selesai',
                default => 'Dilaporkan',
            },
            'technician_name' => $this->technician_name,
            'logs' => $this->whenLoaded('logs', fn () => $this->logs->map(fn ($log) => [
                'status' => $log->status,
                'status_label' => match ($log->status) {
                    'diproses' => 'Diproses',
                    'selesai' => 'Selesai',
                    default => 'Dilaporkan',
                },
                'note' => $log->note,
                'created_at' => $log->created_at?->toISOString(),
            ])->values()),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
