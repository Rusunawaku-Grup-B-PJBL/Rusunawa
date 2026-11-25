<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /** Registrasi awal: membuat akun penghuni dan langsung mengembalikan token. */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Field diambil eksplisit; role selalu penghuni dan tidak bisa diisi dari request.
        $user = User::create([
            'name' => $data['name'],
            'nim' => $data['nim'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => $data['password'],
            'role' => User::ROLE_PENGHUNI,
        ]);

        return response()->json([
            'message' => 'Pendaftaran berhasil.',
            'data' => [
                'user' => new UserResource($user),
                'token' => $user->createToken('mobile')->plainTextToken,
            ],
        ], 201);
    }

    /** Dipakai hanya jika sesi hilang (misalnya aplikasi diinstal ulang). */
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            // Sengaja 422 (bukan 401) agar klien tidak menganggapnya sesi kedaluwarsa.
            return response()->json([
                'message' => 'Email atau kata sandi salah.',
                'errors' => ['email' => ['Email atau kata sandi salah.']],
            ], 422);
        }

        return response()->json([
            'message' => 'Berhasil masuk.',
            'data' => [
                'user' => new UserResource($user),
                'token' => $user->createToken('mobile')->plainTextToken,
            ],
        ]);
    }

    /** Dipanggil splash untuk memvalidasi token tersimpan. */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}