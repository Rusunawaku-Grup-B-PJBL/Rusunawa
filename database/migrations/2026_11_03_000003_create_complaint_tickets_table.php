<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->enum('category', ['fasilitas', 'kebersihan', 'pelayanan', 'lainnya']);
            $table->text('description');
            $table->enum('status', ['dikirim', 'diproses', 'selesai'])->default('dikirim');
            $table->timestamps();
        });

        Schema::create('complaint_ticket_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_ticket_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['dikirim', 'diproses', 'selesai']);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_ticket_logs');
        Schema::dropIfExists('complaint_tickets');
    }
};
