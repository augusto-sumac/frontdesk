<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NextPaxService;

class TestSupplyProperties extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:supply-properties';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test fetching properties from the supply system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing supply properties...');

        $nextPaxService = new NextPaxService();

        // Test with different property managers
        $propertyManagers = ['SAFDK000034', 'SAFDK000036'];

        foreach ($propertyManagers as $pm) {
            $this->info("Testing property manager: {$pm}");
            
            try {
                // Try to get properties from supply system
                $properties = $nextPaxService->getProperties($pm);
                $this->info("✅ Property manager {$pm} exists in supply system");
                $this->line("Properties count: " . count($properties['data'] ?? []));
                
                if (!empty($properties['data'])) {
                    $this->info("Properties found:");
                    foreach ($properties['data'] as $i => $property) {
                        $propertyId = $property['propertyId'] ?? 'N/A';
                        $name = $property['general']['name'] ?? 'N/A';
                        $supplierPropertyId = $property['supplierPropertyId'] ?? 'N/A';
                        $this->line("  {$i}: {$name} (NextPax: {$propertyId}, Supplier: {$supplierPropertyId})");
                    }
                } else {
                    $this->warn("⚠️ No properties found for {$pm}");
                }
                
            } catch (\Exception $e) {
                $this->error("❌ Property manager {$pm} not found in supply system: " . $e->getMessage());
            }
            
            $this->line('');
        }
    }
}
