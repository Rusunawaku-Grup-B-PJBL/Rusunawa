# Hasil Pengujian API Rusunawa

## Informasi pengujian

- Collection: `Rusunawa API - 10 Basic Tests`
- Base URL: `http://127.0.0.1:8000`
- Metode: Postman Collection Runner
- Status dokumen: **Template — belum dijalankan**

## Prasyarat

1. Jalankan server Laravel dengan `php artisan serve`.
2. Pastikan database sudah dimigrasi dan di-seed.
3. Impor `postman/Rusunawa_API_Collection.json` ke Postman.
4. Jalankan seluruh request secara berurutan menggunakan Collection Runner.
5. Pastikan variabel `base_url` sesuai alamat server lokal.

Akun pengujian berasal dari `UserSeeder.php`:
- Admin: `admin@ubbg.ac.id`
- Penghuni: `penghuni@ubbg.ac.id`
- Password kedua akun: `password`

Gunakan akun tersebut hanya pada lingkungan pengembangan/lokal.

## Daftar skenario dan hasil aktual

| No. | Skenario | Ekspektasi | Hasil aktual | Status |
|---:|---|---:|---|---|
| 1 | Login admin | 200 | Belum dijalankan | Pending |
| 2 | Login penghuni | 200 | Belum dijalankan | Pending |
| 3 | Login dengan password salah | 422 | Belum dijalankan | Pending |
| 4 | Profil dengan token penghuni | 200 | Belum dijalankan | Pending |
| 5 | Profil tanpa token | 401 | Belum dijalankan | Pending |
| 6 | Daftar kamar dengan token | 200 | Belum dijalankan | Pending |
| 7 | Detail kamar | 200 | Belum dijalankan | Pending |
| 8 | Booking milik penghuni | 200 | Belum dijalankan | Pending |
| 9 | Admin melihat semua booking | 200 | Belum dijalankan | Pending |
| 10 | Penghuni mengakses endpoint admin | 403 | Belum dijalankan | Pending |

> Catatan: Kolom hasil aktual dan status harus diperbarui berdasarkan hasil Collection Runner. Status ekspektasi bukan bukti bahwa pengujian sudah berhasil.

## Ringkasan

- Total skenario: 10
- Passed: belum diketahui
- Failed: belum diketahui
- Belum dijalankan: 10

## Bukti pengujian

Tambahkan screenshot hasil Collection Runner setelah pengujian benar-benar dijalankan. Screenshot harus memperlihatkan jumlah Passed/Failed dan nama collection.

## Catatan teknis

- Login gagal mengembalikan HTTP 422 sesuai implementasi `AuthController`.
- Endpoint API selain register/login dilindungi Sanctum sesuai daftar route yang diperiksa.
- Endpoint `/api/admin/bookings` memerlukan token admin; pengguna penghuni diharapkan menerima HTTP 403.
- ID kamar disimpan otomatis dari respons `GET /api/rooms` untuk digunakan pada request detail.
