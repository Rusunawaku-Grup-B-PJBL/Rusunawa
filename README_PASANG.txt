CARA PASANG (Tahap 1 - rusunawa_api)

1. Ekstrak ZIP ini LANGSUNG ke folder C:\xampp\htdocs\rusunawa_api
   (pilih "Replace the files" bila ditanya).
2. Pastikan file hasil "make:" lain sudah diisi: AuthController, RegisterRequest,
   LoginRequest, ApiFormRequest, UserResource, dan routes/api.php.
3. Jalankan dari folder proyek:
     php artisan optimize:clear
     php artisan migrate:fresh --seed
     php artisan migrate:status
