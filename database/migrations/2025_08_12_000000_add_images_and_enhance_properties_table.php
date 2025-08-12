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
            // Image fields
            $table->string('main_image')->nullable();
            $table->json('gallery_images')->nullable();
            
            // Enhanced property details
            $table->text('description')->nullable();
            $table->string('property_type')->default('apartment'); // apartment, house, hotel, etc.
            $table->integer('max_occupancy')->default(2);
            $table->integer('max_adults')->default(2);
            $table->integer('max_children')->default(0);
            $table->integer('bedrooms')->default(1);
            $table->integer('bathrooms')->default(1);
            $table->string('postal_code')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Amenities and features
            $table->json('amenities')->nullable();
            $table->json('house_rules')->nullable();
            
            // Pricing and availability
            $table->decimal('base_price', 10, 2)->nullable();
            $table->string('currency')->default('BRL');
            $table->json('pricing_rules')->nullable();
            
            // Contact information
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            
            // Check-in/out times
            $table->time('check_in_from')->default('14:00:00');
            $table->time('check_in_until')->default('22:00:00');
            $table->time('check_out_from')->default('08:00:00');
            $table->time('check_out_until')->default('11:00:00');
            
            // Status and verification
            $table->enum('status', ['draft', 'pending', 'active', 'inactive', 'suspended'])->default('draft');
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn([
                'main_image',
                'gallery_images',
                'description',
                'property_type',
                'max_occupancy',
                'max_adults',
                'max_children',
                'bedrooms',
                'bathrooms',
                'postal_code',
                'latitude',
                'longitude',
                'amenities',
                'house_rules',
                'base_price',
                'currency',
                'pricing_rules',
                'contact_name',
                'contact_phone',
                'contact_email',
                'check_in_from',
                'check_in_until',
                'check_out_from',
                'check_out_until',
                'status',
                'verified_at',
                'verification_notes'
            ]);
        });
    }
}; 