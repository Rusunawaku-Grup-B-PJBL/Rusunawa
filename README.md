# Rusunawa UBBG API

Backend aplikasi booking rusunawa kampus menggunakan Laravel 11, PHP 8.2+, MySQL 8, dan Laravel Sanctum.

## Menjalankan di Laragon

Pastikan `.env` menunjuk ke database `rusunawa_ubbg`, lalu dari PowerShell:

```powershell
Set-Location C:\laragon\www\rusunawa_api
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve --host=0.0.0.0 --port=8000
```

`migrate:fresh` menghapus semua tabel dan data pada database yang dikonfigurasi. Gunakan hanya untuk database pengembangan atau setelah membuat cadangan.

Seeder menyediakan akun `admin@ubbg.ac.id` dan `penghuni@ubbg.ac.id`; kata sandi keduanya `password`.

## Pengujian Tahap 2

Dengan server berjalan, buka terminal PowerShell lain:

```powershell
Set-Location C:\laragon\www\rusunawa_api
powershell -ExecutionPolicy Bypass -File .\tests\tes_tahap2.ps1
```

Skrip memeriksa 17 route dan alur kamar, booking, laporan kerusakan, serah terima, serta admin. Skrip membuat dan membersihkan data uji khusus; database harus sudah dimigrasikan dan di-seed.

## Route API

Semua endpoint selain pendaftaran dan login memerlukan header `Authorization: Bearer <token>`. Respons sukses menggunakan pembungkus `data`. Respons validasi menggunakan HTTP 422 dan bentuk `message` serta `errors`.

| Metode | Endpoint | Akses |
|---|---|---|
| POST | `/api/register` | Publik |
| POST | `/api/login` | Publik |
| GET | `/api/me` | Penghuni |
| GET | `/api/announcements` | Penghuni |
| POST | `/api/announcements/{id}/read` | Penghuni |
| GET | `/api/rooms` | Penghuni |
| GET | `/api/rooms/{id}` | Penghuni |
| POST | `/api/bookings` | Penghuni |
| GET | `/api/bookings/mine` | Penghuni |
| POST | `/api/damage-reports` | Penghuni |
| GET | `/api/damage-reports/mine` | Penghuni |
| POST | `/api/handovers` | Penghuni |
| GET | `/api/handovers/mine` | Penghuni |
| GET | `/api/complaints/mine` | Penghuni |
| POST | `/api/complaints` | Penghuni |
| GET | `/api/support/messages` | Penghuni |
| POST | `/api/support/messages` | Penghuni |
| GET | `/api/admin/bookings` | Admin |
| GET | `/api/admin/damage-reports` | Admin |
| PATCH | `/api/admin/bookings/{id}/payment` | Admin |
| PATCH | `/api/admin/damage-reports/{id}` | Admin |
| POST | `/api/admin/announcements` | Admin |
| GET | `/api/admin/complaints` | Admin |
| PATCH | `/api/admin/complaints/{id}` | Admin |
| GET | `/api/admin/support/conversations` | Admin |
| GET | `/api/admin/support/conversations/{id}` | Admin |
| POST | `/api/admin/support/conversations/{id}/messages` | Admin |

Berkas yang diunggah disimpan pada disk `public` dan dilayani melalui tautan `public/storage`.

Admin memeriksa bukti pembayaran dari `GET /api/admin/bookings`, lalu memperbarui pembayaran melalui `PATCH /api/admin/bookings/{id}/payment` dengan `payment_status` bernilai `terverifikasi` atau `ditolak`. Booking yang ditolak otomatis dibatalkan agar tidak lagi menggunakan slot kamar.
