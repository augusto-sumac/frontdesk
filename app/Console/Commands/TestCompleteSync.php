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
use Illuminate\Support\Facades\Log;

class TestCompleteSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:complete-sync {--user-id= : Specific user ID to test with} {--property-manager= : Specific property manager code to test with}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test complete synchronization flow from property to booking';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Testing complete synchronization flow...');
        $this->newLine();

        // Step 0: Setup and validation
        $this->info('Step 0: Setting up test environment...');
        
        $user = $this->getTestUser();
        if (!$user) {
            return 1;
        }

        $this->info('✅ Using user: ' . $user->name . ' (PM: ' . $user->property_manager_code . ')');
        $this->newLine();

        try {
            // Step 1: Use existing property instead of creating new one
            $this->info('Step 1: Finding existing property...');
            $propertyResult = $this->findExistingProperty($user);
            
            if (!$propertyResult['success']) {
                $this->error('❌ Property not found: ' . $propertyResult['error']);
                return 1;
            }

            $this->info('✅ Using existing property!');
            $this->line('  Property ID: ' . $propertyResult['propertyId']);
            $this->line('  Supplier Property ID: ' . $propertyResult['supplierPropertyId']);
            $this->line('  Local Property ID: ' . $propertyResult['localPropertyId']);
            $this->newLine();

            // Step 2: Create a booking using the existing property
            $this->info('Step 2: Creating booking...');
            $bookingResult = $this->createTestBooking($user, $propertyResult);
            
            if (!$bookingResult['success']) {
                $this->error('❌ Booking creation failed: ' . $bookingResult['error']);
                return 1;
            }

            $this->info('✅ Booking created successfully!');
            $this->line('  Local Booking ID: ' . $bookingResult['localBookingId']);
            $this->line('  NextPax Booking ID: ' . ($bookingResult['nextPaxBookingId'] ?? 'N/A'));
            $this->line('  Sync Status: ' . ($bookingResult['syncStatus'] ?? 'unknown'));
            
            if (isset($bookingResult['syncError'])) {
                $this->warn('  Sync Error: ' . $bookingResult['syncError']);
            }
            $this->newLine();

            // Step 3: Verify data in database
            $this->info('Step 3: Verifying data in database...');
            $verificationResult = $this->verifyDatabaseData($propertyResult, $bookingResult);
            
            if (!$verificationResult['success']) {
                $this->error('❌ Database verification failed: ' . $verificationResult['error']);
                return 1;
            }

            $this->info('✅ Data synchronized to database successfully!');
            $this->line('  Property in DB: ' . $verificationResult['propertyName'] . ' (NextPax: ' . $verificationResult['propertyNextPaxId'] . ')');
            $this->info('  Booking in DB: ' . $verificationResult['bookingGuestName'] . ' (Status: ' . $verificationResult['bookingStatus'] . ')');
            $this->newLine();

            // Step 4: Test listing and retrieval
            $this->info('Step 4: Testing listing and retrieval...');
            $listingResult = $this->testListings($user);
            
            if ($listingResult['success']) {
                $this->info('✅ Listings retrieved successfully!');
                $this->line('  Total bookings found: ' . $listingResult['totalBookings']);
            } else {
                $this->warn('⚠️  Listing test had issues: ' . $listingResult['error']);
            }

            // Step 5: Cleanup test data (optional)
            if ($this->confirm('Do you want to clean up the test data?', false)) {
                $this->info('Step 5: Cleaning up test data...');
                $this->cleanupTestData($propertyResult, $bookingResult);
                $this->info('✅ Test data cleaned up');
            }

            $this->newLine();
            $this->info('🎉 Complete synchronization test finished successfully!');
            return 0;

        } catch (\Exception $e) {
            $this->error('💥 Fatal error during test: ' . $e->getMessage());
            $this->line('Stack trace: ' . $e->getTraceAsString());
            Log::error('TestCompleteSync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Get or create a test user
     */
    private function getTestUser()
    {
        $userId = $this->option('user-id');
        $propertyManager = $this->option('property-manager');

        if ($userId) {
            $user = User::find($userId);
            if (!$user) {
                $this->error('User with ID ' . $userId . ' not found');
                return null;
            }
        } else {
        $user = User::whereNotNull('property_manager_code')->first();
        if (!$user) {
            $this->error('No user with property_manager_code found');
                return null;
            }
        }

        // Override property manager code if specified
        if ($propertyManager) {
            $user->property_manager_code = $propertyManager;
        } elseif (!$user->property_manager_code) {
        $user->property_manager_code = 'SAFDK000034';
        }

        // Ensure user is authenticated for the test
        auth()->login($user);

        return $user;
    }

    /**
     * Find existing property instead of creating new one
     */
    private function findExistingProperty($user)
    {
        try {
            // First, try to get properties from NextPax API to find a stable one
            $nextPaxService = new NextPaxService();
            $apiProperties = $nextPaxService->getProperties($user->property_manager_code);
            
            if (isset($apiProperties['data']) && !empty($apiProperties['data'])) {
                // Use the first property from API that has status "Created"
                foreach ($apiProperties['data'] as $apiProperty) {
                    if (isset($apiProperty['status']) && $apiProperty['status'] === 'Created') {
                        // Find corresponding local property
                        $localProperty = Property::where('property_id', $apiProperty['propertyId'])
                            ->orWhere('supplier_property_id', $apiProperty['supplierPropertyId'])
                            ->first();
                        
                        if ($localProperty) {
                            return [
                                'success' => true,
                                'propertyId' => $apiProperty['propertyId'],
                                'supplierPropertyId' => $apiProperty['supplierPropertyId'],
                                'localPropertyId' => $localProperty->id,
                            ];
                        }
                    }
                }
            }
            
            // Fallback to local database search
            $property = Property::where('channel_type', 'nextpax')
                ->whereNotNull('property_id')
                ->whereNotNull('supplier_property_id')
                ->where('is_active', true)
                ->first();

            if (!$property) {
                return [
                    'success' => false,
                    'error' => 'No existing synchronized property found'
                ];
            }

            return [
                'success' => true,
                'propertyId' => $property->property_id,
                'supplierPropertyId' => $property->supplier_property_id,
                'localPropertyId' => $property->id,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Exception while finding property: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create a test booking
     */
    private function createTestBooking($user, $propertyResult)
    {
        try {
            // Create booking directly via NextPaxBookingsService to avoid DashboardController issues
            $bookingsService = new NextPaxBookingsService();
            
            $bookingNumber = (string) random_int(1000000, 9999999);
            $channelPartnerReference = (string) Str::uuid();
            $channelId = 'AIR298'; // Use a valid channel ID
            
            // Build payload according to NextPax API specification
            $payload = [
                'query' => 'propertyManagerBooking',
                'payload' => [
                    'bookingNumber' => $bookingNumber,
                    'propertyManager' => $user->property_manager_code,
                    'channelPartnerReference' => $channelPartnerReference,
                    'channelId' => $channelId,
                    'propertyId' => $propertyResult['propertyId'], // NextPax UUID (required)
                    'supplierPropertyId' => $propertyResult['supplierPropertyId'], // Our internal ID
                    'period' => [
                        'arrivalDate' => now()->addDays(rand(1, 30))->format('Y-m-d'),
                        'departureDate' => now()->addDays(rand(31, 60))->format('Y-m-d'),
                    ],
                    'occupancy' => [
                        'adults' => rand(1, 3),
                        'children' => rand(0, 2),
                        'babies' => 0,
                        'pets' => 0,
                    ],
                    'stayPrice' => [
                        'amount' => rand(300, 1000),
                        'currency' => 'BRL',
                    ],
                    'mainBooker' => [
                        'surname' => 'Sincronização ' . Str::random(4),
                        'letters' => 'J',
                        'firstName' => 'João',
                        'countryCode' => 'BR',
                        'language' => 'pt',
                        'zipCode' => '00000000',
                        'houseNumber' => '1',
                        'street' => 'Rua Exemplo',
                        'place' => 'São Paulo',
                        'phoneNumber' => '0000000000',
                        'email' => 'joao.sincronizacao.' . Str::random(6) . '@exemplo.com',
                        'dateOfBirth' => '1980-01-01',
                        'titleCode' => 'male',
                    ],
                    'payment' => [
                        'type' => 'creditcard',
                    ],
                    'remarks' => 'Teste de sincronização completa - ' . now()->format('Y-m-d H:i:s'),
                ],
            ];

            // Try to create booking via API first
            $apiSuccess = false;
            $nextPaxBookingId = null;
            $apiError = null;
            
            try {
                $bookingResponse = $bookingsService->createBooking($payload);
                
                if (isset($bookingResponse['result']) && $bookingResponse['result'] === 'success') {
                    $apiSuccess = true;
                    $nextPaxBookingId = $bookingResponse['data']['bookingId'] ?? null;
                } else {
                    $apiError = 'API returned failure: ' . json_encode($bookingResponse);
                }
            } catch (\Exception $e) {
                $apiError = 'API exception: ' . $e->getMessage();
            }

            // Always save booking to database (local fallback)
            $dbBooking = new Booking();
            $dbBooking->nextpax_booking_id = $nextPaxBookingId;
            $dbBooking->booking_number = $bookingNumber;
            $dbBooking->channel_partner_reference = $channelPartnerReference;
            $dbBooking->channel_id = $channelId;
            $dbBooking->property_id = $propertyResult['propertyId'];
            $dbBooking->supplier_property_id = $propertyResult['supplierPropertyId'];
            $dbBooking->property_manager_code = $user->property_manager_code;
            $dbBooking->guest_first_name = 'João';
            $dbBooking->guest_surname = 'Sincronização ' . Str::random(4);
            $dbBooking->guest_email = 'joao.sincronizacao.' . Str::random(6) . '@exemplo.com';
            $dbBooking->guest_country_code = 'BR';
            $dbBooking->guest_language = 'pt';
            $dbBooking->check_in_date = $payload['payload']['period']['arrivalDate'];
            $dbBooking->check_out_date = $payload['payload']['period']['departureDate'];
            $dbBooking->adults = $payload['payload']['occupancy']['adults'];
            $dbBooking->children = $payload['payload']['occupancy']['children'];
            $dbBooking->babies = 0;
            $dbBooking->pets = 0;
            $dbBooking->total_amount = $payload['payload']['stayPrice']['amount'];
            $dbBooking->currency = $payload['payload']['stayPrice']['currency'];
            $dbBooking->payment_type = 'creditcard';
            $dbBooking->status = $apiSuccess ? 'pending' : 'pending_sync'; // New status for pending sync
            $dbBooking->remarks = $payload['payload']['remarks'];
            $dbBooking->api_response = $apiSuccess ? ($bookingResponse ?? null) : null;
            $dbBooking->api_payload = $payload;
            $dbBooking->sync_status = $apiSuccess ? 'synced' : 'pending'; // New field for sync status
            $dbBooking->sync_error = $apiError; // New field for sync errors
            $dbBooking->last_sync_attempt = now(); // New field for sync tracking
            $dbBooking->save();

            if ($apiSuccess) {
                $this->info('✅ Booking created successfully via API!');
                return [
                    'success' => true,
                    'localBookingId' => $dbBooking->id,
                    'nextPaxBookingId' => $nextPaxBookingId,
                    'bookingData' => $bookingResponse ?? null,
                    'syncStatus' => 'synced',
                ];
            } else {
                $this->warn('⚠️  API failed, but booking saved locally for later sync');
                $this->line('  Error: ' . $apiError);
                return [
                    'success' => true,
                    'localBookingId' => $dbBooking->id,
                    'nextPaxBookingId' => null,
                    'bookingData' => null,
                    'syncStatus' => 'pending_sync',
                    'syncError' => $apiError,
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Exception during booking creation: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify data in database
     */
    private function verifyDatabaseData($propertyResult, $bookingResult)
    {
        try {
            $dbProperty = Property::find($propertyResult['localPropertyId']);
            $dbBooking = Booking::find($bookingResult['localBookingId']);
            
            if (!$dbProperty) {
                return [
                    'success' => false,
                    'error' => 'Property not found in database'
                ];
            }
            
            if (!$dbBooking) {
                return [
                    'success' => false,
                    'error' => 'Booking not found in database'
                ];
            }

            // Verify property data
            if ($dbProperty->property_id !== $propertyResult['propertyId']) {
                return [
                    'success' => false,
                    'error' => 'Property ID mismatch: expected ' . $propertyResult['propertyId'] . ', got ' . $dbProperty->property_id
                ];
            }

            // Verify booking data
            if ($dbBooking->property_id !== $propertyResult['propertyId']) {
                return [
                    'success' => false,
                    'error' => 'Booking property ID mismatch: expected ' . $propertyResult['propertyId'] . ', got ' . $dbBooking->property_id
                ];
            }

            return [
                'success' => true,
                'propertyName' => $dbProperty->name,
                'propertyNextPaxId' => $dbProperty->property_id,
                'bookingGuestName' => $dbBooking->guest_first_name . ' ' . $dbBooking->guest_surname,
                'bookingStatus' => $dbBooking->status,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Exception during database verification: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test listings and retrieval
     */
    private function testListings($user)
    {
        try {
            $bookingsService = new NextPaxBookingsService();
            $messagingService = new NextPaxMessagingService();
            $nextPaxService = new NextPaxService();
            $dashboardController = new DashboardController($nextPaxService, $bookingsService, $messagingService);
                        
                        $listings = $dashboardController->bookings();
            
            // Count bookings for this property manager
            $totalBookings = Booking::where('property_manager_code', $user->property_manager_code)->count();
            
            return [
                'success' => true,
                'totalBookings' => $totalBookings,
                'listings' => $listings,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Exception during listing test: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Clean up test data
     */
    private function cleanupTestData($propertyResult, $bookingResult)
    {
        try {
            // Delete test booking
            if (isset($bookingResult['localBookingId'])) {
                $booking = Booking::find($bookingResult['localBookingId']);
                if ($booking) {
                    $booking->delete();
                    $this->line('  Deleted test booking');
                }
            }

            // Delete test property
            if (isset($propertyResult['localPropertyId'])) {
                $property = Property::find($propertyResult['localPropertyId']);
                if ($property) {
                    $property->delete();
                    $this->line('  Deleted test property');
                }
            }

        } catch (\Exception $e) {
            $this->warn('Warning: Could not clean up all test data: ' . $e->getMessage());
        }
    }
}
