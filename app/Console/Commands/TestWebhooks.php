<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Property;
use App\Models\PropertyChannel;
use Illuminate\Support\Facades\Http;

class TestWebhooks extends Command
{
    protected $signature = 'webhooks:test 
                            {--property=4 : ID da propriedade}
                            {--channel= : Canal específico (airbnb, booking, nextpax)}
                            {--all : Testar todos os webhooks}';

    protected $description = 'Testa os webhooks de recebimento de reservas';

    public function handle()
    {
        $propertyId = $this->option('property');
        $channel = $this->option('channel');
        $all = $this->option('all');

        $this->info('🔗 Testando webhooks de reservas...');
        $this->newLine();

        $property = Property::find($propertyId);
        if (!$property) {
            $this->error("Propriedade {$propertyId} não encontrada.");
            return;
        }

        $this->line("🏠 Propriedade: {$property->name}");
        $this->line("   ID NextPax: {$property->channel_property_id}");
        $this->line("   Supplier ID: {$property->supplier_property_id}");
        $this->newLine();

        if ($all) {
            $this->testAllWebhooks($property);
        } elseif ($channel) {
            $this->testSpecificWebhook($property, $channel);
        } else {
            $this->showHelp();
        }
    }

    private function testAllWebhooks(Property $property): void
    {
        $this->info('🧪 Testando todos os webhooks...');
        $this->newLine();

        $webhooks = [
            'airbnb' => 'Airbnb',
            'booking' => 'Booking.com',
            'nextpax' => 'NextPax'
        ];

        foreach ($webhooks as $endpoint => $name) {
            $this->testWebhook($property, $endpoint, $name);
            $this->newLine();
        }
    }

    private function testSpecificWebhook(Property $property, string $channel): void
    {
        $webhooks = [
            'airbnb' => 'Airbnb',
            'booking' => 'Booking.com',
            'nextpax' => 'NextPax'
        ];

        if (!isset($webhooks[$channel])) {
            $this->error("Canal '{$channel}' não reconhecido.");
            $this->line("Canais disponíveis: " . implode(', ', array_keys($webhooks)));
            return;
        }

        $this->testWebhook($property, $channel, $webhooks[$channel]);
    }

    private function testWebhook(Property $property, string $endpoint, string $name): void
    {
        $this->line("🔗 Testando webhook {$name}...");

        $webhookUrl = url("/webhooks/{$endpoint}");
        $this->line("   URL: {$webhookUrl}");

        $payload = $this->generateTestPayload($property, $endpoint);
        $this->line("   📤 Payload:");
        $this->line("   " . json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        try {
            $response = Http::post($webhookUrl, $payload);

            $this->line("   📡 Status HTTP: {$response->status()}");
            $this->line("   📥 Resposta:");
            $this->line("   " . json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            if ($response->successful()) {
                $this->info("   ✅ Webhook {$name} funcionando corretamente!");
            } else {
                $this->error("   ❌ Erro no webhook {$name}");
            }

        } catch (\Exception $e) {
            $this->error("   ❌ Erro na requisição: " . $e->getMessage());
        }
    }

    private function generateTestPayload(Property $property, string $endpoint): array
    {
        $baseData = [
            'guest' => [
                'first_name' => 'João',
                'last_name' => 'Silva',
                'email' => 'joao@teste.com',
                'phone' => '+5511999999999',
                'country_code' => 'BR',
                'language' => 'pt'
            ],
            'currency' => 'BRL',
            'status' => 'confirmed'
        ];

        switch ($endpoint) {
            case 'airbnb':
                return array_merge($baseData, [
                    'listing_id' => $this->getChannelPropertyId($property, 'AIR298'),
                    'reservation_id' => 'TEST-AIR-' . time(),
                    'start_date' => date('Y-m-d', strtotime('+7 days')),
                    'end_date' => date('Y-m-d', strtotime('+9 days')),
                    'total_paid' => 250.00,
                    'guests' => 2,
                    'message' => 'Reserva de teste via webhook'
                ]);

            case 'booking':
                return array_merge($baseData, [
                    'hotel_id' => $this->getChannelPropertyId($property, 'BOO142'),
                    'reservation_id' => 'TEST-BOO-' . time(),
                    'checkin' => date('Y-m-d', strtotime('+7 days')),
                    'checkout' => date('Y-m-d', strtotime('+9 days')),
                    'total_amount' => 300.00,
                    'adults' => 2,
                    'children' => 0,
                    'special_requests' => 'Reserva de teste via webhook'
                ]);

            case 'nextpax':
                return array_merge($baseData, [
                    'propertyId' => $property->channel_property_id,
                    'bookingNumber' => 'TEST-NP-' . time(),
                    'channelId' => 'DIRECT',
                    'checkIn' => date('Y-m-d', strtotime('+7 days')),
                    'checkOut' => date('Y-m-d', strtotime('+9 days')),
                    'totalAmount' => 200.00,
                    'adults' => 2,
                    'children' => 0,
                    'babies' => 0,
                    'pets' => 0,
                    'remarks' => 'Reserva de teste via webhook'
                ]);

            default:
                return $baseData;
        }
    }

    private function getChannelPropertyId(Property $property, string $channelId): string
    {
        $propertyChannel = $property->getChannelConnection($channelId);
        
        if ($propertyChannel && $propertyChannel->channel_property_id) {
            return $propertyChannel->channel_property_id;
        }

        // Retornar ID padrão para teste
        switch ($channelId) {
            case 'AIR298':
                return 'airbnb-123456';
            case 'BOO142':
                return 'booking-789012';
            default:
                return 'test-property-id';
        }
    }

    private function showHelp(): void
    {
        $this->line('Comandos disponíveis:');
        $this->line('');
        $this->line('  Testar todos os webhooks:');
        $this->line('    php artisan webhooks:test --all');
        $this->line('');
        $this->line('  Testar webhook específico:');
        $this->line('    php artisan webhooks:test --channel=airbnb');
        $this->line('    php artisan webhooks:test --channel=booking');
        $this->line('    php artisan webhooks:test --channel=nextpax');
        $this->line('');
        $this->line('  Testar com propriedade específica:');
        $this->line('    php artisan webhooks:test --property=4 --channel=airbnb');
    }
}