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
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->string('channel_id')->unique(); // AIR298, BOO142, etc.
            $table->string('name'); // Airbnb, Booking.com, etc.
            $table->string('slug')->unique(); // airbnb, booking-com, etc.
            $table->text('description')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('website_url')->nullable();
            $table->string('api_base_url')->nullable();
            $table->json('api_config')->nullable(); // Configurações da API
            $table->json('supported_features')->nullable(); // Recursos suportados
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_oauth')->default(false);
            $table->string('oauth_url')->nullable();
            $table->json('oauth_scopes')->nullable();
            $table->integer('sync_interval_minutes')->default(60); // Intervalo de sincronização
            $table->boolean('auto_sync_enabled')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channels');
    }
};