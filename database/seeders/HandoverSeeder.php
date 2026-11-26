<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Handover;
use App\Models\User;
use Illuminate\Database\Seeder;

class HandoverSeeder extends Seeder
{
    private const ITEMS = [
        'Kunci kamar',
        'Kasur & bantal',
        'Lemari',
        'AC',
        'Lampu',
        'Kran & kamar mandi',
    ];

    public function run(): void
    {
        $budi = User::where('email', 'penghuni@ubbg.ac.id')->firstOrFail();

        // Booking milik Budi sengaja dilewati agar tes POST handover tetap bisa dijalankan.
        $bookings = Booking::where('user_id', '!=', $budi->id)
            ->orderBy('id')
            ->take(5)
            ->get();

        foreach ($bookings as $i => $booking) {
            $handover = Handover::create([
                'booking_id' => $booking->id,
                'type' => 'checkin',
                'notes' => 'Serah terima awal, kondisi kamar dicek bersama petugas.',
                'photo_path' => null,
                'signature_path' => null,
            ]);

            foreach (self::ITEMS as $n => $nama) {
                $handover->items()->create([
                    'item_name' => $nama,
                    // Satu item rusak di handover ketiga sebagai variasi data.
                    'condition' => ($i === 2 && $n === 3) ? 'rusak' : 'baik',
                ]);
            }
        }
    }
}