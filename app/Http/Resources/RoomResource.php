<?php

namespace App\Http\Resources;

use App\Models\Room;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Room */
class RoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $today = CarbonImmutable::today();
        $remaining = $this->remainingSlots($today, $today->addDay());

        $data = [
            'id' => $this->id,
            'code' => $this->code,
            'name' => 'Kamar '.$this->code,
            'floor' => $this->floor,
            'gender' => $this->gender,
            'gender_label' => $this->gender === 'putra' ? 'Putra' : 'Putri',
            'capacity' => $this->capacity,
            'deposit' => $this->deposit,
            'image_url' => $this->image_path ? Storage::disk('public')->url($this->image_path) : null,
            'remaining_slots' => $remaining,
            'availability' => [
                'status' => $remaining === 0 ? 'penuh' : ($remaining === 1 ? 'tersisa_1' : 'tersedia'),
                'label' => $remaining === 0 ? 'Penuh' : ($remaining === 1 ? 'Tersisa 1' : 'Tersedia'),
            ],
        ];

        if ($this->relationLoaded('facilities')) {
            $data['description'] = $this->description;
            $data['facilities'] = $this->facilities->pluck('name')->values();
            $data['schedule'] = $this->schedule($today);
        }

        return $data;
    }

    private function schedule(CarbonImmutable $today): array
    {
        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $schedule = [];

        for ($offset = 0; $offset < 7; $offset++) {
            $date = $today->addDays($offset);
            $occupied = $this->occupiedCount($date, $date->addDay());

            $schedule[] = [
                'date' => $date->toDateString(),
                'day_label' => $days[$date->dayOfWeek],
                'occupied' => $occupied,
                'capacity' => $this->capacity,
                'is_full' => $occupied >= $this->capacity,
            ];
        }

        return $schedule;
    }
}
