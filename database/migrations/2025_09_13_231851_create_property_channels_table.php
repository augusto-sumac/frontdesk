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
        Schema::create('property_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->foreignId('channel_id')->constrained()->onDelete('cascade');
            $table->string('channel_property_id')->nullable(); // ID da propriedade no canal
            $table->string('channel_room_id')->nullable(); // ID do quarto no canal
            $table->string('channel_status')->default('inactive'); // active, inactive, suspended
            $table->string('content_status')->default('disabled'); // enabled, disabled
            $table->string('property_status_note')->nullable();
            $table->string('channel_property_url')->nullable(); // URL da propriedade no canal
            $table->json('channel_config')->nullable(); // Configurações específicas do canal
            $table->json('sync_settings')->nullable(); // Configurações de sincronização
            $table->boolean('is_active')->default(false);
            $table->boolean('auto_sync_enabled')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('last_successful_sync_at')->nullable();
            $table->text('last_sync_error')->nullable();
            $table->integer('sync_attempts')->default(0);
            $table->timestamps();
            
            $table->unique(['property_id', 'channel_id']);
            $table->index(['channel_status', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_channels');
    }
};