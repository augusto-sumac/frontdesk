<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class CreateBookingCorrect extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:booking-correct {property_id? : ID da propriedade local}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cria reserva usando o formato correto da API NextPax baseado na documentação YAML';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🎯 Criando reserva com formato correto da API NextPax...');
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

        // Criar reserva com formato correto baseado na documentação YAML
        $this->createBooking($property, $user);
    }

    private function createBooking(Property $property, User $user): void
    {
        $this->info('📝 Criando reserva com formato correto...');
        
        $baseUrl = config('services.nextpax.bookings_api_base', 'https://pci.sandbox.nextpax.com/supply');
        $apiToken = config('services.nextpax.token');
        
        // Formato correto baseado na documentação YAML
        $bookingData = [
            'query' => 'propertyManagerBooking',
            'payload' => [
                'bookingNumber' => 'TEST-' . time(), // Número único da reserva
                'propertyManager' => $user->property_manager_code,
                'channelPartnerReference' => 'CHANNEL-' . time(), // Referência do canal
                'supplierPropertyId' => $property->supplier_property_id,
                'channelId' => 'NEXTPAX', // Canal NextPax
                'rateplanId' => 1, // ID do rate plan (assumindo que existe)
                'remarks' => 'Reserva de teste criada via API',
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

        $this->line('   📤 Enviando dados:');
        $this->line('   ' . json_encode($bookingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        try {
            $response = Http::withHeaders([
                'X-Api-Token' => $apiToken,
                'Content-Type' => 'application/json',
            ])->post($baseUrl . '/bookings', $bookingData);

            $this->line("   📡 Status HTTP: {$response->status()}");
            $this->line("   📄 Resposta:");
            $this->line("   " . $response->body());

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['data']['id'])) {
                    $this->info('   ✅ Reserva criada com sucesso!');
                    $this->line("   NextPax Booking ID: {$data['data']['id']}");
                    $this->line("   Estado: {$data['data']['state']}");
                    $this->line("   Sucesso: " . ($data['data']['success'] ? 'Sim' : 'Não'));
                    $this->line("   Número da Reserva: {$data['data']['bookingNumber']}");
                } else {
                    $this->warn('   ⚠️  Resposta inesperada:');
                    $this->line('   ' . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }
            } else {
                $this->error('   ❌ Erro HTTP: ' . $response->status());
                $this->line('   ' . $response->body());
                
                // Analisar erros específicos
                $errorData = $response->json();
                if (isset($errorData['data']['errors'])) {
                    $this->newLine();
                    $this->warn('💡 POSSÍVEIS SOLUÇÕES:');
                    foreach ($errorData['data']['errors'] as $error) {
                        $this->line("   - {$error}");
                        
                        // Sugestões baseadas no erro
                        if (str_contains($error, 'property')) {
                            $this->line('     → Verifique se a propriedade está ativa e tem rate plans configurados');
                        }
                        if (str_contains($error, 'rateplan')) {
                            $this->line('     → Verifique se o rateplanId existe na propriedade');
                        }
                        if (str_contains($error, 'channel')) {
                            $this->line('     → Verifique se o channelId está correto');
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