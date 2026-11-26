<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $fasilitas = [];
        foreach (['AC', 'Kamar mandi dalam', 'Wi-Fi', 'Lemari', 'Dapur bersama'] as $n) {
            $fasilitas[$n] = Facility::create(['name' => $n]);
        }

        $kamar = [
            [
                'code' => 'A-101', 'floor' => 1, 'gender' => 'putra', 'capacity' => 4, 'deposit' => 500000,
                'description' => 'Kamar bersama 4 bed di Blok A lantai 1, dekat pintu masuk dan area parkir.',
                'fasilitas' => ['AC', 'Kamar mandi dalam', 'Wi-Fi', 'Lemari'],
            ],
            [
                'code' => 'A-102', 'floor' => 1, 'gender' => 'putra', 'capacity' => 4, 'deposit' => 500000,
                'description' => 'Kamar bersama 4 bed di Blok A lantai 1 dengan ventilasi baik.',
                'fasilitas' => ['AC', 'Kamar mandi dalam', 'Wi-Fi', 'Lemari'],
            ],
            [
                'code' => 'B-201', 'floor' => 2, 'gender' => 'putri', 'capacity' => 4, 'deposit' => 600000,
                'description' => 'Kamar bersama 4 bed di Blok B lantai 2, area khusus putri.',
                'fasilitas' => ['AC', 'Kamar mandi dalam', 'Wi-Fi', 'Lemari'],
            ],
            [
                'code' => 'B-202', 'floor' => 2, 'gender' => 'putri', 'capacity' => 2, 'deposit' => 750000,
                'description' => 'Kamar 2 bed di Blok B lantai 2, lebih tenang untuk belajar.',
                'fasilitas' => ['AC', 'Kamar mandi dalam', 'Wi-Fi', 'Lemari'],
            ],
            [
                'code' => 'C-301', 'floor' => 3, 'gender' => 'putra', 'capacity' => 3, 'deposit' => 450000,
                'description' => 'Kamar 3 bed di Blok C lantai 3, kamar mandi bersama di ujung lorong.',
                'fasilitas' => ['Wi-Fi', 'Lemari', 'Dapur bersama'],
            ],
            [
                'code' => 'C-302', 'floor' => 3, 'gender' => 'putri', 'capacity' => 4, 'deposit' => 500000,
                'description' => 'Kamar bersama 4 bed di Blok C lantai 3 dengan pendingin ruangan.',
                'fasilitas' => ['AC', 'Wi-Fi', 'Lemari'],
            ],
        ];

        foreach ($kamar as $data) {
            $daftar = $data['fasilitas'];
            unset($data['fasilitas']);

            $room = Room::create($data);
            $room->facilities()->attach(
                collect($daftar)->map(fn ($n) => $fasilitas[$n]->id)->all()
            );
        }
    }
}