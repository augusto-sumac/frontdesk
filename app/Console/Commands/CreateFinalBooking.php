<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class CreateFinalBooking extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:final-booking {property_id? : ID da propriedade local}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cria reserva final usando canal válido e formato correto da API NextPax';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🎯 Criando reserva final com canal válido...');
        $this->newLine();

        $propertyId = $this->argument('property_id');

        if ($propertyId) {
            $properties = Property::where('id', $propertyId)
                ->orWhere('channel_property_id', $propertyId)
                ->get();
        } else {
            $properties = Property::where('channel_type', 'nextpax')
                ->whereNotNull('channel_property_id')
                ->whereNotNull('supplier_property_id')
                ->limit(1)
                ->get();
        }

        if ($properties->isEmpty()) {
            $this->error('❌ Nenhuma propriedade encontrada');
            return;
        }

        $property = $properties->first();
        $user = User::where('property_manager_code', $property->property_manager_code)->first();

        if (!$user) {
            $this->error('❌ Usuário não encontrado para esta propriedade');
            return;
        }

        $this->line("🏠 Propriedade: {$property->name}");
        $this->line("   NextPax ID: {$property->channel_property_id}");
        $this->line("   Supplier ID: {$property->supplier_property_id}");
        $this->line("   Property Manager: {$user->property_manager_code}");
        $this->newLine();

        // Testar diferentes canais válidos
        $channels = [
            'DIRECT' => 'Direct Booking',
            'MANUAL' => 'Manual Booking',
            'HOM143' => 'HomeAway',
            'BOO142' => 'Booking.com'
        ];

        foreach ($channels as $channelId => $channelName) {
            $this->info("🔄 Testando canal: {$channelId} ({$channelName})");
            $success = $this->createBooking($property, $user, $channelId, $channelName);
            
            if ($success) {
                $this->info("✅ Reserva criada com sucesso usando canal {$channelId}!");
                break;
            }
            
            $this->newLine();
        }

        $this->newLine();
        $this->info('✅ Teste de criação de reserva final concluído!');
    }

    private function createBooking(Property $property, User $user, string $channelId, string $channelName): bool
    {
        $baseUrl = config('services.nextpax.bookings_api_base', 'https://pci.sandbox.nextpax.com/supply');
        $apiToken = config('services.nextpax.token');
        
        // Formato correto baseado na documentação YAML
        $bookingData = [
            'query' => 'propertyManagerBooking',
            'payload' => [
                'bookingNumber' => 'TEST-' . time() . '-' . substr($channelId, 0, 3),
                'propertyManager' => $user->property_manager_code,
                'channelPartnerReference' => 'CHANNEL-' . time() . '-' . $channelId,
                'supplierPropertyId' => $property->supplier_property_id,
                'channelId' => $channelId,
                'rateplanId' => 1, // Assumindo que existe
                'remarks' => "Reserva de teste criada via API usando canal {$channelName}",
                'period' => [
                    'arrivalDate' => date('Y-m-d', strtotime('+7 days')),
                    'departureDate' => date('Y-m-d', strtotime('+9 days'))
                ],
                'occupancy' => [
                    'adults' => 2,
                    'children' => 0,
                    'babies' => 0,
                    'pets' => 0
                ],
                'stayPrice' => [
                    'amount' => 200.00,
                    'currency' => 'BRL'
                ],
                'mainBooker' => [
                    'surname' => 'Silva',
                    'letters' => 'J',
                    'titleCode' => 'male',
                    'firstName' => 'João',
                    'countryCode' => 'BR',
                    'language' => 'pt',
                    'zipCode' => '01234-567',
                    'houseNumber' => '123',
                    'street' => 'Rua das Flores',
                    'place' => 'São Paulo',
                    'phoneNumber' => '+5511999999999',
                    'email' => 'joao.silva@teste.com',
                    'dateOfBirth' => '1980-01-01'
                ],
                'payment' => [
                    'type' => 'default'
                ]
            ]
        ];

        $this->line("   📤 Enviando dados para canal {$channelId}...");

        try {
            $response = Http::withHeaders([
                'X-Api-Token' => $apiToken,
                'Content-Type' => 'application/json',
            ])->post($baseUrl . '/bookings', $bookingData);

            $this->line("   📡 Status HTTP: {$response->status()}");

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['data']['id'])) {
                    $this->info('   ✅ Reserva criada com sucesso!');
                    $this->line("   NextPax Booking ID: {$data['data']['id']}");
                    $this->line("   Estado: {$data['data']['state']}");
                    $this->line("   Sucesso: " . ($data['data']['success'] ? 'Sim' : 'Não'));
                    $this->line("   Número da Reserva: {$data['data']['bookingNumber']}");
                    return true;
                } else {
                    $this->warn('   ⚠️  Resposta inesperada:');
                    $this->line('   ' . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
            } else {
                $this->error('   ❌ Erro HTTP: ' . $response->status());
                $errorBody = $response->body();
                $this->line("   {$errorBody}");
                
                // Analisar erros específicos
                $errorData = $response->json();
                if (isset($errorData['data']['errors'])) {
                    foreach ($errorData['data']['errors'] as $error) {
                        $this->line("   - {$error}");
                    }
                }
            }

        } catch (\Exception $e) {
            $this->error('   ❌ Erro: ' . $e->getMessage());
        }

        return false;
    }
}