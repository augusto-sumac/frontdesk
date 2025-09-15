<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Property;
use App\Models\Channel;
use App\Models\PropertyChannel;
use App\Services\NextPaxService;

class SyncPropertiesWithChannels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:properties-channels {--property= : ID específico da propriedade} {--channel= : ID específico do canal} {--force : Forçar reconexão mesmo se já existir}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza propriedades com canais automaticamente';

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
        $this->info('🔄 Sincronizando propriedades com canais...');
        $this->newLine();

        $propertyId = $this->option('property');
        $channelId = $this->option('channel');
        $force = $this->option('force');

        // Obter propriedades
        if ($propertyId) {
            $properties = Property::where('id', $propertyId)->get();
        } else {
            $properties = Property::where('status', 'active')->get();
        }

        // Obter canais
        if ($channelId) {
            $channels = Channel::where('id', $channelId)->get();
        } else {
            $channels = Channel::where('is_active', true)->get();
        }

        $this->info("📊 Processando {$properties->count()} propriedade(s) e {$channels->count()} canal(is)...");
        $this->newLine();

        $totalConnections = 0;
        $newConnections = 0;
        $updatedConnections = 0;

        foreach ($properties as $property) {
            $this->line("🏠 Processando: {$property->name} (ID: {$property->id})");
            
            foreach ($channels as $channel) {
                $this->line("   📡 Canal: {$channel->name} ({$channel->channel_id})");
                
                // Verificar se já existe conexão
                $existingConnection = PropertyChannel::where('property_id', $property->id)
                    ->where('channel_id', $channel->id)
                    ->first();

                if ($existingConnection && !$force) {
                    $this->line("      ✅ Conexão já existe");
                    $totalConnections++;
                    continue;
                }

                // Criar ou atualizar conexão
                $connectionData = $this->createConnectionData($property, $channel);
                
                if ($existingConnection) {
                    $existingConnection->update($connectionData);
                    $this->line("      🔄 Conexão atualizada");
                    $updatedConnections++;
                } else {
                    PropertyChannel::create(array_merge($connectionData, [
                        'property_id' => $property->id,
                        'channel_id' => $channel->id,
                    ]));
                    $this->line("      ➕ Nova conexão criada");
                    $newConnections++;
                }
                
                $totalConnections++;
            }
            $this->newLine();
        }

        $this->info("✅ Sincronização concluída!");
        $this->line("   📊 Total de conexões: {$totalConnections}");
        $this->line("   ➕ Novas conexões: {$newConnections}");
        $this->line("   🔄 Conexões atualizadas: {$updatedConnections}");
    }

    /**
     * Criar dados de conexão para uma propriedade e canal
     */
    private function createConnectionData(Property $property, Channel $channel): array
    {
        $baseData = [
            'is_active' => true,
            'auto_sync_enabled' => true,
            'channel_status' => 'active',
            'content_status' => 'enabled',
            'last_sync_at' => now(),
        ];

        // Dados específicos por canal
        switch ($channel->channel_id) {
            case 'AIR298': // Airbnb
                return array_merge($baseData, [
                    'channel_property_id' => 'airbnb-' . $property->id,
                    'channel_config' => [
                        'listing_id' => 'airbnb-' . $property->id,
                        'sync_enabled' => true,
                    ],
                ]);

            case 'BOO142': // Booking.com
                return array_merge($baseData, [
                    'channel_property_id' => 'booking-' . $property->id,
                    'channel_config' => [
                        'property_id' => 'booking-' . $property->id,
                        'sync_enabled' => true,
                    ],
                ]);

            case 'HOM143': // HomeAway
                return array_merge($baseData, [
                    'channel_property_id' => 'homeaway-' . $property->id,
                    'channel_config' => [
                        'property_id' => 'homeaway-' . $property->id,
                        'sync_enabled' => true,
                    ],
                ]);

            case 'EXP001': // Expedia
                return array_merge($baseData, [
                    'channel_property_id' => 'expedia-' . $property->id,
                    'channel_config' => [
                        'property_id' => 'expedia-' . $property->id,
                        'sync_enabled' => true,
                    ],
                ]);

            case 'VRB001': // VRBO
                return array_merge($baseData, [
                    'channel_property_id' => 'vrbo-' . $property->id,
                    'channel_config' => [
                        'property_id' => 'vrbo-' . $property->id,
                        'sync_enabled' => true,
                    ],
                ]);

            case 'DIRECT': // Reserva Direta
                return array_merge($baseData, [
                    'channel_property_id' => 'direct-' . $property->id,
                    'channel_config' => [
                        'property_id' => 'direct-' . $property->id,
                        'sync_enabled' => true,
                    ],
                ]);

            default:
                return array_merge($baseData, [
                    'channel_property_id' => strtolower($channel->slug) . '-' . $property->id,
                    'channel_config' => [
                        'property_id' => strtolower($channel->slug) . '-' . $property->id,
                        'sync_enabled' => true,
                    ],
                ]);
        }
    }
}
