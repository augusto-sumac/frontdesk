<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\PropertyController;
use App\Services\NextPaxService;
use App\Models\User;
use Illuminate\Http\Request;

class TestPropertyController extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:property-controller';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test property creation using PropertyController';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing property creation using PropertyController...');

        // Get the user with property_manager_code
        $user = User::whereNotNull('property_manager_code')->first();
        if (!$user) {
            $this->error('No user with property_manager_code found');
            return;
        }

        $this->info('Using user: ' . $user->name . ' (PM: ' . $user->property_manager_code . ')');

        // Create a mock request with property data
        $requestData = [
            'name' => 'Apartamento Teste Controller',
            'property_type' => 'apartment',
            'description' => 'Apartamento moderno para teste',
            'address' => 'Rua das Flores, 123',
            'city' => 'São Paulo',
            'state' => 'SP',
            'country' => 'BR',
            'postal_code' => '01234-567',
            'latitude' => -23.5505,
            'longitude' => -46.6333,
            'max_occupancy' => 4,
            'max_adults' => 2,
            'max_children' => 2,
            'bedrooms' => 2,
            'bathrooms' => 1,
            'base_price' => 150.00,
            'currency' => 'BRL',
            'contact_name' => 'João Silva',
            'contact_phone' => '(11) 99999-9999',
            'contact_email' => 'joao@exemplo.com',
            'check_in_from' => '14:00',
            'check_in_until' => '22:00',
            'check_out_from' => '08:00',
            'check_out_until' => '11:00',
            'amenities' => ['Wi-Fi', 'Ar Condicionado', 'Cozinha'],
            'house_rules' => ['Não fumar', 'Não pets'],
        ];

        // Create a mock request
        $request = new Request($requestData);

        // Set the authenticated user
        auth()->login($user);

        try {
            // Create the controller and call the store method
            $nextPaxService = new NextPaxService();
            $controller = new PropertyController($nextPaxService);

            $this->info('Sending request to PropertyController...');
            $response = $controller->store($request);

            $this->info('Response:');
            $this->line(json_encode($response->getData(), JSON_PRETTY_PRINT));

            if ($response->getData()->success) {
                $this->info('✅ Property created successfully!');
                $this->line('Property ID: ' . $response->getData()->propertyId);
                $this->line('Supplier Property ID: ' . $response->getData()->supplierPropertyId);
                $this->line('Local Property ID: ' . $response->getData()->localPropertyId);
            } else {
                $this->error('❌ Property creation failed');
            }

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            $this->line('Stack trace: ' . $e->getTraceAsString());
        }
    }
}
