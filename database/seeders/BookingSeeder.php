<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $budi = User::where('email', 'penghuni@ubbg.ac.id')->firstOrFail();

        // Setiap penghuni lain hanya dipakai sekali agar tidak ada booking aktif tumpang tindih.
        $lainnya = User::where('role', 'penghuni')
            ->where('id', '!=', $budi->id)
            ->orderBy('id')
            ->get()
            ->values();
        $urutan = 0;

        // Jumlah booking aktif per kamar:
        // A-101 sisa 2 dari 4, A-102 sisa 1 dari 4, B-201 penuh, B-202 sisa 1 dari 2,
        // C-301 kosong, C-302 sisa 3 dari 4.
        $rencana = [
            'A-101' => 2,
            'A-102' => 3,
            'B-201' => 4,
            'B-202' => 1,
            'C-302' => 1,
        ];

        foreach ($rencana as $kode => $jumlah) {
            $room = Room::where('code', $kode)->firstOrFail();

            for ($n = 0; $n < $jumlah; $n++) {
                $user = ($kode === 'A-101' && $n === 0) ? $budi : $lainnya[$urutan++];

                $mulai = Carbon::today()->subDays(15 + $n * 7);
                $durasi = 6;

                Booking::create([
                    'user_id' => $user->id,
                    'room_id' => $room->id,
                    'start_date' => $mulai->toDateString(),
                    'duration_months' => $durasi,
                    'end_date' => $mulai->copy()->addMonthsNoOverflow($durasi)->toDateString(),
                    'occupants' => 1,
                    'payment_proof_path' => null,
                    'payment_status' => $kode === 'B-202' ? 'menunggu' : 'terverifikasi',
                    'status' => 'aktif',
                ]);
            }
        }
    }
}
