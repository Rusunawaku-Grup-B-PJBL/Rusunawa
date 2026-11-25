<?php

namespace Database\Seeders;

use App\Models\DamageReport;
use App\Models\DamageReportLog;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;

class DamageReportSeeder extends Seeder
{
    public function run(): void
    {
        $budi = User::where('email', 'penghuni@ubbg.ac.id')->firstOrFail();
        $room = Room::where('code', 'A-101')->firstOrFail();

        // Setiap laporan: data utama + log [status, catatan, berapa hari lalu].
        $laporan = [
            [
                'category' => 'ac',
                'description' => 'AC tidak dingin',
                'status' => 'diproses',
                'technician_name' => 'Pak Hendra',
                'logs' => [
                    ['dilaporkan', 'Laporan diterima.', 3],
                    ['diproses', 'Teknisi dijadwalkan melakukan pengecekan.', 2],
                ],
            ],
            [
                'category' => 'lampu',
                'description' => 'Lampu kamar mati',
                'status' => 'selesai',
                'technician_name' => 'Pak Rudi',
                'logs' => [
                    ['dilaporkan', 'Laporan diterima.', 9],
                    ['diproses', 'Lampu pengganti sedang disiapkan.', 8],
                    ['selesai', 'Lampu sudah diganti.', 7],
                ],
            ],
            [
                'category' => 'air',
                'description' => 'Kran bocor',
                'status' => 'dilaporkan',
                'technician_name' => null,
                'logs' => [
                    ['dilaporkan', 'Laporan diterima.', 0],
                ],
            ],
        ];

        foreach ($laporan as $item) {
            $logs = $item['logs'];
            unset($item['logs']);

            $report = DamageReport::create($item + [
                'user_id' => $budi->id,
                'room_id' => $room->id,
                'photo_path' => null,
            ]);

            foreach ($logs as [$status, $catatan, $hariLalu]) {
                DamageReportLog::create([
                    'damage_report_id' => $report->id,
                    'status' => $status,
                    'note' => $catatan,
                    'created_at' => now()->subDays($hariLalu),
                ]);
            }
        }
    }
}
