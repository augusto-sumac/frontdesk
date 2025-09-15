<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Property;
use App\Services\NextPaxService;

class ConfigurePropertyPricing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'configure:property-pricing {property_id : ID da propriedade local}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configura rate plans e preços para uma propriedade específica';

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
        $propertyId = $this->argument('property_id');
        
        $property = Property::where('id', $propertyId)
            ->orWhere('channel_property_id', $propertyId)
            ->first();

        if (!$property) {
            $this->error('❌ Propriedade não encontrada');
            return;
        }

        $this->info("🏠 Configurando preços para: {$property->name}");
        $this->line("   NextPax ID: {$property->channel_property_id}");
        $this->line("   Supplier ID: {$property->supplier_property_id}");
        $this->newLine();

        try {
            // 1. Criar Rate Plan
            $this->info('1️⃣ Criando Rate Plan...');
            $this->createRatePlan($property);

            // 2. Configurar Preços
            $this->info('2️⃣ Configurando Preços...');
            $this->configureRates($property);

            $this->newLine();
            $this->info('✅ Configuração de preços concluída!');

        } catch (\Exception $e) {
            $this->error('❌ Erro na configuração: ' . $e->getMessage());
        }
    }

    private function createRatePlan(Property $property): void
    {
        try {
            $ratePlanData = [
                'ratePlanCode' => 'DEFAULT',
                'ratePlanName' => 'Plano Padrão',
                'ratePlanDescription' => 'Plano de preços padrão para reservas',
                'currency' => $property->currency ?? 'BRL',
                'cancellationPolicy' => [
                    'type' => 'flexible',
                    'freeCancellationUntil' => 24,
                    'cancellationFee' => 0
                ],
                'bookingRules' => [
                    'minStay' => 1,
                    'maxStay' => 30,
                    'advanceBooking' => 0,
                    'cutoffTime' => 18
                ],
                'isActive' => true
            ];

            $response = $this->nextPaxService->updateRatePlans($property->channel_property_id, $ratePlanData);
            
            if ($response) {
                $this->info('   ✅ Rate Plan criado com sucesso');
                $this->line('   Código: DEFAULT');
                $this->line('   Nome: Plano Padrão');
            } else {
                $this->warn('   ⚠️  Resposta inesperada da API');
            }

        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'already exists') || str_contains($e->getMessage(), 'duplicate')) {
                $this->info('   ✅ Rate Plan já existe');
            } else {
                throw new \Exception("Erro ao criar rate plan: " . $e->getMessage());
            }
        }
    }

    private function configureRates(Property $property): void
    {
        try {
            $basePrice = $property->base_price ?? 100.00;
            $currency = $property->currency ?? 'BRL';

            // Configurar preços para os próximos 12 meses
            $fromDate = date('Y-m-d');
            $untilDate = date('Y-m-d', strtotime('+12 months'));

            $ratesData = [
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
                            'nightlyPrice' => (int)($basePrice * 100) // Preço em centavos
                        ]
                    ],
                    [
                        'fromDate' => $fromDate,
                        'untilDate' => $untilDate,
                        'persons' => 2,
                        'minStay' => 1,
                        'maxStay' => 30,
                        'prices' => [
                            'nightlyPrice' => (int)($basePrice * 100) // Mesmo preço para 2 pessoas
                        ]
                    ],
                    [
                        'fromDate' => $fromDate,
                        'untilDate' => $untilDate,
                        'persons' => 3,
                        'minStay' => 1,
                        'maxStay' => 30,
                        'prices' => [
                            'nightlyPrice' => (int)($basePrice * 100) // Mesmo preço para 3 pessoas
                        ]
                    ],
                    [
                        'fromDate' => $fromDate,
                        'untilDate' => $untilDate,
                        'persons' => 4,
                        'minStay' => 1,
                        'maxStay' => 30,
                        'prices' => [
                            'nightlyPrice' => (int)($basePrice * 100) // Mesmo preço para 4 pessoas
                        ]
                    ]
                ]
            ];

            $response = $this->nextPaxService->updateRates($property->channel_property_id, $ratesData);
            
            if ($response) {
                $this->info('   ✅ Preços configurados com sucesso');
                $this->line("   Moeda: {$currency}");
                $this->line("   Preço base: " . number_format($basePrice, 2));
                $this->line("   Período: {$fromDate} até {$untilDate}");
                $this->line("   Pessoas: 1-4 (mesmo preço)");
            } else {
                $this->warn('   ⚠️  Resposta inesperada da API');
            }

        } catch (\Exception $e) {
            throw new \Exception("Erro ao configurar preços: " . $e->getMessage());
        }
    }
}