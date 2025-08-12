<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NextPaxService;
use App\Services\NextPaxBookingsService;
use App\Services\NextPaxMessagingService;
use App\Http\Controllers\DashboardController;
use App\Models\User;
use App\Models\Property;
use Illuminate\Http\Request;

class TestBookingWithSupplierId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:booking-supplier-id';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test booking creation using only supplierPropertyId';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing booking creation with supplierPropertyId only...');

        // Get the user with property_manager_code
        $user = User::whereNotNull('property_manager_code')->first();
        if (!$user) {
            $this->error('No user with property_manager_code found');
            return;
        }

        // Use a property manager that exists in both systems
        $user->property_manager_code = 'SAFDK000034';
        
        $this->info('Using user: ' . $user->name . ' (PM: ' . $user->property_manager_code . ')');

        // Use a property that exists in the bookings system
        $property = Property::where('channel_type', 'nextpax')
            ->where('supplier_property_id', 'prop-91GVPtjk3oD0') // Known working property
            ->first();

        if (!$property) {
            $this->error('No properties with supplier_property_id found');
            return;
        }

        $this->info('Using property: ' . $property->name);
        $this->line('NextPax Property ID: ' . $property->property_id);
        $this->line('Supplier Property ID: ' . $property->supplier_property_id);

        // Create a mock request with booking data
        $requestData = [
            'guestFirstName' => 'João',
            'guestSurname' => 'Teste Supplier ID',
            'guestEmail' => 'joao.supplier@exemplo.com',
            'checkIn' => '2025-08-25',
            'checkOut' => '2025-08-28',
            'adults' => 2,
            'children' => 1,
            'totalPrice' => 450.00,
            'currency' => 'BRL',
            'paymentType' => 'creditcard',
            'propertyId' => $property->property_id, // NextPax UUID for lookup
            'remarks' => 'Teste usando apenas supplierPropertyId',
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
                $this->line('Local Booking ID: ' . $response->getData()->localBookingId);
                
                // Verify the payload that was sent
                $this->info('Payload sent to API:');
                $this->line(json_encode($response->getData()->booking->api_payload ?? [], JSON_PRETTY_PRINT));
                
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
