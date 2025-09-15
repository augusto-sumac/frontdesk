<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Property;
use App\Models\User;
use App\Services\NextPaxService;

class ValidatePropertiesForBooking extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'validate:properties-booking {--fix : Tentar corrigir problemas encontrados}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Valida se as propriedades estão prontas para criação de reservas';

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
        $this->info('🔍 Validando propriedades para criação de reservas...');
        $this->newLine();

        $fixMode = $this->option('fix');
        if ($fixMode) {
            $this->warn('⚠️  Modo de correção ativado - tentará corrigir problemas encontrados');
            $this->newLine();
        }

        $properties = Property::where('channel_type', 'nextpax')
            ->whereNotNull('channel_property_id')
            ->get();

        if ($properties->isEmpty()) {
            $this->error('❌ Nenhuma propriedade NextPax encontrada');
            return;
        }

        $this->info("📊 Encontradas {$properties->count()} propriedades para validação");
        $this->newLine();

        $issues = [];
        $fixed = 0;

        foreach ($properties as $property) {
            $this->line("🏠 Validando: {$property->name}");
            $this->line("   ID: {$property->channel_property_id}");
            
            $propertyIssues = $this->validateProperty($property, $fixMode);
            
            if (empty($propertyIssues)) {
                $this->info("   ✅ Propriedade OK");
            } else {
                $this->warn("   ⚠️  Problemas encontrados:");
                foreach ($propertyIssues as $issue) {
                    $this->line("      - {$issue}");
                }
                $issues = array_merge($issues, $propertyIssues);
                
                if ($fixMode) {
                    $fixed += $this->attemptFix($property, $propertyIssues);
                }
            }
            $this->newLine();
        }

        // Resumo
        $this->info('📋 RESUMO DA VALIDAÇÃO:');
        $this->line("Propriedades validadas: {$properties->count()}");
        $this->line("Problemas encontrados: " . count($issues));
        
        if ($fixMode) {
            $this->line("Problemas corrigidos: {$fixed}");
        }

        if (empty($issues)) {
            $this->info('🎉 Todas as propriedades estão prontas para reservas!');
        } else {
            $this->warn('⚠️  Algumas propriedades precisam de atenção antes de criar reservas');
            if (!$fixMode) {
                $this->line('💡 Execute com --fix para tentar corrigir automaticamente');
            }
        }
    }

    private function validateProperty(Property $property, bool $fixMode = false): array
    {
        $issues = [];

        // 1. Verificar se tem supplier_property_id
        if (empty($property->supplier_property_id)) {
            $issues[] = "Falta supplier_property_id";
        }

        // 2. Verificar se está ativa
        if (!$property->is_active) {
            $issues[] = "Propriedade não está ativa";
        }

        // 3. Verificar status
        if ($property->status !== 'active') {
            $issues[] = "Status não é 'active' (atual: {$property->status})";
        }

        // 4. Verificar dados básicos obrigatórios
        if (empty($property->name)) {
            $issues[] = "Nome da propriedade está vazio";
        }

        if (empty($property->address)) {
            $issues[] = "Endereço está vazio";
        }

        if (empty($property->city)) {
            $issues[] = "Cidade está vazia";
        }

        // 5. Verificar dados da API NextPax
        try {
            $apiProperty = $this->nextPaxService->getProperty($property->channel_property_id);
            
            if (!$apiProperty) {
                $issues[] = "Propriedade não encontrada na API NextPax";
            } else {
                // Verificar se tem supplierPropertyId na API
                if (empty($apiProperty['supplierPropertyId'])) {
                    $issues[] = "API não retorna supplierPropertyId";
                }

                // Verificar se tem rate plans
                try {
                    $ratePlans = $this->nextPaxService->getRatePlans($property->channel_property_id);
                    if (empty($ratePlans['data'])) {
                        $issues[] = "Nenhum rate plan configurado";
                    }
                } catch (\Exception $e) {
                    $issues[] = "Erro ao verificar rate plans: " . $e->getMessage();
                }

                // Verificar disponibilidade
                try {
                    $availability = $this->nextPaxService->getAvailability($property->channel_property_id);
                    if (empty($availability['data'])) {
                        $issues[] = "Nenhuma disponibilidade configurada";
                    }
                } catch (\Exception $e) {
                    $issues[] = "Erro ao verificar disponibilidade: " . $e->getMessage();
                }

                // Verificar preços
                try {
                    $rates = $this->nextPaxService->getRates($property->channel_property_id);
                    if (empty($rates['data'])) {
                        $issues[] = "Nenhum preço configurado";
                    }
                } catch (\Exception $e) {
                    $issues[] = "Erro ao verificar preços: " . $e->getMessage();
                }
            }
        } catch (\Exception $e) {
            $issues[] = "Erro ao consultar API: " . $e->getMessage();
        }

        return $issues;
    }

    private function attemptFix(Property $property, array $issues): int
    {
        $fixed = 0;

        foreach ($issues as $issue) {
            try {
                if (str_contains($issue, 'Falta supplier_property_id')) {
                    // Tentar obter supplier_property_id da API
                    $apiProperty = $this->nextPaxService->getProperty($property->channel_property_id);
                    if (!empty($apiProperty['supplierPropertyId'])) {
                        $property->update(['supplier_property_id' => $apiProperty['supplierPropertyId']]);
                        $this->line("      ✅ Corrigido: supplier_property_id = {$apiProperty['supplierPropertyId']}");
                        $fixed++;
                    }
                } elseif (str_contains($issue, 'Propriedade não está ativa')) {
                    // Tentar ativar a propriedade
                    $property->update(['is_active' => true]);
                    $this->line("      ✅ Corrigido: propriedade marcada como ativa");
                    $fixed++;
                } elseif (str_contains($issue, "Status não é 'active'")) {
                    // Tentar alterar status para active
                    $property->update(['status' => 'active']);
                    $this->line("      ✅ Corrigido: status alterado para 'active'");
                    $fixed++;
                } elseif (str_contains($issue, 'Nenhuma disponibilidade configurada')) {
                    // Tentar criar disponibilidade básica
                    $this->createBasicAvailability($property);
                    $this->line("      ✅ Tentativa de correção: disponibilidade básica criada");
                    $fixed++;
                } elseif (str_contains($issue, 'Nenhum preço configurado')) {
                    // Tentar criar preços básicos
                    $this->createBasicRates($property);
                    $this->line("      ✅ Tentativa de correção: preços básicos criados");
                    $fixed++;
                }
            } catch (\Exception $e) {
                $this->line("      ❌ Erro ao corrigir '{$issue}': " . $e->getMessage());
            }
        }

        return $fixed;
    }

    private function createBasicAvailability(Property $property): void
    {
        try {
            $data = [
                'data' => [
                    [
                        'fromDate' => date('Y-m-d'),
                        'untilDate' => date('Y-m-d', strtotime('+1 year')),
                        'quantity' => 1,
                        'restrictions' => [
                            'minStay' => 1,
                            'maxStay' => 30,
                            'departuresAllowed' => true,
                            'arrivalsAllowed' => true
                        ]
                    ]
                ]
            ];
            
            $this->nextPaxService->updateAvailability($property->channel_property_id, $data);
        } catch (\Exception $e) {
            throw new \Exception("Erro ao criar disponibilidade: " . $e->getMessage());
        }
    }

    private function createBasicRates(Property $property): void
    {
        try {
            $basePrice = $property->base_price ?? 100.00; // Preço padrão se não tiver
            
            $data = [
                'currency' => $property->currency ?? 'BRL',
                'pricingType' => 'default',
                'rates' => [
                    [
                        'fromDate' => date('Y-m-d'),
                        'untilDate' => date('Y-m-d', strtotime('+1 year')),
                        'persons' => 1,
                        'minStay' => 1,
                        'maxStay' => 30,
                        'prices' => [
                            'nightlyPrice' => (int)($basePrice * 100), // Preço em centavos
                        ]
                    ]
                ]
            ];
            
            $this->nextPaxService->updateRates($property->channel_property_id, $data);
        } catch (\Exception $e) {
            throw new \Exception("Erro ao criar preços: " . $e->getMessage());
        }
    }
}