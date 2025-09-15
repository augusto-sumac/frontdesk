<?php

namespace App\Services;

use App\Models\Property;
use App\Models\Channel;
use App\Models\PropertyChannel;
use App\Models\Booking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChannelSyncService
{
    /**
     * Sync property data with all connected channels
     */
    public function syncPropertyWithAllChannels(Property $property): array
    {
        $results = [];
        
        foreach ($property->propertyChannels as $propertyChannel) {
            if ($propertyChannel->canSync()) {
                try {
                    $this->syncPropertyWithChannel($property, $propertyChannel->channel, $propertyChannel);
                    $results[$propertyChannel->channel->channel_id] = [
                        'success' => true,
                        'message' => 'Sincronização realizada com sucesso'
                    ];
                } catch (\Exception $e) {
                    $results[$propertyChannel->channel->channel_id] = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
            }
        }
        
        return $results;
    }

    /**
     * Sync property data with a specific channel
     */
    public function syncPropertyWithChannel(Property $property, Channel $channel, PropertyChannel $propertyChannel): void
    {
        Log::info("Iniciando sincronização da propriedade {$property->id} com canal {$channel->channel_id}");

        switch ($channel->channel_id) {
            case 'AIR298':
                $this->syncWithAirbnb($property, $propertyChannel);
                break;
            case 'BOO142':
                $this->syncWithBooking($property, $propertyChannel);
                break;
            case 'HOM143':
                $this->syncWithHomeAway($property, $propertyChannel);
                break;
            case 'EXP001':
                $this->syncWithExpedia($property, $propertyChannel);
                break;
            case 'VRB001':
                $this->syncWithVrbo($property, $propertyChannel);
                break;
            default:
                throw new \Exception("Canal {$channel->channel_id} não suportado para sincronização");
        }

        $propertyChannel->markSyncSuccess();
        Log::info("Sincronização concluída com sucesso para propriedade {$property->id} e canal {$channel->channel_id}");
    }

    /**
     * Sync property data with Airbnb
     */
    private function syncWithAirbnb(Property $property, PropertyChannel $propertyChannel): void
    {
        $config = $propertyChannel->getChannelConfig();
        
        if (!$config || !isset($config['access_token'])) {
            throw new \Exception('Token de acesso do Airbnb não configurado');
        }

        // Implementar sincronização com Airbnb API
        $this->syncPropertyData($property, $propertyChannel, [
            'url' => 'https://api.airbnb.com/v2/listings/' . $propertyChannel->channel_property_id,
            'headers' => [
                'Authorization' => 'Bearer ' . $config['access_token'],
                'Content-Type' => 'application/json',
            ],
            'data' => $this->formatPropertyDataForAirbnb($property)
        ]);
    }

    /**
     * Sync property data with Booking.com
     */
    private function syncWithBooking(Property $property, PropertyChannel $propertyChannel): void
    {
        $config = $propertyChannel->getChannelConfig();
        
        if (!$config || !isset($config['api_key'])) {
            throw new \Exception('API Key do Booking.com não configurada');
        }

        // Implementar sincronização com Booking.com API
        $this->syncPropertyData($property, $propertyChannel, [
            'url' => 'https://distribution-xml.booking.com/hotels/' . $propertyChannel->channel_property_id,
            'headers' => [
                'X-API-Key' => $config['api_key'],
                'Content-Type' => 'application/json',
            ],
            'data' => $this->formatPropertyDataForBooking($property)
        ]);
    }

    /**
     * Sync property data with HomeAway
     */
    private function syncWithHomeAway(Property $property, PropertyChannel $propertyChannel): void
    {
        $config = $propertyChannel->getChannelConfig();
        
        if (!$config || !isset($config['access_token'])) {
            throw new \Exception('Token de acesso do HomeAway não configurado');
        }

        // Implementar sincronização com HomeAway API
        $this->syncPropertyData($property, $propertyChannel, [
            'url' => 'https://api.homeaway.com/v1/listings/' . $propertyChannel->channel_property_id,
            'headers' => [
                'Authorization' => 'Bearer ' . $config['access_token'],
                'Content-Type' => 'application/json',
            ],
            'data' => $this->formatPropertyDataForHomeAway($property)
        ]);
    }

    /**
     * Sync property data with Expedia
     */
    private function syncWithExpedia(Property $property, PropertyChannel $propertyChannel): void
    {
        $config = $propertyChannel->getChannelConfig();
        
        if (!$config || !isset($config['api_key'])) {
            throw new \Exception('API Key do Expedia não configurada');
        }

        // Implementar sincronização com Expedia API
        $this->syncPropertyData($property, $propertyChannel, [
            'url' => 'https://api.expedia.com/v1/hotels/' . $propertyChannel->channel_property_id,
            'headers' => [
                'X-API-Key' => $config['api_key'],
                'Content-Type' => 'application/json',
            ],
            'data' => $this->formatPropertyDataForExpedia($property)
        ]);
    }

    /**
     * Sync property data with VRBO
     */
    private function syncWithVrbo(Property $property, PropertyChannel $propertyChannel): void
    {
        $config = $propertyChannel->getChannelConfig();
        
        if (!$config || !isset($config['access_token'])) {
            throw new \Exception('Token de acesso do VRBO não configurado');
        }

        // Implementar sincronização com VRBO API
        $this->syncPropertyData($property, $propertyChannel, [
            'url' => 'https://api.vrbo.com/v1/listings/' . $propertyChannel->channel_property_id,
            'headers' => [
                'Authorization' => 'Bearer ' . $config['access_token'],
                'Content-Type' => 'application/json',
            ],
            'data' => $this->formatPropertyDataForVrbo($property)
        ]);
    }

    /**
     * Generic method to sync property data with external API
     */
    private function syncPropertyData(Property $property, PropertyChannel $propertyChannel, array $apiConfig): void
    {
        try {
            $response = Http::withHeaders($apiConfig['headers'])
                ->put($apiConfig['url'], $apiConfig['data']);

            if (!$response->successful()) {
                throw new \Exception("Erro na API: " . $response->status() . " - " . $response->body());
            }

            // Atualizar status da propriedade no canal
            $propertyChannel->update([
                'channel_status' => 'active',
                'content_status' => 'enabled',
            ]);

        } catch (\Exception $e) {
            Log::error("Erro na sincronização: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Format property data for Airbnb API
     */
    private function formatPropertyDataForAirbnb(Property $property): array
    {
        return [
            'name' => $property->name,
            'description' => $property->description,
            'property_type' => $property->property_type,
            'room_type' => 'entire_home',
            'accommodates' => $property->max_occupancy,
            'bedrooms' => $property->bedrooms,
            'bathrooms' => $property->bathrooms,
            'address' => $property->full_address,
            'latitude' => $property->latitude,
            'longitude' => $property->longitude,
            'amenities' => $property->amenities ?? [],
            'house_rules' => $property->house_rules ?? [],
            'base_price' => $property->base_price,
            'currency' => $property->currency ?? 'BRL',
            'cleaning_fee' => $property->cleaning_fee,
            'security_deposit' => $property->security_deposit,
        ];
    }

    /**
     * Format property data for Booking.com API
     */
    private function formatPropertyDataForBooking(Property $property): array
    {
        return [
            'hotel_name' => $property->name,
            'description' => $property->description,
            'property_type' => $property->property_type,
            'max_occupancy' => $property->max_occupancy,
            'bedrooms' => $property->bedrooms,
            'bathrooms' => $property->bathrooms,
            'address' => [
                'street' => $property->address,
                'city' => $property->city,
                'state' => $property->state,
                'country' => $property->country,
                'postal_code' => $property->postal_code,
            ],
            'coordinates' => [
                'latitude' => $property->latitude,
                'longitude' => $property->longitude,
            ],
            'amenities' => $property->amenities ?? [],
            'policies' => $property->house_rules ?? [],
            'pricing' => [
                'base_price' => $property->base_price,
                'currency' => $property->currency ?? 'BRL',
                'cleaning_fee' => $property->cleaning_fee,
                'security_deposit' => $property->security_deposit,
            ],
        ];
    }

    /**
     * Format property data for HomeAway API
     */
    private function formatPropertyDataForHomeAway(Property $property): array
    {
        return [
            'title' => $property->name,
            'description' => $property->description,
            'property_type' => $property->property_type,
            'max_occupancy' => $property->max_occupancy,
            'bedrooms' => $property->bedrooms,
            'bathrooms' => $property->bathrooms,
            'address' => $property->full_address,
            'location' => [
                'latitude' => $property->latitude,
                'longitude' => $property->longitude,
            ],
            'amenities' => $property->amenities ?? [],
            'house_rules' => $property->house_rules ?? [],
            'pricing' => [
                'nightly_rate' => $property->nightly_rate ?? $property->base_price,
                'weekly_rate' => $property->weekly_rate,
                'monthly_rate' => $property->monthly_rate,
                'currency' => $property->currency ?? 'BRL',
                'cleaning_fee' => $property->cleaning_fee,
                'security_deposit' => $property->security_deposit,
            ],
        ];
    }

    /**
     * Format property data for Expedia API
     */
    private function formatPropertyDataForExpedia(Property $property): array
    {
        return [
            'hotel_name' => $property->name,
            'description' => $property->description,
            'property_type' => $property->property_type,
            'max_occupancy' => $property->max_occupancy,
            'bedrooms' => $property->bedrooms,
            'bathrooms' => $property->bathrooms,
            'address' => $property->full_address,
            'coordinates' => [
                'latitude' => $property->latitude,
                'longitude' => $property->longitude,
            ],
            'amenities' => $property->amenities ?? [],
            'policies' => $property->house_rules ?? [],
            'pricing' => [
                'base_price' => $property->base_price,
                'currency' => $property->currency ?? 'BRL',
            ],
        ];
    }

    /**
     * Format property data for VRBO API
     */
    private function formatPropertyDataForVrbo(Property $property): array
    {
        return [
            'title' => $property->name,
            'description' => $property->description,
            'property_type' => $property->property_type,
            'max_occupancy' => $property->max_occupancy,
            'bedrooms' => $property->bedrooms,
            'bathrooms' => $property->bathrooms,
            'address' => $property->full_address,
            'location' => [
                'latitude' => $property->latitude,
                'longitude' => $property->longitude,
            ],
            'amenities' => $property->amenities ?? [],
            'house_rules' => $property->house_rules ?? [],
            'pricing' => [
                'nightly_rate' => $property->nightly_rate ?? $property->base_price,
                'currency' => $property->currency ?? 'BRL',
                'cleaning_fee' => $property->cleaning_fee,
                'security_deposit' => $property->security_deposit,
            ],
        ];
    }

    /**
     * Sync bookings from all channels
     */
    public function syncBookingsFromChannels(Property $property): array
    {
        $results = [];
        
        foreach ($property->propertyChannels as $propertyChannel) {
            if ($propertyChannel->isActive()) {
                try {
                    $bookings = $this->syncBookingsFromChannel($property, $propertyChannel->channel, $propertyChannel);
                    $results[$propertyChannel->channel->channel_id] = [
                        'success' => true,
                        'bookings_count' => count($bookings),
                        'message' => count($bookings) . ' reservas sincronizadas'
                    ];
                } catch (\Exception $e) {
                    $results[$propertyChannel->channel->channel_id] = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
            }
        }
        
        return $results;
    }

    /**
     * Sync bookings from a specific channel
     */
    private function syncBookingsFromChannel(Property $property, Channel $channel, PropertyChannel $propertyChannel): array
    {
        // Implementar lógica para buscar reservas do canal
        // Por enquanto, retornar array vazio
        return [];
    }
}
