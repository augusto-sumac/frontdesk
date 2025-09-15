<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Property;
use App\Models\User;
use App\Services\NextPaxService;
use App\Services\NextPaxBookingsService;
use Illuminate\Support\Facades\Http;

class TestBookingCreationCorrect extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:booking-correct {property_id? : ID da propriedade local}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testa criação de reservas com formato correto da API NextPax';

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
        $this->info('🧪 Testando criação de reservas com formato correto...');
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

        // Testar diferentes formatos de criação de reserva
        $this->testBookingFormat1($property, $user);
        $this->newLine();
        $this->testBookingFormat2($property, $user);
        $this->newLine();
        $this->testBookingFormat3($property, $user);
    }

    private function testBookingFormat1(Property $property, User $user): void
    {
        $this->info('1️⃣ Testando Formato 1 - Dados básicos:');
        
        try {
            $bookingData = [
                'supplierPropertyId' => $property->supplier_property_id,
                'propertyManager' => $user->property_manager_code,
                'guestFirstName' => 'João',
                'guestLastName' => 'Silva',
                'guestEmail' => 'joao.silva@teste.com',
                'guestPhone' => '+5511999999999',
                'checkInDate' => date('Y-m-d', strtotime('+7 days')),
                'checkOutDate' => date('Y-m-d', strtotime('+9 days')),
                'adults' => 2,
                'children' => 0,
                'currency' => 'BRL',
                'totalAmount' => 200.00
            ];

            $this->line('   📝 Enviando dados:');
            $this->line('   ' . json_encode($bookingData, JSON_PRETTY_PRINT));

            $response = $this->bookingsService->createBooking($bookingData);
            
            if ($response && isset($response['data']['bookingId'])) {
                $this->info('   ✅ Reserva criada com sucesso!');
                $this->line('   Booking ID: ' . $response['data']['bookingId']);
            } else {
                $this->warn('   ⚠️  Resposta inesperada:');
                $this->line('   ' . json_encode($response, JSON_PRETTY_PRINT));
            }

        } catch (\Exception $e) {
            $this->error('   ❌ Erro: ' . $e->getMessage());
        }
    }

    private function testBookingFormat2(Property $property, User $user): void
    {
        $this->info('2️⃣ Testando Formato 2 - Com rate plan:');
        
        try {
            $bookingData = [
                'supplierPropertyId' => $property->supplier_property_id,
                'propertyManager' => $user->property_manager_code,
                'ratePlanCode' => 'DEFAULT',
                'guestFirstName' => 'Maria',
                'guestLastName' => 'Santos',
                'guestEmail' => 'maria.santos@teste.com',
                'guestPhone' => '+5511888888888',
                'checkInDate' => date('Y-m-d', strtotime('+10 days')),
                'checkOutDate' => date('Y-m-d', strtotime('+12 days')),
                'adults' => 2,
                'children' => 1,
                'currency' => 'BRL',
                'totalAmount' => 200.00,
                'nights' => 2
            ];

            $this->line('   📝 Enviando dados:');
            $this->line('   ' . json_encode($bookingData, JSON_PRETTY_PRINT));

            $response = $this->bookingsService->createBooking($bookingData);
            
            if ($response && isset($response['data']['bookingId'])) {
                $this->info('   ✅ Reserva criada com sucesso!');
                $this->line('   Booking ID: ' . $response['data']['bookingId']);
            } else {
                $this->warn('   ⚠️  Resposta inesperada:');
                $this->line('   ' . json_encode($response, JSON_PRETTY_PRINT));
            }

        } catch (\Exception $e) {
            $this->error('   ❌ Erro: ' . $e->getMessage());
        }
    }

    private function testBookingFormat3(Property $property, User $user): void
    {
        $this->info('3️⃣ Testando Formato 3 - Requisição direta HTTP:');
        
        try {
            $bookingData = [
                'supplierPropertyId' => $property->supplier_property_id,
                'propertyManager' => $user->property_manager_code,
                'guestFirstName' => 'Pedro',
                'guestLastName' => 'Costa',
                'guestEmail' => 'pedro.costa@teste.com',
                'guestPhone' => '+5511777777777',
                'checkInDate' => date('Y-m-d', strtotime('+14 days')),
                'checkOutDate' => date('Y-m-d', strtotime('+16 days')),
                'adults' => 2,
                'children' => 0,
                'currency' => 'BRL',
                'totalAmount' => 200.00
            ];

            $this->line('   📝 Enviando dados:');
            $this->line('   ' . json_encode($bookingData, JSON_PRETTY_PRINT));

            // Teste direto com HTTP
            $baseUrl = config('services.nextpax.bookings_api_base', 'https://pci.sandbox.nextpax.com/supply');
            $apiToken = config('services.nextpax.token');
            
            $response = Http::withHeaders([
                'X-Api-Token' => $apiToken,
                'Content-Type' => 'application/json',
            ])->post($baseUrl . '/bookings', $bookingData);

            $this->line('   📡 Status HTTP: ' . $response->status());
            $this->line('   📄 Resposta:');
            $this->line('   ' . $response->body());

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['data']['bookingId'])) {
                    $this->info('   ✅ Reserva criada com sucesso!');
                    $this->line('   Booking ID: ' . $data['data']['bookingId']);
                } else {
                    $this->warn('   ⚠️  Resposta inesperada:');
                    $this->line('   ' . json_encode($data, JSON_PRETTY_PRINT));
                }
            } else {
                $this->error('   ❌ Erro HTTP: ' . $response->status());
                $this->line('   ' . $response->body());
            }

        } catch (\Exception $e) {
            $this->error('   ❌ Erro: ' . $e->getMessage());
        }
    }
}