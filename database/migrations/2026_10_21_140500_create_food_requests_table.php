<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('food_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipient_id')->constrained('recipient_profiles')->restrictOnDelete();
            $table->string('judul');
            $table->string('deskripsi')->nullable();
            $table->integer('jumlah_porsi');
            $table->timestamp('batas_waktu');
            $table->string('status');
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_requests');
    }
};
