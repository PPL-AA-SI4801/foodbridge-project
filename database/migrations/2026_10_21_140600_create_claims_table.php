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
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->integer('jumlah_porsi');
            $table->string('status');
            $table->string('metode_pengambilan')->nullable();
            $table->string('kode_verifikasi')->nullable();
            $table->date('tanggal_klaim');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('post_id')->constrained('food_posts')->restrictOnDelete();
            $table->foreignId('recipient_id')->constrained('recipient_profiles')->restrictOnDelete();
            $table->index(['recipient_id', 'tanggal_klaim']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
