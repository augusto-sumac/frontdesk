<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Property;
use App\Models\User;
use App\Services\NextPaxService;

class SetupPropertyForBooking extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:property-booking {property_id? : ID da propriedade local} {--all : Configurar todas as propriedades}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configura propriedade completa para criação de reservas na NextPax';

    private NextPaxService $nextPaxService;

    public function __construct(NextPaxService $nextPaxService)
    {
        parent::__construct();
        $this->nextPaxService = $nextPaxService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Configurando propriedades para criação de reservas na NextPax...');
        $this->newLine();

        $propertyId = $this->argument('property_id');
        $all = $this->option('all');

        if ($all) {
            $properties = Property::where('channel_type', 'nextpax')
                ->whereNotNull('channel_property_id')
                ->get();
        } elseif ($propertyId) {
            $properties = Property::where('id', $propertyId)
                ->orWhere('channel_property_id', $propertyId)
                ->get();
        } else {
            $this->error('❌ Especifique um property_id ou use --all');
            return;
        }

        if ($properties->isEmpty()) {
            $this->error('❌ Nenhuma propriedade encontrada');
            return;
        }

        $this->info("📊 Configurando {$properties->count()} propriedade(s)");
        $this->newLine();

        foreach ($properties as $property) {
            $this->setupProperty($property);
        }

        $this->newLine();
        $this->info('✅ Configuração concluída! As propriedades estão prontas para reservas.');
    }

    private function setupProperty(Property $property): void
    {
        $this->line("🏠 Configurando: {$property->name}");
        $this->line("   NextPax ID: {$property->channel_property_id}");

        try {
            // 1. Obter dados completos da propriedade da API
            $this->line("   📡 Obtendo dados da API...");
            $apiProperty = $this->nextPaxService->getProperty($property->channel_property_id);
            
            if (!$apiProperty) {
                $this->error("   ❌ Propriedade não encontrada na API");
                return;
            }

            // 2. Atualizar supplier_property_id se necessário
            if (empty($property->supplier_property_id) && !empty($apiProperty['supplierPropertyId'])) {
                $property->update(['supplier_property_id' => $apiProperty['supplierPropertyId']]);
                $this->line("   ✅ supplier_property_id atualizado: {$apiProperty['supplierPropertyId']}");
            }

            // 3. Criar Rate Plan padrão
            $this->line("   💰 Criando rate plan padrão...");
            $this->createDefaultRatePlan($property);

            // 4. Configurar disponibilidade
            $this->line("   📅 Configurando disponibilidade...");
            $this->setupAvailability($property);

            // 5. Configurar preços
            $this->line("   💵 Configurando preços...");
            $this->setupPricing($property);

            // 6. Ativar propriedade
            $this->line("   🔄 Ativando propriedade...");
            $this->activateProperty($property);

            $this->info("   ✅ Propriedade configurada com sucesso!");

        } catch (\Exception $e) {
            $this->error("   ❌ Erro ao configurar propriedade: " . $e->getMessage());
        }

        $this->newLine();
    }

    private function createDefaultRatePlan(Property $property): void
    {
        try {
            $ratePlanData = [
                'ratePlanCode' => 'DEFAULT',
                'ratePlanName' => 'Plano Padrão',
                'ratePlanDescription' => 'Plano de preços padrão para reservas',
                'currency' => $property->currency ?? 'BRL',
                'cancellationPolicy' => [
                    'type' => 'flexible',
                    'freeCancellationUntil' => 24, // 24 horas antes
                    'cancellationFee' => 0
                ],
                'bookingRules' => [
                    'minStay' => 1,
                    'maxStay' => 30,
                    'advanceBooking' => 0, // Pode reservar no mesmo dia
                    'cutoffTime' => 18 // Até 18h do dia anterior
                ],
                'isActive' => true
            ];

            $this->nextPaxService->updateRatePlans($property->channel_property_id, $ratePlanData);
            $this->line("      ✅ Rate plan 'DEFAULT' criado");

        } catch (\Exception $e) {
            $this->line("      ⚠️  Rate plan já existe ou erro: " . $e->getMessage());
        }
    }

    private function setupAvailability(Property $property): void
    {
        try {
            // Configurar disponibilidade para o próximo ano
            $fromDate = date('Y-m-d');
            $untilDate = date('Y-m-d', strtotime('+1 year'));

            $availabilityData = [
                'data' => [
                    [
                        'fromDate' => $fromDate,
                        'untilDate' => $untilDate,
                        'quantity' => 1, // 1 unidade disponível
                        'restrictions' => [
                            'minStay' => 1,
                            'maxStay' => 30,
                            'departuresAllowed' => true,
                            'arrivalsAllowed' => true,
                            'closedToArrival' => false,
                            'closedToDeparture' => false
                        ]
                    ]
                ]
            ];

            $this->nextPaxService->updateAvailability($property->channel_property_id, $availabilityData);
            $this->line("      ✅ Disponibilidade configurada até {$untilDate}");

        } catch (\Exception $e) {
            throw new \Exception("Erro ao configurar disponibilidade: " . $e->getMessage());
        }
    }

    private function setupPricing(Property $property): void
    {
        try {
            $basePrice = $property->base_price ?? 100.00; // Preço padrão
            $currency = $property->currency ?? 'BRL';

            // Configurar preços para o próximo ano
            $fromDate = date('Y-m-d');
            $untilDate = date('Y-m-d', strtotime('+1 year'));

            $pricingData = [
                'currency' => $currency,
                'pricingType' => 'default',
                'ratePlanCode' => 'DEFAULT',
                'rates' => [
                    [
                        'fromDate' => $fromDate,
                        'untilDate' => $untilDate,
                        'persons' => 1,
                        'minStay' => 1,
                        'maxStay' => 30,
                        'prices' => [
                            'nightlyPrice' => (int)($basePrice * 100), // Preço em centavos
                        ]
                    ],
                    [
                        'fromDate' => $fromDate,
                        'untilDate' => $untilDate,
                        'persons' => 2,
                        'minStay' => 1,
                        'maxStay' => 30,
                        'prices' => [
                            'nightlyPrice' => (int)($basePrice * 100), // Mesmo preço para 2 pessoas
                        ]
                    ]
                ]
            ];

            $this->nextPaxService->updateRates($property->channel_property_id, $pricingData);
            $this->line("      ✅ Preços configurados: {$currency} " . number_format($basePrice, 2));

        } catch (\Exception $e) {
            throw new \Exception("Erro ao configurar preços: " . $e->getMessage());
        }
    }

    private function activateProperty(Property $property): void
    {
        try {
            // Marcar como ativa localmente
            $property->update([
                'is_active' => true,
                'status' => 'active'
            ]);

            // Ativar na NextPax (definir disponibilidade > 0 já ativa)
            $this->line("      ✅ Propriedade ativada localmente");

        } catch (\Exception $e) {
            $this->line("      ⚠️  Erro ao ativar: " . $e->getMessage());
        }
    }
}