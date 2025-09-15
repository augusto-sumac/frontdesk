@extends('layouts/app')

@section('title', 'Conectar Canal')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-link mr-2"></i>
                        Conectar ao Canal
                    </h3>
                    <small class="text-muted">{{ $property->name }} → {{ $channel->name }}</small>
                </div>
                <div class="card-body">
                    <form action="{{ route('properties.channels.store', [$property, $channel]) }}" method="POST">
                        @csrf
                        
                        <!-- Informações do Canal -->
                        <div class="alert alert-info">
                            <h6 class="alert-heading">
                                <i class="fas fa-info-circle mr-2"></i>
                                Informações do Canal
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Canal:</strong> {{ $channel->name }}<br>
                                    <strong>ID:</strong> {{ $channel->channel_id }}<br>
                                    <strong>Descrição:</strong> {{ $channel->description }}
                                </div>
                                <div class="col-md-6">
                                    @if($channel->requires_oauth)
                                        <span class="badge badge-warning">Requer OAuth</span><br>
                                    @endif
                                    @if($channel->website_url)
                                        <a href="{{ $channel->website_url }}" target="_blank">
                                            Visitar Website <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Configurações da Conexão -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="channel_property_id">ID da Propriedade no Canal *</label>
                                    <input type="text" class="form-control @error('channel_property_id') is-invalid @enderror" 
                                           id="channel_property_id" name="channel_property_id" 
                                           value="{{ old('channel_property_id') }}" 
                                           placeholder="Ex: airbnb-123456, booking-789012" required>
                                    @error('channel_property_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        ID único da propriedade neste canal específico
                                    </small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="channel_room_id">ID do Quarto no Canal</label>
                                    <input type="text" class="form-control @error('channel_room_id') is-invalid @enderror" 
                                           id="channel_room_id" name="channel_room_id" 
                                           value="{{ old('channel_room_id') }}" 
                                           placeholder="Ex: room-001, unit-123">
                                    @error('channel_room_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        ID específico do quarto/unidade (opcional)
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="channel_property_url">URL da Propriedade no Canal</label>
                            <input type="url" class="form-control @error('channel_property_url') is-invalid @enderror" 
                                   id="channel_property_url" name="channel_property_url" 
                                   value="{{ old('channel_property_url') }}" 
                                   placeholder="https://airbnb.com/rooms/123456">
                            @error('channel_property_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                Link direto para a propriedade no canal
                            </small>
                        </div>

                        <!-- Configurações Avançadas -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-cogs mr-2"></i>
                                    Configurações Avançadas
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Configurações do Canal</label>
                                            <textarea class="form-control @error('channel_config') is-invalid @enderror" 
                                                      id="channel_config" name="channel_config" rows="3" 
                                                      placeholder='{"api_key": "sua-chave", "webhook_url": "https://..."}'></textarea>
                                            @error('channel_config')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">
                                                Configurações específicas em formato JSON (opcional)
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Configurações de Sincronização</label>
                                            <textarea class="form-control @error('sync_settings') is-invalid @enderror" 
                                                      id="sync_settings" name="sync_settings" rows="3" 
                                                      placeholder='{"interval": 60, "auto_sync": true}'></textarea>
                                            @error('sync_settings')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">
                                                Configurações de sincronização em formato JSON (opcional)
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Configurações de Ativação -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-power-off mr-2"></i>
                                    Configurações de Ativação
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                                                   value="1" {{ old('is_active', false) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="is_active">
                                                <strong>Ativar Conexão</strong>
                                            </label>
                                            <small class="form-text text-muted">
                                                Marque para ativar a conexão imediatamente
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="auto_sync_enabled" name="auto_sync_enabled" 
                                                   value="1" {{ old('auto_sync_enabled', true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="auto_sync_enabled">
                                                <strong>Sincronização Automática</strong>
                                            </label>
                                            <small class="form-text text-muted">
                                                Habilitar sincronização automática de dados
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Informações da Propriedade -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-home mr-2"></i>
                                    Informações da Propriedade
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Nome:</strong> {{ $property->name }}<br>
                                        <strong>ID NextPax:</strong> {{ $property->property_id }}<br>
                                        <strong>Supplier ID:</strong> {{ $property->supplier_property_id }}
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Endereço:</strong> {{ $property->full_address }}<br>
                                        <strong>Tipo:</strong> {{ $property->property_type_text }}<br>
                                        <strong>Status:</strong> {!! $property->status_badge !!}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-link mr-1"></i>
                                Conectar Canal
                            </button>
                            <a href="{{ route('properties.channels.index', $property) }}" class="btn btn-secondary ml-2">
                                <i class="fas fa-times mr-1"></i>
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-generate channel property ID based on channel
    $('#channel_property_id').on('input', function() {
        const channelId = '{{ $channel->channel_id }}';
        const propertyId = $(this).val();
        
        if (propertyId && !propertyId.includes('-')) {
            const prefix = channelId.toLowerCase().replace(/\d+/g, '');
            $(this).val(prefix + '-' + propertyId);
        }
    });

    // Auto-generate URL based on channel and property ID
    $('#channel_property_url').on('input', function() {
        const channelId = '{{ $channel->channel_id }}';
        const propertyId = $('#channel_property_id').val();
        
        if (propertyId && !$(this).val()) {
            let baseUrl = '';
            switch(channelId) {
                case 'AIR298':
                    baseUrl = 'https://airbnb.com/rooms/';
                    break;
                case 'BOO142':
                    baseUrl = 'https://booking.com/hotel/';
                    break;
                case 'HOM143':
                    baseUrl = 'https://homeaway.com/vacation-rental/';
                    break;
            }
            
            if (baseUrl) {
                $(this).val(baseUrl + propertyId);
            }
        }
    });
});
</script>
@endpush
