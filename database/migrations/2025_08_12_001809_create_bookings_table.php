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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            
            // NextPax API Response Data
            $table->string('nextpax_booking_id')->unique()->nullable(); // UUID from NextPax
            $table->string('booking_number')->nullable(); // Supplier booking number
            $table->string('channel_partner_reference')->nullable(); // Channel reference
            $table->string('channel_id')->nullable(); // NextPax channel ID
            
            // Property Information
            $table->string('property_id')->nullable(); // NextPax property UUID
            $table->string('supplier_property_id')->nullable(); // Our internal property ID
            $table->string('property_manager_code')->nullable(); // Property manager code
            
            // Guest Information
            $table->string('guest_first_name')->nullable();
            $table->string('guest_surname')->nullable();
            $table->string('guest_email')->nullable();
            $table->string('guest_phone')->nullable();
            $table->string('guest_country_code')->nullable();
            $table->string('guest_language')->nullable();
            
            // Booking Details
            $table->date('check_in_date')->nullable();
            $table->date('check_out_date')->nullable();
            $table->integer('adults')->default(1);
            $table->integer('children')->default(0);
            $table->integer('babies')->default(0);
            $table->integer('pets')->default(0);
            
            // Financial Information
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->string('currency', 3)->default('BRL');
            $table->string('payment_type')->nullable();
            $table->string('rate_plan_id')->nullable();
            
            // Status and Metadata
            $table->string('status')->default('pending'); // pending, confirmed, cancelled, etc.
            $table->text('remarks')->nullable();
            $table->json('api_response')->nullable(); // Full API response for debugging
            $table->json('api_payload')->nullable(); // Full API payload sent
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index(['property_id', 'supplier_property_id']);
            $table->index(['property_manager_code']);
            $table->index(['status']);
            $table->index(['check_in_date', 'check_out_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
