<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class TestBookingEndpoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:booking-endpoints {property_id? : ID da propriedade local}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testa diferentes endpoints da API de reservas NextPax';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Testando endpoints da API de reservas NextPax...');
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

        // Testar diferentes endpoints
        $this->testEndpoint1($property, $user);
        $this->newLine();
        $this->testEndpoint2($property, $user);
        $this->newLine();
        $this->testEndpoint3($property, $user);
        $this->newLine();
        $this->testEndpoint4($property, $user);
    }

    private function testEndpoint1(Property $property, User $user): void
    {
        $this->info('1️⃣ Testando endpoint: /bookings');
        
        $baseUrl = config('services.nextpax.bookings_api_base', 'https://pci.sandbox.nextpax.com/supply');
        $apiToken = config('services.nextpax.token');
        
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

        try {
            $response = Http::withHeaders([
                'X-Api-Token' => $apiToken,
                'Content-Type' => 'application/json',
            ])->post($baseUrl . '/bookings', $bookingData);

            $this->line("   📡 Status: {$response->status()}");
            $this->line("   📄 Resposta: " . $response->body());
        } catch (\Exception $e) {
            $this->error("   ❌ Erro: " . $e->getMessage());
        }
    }

    private function testEndpoint2(Property $property, User $user): void
    {
        $this->info('2️⃣ Testando endpoint: /booking');
        
        $baseUrl = config('services.nextpax.bookings_api_base', 'https://pci.sandbox.nextpax.com/supply');
        $apiToken = config('services.nextpax.token');
        
        $bookingData = [
            'supplierPropertyId' => $property->supplier_property_id,
            'propertyManager' => $user->property_manager_code,
            'guestFirstName' => 'Maria',
            'guestLastName' => 'Santos',
            'guestEmail' => 'maria.santos@teste.com',
            'guestPhone' => '+5511888888888',
            'checkInDate' => date('Y-m-d', strtotime('+10 days')),
            'checkOutDate' => date('Y-m-d', strtotime('+12 days')),
            'adults' => 2,
            'children' => 0,
            'currency' => 'BRL',
            'totalAmount' => 200.00
        ];

        try {
            $response = Http::withHeaders([
                'X-Api-Token' => $apiToken,
                'Content-Type' => 'application/json',
            ])->post($baseUrl . '/booking', $bookingData);

            $this->line("   📡 Status: {$response->status()}");
            $this->line("   📄 Resposta: " . $response->body());
        } catch (\Exception $e) {
            $this->error("   ❌ Erro: " . $e->getMessage());
        }
    }

    private function testEndpoint3(Property $property, User $user): void
    {
        $this->info('3️⃣ Testando endpoint: /reservations');
        
        $baseUrl = config('services.nextpax.bookings_api_base', 'https://pci.sandbox.nextpax.com/supply');
        $apiToken = config('services.nextpax.token');
        
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

        try {
            $response = Http::withHeaders([
                'X-Api-Token' => $apiToken,
                'Content-Type' => 'application/json',
            ])->post($baseUrl . '/reservations', $bookingData);

            $this->line("   📡 Status: {$response->status()}");
            $this->line("   📄 Resposta: " . $response->body());
        } catch (\Exception $e) {
            $this->error("   ❌ Erro: " . $e->getMessage());
        }
    }

    private function testEndpoint4(Property $property, User $user): void
    {
        $this->info('4️⃣ Testando endpoint: /booking-requests');
        
        $baseUrl = config('services.nextpax.bookings_api_base', 'https://pci.sandbox.nextpax.com/supply');
        $apiToken = config('services.nextpax.token');
        
        $bookingData = [
            'supplierPropertyId' => $property->supplier_property_id,
            'propertyManager' => $user->property_manager_code,
            'guestFirstName' => 'Ana',
            'guestLastName' => 'Lima',
            'guestEmail' => 'ana.lima@teste.com',
            'guestPhone' => '+5511666666666',
            'checkInDate' => date('Y-m-d', strtotime('+17 days')),
            'checkOutDate' => date('Y-m-d', strtotime('+19 days')),
            'adults' => 2,
            'children' => 0,
            'currency' => 'BRL',
            'totalAmount' => 200.00
        ];

        try {
            $response = Http::withHeaders([
                'X-Api-Token' => $apiToken,
                'Content-Type' => 'application/json',
            ])->post($baseUrl . '/booking-requests', $bookingData);

            $this->line("   📡 Status: {$response->status()}");
            $this->line("   📄 Resposta: " . $response->body());
        } catch (\Exception $e) {
            $this->error("   ❌ Erro: " . $e->getMessage());
        }
    }
}