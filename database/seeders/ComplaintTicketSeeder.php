<?php

namespace Database\Seeders;

use App\Models\ComplaintTicket;
use App\Models\User;
use Illuminate\Database\Seeder;

class ComplaintTicketSeeder extends Seeder
{
    public function run(): void
    {
        $budi = User::where('email', 'penghuni@ubbg.ac.id')->firstOrFail();
        $admin = User::where('role', User::ROLE_ADMIN)->first();
        $lainnya = User::where('role', User::ROLE_PENGHUNI)
            ->where('id', '!=', $budi->id)
            ->orderBy('id')
            ->get()
            ->values();

        // [pemilik, judul, kategori, deskripsi, status akhir]
        $data = [
            [$budi, 'Pintu kamar mandi sulit ditutup', 'fasilitas', 'Engsel pintu kamar mandi longgar sehingga pintu tidak menutup rapat.', 'diproses'],
            [$budi, 'Lorong Blok A kotor', 'kebersihan', 'Lorong lantai 1 Blok A jarang dipel dan banyak sampah menumpuk.', 'dikirim'],
            [$lainnya[0], 'Petugas lambat merespons', 'pelayanan', 'Laporan sebelumnya belum ditanggapi petugas selama beberapa hari.', 'selesai'],
            [$lainnya[1], 'Wi-Fi sering terputus', 'fasilitas', 'Koneksi Wi-Fi di lantai 2 terputus setiap malam.', 'diproses'],
            [$lainnya[2], 'Usulan jadwal jemur pakaian', 'lainnya', 'Mohon disediakan jadwal penggunaan area jemur agar tidak berebut.', 'dikirim'],
        ];

        foreach ($data as [$user, $judul, $kategori, $deskripsi, $status]) {
            $ticket = ComplaintTicket::create([
                'user_id' => $user->id,
                'title' => $judul,
                'category' => $kategori,
                'description' => $deskripsi,
                'status' => $status,
            ]);

            $ticket->logs()->create([
                'author_id' => null,
                'status' => 'dikirim',
                'note' => 'Keluhan diterima.',
            ]);

            if (in_array($status, ['diproses', 'selesai'], true)) {
                $ticket->logs()->create([
                    'author_id' => $admin?->id,
                    'status' => 'diproses',
                    'note' => 'Keluhan sedang ditindaklanjuti pengelola.',
                ]);
            }

            if ($status === 'selesai') {
                $ticket->logs()->create([
                    'author_id' => $admin?->id,
                    'status' => 'selesai',
                    'note' => 'Keluhan sudah ditangani.',
                ]);
            }
        }
    }
}