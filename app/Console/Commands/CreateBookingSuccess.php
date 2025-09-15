<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class CreateBookingSuccess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:booking-success {property_id? : ID da propriedade local}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cria reserva com sucesso usando propertyId correto e canal válido';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🎯 Criando reserva com sucesso usando propertyId correto...');
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

        // Usar canal válido com propertyId correto
        $this->createBooking($property, $user);
    }

    private function createBooking(Property $property, User $user): void
    {
        $baseUrl = config('services.nextpax.bookings_api_base', 'https://pci.sandbox.nextpax.com/supply');
        $apiToken = config('services.nextpax.token');
        
        // Formato correto usando propertyId (NextPax ID) em vez de supplierPropertyId
        $bookingData = [
            'query' => 'propertyManagerBooking',
            'payload' => [
                'bookingNumber' => 'SUCCESS-' . time(),
                'propertyManager' => $user->property_manager_code,
                'channelPartnerReference' => 'CHANNEL-' . time(),
                'propertyId' => $property->channel_property_id, // Usando NextPax ID
                'channelId' => 'HOM143', // Canal válido HomeAway
                'rateplanId' => 1, // Assumindo que existe
                'remarks' => 'Reserva de teste criada com sucesso via API',
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

        $this->info('📝 Criando reserva com propertyId correto...');
        $this->line('   📤 Enviando dados:');
        $this->line('   ' . json_encode($bookingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        try {
            $response = Http::withHeaders([
                'X-Api-Token' => $apiToken,
                'Content-Type' => 'application/json',
            ])->post($baseUrl . '/bookings', $bookingData);

            $this->line("   📡 Status HTTP: {$response->status()}");

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['data']['id'])) {
                    $this->info('   🎉 RESERVA CRIADA COM SUCESSO!');
                    $this->line("   NextPax Booking ID: {$data['data']['id']}");
                    $this->line("   Estado: {$data['data']['state']}");
                    $this->line("   Sucesso: " . ($data['data']['success'] ? 'Sim' : 'Não'));
                    $this->line("   Número da Reserva: {$data['data']['bookingNumber']}");
                    
                    $this->newLine();
                    $this->info('✅ SOLUÇÃO ENCONTRADA!');
                    $this->line('A propriedade está configurada corretamente e pronta para receber reservas.');
                    $this->line('Use o formato correto com:');
                    $this->line('- query: "propertyManagerBooking"');
                    $this->line('- propertyId: NextPax ID da propriedade');
                    $this->line('- channelId: Canal válido (HOM143, BOO142, etc.)');
                    $this->line('- Todos os campos obrigatórios preenchidos');
                    
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
                    $this->newLine();
                    $this->warn('💡 PRÓXIMOS PASSOS:');
                    foreach ($errorData['data']['errors'] as $error) {
                        $this->line("   - {$error}");
                        
                        if (str_contains($error, 'rateplan')) {
                            $this->line('     → Configure rate plans na propriedade');
                        }
                        if (str_contains($error, 'availability')) {
                            $this->line('     → Configure disponibilidade na propriedade');
                        }
                        if (str_contains($error, 'pricing')) {
                            $this->line('     → Configure preços na propriedade');
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            $this->error('   ❌ Erro: ' . $e->getMessage());
        }

        $this->newLine();
        $this->info('✅ Teste de criação de reserva concluído!');
    }
}