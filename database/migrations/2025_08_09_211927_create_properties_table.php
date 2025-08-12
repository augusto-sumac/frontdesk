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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('property_id')->unique(); // Código da propriedade do NextPax
            $table->string('channel_type')->default('nextpax'); // booking, airbnb, expedia, nextpax, etc.
            $table->string('channel_property_id')->nullable(); // ID específico do canal
            $table->string('address')->nullable(); // Será preenchido via API
            $table->string('city')->nullable(); // Será preenchido via API
            $table->string('state')->nullable(); // Será preenchido via API
            $table->string('country')->nullable(); // Será preenchido via API
            $table->string('phone')->nullable(); // Será preenchido via API
            $table->string('email')->nullable(); // Será preenchido via API
            $table->integer('total_rooms')->default(0); // Será preenchido via API
            $table->boolean('is_active')->default(true);
            $table->json('channel_config')->nullable(); // Configurações específicas do canal
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
