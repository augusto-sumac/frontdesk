<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Support\Facades\Storage;

class PropertySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample properties
        $properties = [
            [
                'name' => 'Apartamento Luxo Centro',
                'property_id' => 'prop-' . uniqid(),
                'channel_type' => 'nextpax',
                'channel_property_id' => null,
                'address' => 'Rua das Flores, 123',
                'city' => 'São Paulo',
                'state' => 'SP',
                'country' => 'Brasil',
                'postal_code' => '01234-567',
                'latitude' => -23.5505,
                'longitude' => -46.6333,
                'description' => 'Apartamento de luxo localizado no coração de São Paulo, com vista para o centro da cidade. Ideal para executivos e turistas que buscam conforto e localização privilegiada.',
                'property_type' => 'apartment',
                'max_occupancy' => 4,
                'max_adults' => 3,
                'max_children' => 1,
                'bedrooms' => 2,
                'bathrooms' => 2,
                'base_price' => 350.00,
                'currency' => 'BRL',
                'amenities' => ['Wi-Fi', 'Ar Condicionado', 'TV', 'Cozinha', 'Frigobar', 'Estacionamento', 'Elevador', 'Terraço'],
                'house_rules' => ['Não é permitido fumar', 'Check-in pontual', 'Respeitar o silêncio', 'Manter limpeza'],
                'contact_name' => 'João Silva',
                'contact_phone' => '(11) 99999-9999',
                'contact_email' => 'joao@apartamentoluxo.com',
                'check_in_from' => '14:00:00',
                'check_in_until' => '22:00:00',
                'check_out_from' => '08:00:00',
                'check_out_until' => '11:00:00',
                'status' => 'active',
            ],
            [
                'name' => 'Casa de Campo Serenidade',
                'property_id' => 'prop-' . uniqid(),
                'channel_type' => 'nextpax',
                'channel_property_id' => null,
                'address' => 'Estrada do Sítio, 456',
                'city' => 'Campinas',
                'state' => 'SP',
                'country' => 'Brasil',
                'postal_code' => '13000-000',
                'latitude' => -22.9064,
                'longitude' => -47.0616,
                'description' => 'Casa de campo com vista para as montanhas, ideal para famílias que buscam tranquilidade e contato com a natureza. Possui ampla área de lazer e jardim.',
                'property_type' => 'house',
                'max_occupancy' => 6,
                'max_adults' => 4,
                'max_children' => 2,
                'bedrooms' => 3,
                'bathrooms' => 2,
                'base_price' => 280.00,
                'currency' => 'BRL',
                'amenities' => ['Wi-Fi', 'TV', 'Cozinha', 'Churrasqueira', 'Jardim', 'Piscina', 'Estacionamento'],
                'house_rules' => ['Não é permitido animais', 'Respeitar vizinhos', 'Manter limpeza'],
                'contact_name' => 'Maria Santos',
                'contact_phone' => '(19) 88888-8888',
                'contact_email' => 'maria@casacampo.com',
                'check_in_from' => '15:00:00',
                'check_in_until' => '20:00:00',
                'check_out_from' => '08:00:00',
                'check_out_until' => '10:00:00',
                'status' => 'active',
            ],
            [
                'name' => 'Loft Moderno Vila Madalena',
                'property_id' => 'prop-' . uniqid(),
                'channel_type' => 'nextpax',
                'channel_property_id' => null,
                'address' => 'Rua Harmonia, 789',
                'city' => 'São Paulo',
                'state' => 'SP',
                'country' => 'Brasil',
                'postal_code' => '05435-000',
                'latitude' => -23.5674,
                'longitude' => -46.6912,
                'description' => 'Loft moderno e elegante localizado na vibrante Vila Madalena. Perfeito para casais ou profissionais que apreciam design contemporâneo e localização estratégica.',
                'property_type' => 'loft',
                'max_occupancy' => 2,
                'max_adults' => 2,
                'max_children' => 0,
                'bedrooms' => 1,
                'bathrooms' => 1,
                'base_price' => 420.00,
                'currency' => 'BRL',
                'amenities' => ['Wi-Fi', 'Ar Condicionado', 'TV', 'Cozinha', 'Frigobar', 'Academia', 'Piscina', 'Spa'],
                'house_rules' => ['Não é permitido festas', 'Check-in pontual', 'Economizar energia'],
                'contact_name' => 'Pedro Costa',
                'contact_phone' => '(11) 77777-7777',
                'contact_email' => 'pedro@loftmoderno.com',
                'check_in_from' => '14:00:00',
                'check_in_until' => '22:00:00',
                'check_out_from' => '08:00:00',
                'check_out_until' => '11:00:00',
                'status' => 'pending',
            ],
        ];

        foreach ($properties as $propertyData) {
            $property = Property::create($propertyData);
            
            // Create sample images for each property
            $this->createSampleImages($property);
        }
    }

    private function createSampleImages(Property $property): void
    {
        // Create main image
        PropertyImage::create([
            'property_id' => $property->id,
            'image_path' => 'properties/' . $property->id . '/main/sample_main.jpg',
            'image_name' => 'sample_main.jpg',
            'alt_text' => $property->name . ' - Imagem Principal',
            'type' => 'main',
            'sort_order' => 1,
            'is_active' => true
        ]);

        // Create gallery images
        for ($i = 1; $i <= 3; $i++) {
            PropertyImage::create([
                'property_id' => $property->id,
                'image_path' => 'properties/' . $property->id . '/gallery/sample_gallery_' . $i . '.jpg',
                'image_name' => 'sample_gallery_' . $i . '.jpg',
                'alt_text' => $property->name . ' - Imagem ' . $i,
                'type' => 'gallery',
                'sort_order' => $i + 1,
                'is_active' => true
            ]);
        }
    }
} 