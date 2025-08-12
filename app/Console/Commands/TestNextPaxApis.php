<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NextPaxBookingsService;
use App\Services\NextPaxMessagingService;
use Illuminate\Support\Facades\Http;

class TestNextPaxApis extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nextpax:test {--service=all : Service to test (bookings, messaging, all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test NextPax APIs connectivity and endpoints';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing NextPax APIs...');
        $this->newLine();

        $service = $this->option('service');

        if ($service === 'all' || $service === 'bookings') {
            $this->testBookingsApi();
        }

        if ($service === 'all' || $service === 'messaging') {
            $this->testMessagingApi();
        }

        $this->info('✅ API testing completed!');
    }

    private function testBookingsApi()
    {
        $this->info('📚 Testing Bookings API...');
        
        try {
            $bookingsService = new NextPaxBookingsService();
            
            // Test configuration
            $this->line('  📋 Configuration:');
            $this->line('    Base URL: ' . config('services.nextpax.bookings_api_base'));
            $this->line('    Token: ' . substr(config('services.nextpax.token'), 0, 10) . '...');
            
            // Test with different property manager codes
            $propertyManagerCodes = ['SAFDK', 'LR00001', 'TEST001'];
            
            foreach ($propertyManagerCodes as $propertyManagerCode) {
                $this->line("  🔍 Testing GET /bookings with propertyManager: {$propertyManagerCode}");
                
                $response = $bookingsService->getBookings($propertyManagerCode, false, null);
                
                if (isset($response['result'])) {
                    if ($response['result'] === 'success') {
                        $this->info("    ✅ Success for {$propertyManagerCode}!");
                        $this->line('    📊 Found ' . count($response['data'] ?? []) . ' bookings');
                        
                        if (!empty($response['data'])) {
                            $this->line('    📝 Sample booking:');
                            $sample = $response['data'][0];
                            $this->line('      ID: ' . ($sample['bookingId'] ?? 'N/A'));
                            $this->line('      Number: ' . ($sample['bookingNumber'] ?? 'N/A'));
                            $this->line('      State: ' . ($sample['state'] ?? 'N/A'));
                            $this->line('      Last Modified: ' . ($sample['lastModified'] ?? 'N/A'));
                            
                            if (isset($sample['remarks'])) {
                                $this->line('      Remarks: ' . substr($sample['remarks'], 0, 50) . '...');
                            }
                        }
                        break; // Found working property manager code
                    } else {
                        $this->warn("    ⚠️  Failed for {$propertyManagerCode}: " . ($response['data']['message'] ?? 'Unknown error'));
                    }
                } else {
                    $this->warn("    ⚠️  Unexpected response for {$propertyManagerCode}");
                    $this->line('    Response: ' . json_encode($response, JSON_PRETTY_PRINT));
                }
            }
            
        } catch (\Exception $e) {
            $this->error('    ❌ Bookings API Error: ' . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function testMessagingApi()
    {
        $this->info('💬 Testing Messaging API...');
        
        try {
            $messagingService = new NextPaxMessagingService();
            
            // Test configuration
            $this->line('  📋 Configuration:');
            $this->line('    Base URL: ' . config('services.nextpax.messaging_api_base'));
            $this->line('    Client ID: ' . config('services.nextpax.client_id'));
            $this->line('    Auth URL: ' . config('services.nextpax.auth_url'));
            
            $this->line('  🔐 Testing OAuth2 authentication...');
            
            // Test authentication
            $authResult = $messagingService->testAuthentication();
            if ($authResult['success']) {
                $this->info('    ✅ OAuth2 authentication successful!');
                $this->line('    Token: ' . substr($authResult['token'], 0, 20) . '...');
                
                $this->line('  🔍 Testing GET /threads endpoint...');
                
                // Test threads endpoint
                $response = $messagingService->getThreads(
                    null, // hasNewMessages
                    null, // channelId
                    null, // channelPartnerReference
                    null, // fromTimestamp
                    null, // untilTimestamp
                    0,    // offset
                    5,    // limit
                    'time_desc' // orderBy
                );
                
                if (isset($response['data'])) {
                    $this->info('    ✅ Messaging API is working!');
                    $this->line('    📊 Found ' . count($response['data']) . ' threads');
                    
                    if (!empty($response['data'])) {
                        $this->line('    📝 Sample thread:');
                        $sample = $response['data'][0];
                        $this->line('      ID: ' . ($sample['threadId'] ?? 'N/A'));
                        $this->line('      Guest: ' . ($sample['guestName'] ?? 'N/A'));
                        $this->line('      Channel: ' . ($sample['channelId'] ?? 'N/A'));
                    }
                } else {
                    $this->warn('    ⚠️  Messaging API returned unexpected response');
                    $this->line('    Response: ' . json_encode($response, JSON_PRETTY_PRINT));
                }
            } else {
                $this->error('    ❌ OAuth2 authentication failed!');
                $this->line('    Error: ' . $authResult['error']);
                
                // Test alternative: direct HTTP request to check if endpoint exists
                $this->line('  🔍 Testing endpoint availability...');
                $this->testMessagingEndpointAvailability();
            }
            
        } catch (\Exception $e) {
            $this->error('    ❌ Messaging API Error: ' . $e->getMessage());
        }
        
        $this->newLine();
    }

    private function testMessagingEndpointAvailability()
    {
        try {
            $baseUrl = config('services.nextpax.messaging_api_base');
            $response = Http::get($baseUrl . '/threads');
            
            if ($response->successful()) {
                $this->info('    ✅ Messaging endpoint is accessible!');
                $this->line('    Status: ' . $response->status());
            } else {
                $this->warn('    ⚠️  Messaging endpoint returned status: ' . $response->status());
                $this->line('    Response: ' . $response->body());
            }
        } catch (\Exception $e) {
            $this->error('    ❌ Cannot reach messaging endpoint: ' . $e->getMessage());
        }
    }
}
