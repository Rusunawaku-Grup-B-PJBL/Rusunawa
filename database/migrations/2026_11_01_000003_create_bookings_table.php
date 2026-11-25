<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->unsignedTinyInteger('duration_months');
            $table->date('end_date');
            $table->unsignedTinyInteger('occupants')->default(1);
            $table->string('payment_proof_path')->nullable();
            $table->enum('payment_status', ['menunggu', 'terverifikasi', 'ditolak'])->default('menunggu');
            $table->enum('status', ['aktif', 'selesai', 'dibatalkan'])->default('aktif');
            $table->timestamps();

            $table->index(['room_id', 'status', 'start_date', 'end_date'], 'bookings_slot_index');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
