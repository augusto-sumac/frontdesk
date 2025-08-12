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
            // Remover a foreign key constraint
            $table->dropForeign(['property_id']);
            
            // Alterar o tipo da coluna de foreignId para string
            $table->string('property_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Reverter para foreign key
            $table->foreignId('property_id')->nullable()->constrained()->onDelete('cascade')->change();
        });
    }
};
