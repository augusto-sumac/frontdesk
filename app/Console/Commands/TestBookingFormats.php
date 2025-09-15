<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class TestBookingFormats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:booking-formats {property_id? : ID da propriedade local}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testa diferentes formatos de requisição para criação de reservas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Testando diferentes formatos de requisição para reservas...');
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

        // Testar diferentes formatos
        $this->testFormat1($property, $user);
        $this->newLine();
        $this->testFormat2($property, $user);
        $this->newLine();
        $this->testFormat3($property, $user);
        $this->newLine();
        $this->testFormat4($property, $user);
    }

    private function testFormat1(Property $property, User $user): void
    {
        $this->info('1️⃣ Formato 1 - Com query parameter:');
        
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
            ])->post($baseUrl . '/bookings?query=createBooking', $bookingData);

            $this->line("   📡 Status: {$response->status()}");
            $this->line("   📄 Resposta: " . $response->body());
        } catch (\Exception $e) {
            $this->error("   ❌ Erro: " . $e->getMessage());
        }
    }

    private function testFormat2(Property $property, User $user): void
    {
        $this->info('2️⃣ Formato 2 - Com wrapper de query:');
        
        $baseUrl = config('services.nextpax.bookings_api_base', 'https://pci.sandbox.nextpax.com/supply');
        $apiToken = config('services.nextpax.token');
        
        $requestData = [
            'query' => 'createBooking',
            'propertyManager' => $user->property_manager_code,
            'payload' => [
                'supplierPropertyId' => $property->supplier_property_id,
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
            ]
        ];

        try {
            $response = Http::withHeaders([
                'X-Api-Token' => $apiToken,
                'Content-Type' => 'application/json',
            ])->post($baseUrl . '/bookings', $requestData);

            $this->line("   📡 Status: {$response->status()}");
            $this->line("   📄 Resposta: " . $response->body());
        } catch (\Exception $e) {
            $this->error("   ❌ Erro: " . $e->getMessage());
        }
    }

    private function testFormat3(Property $property, User $user): void
    {
        $this->info('3️⃣ Formato 3 - Com channelId:');
        
        $baseUrl = config('services.nextpax.bookings_api_base', 'https://pci.sandbox.nextpax.com/supply');
        $apiToken = config('services.nextpax.token');
        
        $requestData = [
            'query' => 'createBooking',
            'channelId' => 'nextpax', // ou outro canal
            'propertyManager' => $user->property_manager_code,
            'payload' => [
                'supplierPropertyId' => $property->supplier_property_id,
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
            ]
        ];

        try {
            $response = Http::withHeaders([
                'X-Api-Token' => $apiToken,
                'Content-Type' => 'application/json',
            ])->post($baseUrl . '/bookings', $requestData);

            $this->line("   📡 Status: {$response->status()}");
            $this->line("   📄 Resposta: " . $response->body());
        } catch (\Exception $e) {
            $this->error("   ❌ Erro: " . $e->getMessage());
        }
    }

    private function testFormat4(Property $property, User $user): void
    {
        $this->info('4️⃣ Formato 4 - Com dados mínimos:');
        
        $baseUrl = config('services.nextpax.bookings_api_base', 'https://pci.sandbox.nextpax.com/supply');
        $apiToken = config('services.nextpax.token');
        
        $requestData = [
            'query' => 'createBooking',
            'propertyManager' => $user->property_manager_code,
            'payload' => [
                'supplierPropertyId' => $property->supplier_property_id,
                'guestFirstName' => 'Ana',
                'guestLastName' => 'Lima',
                'guestEmail' => 'ana.lima@teste.com',
                'checkInDate' => date('Y-m-d', strtotime('+17 days')),
                'checkOutDate' => date('Y-m-d', strtotime('+19 days')),
                'adults' => 2
            ]
        ];

        try {
            $response = Http::withHeaders([
                'X-Api-Token' => $apiToken,
                'Content-Type' => 'application/json',
            ])->post($baseUrl . '/bookings', $requestData);

            $this->line("   📡 Status: {$response->status()}");
            $this->line("   📄 Resposta: " . $response->body());
        } catch (\Exception $e) {
            $this->error("   ❌ Erro: " . $e->getMessage());
        }
    }
}