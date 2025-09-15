<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Property;
use App\Models\Channel;
use App\Models\PropertyChannel;
use App\Services\NextPaxService;

class AutoSyncPropertiesChannels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:auto-properties-channels {--interval=5 : Intervalo em minutos para verificar novas propriedades}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincronização automática de propriedades com canais (executar via cron)';

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
        $interval = $this->option('interval');
        
        $this->info("🔄 Iniciando sincronização automática (intervalo: {$interval}min)...");
        
        // Buscar propriedades criadas nos últimos X minutos que não têm conexões
        $cutoffTime = now()->subMinutes($interval);
        
        $propertiesWithoutConnections = Property::where('status', 'active')
            ->where('created_at', '>=', $cutoffTime)
            ->whereDoesntHave('propertyChannels')
            ->get();

        if ($propertiesWithoutConnections->isEmpty()) {
            $this->line("✅ Nenhuma propriedade nova encontrada para sincronizar.");
            return;
        }

        $this->info("📊 Encontradas {$propertiesWithoutConnections->count()} propriedade(s) para sincronizar...");

        $activeChannels = Channel::where('is_active', true)->get();
        $totalConnections = 0;

        foreach ($propertiesWithoutConnections as $property) {
            $this->line("🏠 Sincronizando: {$property->name} (ID: {$property->id})");
            
            $connectionsCreated = 0;
            
            foreach ($activeChannels as $channel) {
                $connectionData = $this->createConnectionData($property, $channel);
                
                PropertyChannel::create([
                    'property_id' => $property->id,
                    'channel_id' => $channel->id,
                    'is_active' => true,
                    'auto_sync_enabled' => true,
                    'channel_status' => 'active',
                    'content_status' => 'enabled',
                    'channel_property_id' => $connectionData['channel_property_id'],
                    'channel_config' => $connectionData['channel_config'],
                    'last_sync_at' => now(),
                ]);
                
                $connectionsCreated++;
                $totalConnections++;
            }
            
            $this->line("   ✅ {$connectionsCreated} conexões criadas");
        }

        $this->info("✅ Sincronização automática concluída!");
        $this->line("   📊 Total de conexões criadas: {$totalConnections}");
        
        // Log da sincronização
        \Log::info('Auto sync properties-channels completed', [
            'properties_synced' => $propertiesWithoutConnections->count(),
            'connections_created' => $totalConnections,
            'interval_minutes' => $interval,
        ]);
    }

    /**
     * Criar dados de conexão para uma propriedade e canal
     */
    private function createConnectionData(Property $property, Channel $channel): array
    {
        $baseData = [
            'channel_property_id' => strtolower($channel->slug) . '-' . $property->id,
            'channel_config' => [
                'property_id' => strtolower($channel->slug) . '-' . $property->id,
                'sync_enabled' => true,
                'auto_sync' => true,
            ],
        ];

        // Dados específicos por canal
        switch ($channel->channel_id) {
            case 'AIR298': // Airbnb
                return [
                    'channel_property_id' => 'airbnb-' . $property->id,
                    'channel_config' => [
                        'listing_id' => 'airbnb-' . $property->id,
                        'sync_enabled' => true,
                        'auto_sync' => true,
                    ],
                ];

            case 'BOO142': // Booking.com
                return [
                    'channel_property_id' => 'booking-' . $property->id,
                    'channel_config' => [
                        'property_id' => 'booking-' . $property->id,
                        'sync_enabled' => true,
                        'auto_sync' => true,
                    ],
                ];

            case 'HOM143': // HomeAway
                return [
                    'channel_property_id' => 'homeaway-' . $property->id,
                    'channel_config' => [
                        'property_id' => 'homeaway-' . $property->id,
                        'sync_enabled' => true,
                        'auto_sync' => true,
                    ],
                ];

            case 'EXP001': // Expedia
                return [
                    'channel_property_id' => 'expedia-' . $property->id,
                    'channel_config' => [
                        'property_id' => 'expedia-' . $property->id,
                        'sync_enabled' => true,
                        'auto_sync' => true,
                    ],
                ];

            case 'VRB001': // VRBO
                return [
                    'channel_property_id' => 'vrbo-' . $property->id,
                    'channel_config' => [
                        'property_id' => 'vrbo-' . $property->id,
                        'sync_enabled' => true,
                        'auto_sync' => true,
                    ],
                ];

            case 'DIRECT': // Reserva Direta
                return [
                    'channel_property_id' => 'direct-' . $property->id,
                    'channel_config' => [
                        'property_id' => 'direct-' . $property->id,
                        'sync_enabled' => true,
                        'auto_sync' => true,
                    ],
                ];

            default:
                return $baseData;
        }
    }
}
