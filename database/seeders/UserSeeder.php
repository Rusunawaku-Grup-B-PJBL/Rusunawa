<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Kata sandi keduanya: password (di-hash otomatis oleh cast pada model User).
        User::create([
            'name' => 'Admin Rusunawa',
            'nim' => null,
            'email' => 'admin@ubbg.ac.id',
            'phone' => '081200000001',
            'password' => 'password',
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Budi Santoso',
            'nim' => '2021010001',
            'email' => 'penghuni@ubbg.ac.id',
            'phone' => '081200000002',
            'password' => 'password',
            'role' => 'penghuni',
        ]);

        // Penghuni tambahan untuk mengisi slot kamar pada BookingSeeder.
        User::factory()->count(10)->create();
    }
}
