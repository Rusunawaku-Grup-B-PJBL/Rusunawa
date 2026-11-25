<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('damage_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->enum('category', ['air', 'ac', 'lampu']);
            $table->text('description');
            $table->string('photo_path')->nullable();
            $table->enum('status', ['dilaporkan', 'diproses', 'selesai'])->default('dilaporkan');
            $table->string('technician_name', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('damage_report_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('damage_report_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['dilaporkan', 'diproses', 'selesai']);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('damage_report_logs');
        Schema::dropIfExists('damage_reports');
    }
};
