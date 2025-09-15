<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Channel;
use App\Models\Property;
use App\Models\PropertyChannel;
use App\Models\User;
use App\Services\NextPaxService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class ChannelController extends Controller
{
    protected $nextPaxService;

    public function __construct(NextPaxService $nextPaxService)
    {
        $this->nextPaxService = $nextPaxService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $channels = Channel::active()->get();
        
        return view('channels.index', compact('channels'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('channels.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'channel_id' => 'required|string|unique:channels',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:channels',
            'description' => 'nullable|string',
            'logo_url' => 'nullable|url',
            'website_url' => 'nullable|url',
            'api_base_url' => 'nullable|url',
            'is_active' => 'boolean',
            'requires_oauth' => 'boolean',
            'sync_interval_minutes' => 'integer|min:1',
        ]);

        $channel = Channel::create($request->all());

        return redirect()->route('channels.index')
                        ->with('success', 'Canal criado com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Channel $channel)
    {
        $channel->load(['properties', 'propertyChannels']);
        
        return view('channels.show', compact('channel'));
    }

    /**
     * Display properties connected to a specific channel
     */
    public function properties(Channel $channel)
    {
        // Buscar propriedades que realmente estão conectadas a este canal específico
        $propertyChannels = PropertyChannel::where('channel_id', $channel->id)
            ->with(['property', 'channel'])
            ->get();
        
        $connectedProperties = $propertyChannels->map(function($propertyChannel) {
            return [
                'property' => $propertyChannel->property,
                'connection' => $propertyChannel,
                'is_active' => $propertyChannel->is_active,
                'last_sync' => $propertyChannel->last_successful_sync_at,
                'sync_error' => $propertyChannel->last_sync_error,
            ];
        });
        
        return view('channels.properties', compact('channel', 'connectedProperties'));
    }

    /**
     * Sync properties from channel API
     */
    public function syncProperties(Channel $channel)
    {
        try {
            $syncedProperties = [];
            
            // Sincronizar baseado no tipo de canal
            switch ($channel->channel_id) {
                case 'BOO142': // Booking.com
                    // Usar o comando de sincronização via Artisan
                    \Artisan::call('sync:booking-properties', [
                        '--property-manager' => 'SAFDK000046',
                        '--create-local' => true
                    ]);
                    
                    // Contar propriedades sincronizadas
                    $count = PropertyChannel::where('channel_id', $channel->id)->count();
                    $syncedProperties = ['count' => $count];
                    break;
                    
                case 'AIR298': // Airbnb
                    $syncedProperties = $this->syncAirbnbProperties($channel);
                    break;
                    
                default:
                    $syncedProperties = $this->syncGenericChannelProperties($channel);
                    break;
            }
            
            $message = isset($syncedProperties['error']) ? 
                "Erro: {$syncedProperties['error']}" :
                "Sincronização concluída! {$syncedProperties['count']} propriedades verificadas e atualizadas.";
            
            return redirect()->route('channels.properties', $channel)
                ->with(isset($syncedProperties['error']) ? 'error' : 'success', $message);
                
        } catch (\Exception $e) {
            return redirect()->route('channels.properties', $channel)
                ->with('error', 'Erro na sincronização: ' . $e->getMessage());
        }
    }
    
    /**
     * Sync Booking.com properties
     */
    private function syncBookingComProperties(Channel $channel)
    {
        $syncedCount = 0;
        $newProperties = [];
        $propertyManagerCode = 'SAFDK000046'; // Ou pegar do usuário logado
        
        try {
            // Buscar todas as propriedades do property manager via NextPax API
            $response = $this->nextPaxService->getPropertiesWithChannels($propertyManagerCode);
            
            if (isset($response['error'])) {
                throw new \Exception($response['error']);
            }
            
            $allProperties = $response['data'] ?? [];
            
            // Filtrar propriedades que têm conexão com Booking.com
            $bookingComProperties = [];
            foreach ($allProperties as $property) {
                // Buscar detalhes da propriedade para verificar canais
                $propertyDetails = $this->nextPaxService->getProperty($property['propertyId']);
                
                if (isset($propertyDetails['property'])) {
                    $propData = $propertyDetails['property'];
                    
                    // Verificar se a propriedade tem configuração para Booking.com
                    // Isso pode estar em channels, ou podemos assumir que todas as propriedades
                    // do property manager podem ser conectadas ao Booking.com
                    $hasBookingConfig = true; // Por enquanto, assumir que todas podem
                    
                    if ($hasBookingConfig) {
                        $bookingComProperties[] = [
                            'propertyId' => $property['propertyId'],
                            'supplierPropertyId' => $property['supplierPropertyId'] ?? $property['propertyId'],
                            'name' => $propData['general']['name'] ?? 'Propriedade',
                            'address' => $propData['general']['address'] ?? [],
                            'general' => $propData['general'] ?? [],
                            'channelStatus' => 'active',
                            'contentStatus' => 'enabled'
                        ];
                    }
                }
            }
            
            // Se não há propriedades na API, usar fallback
            if (empty($bookingComProperties)) {
                return $this->syncBookingComPropertiesFallback($channel);
            }
            
            // Processar propriedades vindas da API
            foreach ($bookingComProperties as $apiProperty) {
                $propertyId = $apiProperty['propertyId'];
                $supplierPropertyId = $apiProperty['supplierPropertyId'];
                $channelPropertyId = $supplierPropertyId; // Usar supplier ID como channel ID
                
                // Verificar se a propriedade já existe localmente
                $existingProperty = Property::where('property_id', $propertyId)
                    ->orWhere('supplier_property_id', $supplierPropertyId)
                    ->first();
                
                if (!$existingProperty) {
                    // Criar nova propriedade local
                    $propGeneral = $apiProperty['general'];
                    $propAddress = $apiProperty['address'];
                    
                    $property = Property::create([
                        'name' => $apiProperty['name'],
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
                        'base_price' => 100.00,
                        'currency' => $propGeneral['baseCurrency'] ?? 'BRL',
                        'status' => 'active',
                        'is_active' => true
                    ]);
                    
                    $newProperties[] = $property;
                } else {
                    $property = $existingProperty;
                    
                    // Atualizar dados da propriedade existente se necessário
                    if (empty($property->property_id) && $propertyId) {
                        $property->property_id = $propertyId;
                        $property->save();
                    }
                }
                
                // Verificar se já existe conexão com o canal
                $existingConnection = PropertyChannel::where('property_id', $property->id)
                    ->where('channel_id', $channel->id)
                    ->first();
                
                if (!$existingConnection) {
                    // Criar nova conexão
                    PropertyChannel::create([
                        'property_id' => $property->id,
                        'channel_id' => $channel->id,
                        'channel_property_id' => $channelPropertyId,
                        'is_active' => true,
                        'auto_sync_enabled' => true,
                        'channel_status' => $apiProperty['channelStatus'],
                        'content_status' => $apiProperty['contentStatus'],
                        'channel_config' => [
                            'external_id' => $channelPropertyId,
                            'property_id' => $propertyId,
                            'supplier_property_id' => $supplierPropertyId,
                            'sync_enabled' => true,
                            'last_api_sync' => now()->toISOString(),
                            'verified' => true,
                            'source' => 'nextpax_api'
                        ],
                        'last_sync_at' => now(),
                        'last_successful_sync_at' => now()
                    ]);
                } else {
                    // Atualizar conexão existente
                    $existingConnection->update([
                        'channel_property_id' => $channelPropertyId,
                        'channel_status' => $apiProperty['channelStatus'],
                        'content_status' => $apiProperty['contentStatus'],
                        'last_sync_at' => now(),
                        'last_successful_sync_at' => now(),
                        'channel_config' => array_merge(
                            $existingConnection->channel_config ?? [],
                            [
                                'external_id' => $channelPropertyId,
                                'property_id' => $propertyId,
                                'supplier_property_id' => $supplierPropertyId,
                                'last_api_sync' => now()->toISOString(),
                                'verified' => true,
                                'source' => 'nextpax_api'
                            ]
                        )
                    ]);
                }
                
                $syncedCount++;
            }
            
            // Remover conexões de propriedades que não existem mais na API
            $apiPropertyIds = collect($bookingComProperties)->pluck('propertyId')->filter()->toArray();
            $apiSupplierIds = collect($bookingComProperties)->pluck('supplierPropertyId')->filter()->toArray();
            
            $connectionsToRemove = PropertyChannel::where('channel_id', $channel->id)
                ->whereHas('property', function($query) use ($apiPropertyIds, $apiSupplierIds) {
                    $query->whereNotIn('property_id', $apiPropertyIds)
                          ->whereNotIn('supplier_property_id', $apiSupplierIds);
                })
                ->get();
            
            foreach ($connectionsToRemove as $connection) {
                // Só remover se foi criado via API (não remover conexões manuais)
                $config = $connection->channel_config ?? [];
                if (isset($config['source']) && $config['source'] === 'nextpax_api') {
                    $connection->delete();
                }
            }
            
            return [
                'count' => $syncedCount,
                'new_properties' => $newProperties
            ];
            
        } catch (\Exception $e) {
            Log::error('Erro na sincronização do Booking.com:', [
                'channel_id' => $channel->id,
                'error' => $e->getMessage()
            ]);
            
            // Fallback: usar método anterior se a API falhar
            return $this->syncBookingComPropertiesFallback($channel);
        }
    }
    
    /**
     * Fallback method for Booking.com sync
     */
    private function syncBookingComPropertiesFallback(Channel $channel)
    {
        $syncedCount = 0;
        $newProperties = [];
        
        // Buscar propriedades que já estão conectadas ao Booking.com
        $existingConnections = PropertyChannel::where('channel_id', $channel->id)->get();
        
        foreach ($existingConnections as $connection) {
            $property = $connection->property;
            
            // Verificar se propriedade ainda é válida
            $existsInBookingCom = $this->checkPropertyExistsInBookingCom($property);
            
            if ($existsInBookingCom) {
                // Atualizar dados da conexão existente
                $connection->update([
                    'last_sync_at' => now(),
                    'last_successful_sync_at' => now(),
                    'channel_config' => array_merge(
                        $connection->channel_config ?? [],
                        [
                            'last_api_sync' => now()->toISOString(),
                            'verified' => true,
                            'fallback_sync' => true
                        ]
                    )
                ]);
                
                $syncedCount++;
            } else {
                // Propriedade não existe no Booking.com, remover conexão
                $connection->delete();
            }
        }
        
        return [
            'count' => $syncedCount,
            'new_properties' => $newProperties
        ];
    }
    
    /**
     * Verificar se propriedade existe no Booking.com
     */
    private function checkPropertyExistsInBookingCom(Property $property)
    {
        // Simular verificação na API do Booking.com
        // Por enquanto, vamos considerar que apenas algumas propriedades existem
        
        $validProperties = [
            'booking-789012', // Teste
            'booking-5'       // Madonna Nicholson
        ];
        
        // Verificar se o channel_property_id está na lista de válidos
        $connection = PropertyChannel::where('property_id', $property->id)
            ->where('channel_id', 2) // Booking.com
            ->first();
            
        if ($connection && in_array($connection->channel_property_id, $validProperties)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Sync Airbnb properties
     */
    private function syncAirbnbProperties(Channel $channel)
    {
        // Implementar sincronização com Airbnb
        return ['count' => 0, 'new_properties' => []];
    }
    
    /**
     * Sync generic channel properties
     */
    private function syncGenericChannelProperties(Channel $channel)
    {
        // Implementar sincronização genérica
        return ['count' => 0, 'new_properties' => []];
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Channel $channel)
    {
        return view('channels.edit', compact('channel'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Channel $channel)
    {
        $request->validate([
            'channel_id' => 'required|string|unique:channels,channel_id,' . $channel->id,
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:channels,slug,' . $channel->id,
            'description' => 'nullable|string',
            'logo_url' => 'nullable|url',
            'website_url' => 'nullable|url',
            'api_base_url' => 'nullable|url',
            'is_active' => 'boolean',
            'requires_oauth' => 'boolean',
            'sync_interval_minutes' => 'integer|min:1',
        ]);

        $channel->update($request->all());

        return redirect()->route('channels.index')
                        ->with('success', 'Canal atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Channel $channel)
    {
        $channel->delete();

        return redirect()->route('channels.index')
                        ->with('success', 'Canal removido com sucesso!');
    }

    /**
     * Connect a property to a channel
     */
    public function connectProperty(Request $request, Channel $channel)
    {
        $request->validate([
            'property_id' => 'required|exists:properties,id',
            'channel_property_id' => 'required|string',
            'channel_room_id' => 'nullable|string',
            'channel_property_url' => 'nullable|url',
            'channel_config' => 'nullable|array',
            'sync_settings' => 'nullable|array',
        ]);

        $property = Property::findOrFail($request->property_id);

        // Verificar se já existe conexão
        if ($property->isConnectedToChannel($channel->channel_id)) {
            return back()->with('error', 'Esta propriedade já está conectada a este canal.');
        }

        // Criar conexão
        $propertyChannel = PropertyChannel::create([
            'property_id' => $property->id,
            'channel_id' => $channel->id,
            'channel_property_id' => $request->channel_property_id,
            'channel_room_id' => $request->channel_room_id,
            'channel_property_url' => $request->channel_property_url,
            'channel_config' => $request->channel_config,
            'sync_settings' => $request->sync_settings,
            'channel_status' => 'inactive',
            'content_status' => 'disabled',
            'is_active' => false,
            'auto_sync_enabled' => true,
        ]);

        return back()->with('success', 'Propriedade conectada ao canal com sucesso!');
    }

    /**
     * Disconnect a property from a channel
     */
    public function disconnectProperty(Channel $channel, Property $property)
    {
        $propertyChannel = $property->getChannelConnection($channel->channel_id);
        
        if (!$propertyChannel) {
            return back()->with('error', 'Esta propriedade não está conectada a este canal.');
        }

        $propertyChannel->delete();

        return back()->with('success', 'Propriedade desconectada do canal com sucesso!');
    }

    /**
     * Update property channel settings
     */
    public function updatePropertyChannel(Request $request, Channel $channel, Property $property)
    {
        $request->validate([
            'channel_status' => 'required|in:active,inactive,suspended',
            'content_status' => 'required|in:enabled,disabled',
            'is_active' => 'boolean',
            'auto_sync_enabled' => 'boolean',
            'channel_config' => 'nullable|array',
            'sync_settings' => 'nullable|array',
        ]);

        $propertyChannel = $property->getChannelConnection($channel->channel_id);
        
        if (!$propertyChannel) {
            return back()->with('error', 'Esta propriedade não está conectada a este canal.');
        }

        $propertyChannel->update($request->all());

        return back()->with('success', 'Configurações do canal atualizadas com sucesso!');
    }

    /**
     * Sync property data with channel
     */
    public function syncProperty(Channel $channel, Property $property)
    {
        $propertyChannel = $property->getChannelConnection($channel->channel_id);
        
        if (!$propertyChannel) {
            return back()->with('error', 'Esta propriedade não está conectada a este canal.');
        }

        if (!$propertyChannel->canSync()) {
            return back()->with('error', 'Esta propriedade não pode ser sincronizada com este canal.');
        }

        try {
            // Aqui você implementaria a lógica de sincronização específica para cada canal
            $this->performChannelSync($channel, $property, $propertyChannel);
            
            $propertyChannel->markSyncSuccess();
            
            return back()->with('success', 'Sincronização realizada com sucesso!');
            
        } catch (\Exception $e) {
            $propertyChannel->markSyncError($e->getMessage());
            
            return back()->with('error', 'Erro na sincronização: ' . $e->getMessage());
        }
    }

    /**
     * Get available channels for a property
     */
    public function getAvailableChannels(Property $property)
    {
        $connectedChannels = $property->channels()->pluck('channel_id')->toArray();
        $availableChannels = Channel::active()
            ->whereNotIn('channel_id', $connectedChannels)
            ->get();

        return response()->json($availableChannels);
    }

    /**
     * Get property channel status
     */
    public function getPropertyChannelStatus(Channel $channel, Property $property)
    {
        $propertyChannel = $property->getChannelConnection($channel->channel_id);
        
        if (!$propertyChannel) {
            return response()->json(['connected' => false]);
        }

        return response()->json([
            'connected' => true,
            'status' => $propertyChannel->channel_status,
            'content_status' => $propertyChannel->content_status,
            'is_active' => $propertyChannel->is_active,
            'last_sync' => $propertyChannel->last_sync_at,
            'last_successful_sync' => $propertyChannel->last_successful_sync_at,
            'sync_error' => $propertyChannel->last_sync_error,
            'sync_attempts' => $propertyChannel->sync_attempts,
        ]);
    }

    /**
     * Perform channel-specific sync logic
     */
    private function performChannelSync(Channel $channel, Property $property, PropertyChannel $propertyChannel)
    {
        // Implementar lógica específica para cada canal
        switch ($channel->channel_id) {
            case 'AIR298':
                $this->syncWithAirbnb($property, $propertyChannel);
                break;
            case 'BOO142':
                $this->syncWithBooking($property, $propertyChannel);
                break;
            case 'HOM143':
                $this->syncWithHomeAway($property, $propertyChannel);
                break;
            default:
                throw new \Exception('Canal não suportado para sincronização automática');
        }
    }

    /**
     * Sync with Airbnb
     */
    private function syncWithAirbnb(Property $property, PropertyChannel $propertyChannel)
    {
        // Implementar sincronização com Airbnb
        // Por enquanto, apenas simular
        sleep(2); // Simular tempo de processamento
    }

    /**
     * Sync with Booking.com
     */
    private function syncWithBooking(Property $property, PropertyChannel $propertyChannel)
    {
        // Implementar sincronização com Booking.com
        // Por enquanto, apenas simular
        sleep(2); // Simular tempo de processamento
    }

    /**
     * Sync with HomeAway
     */
    private function syncWithHomeAway(Property $property, PropertyChannel $propertyChannel)
    {
        // Implementar sincronização com HomeAway
        // Por enquanto, apenas simular
        sleep(2); // Simular tempo de processamento
    }
}