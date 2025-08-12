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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('property_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('role')->default('manager'); // owner, manager, staff
            $table->string('phone')->nullable();
            $table->string('company_name')->nullable();
            $table->boolean('is_active')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['property_id']);
            $table->dropColumn(['property_id', 'role', 'phone', 'company_name', 'is_active']);
        });
    }
};
