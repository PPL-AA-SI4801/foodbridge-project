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
        Schema::create('food_posts', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('deskripsi');
            $table->string('foto');
            $table->integer('jumlah_porsi');
            $table->integer('sisa_porsi');
            $table->string('alamat');
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->timestamp('batas_konsumsi');
            $table->string('status');
            $table->foreignId('donor_id')->constrained('donor_profiles')->restrictOnDelete();
            $table->foreignId('category_id')->constrained('food_categories')->restrictOnDelete();
            $table->index(['status', 'batas_konsumsi']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_posts');
    }
};
