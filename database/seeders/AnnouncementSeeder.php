<?php

namespace Database\Seeders;

use App\Models\Announcement;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'title' => 'Jam malam mulai 22.00',
                'body' => 'Seluruh penghuni wajib berada di dalam rusunawa paling lambat pukul 22.00. Tamu tidak diperkenankan menginap.',
                'published_at' => now()->subHours(2),
            ],
            [
                'title' => 'Jadwal piket Blok A: Senin',
                'body' => 'Piket kebersihan Blok A dilaksanakan hari Senin pukul 06.30. Harap hadir sesuai jadwal masing-masing kamar.',
                'published_at' => now()->subDay(),
            ],
            [
                'title' => 'Razia kamar Jumat 14.00',
                'body' => 'Razia kamar akan dilakukan hari Jumat pukul 14.00. Pastikan kamar rapi dan tidak menyimpan barang terlarang.',
                'published_at' => now()->subDays(2),
            ],
            [
                'title' => 'Pemadaman listrik Sabtu 09.00',
                'body' => 'Akan ada pemadaman listrik terjadwal hari Sabtu pukul 09.00 sampai 12.00 untuk perawatan panel. Mohon mengisi daya perangkat sebelumnya.',
                'published_at' => now()->subDays(3),
            ],
            [
                'title' => 'Pembayaran sewa paling lambat tanggal 10',
                'body' => 'Pembayaran sewa bulan ini paling lambat tanggal 10. Unggah bukti pembayaran melalui aplikasi.',
                'published_at' => now()->subDays(4),
            ],
        ];

        foreach ($data as $item) {
            Announcement::create($item);
        }
    }
}