<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PropertyChannel;
use App\Models\Channel;
use App\Models\Property;
use App\Services\ChannelSyncService;
use Illuminate\Support\Facades\Log;

class AutoSyncChannels extends Command
{
    protected $signature = 'sync:auto 
                            {--channel= : Canal específico para sincronizar}
                            {--property= : Propriedade específica para sincronizar}
                            {--force : Forçar sincronização mesmo se não precisar}
                            {--dry-run : Apenas simular, não executar}';

    protected $description = 'Executa sincronização automática de todos os canais';

    private ChannelSyncService $syncService;

    public function __construct(ChannelSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    public function handle()
    {
        $channelId = $this->option('channel');
        $propertyId = $this->option('property');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        $this->info('🔄 Iniciando sincronização automática...');
        $this->newLine();

        if ($dryRun) {
            $this->warn('🧪 Modo DRY-RUN ativado - nenhuma alteração será feita');
            $this->newLine();
        }

        if ($propertyId) {
            $this->syncSpecificProperty($propertyId, $force, $dryRun);
        } elseif ($channelId) {
            $this->syncSpecificChannel($channelId, $force, $dryRun);
        } else {
            $this->syncAllChannels($force, $dryRun);
        }

        $this->newLine();
        $this->info('✅ Sincronização automática concluída!');
    }

    private function syncSpecificProperty(int $propertyId, bool $force, bool $dryRun): void
    {
        $property = Property::find($propertyId);
        if (!$property) {
            $this->error("Propriedade {$propertyId} não encontrada.");
            return;
        }

        $this->line("🏠 Sincronizando propriedade: {$property->name}");
        $this->line("   ID NextPax: {$property->channel_property_id}");
        $this->line("   Supplier ID: {$property->supplier_property_id}");
        $this->newLine();

        $connections = $property->propertyChannels()
            ->where('is_active', true)
            ->where('auto_sync_enabled', true)
            ->get();

        if ($connections->isEmpty()) {
            $this->warn('Nenhuma conexão ativa encontrada para esta propriedade.');
            return;
        }

        foreach ($connections as $connection) {
            $this->syncConnection($connection, $force, $dryRun);
        }
    }

    private function syncSpecificChannel(string $channelId, bool $force, bool $dryRun): void
    {
        $channel = Channel::where('channel_id', $channelId)->first();
        if (!$channel) {
            $this->error("Canal {$channelId} não encontrado.");
            return;
        }

        $this->line("📡 Sincronizando canal: {$channel->name}");
        $this->newLine();

        $connections = PropertyChannel::where('channel_id', $channel->id)
            ->where('is_active', true)
            ->where('auto_sync_enabled', true)
            ->get();

        if ($connections->isEmpty()) {
            $this->warn('Nenhuma conexão ativa encontrada para este canal.');
            return;
        }

        foreach ($connections as $connection) {
            $this->syncConnection($connection, $force, $dryRun);
        }
    }

    private function syncAllChannels(bool $force, bool $dryRun): void
    {
        $this->line("🌐 Sincronizando todos os canais...");
        $this->newLine();

        $connections = PropertyChannel::where('is_active', true)
            ->where('auto_sync_enabled', true)
            ->with(['property', 'channel'])
            ->get();

        if ($connections->isEmpty()) {
            $this->warn('Nenhuma conexão ativa encontrada.');
            return;
        }

        $this->line("Encontradas {$connections->count()} conexões para sincronizar:");
        $this->newLine();

        $successCount = 0;
        $errorCount = 0;

        foreach ($connections as $connection) {
            $result = $this->syncConnection($connection, $force, $dryRun);
            if ($result) {
                $successCount++;
            } else {
                $errorCount++;
            }
        }

        $this->newLine();
        $this->info("📊 Resumo da sincronização:");
        $this->line("   ✅ Sucessos: {$successCount}");
        $this->line("   ❌ Erros: {$errorCount}");
        $this->line("   📈 Taxa de sucesso: " . round(($successCount / ($successCount + $errorCount)) * 100, 2) . "%");
    }

    private function syncConnection(PropertyChannel $connection, bool $force, bool $dryRun): bool
    {
        $property = $connection->property;
        $channel = $connection->channel;

        $this->line("🔄 Sincronizando: {$property->name} → {$channel->name}");

        // Verificar se precisa sincronizar
        if (!$force && !$connection->needsSync()) {
            $this->line("   ⏭️  Sincronização não necessária");
            return true;
        }

        if ($dryRun) {
            $this->line("   🧪 [DRY-RUN] Simulando sincronização...");
            $this->line("   📡 Canal: {$channel->channel_id}");
            $this->line("   🏠 Propriedade: {$property->name}");
            $this->line("   🔗 ID no Canal: {$connection->channel_property_id}");
            $this->line("   ✅ [DRY-RUN] Sincronização simulada com sucesso");
            return true;
        }

        try {
            // Executar sincronização
            $this->syncService->syncPropertyWithChannel($property, $channel, $connection);
            
            $this->line("   ✅ Sincronização realizada com sucesso");
            return true;

        } catch (\Exception $e) {
            $this->error("   ❌ Erro na sincronização: " . $e->getMessage());
            
            // Log do erro
            Log::error('Auto sync error', [
                'property_id' => $property->id,
                'channel_id' => $channel->channel_id,
                'error' => $e->getMessage(),
                'connection_id' => $connection->id
            ]);

            return false;
        }
    }
}