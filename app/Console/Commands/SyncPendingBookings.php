<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Services\NextPaxBookingsService;
use Illuminate\Support\Facades\Log;

class SyncPendingBookings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bookings:sync-pending {--limit=10 : Maximum number of bookings to sync} {--force : Force sync even if recently attempted}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync pending bookings with NextPax API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Starting pending bookings synchronization...');
        $this->newLine();

        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        // Get pending bookings
        $query = Booking::where('sync_status', 'pending')
            ->orWhere('sync_status', 'failed');

        if (!$force) {
            // Don't retry bookings that were attempted in the last 5 minutes
            $query->where(function ($q) {
                $q->whereNull('last_sync_attempt')
                  ->orWhere('last_sync_attempt', '<=', now()->subMinutes(5));
            });
        }

        $pendingBookings = $query->limit($limit)->get();

        if ($pendingBookings->isEmpty()) {
            $this->info('✅ No pending bookings to sync');
            return 0;
        }

        $this->info("Found {$pendingBookings->count()} pending bookings to sync");
        $this->newLine();

        $successCount = 0;
        $failureCount = 0;

        foreach ($pendingBookings as $booking) {
            $this->line("Processing booking #{$booking->id} ({$booking->guest_first_name} {$booking->guest_surname})...");
            
            try {
                $result = $this->syncBooking($booking);
                
                if ($result['success']) {
                    $this->info("  ✅ Synced successfully! NextPax ID: {$result['nextPaxId']}");
                    $successCount++;
                } else {
                    $this->error("  ❌ Sync failed: {$result['error']}");
                    $failureCount++;
                }
            } catch (\Exception $e) {
                $this->error("  💥 Exception: {$e->getMessage()}");
                $failureCount++;
                
                // Update booking with error
                $booking->update([
                    'sync_status' => 'failed',
                    'sync_error' => 'Exception: ' . $e->getMessage(),
                    'last_sync_attempt' => now(),
                ]);
            }
            
            $this->newLine();
        }

        $this->info("🎯 Synchronization completed!");
        $this->line("  ✅ Successfully synced: {$successCount}");
        $this->line("  ❌ Failed to sync: {$failureCount}");
        $this->line("  📊 Total processed: " . ($successCount + $failureCount));

        return $failureCount === 0 ? 0 : 1;
    }

    /**
     * Sync a single booking with NextPax API
     */
    private function syncBooking(Booking $booking)
    {
        // Update sync status to syncing
        $booking->update([
            'sync_status' => 'syncing',
            'last_sync_attempt' => now(),
        ]);

        try {
            $bookingsService = new NextPaxBookingsService();
            
            // Prepare payload from stored data
            $payload = $this->buildPayloadFromBooking($booking);
            
            // Attempt to create booking via API
            $response = $bookingsService->createBooking($payload);
            
            if (isset($response['result']) && $response['result'] === 'success') {
                $nextPaxId = $response['data']['bookingId'] ?? null;
                
                // Update booking with success
                $booking->update([
                    'nextpax_booking_id' => $nextPaxId,
                    'sync_status' => 'synced',
                    'sync_error' => null,
                    'synced_at' => now(),
                    'api_response' => $response,
                ]);
                
                return [
                    'success' => true,
                    'nextPaxId' => $nextPaxId,
                ];
            } else {
                $error = 'API returned failure: ' . json_encode($response);
                
                // Update booking with failure
                $booking->update([
                    'sync_status' => 'failed',
                    'sync_error' => $error,
                ]);
                
                return [
                    'success' => false,
                    'error' => $error,
                ];
            }
            
        } catch (\Exception $e) {
            $error = 'API exception: ' . $e->getMessage();
            
            // Update booking with error
            $booking->update([
                'sync_status' => 'failed',
                'sync_error' => $error,
            ]);
            
            return [
                'success' => false,
                'error' => $error,
            ];
        }
    }

    /**
     * Build API payload from stored booking data
     */
    private function buildPayloadFromBooking(Booking $booking)
    {
        return [
            'query' => 'propertyManagerBooking',
            'payload' => [
                'bookingNumber' => $booking->booking_number,
                'propertyManager' => $booking->property_manager_code,
                'channelPartnerReference' => $booking->channel_partner_reference,
                'channelId' => $booking->channel_id,
                'propertyId' => $booking->property_id,
                'supplierPropertyId' => $booking->supplier_property_id,
                'period' => [
                    'arrivalDate' => $booking->check_in_date->format('Y-m-d'),
                    'departureDate' => $booking->check_out_date->format('Y-m-d'),
                ],
                'occupancy' => [
                    'adults' => $booking->adults,
                    'children' => $booking->children,
                    'babies' => $booking->babies,
                    'pets' => $booking->pets,
                ],
                'stayPrice' => [
                    'amount' => $booking->total_amount,
                    'currency' => $booking->currency,
                ],
                'mainBooker' => [
                    'surname' => $booking->guest_surname,
                    'letters' => substr($booking->guest_first_name, 0, 1),
                    'firstName' => $booking->guest_first_name,
                    'countryCode' => $booking->guest_country_code,
                    'language' => $booking->guest_language,
                    'zipCode' => '00000000',
                    'houseNumber' => '1',
                    'street' => 'Rua Exemplo',
                    'place' => 'São Paulo',
                    'phoneNumber' => $booking->guest_phone ?? '0000000000',
                    'email' => $booking->guest_email,
                    'dateOfBirth' => '1980-01-01',
                    'titleCode' => 'male',
                ],
                'payment' => [
                    'type' => $booking->payment_type,
                ],
                'remarks' => $booking->remarks,
            ],
        ];
    }
}
