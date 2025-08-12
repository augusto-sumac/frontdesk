<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NextPaxService;
use App\Models\Property;
use Illuminate\Support\Str;

class TestPropertyCreation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:property-creation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test property creation to debug API response';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing property creation...');

        $nextPaxService = new NextPaxService();

        // Create a test property payload
        $tmp = new Property();
        $tmp->property_id = 'prop-' . Str::random(12);
        $tmp->currency = 'BRL';

        $apiPayload = [
            'supplierPropertyId' => $tmp->property_id,
            'propertyManager' => 'SAFDK000036', // Use the actual property manager code
            'general' => [
                'name' => 'Test Property Debug',
                'typeCode' => 'APP',
                'baseCurrency' => 'BRL',
                'minOccupancy' => 1,
                'maxOccupancy' => 4,
                'maxAdults' => 2,
                'classification' => 'single-unit',
                'address' => [
                    'apt' => '',
                    'city' => 'São Paulo',
                    'countryCode' => 'BR',
                    'street' => 'Rua Teste',
                    'postalCode' => '01234-567',
                    'state' => 'BR_SP',
                ],
                'geoLocation' => [
                    'latitude' => -23.5505,
                    'longitude' => -46.6333,
                ],
                'checkInOutTimes' => [
                    'checkInFrom' => '14:00',
                    'checkInUntil' => '22:00',
                    'checkOutFrom' => '08:00',
                    'checkOutUntil' => '11:00',
                ],
            ],
                'contacts' => [
                    [
                        'typeCode' => 'OWNER',
                        'firstName' => 'Test',
                        'surname' => 'Owner',
                        'email' => 'test@example.com',
                        'phoneNumber' => '11999999999',
                    ],
                ],
                'ratesAndAvailabilitySettings' => [
                    'maxOccupancy' => 4,
                    'maxAdults' => 2,
                    'maxChildren' => 2,
                    'bedrooms' => 2,
                    'bathrooms' => 1,
                ],
        ];

        try {
            $this->info('Sending request to NextPax API...');
            $this->line('Payload: ' . json_encode($apiPayload, JSON_PRETTY_PRINT));

            $apiResponse = $nextPaxService->createProperty($apiPayload);

            $this->info('API Response:');
            $this->line(json_encode($apiResponse, JSON_PRETTY_PRINT));

            // Check if propertyId is in the response
            if (isset($apiResponse['data']['propertyId'])) {
                $this->info('✅ propertyId found: ' . $apiResponse['data']['propertyId']);
            } else {
                $this->warn('❌ propertyId not found in response');
                $this->line('Available keys: ' . implode(', ', array_keys($apiResponse)));
            }

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
