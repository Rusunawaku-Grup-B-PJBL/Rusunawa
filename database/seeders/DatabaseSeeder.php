<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Urutan penting: kamar dan pengguna harus ada sebelum booking dan laporan.
        $this->call([
            UserSeeder::class,
            RoomSeeder::class,
            BookingSeeder::class,
            HandoverSeeder::class,
            DamageReportSeeder::class,
            AnnouncementSeeder::class,
            ComplaintTicketSeeder::class,
        ]);
    }
}