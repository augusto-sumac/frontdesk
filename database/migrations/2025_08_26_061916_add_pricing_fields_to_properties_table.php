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
        Schema::table('properties', function (Blueprint $table) {
            $table->decimal('nightly_rate', 10, 2)->nullable()->after('base_price');
            $table->decimal('weekly_rate', 10, 2)->nullable()->after('nightly_rate');
            $table->decimal('monthly_rate', 10, 2)->nullable()->after('weekly_rate');
            $table->decimal('cleaning_fee', 10, 2)->nullable()->after('monthly_rate');
            $table->decimal('security_deposit', 10, 2)->nullable()->after('cleaning_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn([
                'nightly_rate',
                'weekly_rate', 
                'monthly_rate',
                'cleaning_fee',
                'security_deposit'
            ]);
        });
    }
};
