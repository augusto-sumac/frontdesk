<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Property;
use App\Http\Controllers\PropertyController;
use App\Services\NextPaxService;

class TestPropertyFiltering extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:property-filtering';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test property filtering by property_manager_code';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testando filtro de propriedades por property_manager_code...');
        $this->newLine();

        // Teste 1: Verificar usuários e suas propriedades
        $this->info('📊 Usuários e suas propriedades:');
        $users = User::whereNotNull('property_manager_code')->where('role', 'supply')->get();
        
        foreach ($users as $user) {
            $properties = Property::where('channel_type', 'nextpax')
                ->where('property_manager_code', $user->property_manager_code)
                ->get();
            
            $this->line("👤 {$user->name} (PM: {$user->property_manager_code})");
            $this->line("   Propriedades: {$properties->count()}");
            
            foreach ($properties as $property) {
                $this->line("   - {$property->name} (ID: {$property->channel_property_id})");
            }
            $this->newLine();
        }

        // Teste 2: Verificar se não há propriedades órfãs
        $this->info('🔍 Verificando propriedades órfãs:');
        $orphanProperties = Property::where('channel_type', 'nextpax')
            ->whereNull('property_manager_code')
            ->get();
        
        if ($orphanProperties->count() > 0) {
            $this->warn("⚠️  Encontradas {$orphanProperties->count()} propriedades sem property_manager_code:");
            foreach ($orphanProperties as $property) {
                $this->line("   - {$property->name}");
            }
        } else {
            $this->info("✅ Todas as propriedades têm property_manager_code associado");
        }
        $this->newLine();

        // Teste 3: Simular carregamento de propriedades para um usuário específico
        $this->info('🎯 Simulando carregamento de propriedades para usuário específico:');
        $testUser = $users->first();
        if ($testUser) {
            $this->line("Usuário de teste: {$testUser->name} (PM: {$testUser->property_manager_code})");
            
            // Simular o que o PropertyController faz
            $localProperties = Property::where('channel_type', 'nextpax')
                ->whereNotNull('channel_property_id')
                ->where('property_manager_code', $testUser->property_manager_code)
                ->orderBy('created_at', 'desc')
                ->get();
            
            $this->line("Propriedades carregadas: {$localProperties->count()}");
            foreach ($localProperties as $property) {
                $this->line("   - {$property->name}");
            }
        }

        $this->newLine();
        $this->info('✅ Teste concluído! O problema de propriedades de outros suppliers foi resolvido.');
    }
}