<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Channel;
use App\Models\Property;
use App\Models\PropertyChannel;
use App\Models\User;
use App\Services\ChannelSyncService;

class ManageChannels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'channels:manage 
                            {action : Ação a executar (list, connect, disconnect, sync, status)}
                            {--property= : ID da propriedade}
                            {--channel= : ID do canal}
                            {--channel-property-id= : ID da propriedade no canal}
                            {--channel-room-id= : ID do quarto no canal}
                            {--channel-url= : URL da propriedade no canal}
                            {--active : Ativar conexão}
                            {--auto-sync : Habilitar sincronização automática}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gerencia canais de distribuição (Airbnb, Booking.com, etc.)';

    private ChannelSyncService $syncService;

    public function __construct(ChannelSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'list':
                $this->listChannels();
                break;
            case 'connect':
                $this->connectProperty();
                break;
            case 'disconnect':
                $this->disconnectProperty();
                break;
            case 'sync':
                $this->syncProperty();
                break;
            case 'status':
                $this->showStatus();
                break;
            default:
                $this->error("Ação '{$action}' não reconhecida.");
                $this->showHelp();
        }
    }

    private function listChannels(): void
    {
        $this->info('📡 Canais de Distribuição Disponíveis:');
        $this->newLine();

        $channels = Channel::active()->get();

        if ($channels->isEmpty()) {
            $this->warn('Nenhum canal encontrado.');
            return;
        }

        $headers = ['ID', 'Nome', 'Slug', 'OAuth', 'Sincronização', 'Propriedades'];
        $rows = [];

        foreach ($channels as $channel) {
            $rows[] = [
                $channel->channel_id,
                $channel->name,
                $channel->slug,
                $channel->requires_oauth ? 'Sim' : 'Não',
                $channel->auto_sync_enabled ? 'Ativa' : 'Inativa',
                $channel->properties()->count()
            ];
        }

        $this->table($headers, $rows);
    }

    private function connectProperty(): void
    {
        $propertyId = $this->option('property');
        $channelId = $this->option('channel');
        $channelPropertyId = $this->option('channel-property-id');
        $channelRoomId = $this->option('channel-room-id');
        $channelUrl = $this->option('channel-url');

        if (!$propertyId || !$channelId || !$channelPropertyId) {
            $this->error('Parâmetros obrigatórios: --property, --channel, --channel-property-id');
            return;
        }

        $property = Property::find($propertyId);
        if (!$property) {
            $this->error("Propriedade {$propertyId} não encontrada.");
            return;
        }

        $channel = Channel::where('channel_id', $channelId)->first();
        if (!$channel) {
            $this->error("Canal {$channelId} não encontrado.");
            return;
        }

        if ($property->isConnectedToChannel($channelId)) {
            $this->error("Propriedade já está conectada ao canal {$channelId}.");
            return;
        }

        $propertyChannel = PropertyChannel::create([
            'property_id' => $property->id,
            'channel_id' => $channel->id,
            'channel_property_id' => $channelPropertyId,
            'channel_room_id' => $channelRoomId,
            'channel_property_url' => $channelUrl,
            'channel_status' => 'inactive',
            'content_status' => 'disabled',
            'is_active' => $this->option('active'),
            'auto_sync_enabled' => $this->option('auto-sync'),
        ]);

        $this->info("✅ Propriedade conectada ao canal {$channelId} com sucesso!");
        $this->line("   Propriedade: {$property->name}");
        $this->line("   Canal: {$channel->name}");
        $this->line("   ID no Canal: {$channelPropertyId}");
        if ($channelRoomId) {
            $this->line("   ID do Quarto: {$channelRoomId}");
        }
        if ($channelUrl) {
            $this->line("   URL: {$channelUrl}");
        }
    }

    private function disconnectProperty(): void
    {
        $propertyId = $this->option('property');
        $channelId = $this->option('channel');

        if (!$propertyId || !$channelId) {
            $this->error('Parâmetros obrigatórios: --property, --channel');
            return;
        }

        $property = Property::find($propertyId);
        if (!$property) {
            $this->error("Propriedade {$propertyId} não encontrada.");
            return;
        }

        $propertyChannel = $property->getChannelConnection($channelId);
        if (!$propertyChannel) {
            $this->error("Propriedade não está conectada ao canal {$channelId}.");
            return;
        }

        $propertyChannel->delete();

        $this->info("✅ Propriedade desconectada do canal {$channelId} com sucesso!");
    }

    private function syncProperty(): void
    {
        $propertyId = $this->option('property');
        $channelId = $this->option('channel');

        if (!$propertyId) {
            $this->error('Parâmetro obrigatório: --property');
            return;
        }

        $property = Property::find($propertyId);
        if (!$property) {
            $this->error("Propriedade {$propertyId} não encontrada.");
            return;
        }

        if ($channelId) {
            // Sincronizar com canal específico
            $propertyChannel = $property->getChannelConnection($channelId);
            if (!$propertyChannel) {
                $this->error("Propriedade não está conectada ao canal {$channelId}.");
                return;
            }

            $this->info("🔄 Sincronizando propriedade {$property->name} com canal {$channelId}...");
            
            try {
                $this->syncService->syncPropertyWithChannel($property, $propertyChannel->channel, $propertyChannel);
                $this->info("✅ Sincronização concluída com sucesso!");
            } catch (\Exception $e) {
                $this->error("❌ Erro na sincronização: " . $e->getMessage());
            }
        } else {
            // Sincronizar com todos os canais conectados
            $this->info("🔄 Sincronizando propriedade {$property->name} com todos os canais...");
            
            $results = $this->syncService->syncPropertyWithAllChannels($property);
            
            foreach ($results as $channelId => $result) {
                if ($result['success']) {
                    $this->info("✅ {$channelId}: {$result['message']}");
                } else {
                    $this->error("❌ {$channelId}: {$result['message']}");
                }
            }
        }
    }

    private function showStatus(): void
    {
        $propertyId = $this->option('property');

        if (!$propertyId) {
            $this->error('Parâmetro obrigatório: --property');
            return;
        }

        $property = Property::find($propertyId);
        if (!$property) {
            $this->error("Propriedade {$propertyId} não encontrada.");
            return;
        }

        $this->info("📊 Status dos Canais para: {$property->name}");
        $this->newLine();

        $property->load(['propertyChannels.channel']);

        if ($property->propertyChannels->isEmpty()) {
            $this->warn('Nenhum canal conectado a esta propriedade.');
            return;
        }

        $headers = ['Canal', 'Status', 'Conteúdo', 'Ativo', 'Auto Sync', 'Última Sincronização', 'Erro'];
        $rows = [];

        foreach ($property->propertyChannels as $propertyChannel) {
            $rows[] = [
                $propertyChannel->channel->name,
                $propertyChannel->channel_status,
                $propertyChannel->content_status,
                $propertyChannel->is_active ? 'Sim' : 'Não',
                $propertyChannel->auto_sync_enabled ? 'Sim' : 'Não',
                $propertyChannel->last_successful_sync_at ? $propertyChannel->last_successful_sync_at->format('d/m/Y H:i') : 'Nunca',
                $propertyChannel->last_sync_error ? 'Sim' : 'Não'
            ];
        }

        $this->table($headers, $rows);
    }

    private function showHelp(): void
    {
        $this->line('Comandos disponíveis:');
        $this->line('');
        $this->line('  list                                    - Lista todos os canais');
        $this->line('  connect                                 - Conecta propriedade a um canal');
        $this->line('  disconnect                              - Desconecta propriedade de um canal');
        $this->line('  sync                                    - Sincroniza propriedade com canal(ais)');
        $this->line('  status                                  - Mostra status dos canais de uma propriedade');
        $this->line('');
        $this->line('Exemplos:');
        $this->line('  php artisan channels:manage list');
        $this->line('  php artisan channels:manage connect --property=1 --channel=AIR298 --channel-property-id=123456');
        $this->line('  php artisan channels:manage sync --property=1 --channel=AIR298');
        $this->line('  php artisan channels:manage status --property=1');
    }
}