<?php

namespace App\Http\Resources;

use App\Models\Handover;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Handover */
class HandoverResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'room_code' => $this->booking->room->code,
            'type' => $this->type,
            'type_label' => $this->type === 'checkin' ? 'Check-in' : 'Check-out',
            'notes' => $this->notes,
            'photo_url' => $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null,
            'signature_url' => $this->signature_path ? Storage::disk('public')->url($this->signature_path) : null,
            'items' => $this->items->map(fn ($item) => [
                'item_name' => $item->item_name,
                'condition' => $item->condition,
            ])->values(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
