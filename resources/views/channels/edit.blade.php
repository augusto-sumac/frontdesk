@extends('layouts/app')

@section('title', 'Editar Canal')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-edit mr-2"></i>
                        Editar Canal
                    </h3>
                    <small class="text-muted">{{ $channel->name }}</small>
                </div>
                <div class="card-body">
                    <form action="{{ route('channels.update', $channel) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <!-- Informações Básicas -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    Informações Básicas
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="name">Nome do Canal *</label>
                                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                                   id="name" name="name" value="{{ old('name', $channel->name) }}" required>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="channel_id">ID do Canal *</label>
                                            <input type="text" class="form-control @error('channel_id') is-invalid @enderror" 
                                                   id="channel_id" name="channel_id" value="{{ old('channel_id', $channel->channel_id) }}" required>
                                            @error('channel_id')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <small class="form-text text-muted">
                                                ID único do canal (ex: AIR298, BOO142)
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="description">Descrição</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" 
                                              id="description" name="description" rows="3">{{ old('description', $channel->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="website_url">Website URL</label>
                                            <input type="url" class="form-control @error('website_url') is-invalid @enderror" 
                                                   id="website_url" name="website_url" 
                                                   value="{{ old('website_url', $channel->website_url) }}">
                                            @error('website_url')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="logo_url">Logo URL</label>
                                            <input type="url" class="form-control @error('logo_url') is-invalid @enderror" 
                                                   id="logo_url" name="logo_url" 
                                                   value="{{ old('logo_url', $channel->logo_url) }}">
                                            @error('logo_url')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Configurações da API -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-code mr-2"></i>
                                    Configurações da API
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="api_base_url">API Base URL</label>
                                    <input type="url" class="form-control @error('api_base_url') is-invalid @enderror" 
                                           id="api_base_url" name="api_base_url" 
                                           value="{{ old('api_base_url', $channel->api_base_url) }}">
                                    @error('api_base_url')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="form-group">
                                    <label for="api_config">Configurações da API (JSON)</label>
                                    <textarea class="form-control @error('api_config') is-invalid @enderror" 
                                              id="api_config" name="api_config" rows="4" 
                                              placeholder='{"api_key": "sua-chave", "webhook_url": "https://..."}'>{{ old('api_config', is_array($channel->api_config) ? json_encode($channel->api_config, JSON_PRETTY_PRINT) : $channel->api_config) }}</textarea>
                                    @error('api_config')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Configurações específicas da API em formato JSON
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Configurações OAuth -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-key mr-2"></i>
                                    Configurações OAuth
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="requires_oauth" name="requires_oauth" 
                                           value="1" {{ old('requires_oauth', $channel->requires_oauth) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="requires_oauth">
                                        <strong>Requer OAuth</strong>
                                    </label>
                                    <small class="form-text text-muted">
                                        Marque se este canal requer autenticação OAuth
                                    </small>
                                </div>
                                
                                <div id="oauth-config" style="{{ old('requires_oauth', $channel->requires_oauth) ? '' : 'display: none;' }}">
                                    <div class="form-group">
                                        <label for="oauth_url">OAuth URL</label>
                                        <input type="url" class="form-control @error('oauth_url') is-invalid @enderror" 
                                               id="oauth_url" name="oauth_url" 
                                               value="{{ old('oauth_url', $channel->oauth_url) }}">
                                        @error('oauth_url')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="oauth_scopes">Escopos OAuth (JSON)</label>
                                        <textarea class="form-control @error('oauth_scopes') is-invalid @enderror" 
                                                  id="oauth_scopes" name="oauth_scopes" rows="3" 
                                                  placeholder='["read", "write", "admin"]'>{{ old('oauth_scopes', is_array($channel->oauth_scopes) ? json_encode($channel->oauth_scopes, JSON_PRETTY_PRINT) : $channel->oauth_scopes) }}</textarea>
                                        @error('oauth_scopes')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">
                                            Lista de escopos OAuth em formato JSON
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Configurações de Sincronização -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-sync mr-2"></i>
                                    Configurações de Sincronização
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="auto_sync_enabled" name="auto_sync_enabled" 
                                                   value="1" {{ old('auto_sync_enabled', $channel->auto_sync_enabled) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="auto_sync_enabled">
                                                <strong>Sincronização Automática</strong>
                                            </label>
                                            <small class="form-text text-muted">
                                                Habilitar sincronização automática
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="sync_interval_minutes">Intervalo de Sincronização (minutos)</label>
                                            <input type="number" class="form-control @error('sync_interval_minutes') is-invalid @enderror" 
                                                   id="sync_interval_minutes" name="sync_interval_minutes" 
                                                   value="{{ old('sync_interval_minutes', $channel->sync_interval_minutes) }}" 
                                                   min="1" max="1440">
                                            @error('sync_interval_minutes')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recursos Suportados -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-tools mr-2"></i>
                                    Recursos Suportados
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="supported_features">Recursos Suportados (JSON)</label>
                                    <textarea class="form-control @error('supported_features') is-invalid @enderror" 
                                              id="supported_features" name="supported_features" rows="4" 
                                              placeholder='["bookings", "availability", "rates", "content"]'>{{ old('supported_features', is_array($channel->supported_features) ? json_encode($channel->supported_features, JSON_PRETTY_PRINT) : $channel->supported_features) }}</textarea>
                                    @error('supported_features')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        Lista de recursos suportados pelo canal em formato JSON
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-power-off mr-2"></i>
                                    Status
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                                           value="1" {{ old('is_active', $channel->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">
                                        <strong>Canal Ativo</strong>
                                    </label>
                                    <small class="form-text text-muted">
                                        Marque para ativar o canal no sistema
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i>
                                Salvar Alterações
                            </button>
                            <a href="{{ route('channels.show', $channel) }}" class="btn btn-secondary ml-2">
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
    // Toggle OAuth configuration visibility
    $('#requires_oauth').on('change', function() {
        if ($(this).is(':checked')) {
            $('#oauth-config').show();
        } else {
            $('#oauth-config').hide();
        }
    });
    
    // Auto-generate channel ID based on name
    $('#name').on('input', function() {
        const name = $(this).val();
        if (name && !$('#channel_id').val()) {
            const channelId = name.toUpperCase().replace(/\s+/g, '');
            $('#channel_id').val(channelId);
        }
    });
    
    // Validate JSON fields
    function validateJSON(fieldId) {
        const field = $('#' + fieldId);
        const value = field.val().trim();
        
        if (value) {
            try {
                JSON.parse(value);
                field.removeClass('is-invalid');
                field.addClass('is-valid');
            } catch (e) {
                field.removeClass('is-valid');
                field.addClass('is-invalid');
            }
        } else {
            field.removeClass('is-invalid is-valid');
        }
    }
    
    // Validate JSON on input
    $('#api_config, #oauth_scopes, #supported_features').on('input', function() {
        validateJSON($(this).attr('id'));
    });
    
    // Initial validation
    validateJSON('api_config');
    validateJSON('oauth_scopes');
    validateJSON('supported_features');
});
</script>
@endpush
