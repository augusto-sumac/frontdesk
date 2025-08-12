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
        Schema::table('bookings', function (Blueprint $table) {
            // Add sync-related fields
            $table->enum('sync_status', ['pending', 'syncing', 'synced', 'failed'])->default('pending')->after('status');
            $table->text('sync_error')->nullable()->after('sync_status');
            $table->timestamp('last_sync_attempt')->nullable()->after('sync_error');
            $table->timestamp('synced_at')->nullable()->after('last_sync_attempt');
            
            // Add new status for pending sync
            $table->enum('status', [
                'pending', 'confirmed', 'cancelled', 'failed', 'request', 
                'request-accepted', 'request-rejected', 'request-booked', 'pending_sync'
            ])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['sync_status', 'sync_error', 'last_sync_attempt', 'synced_at']);
            
            // Revert status enum
            $table->enum('status', [
                'pending', 'confirmed', 'cancelled', 'failed', 'request', 
                'request-accepted', 'request-rejected', 'request-booked'
            ])->change();
        });
    }
};
