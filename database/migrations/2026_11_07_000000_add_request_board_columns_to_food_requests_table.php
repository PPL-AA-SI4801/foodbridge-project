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
        Schema::table('food_requests', function (Blueprint $table) {
            $table->string('urgency_level')->default('mendesak')->after('jumlah_porsi');
            $table->string('metode_penerimaan')->nullable()->after('urgency_level');
            $table->foreignId('category_id')->nullable()->after('recipient_id')->constrained('food_categories')->nullOnDelete();
            $table->foreignId('donor_id')->nullable()->after('status')->constrained('donor_profiles')->nullOnDelete();
            $table->foreignId('claim_id')->nullable()->after('donor_id')->constrained('claims')->nullOnDelete();
            $table->index(['status', 'batas_waktu']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('food_requests', function (Blueprint $table) {
            $table->dropIndex(['status', 'batas_waktu']);
            $table->dropConstrainedForeignId('claim_id');
            $table->dropConstrainedForeignId('donor_id');
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn(['urgency_level', 'metode_penerimaan']);
        });
    }
};
