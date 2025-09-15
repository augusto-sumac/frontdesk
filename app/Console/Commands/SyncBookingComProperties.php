<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Channel;
use App\Models\Property;
use App\Models\PropertyChannel;
use App\Services\NextPaxService;
use Illuminate\Support\Facades\Log;

class SyncBookingComProperties extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:booking-properties 
                            {--property-manager=SAFDK000046 : Código do property manager}
                            {--create-local : Criar propriedades localmente se não existirem}
                            {--dry-run : Apenas mostrar o que seria feito, sem executar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincronizar propriedades do Booking.com via NextPax API';

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
        $propertyManagerCode = $this->option('property-manager');
        $createLocal = $this->option('create-local');
        $dryRun = $this->option('dry-run');

        $this->info('🔄 Sincronizando propriedades do Booking.com via NextPax API...');
        $this->info("📊 Property Manager: {$propertyManagerCode}");
        
        if ($dryRun) {
            $this->warn('⚠️  Modo DRY RUN - Nenhuma alteração será feita');
        }

        // Buscar o canal Booking.com
        $bookingChannel = Channel::where('channel_id', 'BOO142')->first();
        
        if (!$bookingChannel) {
            $this->error('❌ Canal Booking.com (BOO142) não encontrado no sistema');
            return 1;
        }

        try {
            // Buscar propriedades da API NextPax
            $this->info('📡 Buscando propriedades da API NextPax...');
            $response = $this->nextPaxService->getProperties($propertyManagerCode);
            
            if (isset($response['error'])) {
                throw new \Exception($response['error']);
            }
            
            $apiProperties = $response['data'] ?? [];
            $this->info('✅ Encontradas ' . count($apiProperties) . ' propriedades na API');
            
            if (empty($apiProperties)) {
                $this->warn('⚠️  Nenhuma propriedade encontrada para o property manager ' . $propertyManagerCode);
                return 0;
            }

            $syncedCount = 0;
            $createdCount = 0;
            $skippedCount = 0;
            $errorCount = 0;

            // Processar cada propriedade da API
            foreach ($apiProperties as $apiProperty) {
                $propertyId = $apiProperty['propertyId'] ?? null;
                $supplierPropertyId = $apiProperty['supplierPropertyId'] ?? $propertyId;
                $status = $apiProperty['status'] ?? 'unknown';
                
                if (!$propertyId) {
                    $this->warn("⚠️  Propriedade sem ID, pulando...");
                    $skippedCount++;
                    continue;
                }

                $this->line("\n🏠 Processando propriedade: {$propertyId}");
                $this->line("   Status na API: {$status}");

                try {
                    // Tentar buscar detalhes completos da propriedade
                    $propertyDetails = null;
                    $propData = [];
                    $propGeneral = [];
                    $propAddress = [];
                    
                    try {
                        $propertyDetails = $this->nextPaxService->getProperty($propertyId);
                        if (isset($propertyDetails['property'])) {
                            $propData = $propertyDetails['property'];
                            $propGeneral = $propData['general'] ?? [];
                            $propAddress = $propData['general']['address'] ?? [];
                        }
                    } catch (\Exception $detailsError) {
                        $this->warn("   ⚠️  Não foi possível buscar detalhes completos: " . $detailsError->getMessage());
                        // Usar dados básicos da listagem
                        $propGeneral = [
                            'name' => 'Propriedade ' . substr($propertyId, 0, 8)
                        ];
                    }
                    
                    $propertyName = $propGeneral['name'] ?? $apiProperty['name'] ?? 'Propriedade ' . substr($propertyId, 0, 8);
                    
                    $this->line("   Nome: {$propertyName}");
                    
                    // Verificar se existe localmente
                    $existingProperty = Property::where('property_id', $propertyId)
                        ->orWhere('supplier_property_id', $supplierPropertyId)
                        ->first();
                    
                    if ($existingProperty) {
                        $this->line("   ✅ Propriedade já existe localmente (ID: {$existingProperty->id})");
                        
                        // Verificar conexão com Booking.com
                        $propertyChannel = PropertyChannel::where('property_id', $existingProperty->id)
                            ->where('channel_id', $bookingChannel->id)
                            ->first();
                        
                        if ($propertyChannel) {
                            if (!$dryRun) {
                                // Atualizar status da sincronização
                                $propertyChannel->update([
                                    'last_sync_at' => now(),
                                    'channel_status' => 'active',
                                    'content_status' => 'enabled',
                                    'channel_config' => array_merge(
                                        $propertyChannel->channel_config ?? [],
                                        [
                                            'api_status' => $status,
                                            'last_api_sync' => now()->toISOString(),
                                            'api_property_id' => $propertyId,
                                            'api_supplier_id' => $supplierPropertyId
                                        ]
                                    )
                                ]);
                            }
                            $this->line("   ✅ Conexão com Booking.com atualizada");
                        } else {
                            if (!$dryRun) {
                                // Criar conexão com Booking.com
                                PropertyChannel::create([
                                    'property_id' => $existingProperty->id,
                                    'channel_id' => $bookingChannel->id,
                                    'channel_property_id' => 'BOO-' . $supplierPropertyId,
                                    'channel_status' => 'active',
                                    'content_status' => 'enabled',
                                    'is_active' => true,
                                    'auto_sync_enabled' => true,
                                    'channel_config' => [
                                        'api_status' => $status,
                                        'api_property_id' => $propertyId,
                                        'api_supplier_id' => $supplierPropertyId,
                                        'source' => 'nextpax_api'
                                    ]
                                ]);
                            }
                            $this->line("   ➕ Nova conexão com Booking.com criada");
                        }
                        
                        $syncedCount++;
                        
                    } else {
                        $this->warn("   ⚠️  Propriedade não existe localmente");
                        
                        if ($createLocal) {
                            if (!$dryRun) {
                                // Criar propriedade local
                                $newProperty = Property::create([
                                    'name' => $propertyName,
                                    'property_id' => $propertyId,
                                    'supplier_property_id' => $supplierPropertyId,
                                    'property_manager_code' => $propertyManagerCode,
                                    'address' => $propAddress['street'] ?? '',
                                    'city' => $propAddress['city'] ?? '',
                                    'state' => $propAddress['state'] ?? '',
                                    'country' => $propAddress['countryCode'] ?? 'BR',
                                    'postal_code' => $propAddress['postalCode'] ?? '',
                                    'max_occupancy' => $propGeneral['maxOccupancy'] ?? 2,
                                    'max_adults' => $propGeneral['maxAdults'] ?? 2,
                                    'max_children' => $propGeneral['maxChildren'] ?? 0,
                                    'property_type' => $propGeneral['typeCode'] ?? 'APARTMENT',
                                    'status' => 'active',
                                    'is_active' => true
                                ]);
                                
                                // Criar conexão com Booking.com
                                PropertyChannel::create([
                                    'property_id' => $newProperty->id,
                                    'channel_id' => $bookingChannel->id,
                                    'channel_property_id' => 'BOO-' . $supplierPropertyId,
                                    'channel_status' => 'active',
                                    'content_status' => 'enabled',
                                    'is_active' => true,
                                    'auto_sync_enabled' => true,
                                    'channel_config' => [
                                        'api_status' => $status,
                                        'api_property_id' => $propertyId,
                                        'api_supplier_id' => $supplierPropertyId,
                                        'source' => 'nextpax_api'
                                    ]
                                ]);
                            }
                            
                            $this->info("   ➕ Propriedade criada localmente com conexão ao Booking.com");
                            $createdCount++;
                            $syncedCount++;
                        } else {
                            $this->line("   ℹ️  Use --create-local para criar esta propriedade localmente");
                            $skippedCount++;
                        }
                    }
                    
                } catch (\Exception $e) {
                    $this->error("   ❌ Erro ao processar: " . $e->getMessage());
                    $errorCount++;
                }
            }

            // Resumo final
            $this->newLine();
            $this->info('📊 Resumo da Sincronização:');
            $this->line("   ✅ Sincronizadas: {$syncedCount}");
            $this->line("   ➕ Criadas: {$createdCount}");
            $this->line("   ⏭️  Puladas: {$skippedCount}");
            $this->line("   ❌ Erros: {$errorCount}");
            
            if ($dryRun) {
                $this->warn('⚠️  Modo DRY RUN - Nenhuma alteração foi feita');
            }

            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Erro ao conectar com a API NextPax: ' . $e->getMessage());
            Log::error('Erro na sincronização do Booking.com:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}