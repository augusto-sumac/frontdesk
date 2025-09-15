<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Channel;
use App\Models\Property;
use App\Models\PropertyChannel;
use App\Services\NextPaxService;
use Illuminate\Support\Facades\Log;

class PushPropertiesToBooking extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:properties-to-booking 
                            {--property-id= : ID específico da propriedade local para enviar}
                            {--all-local : Enviar todas as propriedades locais}
                            {--property-manager=SAFDK000046 : Código do property manager}
                            {--dry-run : Apenas mostrar o que seria feito, sem executar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar propriedades locais para a API NextPax/Booking.com';

    protected $nextPaxService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(NextPaxService $nextPaxService)
    {
        parent::__construct();
        $this->nextPaxService = $nextPaxService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $propertyId = $this->option('property-id');
        $allLocal = $this->option('all-local');
        $propertyManagerCode = $this->option('property-manager');
        $dryRun = $this->option('dry-run');

        $this->info('📤 Enviando propriedades locais para NextPax/Booking.com...');
        $this->info("📊 Property Manager: {$propertyManagerCode}");
        
        if ($dryRun) {
            $this->warn('⚠️  Modo DRY RUN - Nenhuma alteração será feita');
        }

        // Determinar quais propriedades enviar
        if ($propertyId) {
            $properties = Property::where('id', $propertyId)
                ->whereNull('property_id') // Apenas propriedades sem ID da API
                ->get();
                
            if ($properties->isEmpty()) {
                $this->error('❌ Propriedade não encontrada ou já possui ID da API');
                return 1;
            }
        } elseif ($allLocal) {
            $properties = Property::whereNull('property_id')->get();
            
            if ($properties->isEmpty()) {
                $this->warn('⚠️  Nenhuma propriedade local encontrada para enviar');
                return 0;
            }
        } else {
            $this->error('❌ Especifique --property-id=ID ou --all-local');
            return 1;
        }

        $this->info('📋 Encontradas ' . $properties->count() . ' propriedade(s) para enviar');

        $successCount = 0;
        $errorCount = 0;
        $skippedCount = 0;

        foreach ($properties as $property) {
            $this->line("\n🏠 Processando: {$property->name} (ID Local: {$property->id})");
            
            try {
                // Preparar dados para enviar à API
                $propertyData = $this->preparePropertyData($property, $propertyManagerCode);
                
                $this->line("   📝 Dados preparados:");
                $this->line("   - Nome: " . $propertyData['general']['name']);
                $this->line("   - Tipo: " . ($propertyData['general']['typeCode'] ?? 'APARTMENT'));
                $this->line("   - Cidade: " . ($propertyData['general']['address']['city'] ?? 'N/A'));
                
                if (!$dryRun) {
                    // Enviar para a API NextPax
                    $this->info("   📡 Enviando para NextPax API...");
                    
                    $response = $this->nextPaxService->createProperty($propertyData);
                    
                    if (isset($response['data']['propertyId'])) {
                        $apiPropertyId = $response['data']['propertyId'];
                        $this->info("   ✅ Propriedade criada na API! ID: {$apiPropertyId}");
                        
                        // Atualizar propriedade local com o ID da API
                        $property->update([
                            'property_id' => $apiPropertyId,
                            'supplier_property_id' => $response['data']['supplierPropertyId'] ?? $apiPropertyId
                        ]);
                        
                        // Atualizar conexão com Booking.com se existir
                        $bookingChannel = Channel::where('channel_id', 'BOO142')->first();
                        if ($bookingChannel) {
                            $propertyChannel = PropertyChannel::where('property_id', $property->id)
                                ->where('channel_id', $bookingChannel->id)
                                ->first();
                                
                            if ($propertyChannel) {
                                $propertyChannel->update([
                                    'channel_property_id' => 'BOO-' . $apiPropertyId,
                                    'auto_sync_enabled' => true,
                                    'channel_config' => array_merge(
                                        $propertyChannel->channel_config ?? [],
                                        [
                                            'source' => 'nextpax_api',
                                            'api_property_id' => $apiPropertyId,
                                            'pushed_at' => now()->toISOString()
                                        ]
                                    )
                                ]);
                                $this->line("   ✅ Conexão com Booking.com atualizada");
                            }
                        }
                        
                        $successCount++;
                    } else {
                        $this->error("   ❌ Erro: Resposta inesperada da API");
                        $this->line("   Resposta: " . json_encode($response));
                        $errorCount++;
                    }
                } else {
                    $this->info("   ✅ [DRY RUN] Propriedade seria enviada para a API");
                    $successCount++;
                }
                
            } catch (\Exception $e) {
                $this->error("   ❌ Erro ao processar: " . $e->getMessage());
                Log::error('Erro ao enviar propriedade para API:', [
                    'property_id' => $property->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $errorCount++;
            }
        }

        // Resumo final
        $this->newLine();
        $this->info('📊 Resumo do Envio:');
        $this->line("   ✅ Enviadas com sucesso: {$successCount}");
        $this->line("   ❌ Erros: {$errorCount}");
        $this->line("   ⏭️  Puladas: {$skippedCount}");
        
        if ($dryRun) {
            $this->warn('⚠️  Modo DRY RUN - Nenhuma alteração foi feita');
        }

        return $errorCount > 0 ? 1 : 0;
    }

    /**
     * Preparar dados da propriedade para enviar à API
     */
    private function preparePropertyData(Property $property, string $propertyManagerCode): array
    {
        // Mapear tipo de propriedade - usando códigos da API NextPax
        $propertyTypeMap = [
            'apartment' => 'APP',  // Apartment
            'house' => 'HOU',      // House
            'villa' => 'VIL',      // Villa
            'studio' => 'STU',     // Studio
            'condo' => 'CON',      // Condominium
            'hotel' => 'HOT',      // Hotel
            'hostel' => 'HOS',     // Hostel
            'bnb' => 'BNB',        // Bed and Breakfast
            'APARTMENT' => 'APP'   // Fallback para o valor atual
        ];
        
        $propertyType = $propertyTypeMap[strtolower($property->property_type)] ?? 
                       $propertyTypeMap[$property->property_type] ?? 
                       'APP'; // Default para apartamento
        
        // Estrutura de dados conforme a API NextPax
        return [
            'propertyManager' => $propertyManagerCode,
            'supplierPropertyId' => 'LOCAL-' . $property->id, // ID único para referência
            'general' => [
                'name' => $property->name,
                'typeCode' => $propertyType,
                'classification' => 'single-unit', // Unidade única
                'address' => [
                    'street' => $property->address ?: 'Rua Exemplo',
                    'city' => $property->city ?: 'São Paulo',
                    'state' => $property->state ?: 'SP',
                    'countryCode' => $property->country ?: 'BR',
                    'postalCode' => $property->postal_code ?: '00000-000'
                ],
                'geoLocation' => [
                    'latitude' => $property->latitude ?: -23.550520, // São Paulo default
                    'longitude' => $property->longitude ?: -46.633308
                ],
                'maxOccupancy' => $property->max_occupancy ?: 2,
                'maxAdults' => $property->max_adults ?: 2,
                'maxChildren' => $property->max_children ?: 0,
                'bedrooms' => $property->bedrooms ?: 1,
                'bathrooms' => $property->bathrooms ?: 1,
                'baseCurrency' => $property->currency ?: 'BRL',
                'description' => [
                    'brief' => $property->description ?: 'Propriedade confortável e bem localizada.',
                    'general' => $property->description ?: 'Esta propriedade oferece todo o conforto necessário para uma estadia agradável.'
                ]
            ],
            'contacts' => [
                'reservations' => [
                    'name' => 'Reservas',
                    'phone' => '+55 11 99999-9999',
                    'email' => 'reservas@example.com'
                ]
            ],
            'ratesAndAvailabilitySettings' => [
                'basePrice' => [
                    'amount' => $property->base_price ?: 100.00,
                    'currency' => $property->currency ?: 'BRL'
                ],
                'minimumStay' => 1,
                'maximumStay' => 30
            ],
            'policies' => [
                'checkIn' => [
                    'from' => '14:00',
                    'until' => '22:00'
                ],
                'checkOut' => [
                    'from' => '08:00',
                    'until' => '11:00'
                ],
                'cancellation' => [
                    'type' => 'FLEXIBLE',
                    'description' => 'Cancelamento gratuito até 24 horas antes do check-in'
                ]
            ]
        ];
    }
}
