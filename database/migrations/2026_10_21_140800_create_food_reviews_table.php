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
        Schema::create('food_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->unique()->constrained('claims')->cascadeOnDelete();
            $table->integer('rating')->nullable();
            $table->string('komentar');
            $table->timestamp('created_at');
            $table->string('jenis_feedback');
            $table->string('foto_bukti')->nullable();
            $table->string('status_penanganan')->nullable();
            $table->string('tindak_lanjut')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_reviews');
    }
};
