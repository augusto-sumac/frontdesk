<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Property;
use App\Models\User;
use App\Services\NextPaxService;
use App\Services\NextPaxBookingsService;

class TestBookingAPI extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:booking-api {property_id? : ID da propriedade local}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testa a criação de reservas na API NextPax';

    private NextPaxService $nextPaxService;
    private NextPaxBookingsService $bookingsService;

    public function __construct(NextPaxService $nextPaxService, NextPaxBookingsService $bookingsService)
    {
        parent::__construct();
        $this->nextPaxService = $nextPaxService;
        $this->bookingsService = $bookingsService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testando API de reservas NextPax...');
        $this->newLine();

        $propertyId = $this->argument('property_id');

        if ($propertyId) {
            $properties = Property::where('id', $propertyId)
                ->orWhere('channel_property_id', $propertyId)
                ->get();
        } else {
            // Usar a primeira propriedade disponível
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
        $this->line("🏠 Testando com propriedade: {$property->name}");
        $this->line("   NextPax ID: {$property->channel_property_id}");
        $this->line("   Supplier ID: {$property->supplier_property_id}");
        $this->newLine();

        // 1. Verificar se a propriedade existe na API
        $this->info('1️⃣ Verificando propriedade na API...');
        try {
            $apiProperty = $this->nextPaxService->getProperty($property->channel_property_id);
            if ($apiProperty) {
                $this->info('   ✅ Propriedade encontrada na API');
                $this->line("   Nome: " . ($apiProperty['general']['name'] ?? 'N/A'));
                $this->line("   Supplier ID: " . ($apiProperty['supplierPropertyId'] ?? 'N/A'));
            } else {
                $this->error('   ❌ Propriedade não encontrada na API');
                return;
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Erro ao consultar API: ' . $e->getMessage());
            return;
        }

        // 2. Verificar rate plans
        $this->info('2️⃣ Verificando rate plans...');
        try {
            $ratePlans = $this->nextPaxService->getRatePlans($property->channel_property_id);
            if (!empty($ratePlans['data'])) {
                $this->info('   ✅ Rate plans encontrados: ' . count($ratePlans['data']));
                foreach ($ratePlans['data'] as $plan) {
                    $this->line("   - {$plan['ratePlanName']} ({$plan['ratePlanCode']})");
                }
            } else {
                $this->warn('   ⚠️  Nenhum rate plan encontrado');
            }
        } catch (\Exception $e) {
            $this->warn('   ⚠️  Erro ao verificar rate plans: ' . $e->getMessage());
        }

        // 3. Verificar disponibilidade
        $this->info('3️⃣ Verificando disponibilidade...');
        try {
            $availability = $this->nextPaxService->getAvailability($property->channel_property_id);
            if (!empty($availability['data'])) {
                $this->info('   ✅ Disponibilidade configurada');
                foreach ($availability['data'] as $avail) {
                    $this->line("   - {$avail['fromDate']} até {$avail['untilDate']}: {$avail['quantity']} unidades");
                }
            } else {
                $this->warn('   ⚠️  Nenhuma disponibilidade encontrada');
            }
        } catch (\Exception $e) {
            $this->warn('   ⚠️  Erro ao verificar disponibilidade: ' . $e->getMessage());
        }

        // 4. Verificar preços
        $this->info('4️⃣ Verificando preços...');
        try {
            $rates = $this->nextPaxService->getRates($property->channel_property_id);
            if (!empty($rates['data'])) {
                $this->info('   ✅ Preços configurados');
                foreach ($rates['data'] as $rate) {
                    $this->line("   - {$rate['fromDate']} até {$rate['untilDate']}: {$rate['currency']} " . 
                        (isset($rate['prices']['nightlyPrice']) ? number_format($rate['prices']['nightlyPrice'] / 100, 2) : 'N/A'));
                }
            } else {
                $this->warn('   ⚠️  Nenhum preço encontrado');
            }
        } catch (\Exception $e) {
            $this->warn('   ⚠️  Erro ao verificar preços: ' . $e->getMessage());
        }

        // 5. Testar criação de reserva
        $this->info('5️⃣ Testando criação de reserva...');
        $this->testBookingCreation($property);

        $this->newLine();
        $this->info('✅ Teste concluído!');
    }

    private function testBookingCreation(Property $property): void
    {
        try {
            // Dados de teste para reserva
            $bookingData = [
                'supplierPropertyId' => $property->supplier_property_id,
                'guestFirstName' => 'João',
                'guestLastName' => 'Silva',
                'guestEmail' => 'joao.silva@teste.com',
                'guestPhone' => '+5511999999999',
                'checkInDate' => date('Y-m-d', strtotime('+7 days')),
                'checkOutDate' => date('Y-m-d', strtotime('+9 days')),
                'adults' => 2,
                'children' => 0,
                'currency' => 'BRL',
                'totalAmount' => 200.00,
                'ratePlanCode' => 'DEFAULT'
            ];

            $this->line('   📝 Dados da reserva:');
            $this->line('   - Hóspede: ' . $bookingData['guestFirstName'] . ' ' . $bookingData['guestLastName']);
            $this->line('   - Check-in: ' . $bookingData['checkInDate']);
            $this->line('   - Check-out: ' . $bookingData['checkOutDate']);
            $this->line('   - Valor: ' . $bookingData['currency'] . ' ' . number_format($bookingData['totalAmount'], 2));

            // Tentar criar reserva
            $response = $this->bookingsService->createBooking($bookingData);
            
            if ($response && isset($response['data']['bookingId'])) {
                $this->info('   ✅ Reserva criada com sucesso!');
                $this->line('   Booking ID: ' . $response['data']['bookingId']);
            } else {
                $this->warn('   ⚠️  Resposta inesperada da API:');
                $this->line('   ' . json_encode($response, JSON_PRETTY_PRINT));
            }

        } catch (\Exception $e) {
            $this->error('   ❌ Erro ao criar reserva: ' . $e->getMessage());
            
            // Se for erro de propriedade não encontrada, mostrar dicas
            if (str_contains($e->getMessage(), 'property') && str_contains($e->getMessage(), 'not found')) {
                $this->newLine();
                $this->warn('💡 DICAS PARA RESOLVER O PROBLEMA:');
                $this->line('1. Verifique se o supplier_property_id está correto');
                $this->line('2. Confirme se a propriedade está ativa na NextPax');
                $this->line('3. Verifique se tem rate plans configurados');
                $this->line('4. Confirme se tem disponibilidade configurada');
                $this->line('5. Verifique se tem preços configurados');
            }
        }
    }
}