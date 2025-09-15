<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Channel;

class ChannelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $channels = [
            [
                'channel_id' => 'AIR298',
                'name' => 'Airbnb',
                'slug' => 'airbnb',
                'description' => 'Airbnb - Plataforma de hospedagem compartilhada',
                'logo_url' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/airbnb.svg',
                'website_url' => 'https://www.airbnb.com',
                'api_base_url' => 'https://api.airbnb.com',
                'api_config' => [
                    'version' => 'v1',
                    'auth_type' => 'oauth2',
                    'rate_limit' => 1000,
                    'rate_limit_window' => 3600,
                ],
                'supported_features' => [
                    'listings',
                    'bookings',
                    'pricing',
                    'availability',
                    'messages',
                    'reviews',
                    'photos',
                    'calendar'
                ],
                'is_active' => true,
                'requires_oauth' => true,
                'oauth_url' => 'https://www.airbnb.com/oauth/authorize',
                'oauth_scopes' => [
                    'read',
                    'write',
                    'read_listings',
                    'write_listings',
                    'read_bookings',
                    'write_bookings'
                ],
                'sync_interval_minutes' => 30,
                'auto_sync_enabled' => true,
            ],
            [
                'channel_id' => 'BOO142',
                'name' => 'Booking.com',
                'slug' => 'booking-com',
                'description' => 'Booking.com - Plataforma de reservas de hotéis e acomodações',
                'logo_url' => 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/bookingdotcom.svg',
                'website_url' => 'https://www.booking.com',
                'api_base_url' => 'https://distribution-xml.booking.com',
                'api_config' => [
                    'version' => 'v1',
                    'auth_type' => 'api_key',
                    'rate_limit' => 500,
                    'rate_limit_window' => 3600,
                ],
                'supported_features' => [
                    'hotels',
                    'bookings',
                    'pricing',
                    'availability',
                    'rooms',
                    'photos',
                    'reviews',
                    'calendar'
                ],
                'is_active' => true,
                'requires_oauth' => false,
                'oauth_url' => null,
                'oauth_scopes' => null,
                'sync_interval_minutes' => 60,
                'auto_sync_enabled' => true,
            ],
            [
                'channel_id' => 'DIRECT',
                'name' => 'Reserva Direta',
                'slug' => 'direct',
                'description' => 'Reservas diretas através do site próprio',
                'logo_url' => null,
                'website_url' => null,
                'api_base_url' => null,
                'api_config' => null,
                'supported_features' => [
                    'bookings',
                    'pricing',
                    'availability',
                    'calendar'
                ],
                'is_active' => true,
                'requires_oauth' => false,
                'oauth_url' => null,
                'oauth_scopes' => null,
                'sync_interval_minutes' => 0,
                'auto_sync_enabled' => false,
            ],
        ];

        foreach ($channels as $channelData) {
            Channel::updateOrCreate(
                ['channel_id' => $channelData['channel_id']],
                $channelData
            );
        }

        $this->command->info('Canais criados com sucesso!');
    }
}