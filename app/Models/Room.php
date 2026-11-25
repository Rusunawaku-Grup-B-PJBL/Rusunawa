<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = [
        'code', 'floor', 'gender', 'capacity', 'deposit', 'description', 'image_path',
    ];

    protected function casts(): array
    {
        return [
            'floor' => 'integer',
            'capacity' => 'integer',
            'deposit' => 'integer',
        ];
    }

    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(Facility::class, 'room_facility');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function damageReports(): HasMany
    {
        return $this->hasMany(DamageReport::class);
    }

    /** Jumlah penghuni terbanyak yang menempati kamar bersamaan pada rentang tanggal ini. */
    public function occupiedCount(CarbonInterface $mulai, CarbonInterface $selesai): int
    {
        $bookings = $this->bookings()
            ->where('status', 'aktif')
            ->where('start_date', '<', $selesai->toDateString())
            ->where('end_date', '>', $mulai->toDateString())
            ->get(['start_date', 'end_date', 'occupants']);

        $events = [];

        foreach ($bookings as $booking) {
            $start = max($mulai->toDateString(), $booking->start_date->toDateString());
            $end = min($selesai->toDateString(), $booking->end_date->toDateString());

            $events[$start] = ($events[$start] ?? 0) + $booking->occupants;
            $events[$end] = ($events[$end] ?? 0) - $booking->occupants;
        }

        ksort($events);

        $occupied = 0;
        $maximum = 0;

        foreach ($events as $change) {
            $occupied += $change;
            $maximum = max($maximum, $occupied);
        }

        return $maximum;
    }

    /** Sisa slot pada rentang tertentu (tidak pernah negatif). */
    public function remainingSlots(CarbonInterface $mulai, CarbonInterface $selesai): int
    {
        return max(0, $this->capacity - $this->occupiedCount($mulai, $selesai));
    }
}
