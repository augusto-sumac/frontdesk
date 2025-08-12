<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NextPaxBookingsService;

class TestBookingsProperties extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:bookings-properties';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test fetching properties from the bookings system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing bookings properties...');

        $bookingsService = new NextPaxBookingsService();

        // Test with different property managers
        $propertyManagers = ['SAFDK000034', 'SAFDK000036'];

        foreach ($propertyManagers as $pm) {
            $this->info("Testing property manager: {$pm}");
            
            try {
                // Try to get bookings to see if the PM exists
                $bookings = $bookingsService->getBookings($pm);
                $this->info("✅ Property manager {$pm} exists in bookings system");
                $this->line("Bookings response: " . json_encode($bookings, JSON_PRETTY_PRINT));
                
                // Try to get supplier payment provider
                try {
                    $paymentProvider = $bookingsService->getSupplierPaymentProvider($pm);
                    $this->info("✅ Payment provider found for {$pm}");
                    $this->line("Payment provider: " . json_encode($paymentProvider, JSON_PRETTY_PRINT));
                } catch (\Exception $e) {
                    $this->warn("⚠️ Payment provider not found for {$pm}: " . $e->getMessage());
                }
                
            } catch (\Exception $e) {
                $this->error("❌ Property manager {$pm} not found in bookings system: " . $e->getMessage());
            }
            
            $this->line('');
        }
    }
}
