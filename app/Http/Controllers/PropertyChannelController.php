<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\Channel;
use App\Models\PropertyChannel;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PropertyChannelController extends Controller
{
    /**
     * Display a listing of property channels for a specific property
     */
    public function index(Property $property)
    {
        $property->load(['channels', 'propertyChannels.channel']);
        
        $availableChannels = Channel::active()
            ->whereNotIn('id', $property->channels()->pluck('channels.id'))
            ->get();

        return view('properties.channels.index', compact('property', 'availableChannels'));
    }

    /**
     * Show the form for connecting a property to a channel
     */
    public function create(Property $property, Channel $channel)
    {
        if ($property->isConnectedToChannel($channel->channel_id)) {
            return redirect()->route('properties.channels.index', $property)
                            ->with('error', 'Esta propriedade já está conectada a este canal.');
        }

        return view('properties.channels.create', compact('property', 'channel'));
    }

    /**
     * Store a newly created property channel connection
     */
    public function store(Request $request, Property $property, Channel $channel)
    {
        $request->validate([
            'channel_property_id' => 'required|string',
            'channel_room_id' => 'nullable|string',
            'channel_property_url' => 'nullable|url',
            'channel_config' => 'nullable|array',
            'sync_settings' => 'nullable|array',
        ]);

        // Verificar se já existe conexão
        if ($property->isConnectedToChannel($channel->channel_id)) {
            return redirect()->route('properties.channels.index', $property)
                            ->with('error', 'Esta propriedade já está conectada a este canal.');
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

        return redirect()->route('properties.channels.index', $property)
                        ->with('success', 'Propriedade conectada ao canal com sucesso!');
    }

    /**
     * Display the specified property channel connection
     */
    public function show(Property $property, Channel $channel)
    {
        $propertyChannel = $property->getChannelConnection($channel->channel_id);
        
        if (!$propertyChannel) {
            return redirect()->route('properties.channels.index', $property)
                            ->with('error', 'Esta propriedade não está conectada a este canal.');
        }

        return view('properties.channels.show', compact('property', 'channel', 'propertyChannel'));
    }

    /**
     * Show the form for editing the specified property channel connection
     */
    public function edit(Property $property, Channel $channel)
    {
        $propertyChannel = $property->getChannelConnection($channel->channel_id);
        
        if (!$propertyChannel) {
            return redirect()->route('properties.channels.index', $property)
                            ->with('error', 'Esta propriedade não está conectada a este canal.');
        }

        return view('properties.channels.edit', compact('property', 'channel', 'propertyChannel'));
    }

    /**
     * Update the specified property channel connection
     */
    public function update(Request $request, Property $property, Channel $channel)
    {
        $request->validate([
            'channel_property_id' => 'required|string',
            'channel_room_id' => 'nullable|string',
            'channel_property_url' => 'nullable|url',
            'channel_status' => 'required|in:active,inactive,suspended',
            'content_status' => 'required|in:enabled,disabled',
            'is_active' => 'boolean',
            'auto_sync_enabled' => 'boolean',
            'channel_config' => 'nullable|array',
            'sync_settings' => 'nullable|array',
            'property_status_note' => 'nullable|string',
        ]);

        $propertyChannel = $property->getChannelConnection($channel->channel_id);
        
        if (!$propertyChannel) {
            return redirect()->route('properties.channels.index', $property)
                            ->with('error', 'Esta propriedade não está conectada a este canal.');
        }

        $propertyChannel->update($request->all());

        return redirect()->route('properties.channels.show', [$property, $channel])
                        ->with('success', 'Configurações do canal atualizadas com sucesso!');
    }

    /**
     * Remove the specified property channel connection
     */
    public function destroy(Property $property, Channel $channel)
    {
        $propertyChannel = $property->getChannelConnection($channel->channel_id);
        
        if (!$propertyChannel) {
            return redirect()->route('properties.channels.index', $property)
                            ->with('error', 'Esta propriedade não está conectada a este canal.');
        }

        $propertyChannel->delete();

        return redirect()->route('properties.channels.index', $property)
                        ->with('success', 'Propriedade desconectada do canal com sucesso!');
    }

    /**
     * Sync property data with channel
     */
    public function sync(Property $property, Channel $channel)
    {
        $propertyChannel = $property->getChannelConnection($channel->channel_id);
        
        if (!$propertyChannel) {
            return back()->with('error', 'Esta propriedade não está conectada a este canal.');
        }

        if (!$propertyChannel->canSync()) {
            return back()->with('error', 'Esta propriedade não pode ser sincronizada com este canal.');
        }

        try {
            // Implementar lógica de sincronização específica para cada canal
            $this->performChannelSync($channel, $property, $propertyChannel);
            
            $propertyChannel->markSyncSuccess();
            
            return back()->with('success', 'Sincronização realizada com sucesso!');
            
        } catch (\Exception $e) {
            $propertyChannel->markSyncError($e->getMessage());
            
            return back()->with('error', 'Erro na sincronização: ' . $e->getMessage());
        }
    }

    /**
     * Toggle property channel active status
     */
    public function toggleActive(Property $property, Channel $channel)
    {
        $propertyChannel = $property->getChannelConnection($channel->channel_id);
        
        if (!$propertyChannel) {
            return back()->with('error', 'Esta propriedade não está conectada a este canal.');
        }

        $propertyChannel->update([
            'is_active' => !$propertyChannel->is_active
        ]);

        $status = $propertyChannel->is_active ? 'ativado' : 'desativado';
        
        return back()->with('success', "Canal {$status} com sucesso!");
    }

    /**
     * Toggle auto sync for property channel
     */
    public function toggleAutoSync(Property $property, Channel $channel)
    {
        $propertyChannel = $property->getChannelConnection($channel->channel_id);
        
        if (!$propertyChannel) {
            return back()->with('error', 'Esta propriedade não está conectada a este canal.');
        }

        $propertyChannel->update([
            'auto_sync_enabled' => !$propertyChannel->auto_sync_enabled
        ]);

        $status = $propertyChannel->auto_sync_enabled ? 'habilitado' : 'desabilitado';
        
        return back()->with('success', "Sincronização automática {$status} com sucesso!");
    }

    /**
     * Get property channel statistics
     */
    public function statistics(Property $property)
    {
        $property->load(['propertyChannels.channel']);
        
        $stats = [
            'total_channels' => $property->getConnectedChannelsCount(),
            'active_channels' => $property->getActiveChannelsCount(),
            'channels_with_errors' => $property->propertyChannels()->withErrors()->count(),
            'channels_needing_sync' => $property->propertyChannels()->needsSync()->count(),
            'last_sync' => $property->propertyChannels()
                ->whereNotNull('last_successful_sync_at')
                ->orderBy('last_successful_sync_at', 'desc')
                ->first()?->last_successful_sync_at,
        ];

        return response()->json($stats);
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