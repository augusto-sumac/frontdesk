<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NextPaxService;
use App\Services\NextPaxBookingsService;
use App\Services\NextPaxMessagingService;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\DashboardController;
use App\Models\User;
use App\Models\Property;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class TestFullFlow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:full-flow';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test complete flow: user creation, property creation, and booking';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Testing Complete Flow: User → Property → Booking');
        $this->line('');

        // Step 1: Create a new user with property manager
        $this->info('📋 Step 1: Creating new user with property manager...');
        
        $userData = [
            'name' => 'Teste Fluxo Completo',
            'email' => 'teste.fluxo.' . Str::random(8) . '@exemplo.com',
            'password' => Hash::make('password123'),
            'property_manager_code' => 'SAFDK' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT),
        ];

        $user = User::create($userData);
        $this->info('✅ User created successfully!');
        $this->line('User ID: ' . $user->id);
        $this->line('Property Manager: ' . $user->property_manager_code);
        $this->line('');

        // Step 2: Create property manager in NextPax
        $this->info('🏢 Step 2: Creating property manager in NextPax...');
        
        $nextPaxService = new NextPaxService();
        
        $propertyManagerPayload = [
            'query' => 'propertyManager',
            'payload' => [
                'companyName' => 'Teste Fluxo Completo - ' . $user->property_manager_code,
                'general' => [
                    'companyEmail' => $user->email,
                    'companyPhone' => '11999999999',
                    'countryCode' => 'BR',
                    'mainCurrency' => 'BRL',
                    'spokenLanguages' => ['PT'],
                    'acceptedCurrencies' => ['BRL', 'USD'],
                    'checkInOutTimes' => [
                        'checkInFrom' => '14:00',
                        'checkInUntil' => '22:00',
                        'checkOutFrom' => '08:00',
                        'checkOutUntil' => '11:00',
                    ],
                    'website' => 'https://exemplo.com',
                    'address' => [
                        'city' => 'São Paulo',
                        'countryCode' => 'BR',
                        'postalCode' => '01234-567',
                    ],
                    'hostInformation' => [
                        'firstName' => 'Teste',
                        'surname' => 'Fluxo',
                        'email' => $user->email,
                        'phoneNumber' => '11999999999',
                    ],
                ],
            ],
        ];

        try {
            $propertyManagerResponse = $nextPaxService->createPropertyManager($propertyManagerPayload);
            $this->info('✅ Property manager created in NextPax!');
            $this->line('Response: ' . json_encode($propertyManagerResponse, JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            $this->warn('⚠️ Property manager creation failed: ' . $e->getMessage());
            $this->line('Continuing with existing property manager...');
        }
        
        $this->line('');

        // Step 3: Create property using the new user
        $this->info('🏠 Step 3: Creating property using new user...');
        
        auth()->login($user);
        
        $propertyData = [
            'name' => 'Apartamento Fluxo Completo',
            'property_type' => 'apartment',
            'description' => 'Apartamento para teste do fluxo completo',
            'address' => 'Rua do Fluxo, 123',
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
            'base_price' => 300.00,
            'currency' => 'BRL',
            'contact_name' => 'Teste Fluxo',
            'contact_phone' => '(11) 99999-9999',
            'contact_email' => $user->email,
            'check_in_from' => '14:00',
            'check_in_until' => '22:00',
            'check_out_from' => '08:00',
            'check_out_until' => '11:00',
            'amenities' => ['Wi-Fi', 'Ar Condicionado', 'Cozinha'],
            'house_rules' => ['Não fumar', 'Não pets'],
        ];

        $propertyRequest = new Request($propertyData);
        $propertyController = new PropertyController($nextPaxService);
        
        try {
            $propertyResponse = $propertyController->store($propertyRequest);
            $propertyData = $propertyResponse->getData();
            
            if ($propertyData->success) {
                $this->info('✅ Property created successfully!');
                $this->line('NextPax Property ID: ' . $propertyData->propertyId);
                $this->line('Supplier Property ID: ' . $propertyData->supplierPropertyId);
                $this->line('Local Property ID: ' . $propertyData->localPropertyId);
                
                // Step 4: Create booking using the new property
                $this->info('📅 Step 4: Creating booking using new property...');
                
                $bookingData = [
                    'guestFirstName' => 'João',
                    'guestSurname' => 'Fluxo Completo',
                    'guestEmail' => 'joao.fluxo@exemplo.com',
                    'checkIn' => '2025-08-30',
                    'checkOut' => '2025-09-02',
                    'adults' => 2,
                    'children' => 1,
                    'totalPrice' => 900.00,
                    'currency' => 'BRL',
                    'paymentType' => 'creditcard',
                    'propertyId' => $propertyData->propertyId,
                    'remarks' => 'Teste do fluxo completo',
                ];

                $bookingRequest = new Request($bookingData);
                
                $bookingsService = new NextPaxBookingsService();
                $messagingService = new NextPaxMessagingService();
                $dashboardController = new DashboardController($nextPaxService, $bookingsService, $messagingService);
                
                try {
                    $bookingResponse = $dashboardController->createBooking($bookingRequest);
                    $bookingData = $bookingResponse->getData();
                    
                    if (isset($bookingData->success) && $bookingData->success) {
                        $this->info('✅ Booking created successfully!');
                        $this->line('Local Booking ID: ' . $bookingData->localBookingId);
                        
                        // Step 5: Verify data in database
                        $this->info('🔍 Step 5: Verifying data in database...');
                        
                        $dbProperty = Property::find($propertyData->localPropertyId);
                        $dbBooking = Booking::find($bookingData->localBookingId);
                        
                        if ($dbProperty && $dbBooking) {
                            $this->info('✅ Data synchronized to database successfully!');
                            $this->line('Property in DB: ' . $dbProperty->name . ' (NextPax: ' . $dbProperty->property_id . ')');
                            $this->line('Booking in DB: ' . $dbBooking->guest_first_name . ' ' . $dbBooking->guest_surname . ' (Status: ' . $dbBooking->status . ')');
                            
                            // Step 6: Test listing
                            $this->info('📋 Step 6: Testing listing...');
                            
                            try {
                                $listings = $dashboardController->bookings();
                                $this->info('✅ Listings retrieved successfully!');
                            } catch (\Exception $e) {
                                $this->warn('⚠️ Listings failed: ' . $e->getMessage());
                            }
                            
                        } else {
                            $this->error('❌ Data not found in database');
                        }
                        
                    } else {
                        $this->error('❌ Booking creation failed');
                        if (isset($bookingData->error)) {
                            $this->error('Error: ' . $bookingData->error);
                        }
                    }
                    
                } catch (\Exception $e) {
                    $this->error('❌ Booking creation error: ' . $e->getMessage());
                }
                
            } else {
                $this->error('❌ Property creation failed');
            }

        } catch (\Exception $e) {
            $this->error('❌ Property creation error: ' . $e->getMessage());
        }

        $this->line('');
        $this->info('🏁 Flow test completed!');
        
        // Summary
        $this->line('');
        $this->info('📊 Summary:');
        $this->line('User: ' . $user->name . ' (PM: ' . $user->property_manager_code . ')');
        if (isset($propertyData->success) && $propertyData->success) {
            $this->line('Property: ' . $propertyData->propertyId . ' (Supplier: ' . $propertyData->supplierPropertyId . ')');
        }
        if (isset($bookingData->success) && $bookingData->success) {
            $this->line('Booking: ' . $bookingData->localBookingId);
        }
    }
}
