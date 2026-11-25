<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('handovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['checkin', 'checkout']);
            $table->text('notes')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('signature_path')->nullable();
            $table->timestamps();
        });

        Schema::create('handover_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('handover_id')->constrained()->cascadeOnDelete();
            $table->string('item_name', 80);
            $table->enum('condition', ['baik', 'rusak']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('handover_items');
        Schema::dropIfExists('handovers');
    }
};
