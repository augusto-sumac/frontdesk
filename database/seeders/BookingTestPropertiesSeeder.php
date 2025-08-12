<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Property;

class BookingTestPropertiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test properties with proper supplierPropertyId
        Property::create([
            'name' => 'Apartamento Teste 1',
            'property_id' => '7212604d-4d17-4c0f-a02b-741a115fa7c8', // NextPax UUID
            'channel_type' => 'nextpax',
            'channel_property_id' => '7212604d-4d17-4c0f-a02b-741a115fa7c8', // NextPax UUID
            'supplier_property_id' => 'prop-mb5LSNlJHYpV', // Our internal ID
            'address' => 'Rua das Flores, 123',
            'city' => 'São Paulo',
            'state' => 'SP',
            'country' => 'Brasil',
            'description' => 'Apartamento moderno no centro da cidade',
            'property_type' => 'apartment',
            'max_occupancy' => 4,
            'max_adults' => 2,
            'max_children' => 2,
            'bedrooms' => 2,
            'bathrooms' => 1,
            'base_price' => 150.00,
            'currency' => 'BRL',
            'contact_name' => 'João Silva',
            'contact_phone' => '(11) 99999-9999',
            'contact_email' => 'joao@exemplo.com',
            'check_in_from' => '14:00:00',
            'check_in_until' => '22:00:00',
            'check_out_from' => '08:00:00',
            'check_out_until' => '11:00:00',
            'amenities' => ['wifi', 'air_conditioning', 'kitchen'],
            'house_rules' => ['no_smoking', 'no_pets'],
            'status' => 'active',
            'is_active' => true,
        ]);

        Property::create([
            'name' => 'Casa de Campo',
            'property_id' => 'ca48b093-7e52-c7c9-66ac-a010da93241a', // NextPax UUID
            'channel_type' => 'nextpax',
            'channel_property_id' => 'ca48b093-7e52-c7c9-66ac-a010da93241a', // NextPax UUID
            'supplier_property_id' => 'prop-ABC123DEF456', // Our internal ID
            'address' => 'Estrada Rural, 456',
            'city' => 'Campinas',
            'state' => 'SP',
            'country' => 'Brasil',
            'description' => 'Casa de campo com vista para as montanhas',
            'property_type' => 'house',
            'max_occupancy' => 6,
            'max_adults' => 4,
            'max_children' => 2,
            'bedrooms' => 3,
            'bathrooms' => 2,
            'base_price' => 250.00,
            'currency' => 'BRL',
            'contact_name' => 'Maria Santos',
            'contact_phone' => '(19) 88888-8888',
            'contact_email' => 'maria@exemplo.com',
            'check_in_from' => '15:00:00',
            'check_in_until' => '23:00:00',
            'check_out_from' => '09:00:00',
            'check_out_until' => '12:00:00',
            'amenities' => ['wifi', 'parking', 'garden', 'bbq'],
            'house_rules' => ['no_smoking'],
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->command->info('Test properties created successfully!');
    }
}
