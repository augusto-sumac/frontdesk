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
            // Drop the unique constraint first
            $table->dropUnique(['property_id']);
            
            // Change property_id to allow NULL
            $table->string('property_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            // Change back to not nullable
            $table->string('property_id')->nullable(false)->change();
            
            // Add back unique constraint
            $table->unique('property_id');
        });
    }
};
