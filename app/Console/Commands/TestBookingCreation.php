<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\DashboardController;
use App\Services\NextPaxService;
use App\Services\NextPaxBookingsService;
use App\Services\NextPaxMessagingService;
use App\Models\User;
use App\Models\Property;
use Illuminate\Http\Request;

class TestBookingCreation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:booking-creation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test booking creation using DashboardController';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing booking creation using DashboardController...');

        // Get the user with property_manager_code
        $user = User::whereNotNull('property_manager_code')->first();
        if (!$user) {
            $this->error('No user with property_manager_code found');
            return;
        }

        // Try with a different property manager that might work in bookings system
        $user->property_manager_code = 'SAFDK000034'; // Try a different PM code

        // Use a property that might be synchronized between systems
        $propertyId = '50721ca9-1b1d-4021-a1b8-217fdbb68e9d'; // From SAFDK000034
        $supplierPropertyId = 'prop-91GVPtjk3oD0'; // From SAFDK000034
        
        $this->info("Using existing property from supply system:");
        $this->line("Property ID: {$propertyId}");
        $this->line("Supplier Property ID: {$supplierPropertyId}");

        $this->info('Using user: ' . $user->name . ' (PM: ' . $user->property_manager_code . ')');

        // Create a mock request with booking data
        $requestData = [
            'guestFirstName' => 'João',
            'guestSurname' => 'Silva',
            'guestEmail' => 'joao.silva@exemplo.com',
            'checkIn' => '2025-08-15',
            'checkOut' => '2025-08-18',
            'adults' => 2,
            'children' => 1,
            'totalPrice' => 450.00,
            'currency' => 'BRL',
            'paymentType' => 'creditcard',
            'propertyId' => $propertyId, // NextPax UUID
            'remarks' => 'Chegada às 15h, preciso de estacionamento',
        ];

        // Create a mock request
        $request = new Request($requestData);

        // Set the authenticated user
        auth()->login($user);

        try {
            // Create the controller and call the createBooking method
            $nextPaxService = new NextPaxService();
            $bookingsService = new NextPaxBookingsService();
            $messagingService = new NextPaxMessagingService();
            
            $controller = new DashboardController($nextPaxService, $bookingsService, $messagingService);

            $this->info('Sending booking request to DashboardController...');
            $this->line('Request data: ' . json_encode($requestData, JSON_PRETTY_PRINT));

            $response = $controller->createBooking($request);

            $this->info('Response:');
            $this->line(json_encode($response->getData(), JSON_PRETTY_PRINT));

            if (isset($response->getData()->success) && $response->getData()->success) {
                $this->info('✅ Booking created successfully!');
                $this->line('Booking: ' . json_encode($response->getData()->booking, JSON_PRETTY_PRINT));
            } else {
                $this->error('❌ Booking creation failed');
                if (isset($response->getData()->error)) {
                    $this->error('Error: ' . $response->getData()->error);
                }
            }

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            $this->line('Stack trace: ' . $e->getTraceAsString());
        }
    }
}
