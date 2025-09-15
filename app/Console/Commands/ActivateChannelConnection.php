<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Property;
use App\Models\Channel;
use App\Models\PropertyChannel;

class ActivateChannelConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'channels:activate 
                            {property_id : ID da propriedade}
                            {channel_id : ID do canal}
                            {--status=active : Status do canal (active, inactive, suspended)}
                            {--content=enabled : Status do conteúdo (enabled, disabled)}
                            {--auto-sync : Habilitar sincronização automática}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ativa conexão de propriedade com canal';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $propertyId = $this->argument('property_id');
        $channelId = $this->argument('channel_id');
        $status = $this->option('status');
        $contentStatus = $this->option('content');
        $autoSync = $this->option('auto-sync');

        $this->info('🔧 Ativando conexão de canal...');
        $this->newLine();

        // Buscar propriedade
        $property = Property::find($propertyId);
        if (!$property) {
            $this->error("❌ Propriedade {$propertyId} não encontrada.");
            return;
        }

        // Buscar canal
        $channel = Channel::where('channel_id', $channelId)->first();
        if (!$channel) {
            $this->error("❌ Canal {$channelId} não encontrado.");
            return;
        }

        // Verificar conexão
        $propertyChannel = $property->getChannelConnection($channelId);
        if (!$propertyChannel) {
            $this->error("❌ Propriedade não está conectada ao canal {$channelId}.");
            return;
        }

        $this->line("🏠 Propriedade: {$property->name}");
        $this->line("📡 Canal: {$channel->name}");
        $this->line("🔗 ID no Canal: {$propertyChannel->channel_property_id}");
        $this->newLine();

        // Atualizar configurações
        $propertyChannel->update([
            'channel_status' => $status,
            'content_status' => $contentStatus,
            'is_active' => true,
            'auto_sync_enabled' => $autoSync,
        ]);

        $this->info("✅ Conexão ativada com sucesso!");
        $this->line("   Status do Canal: {$status}");
        $this->line("   Status do Conteúdo: {$contentStatus}");
        $this->line("   Ativo: Sim");
        $this->line("   Auto Sync: " . ($autoSync ? 'Sim' : 'Não'));
    }
}