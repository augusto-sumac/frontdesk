@extends('layouts/app')

@section('title', 'Criar Canal')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-plus mr-2"></i>
                        Criar Novo Canal
                    </h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('channels.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="channel_id">ID do Canal *</label>
                                    <input type="text" class="form-control @error('channel_id') is-invalid @enderror" 
                                           id="channel_id" name="channel_id" value="{{ old('channel_id') }}" 
                                           placeholder="Ex: AIR298, BOO142" required>
                                    @error('channel_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Identificador único do canal (ex: AIR298 para Airbnb)</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Nome do Canal *</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name') }}" 
                                           placeholder="Ex: Airbnb, Booking.com" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="slug">Slug *</label>
                                    <input type="text" class="form-control @error('slug') is-invalid @enderror" 
                                           id="slug" name="slug" value="{{ old('slug') }}" 
                                           placeholder="Ex: airbnb, booking-com" required>
                                    @error('slug')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">URL amigável (sem espaços, apenas letras e hífens)</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="logo_url">URL do Logo</label>
                                    <input type="url" class="form-control @error('logo_url') is-invalid @enderror" 
                                           id="logo_url" name="logo_url" value="{{ old('logo_url') }}" 
                                           placeholder="https://exemplo.com/logo.png">
                                    @error('logo_url')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Descrição</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3" 
                                      placeholder="Descrição do canal...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="website_url">Website</label>
                                    <input type="url" class="form-control @error('website_url') is-invalid @enderror" 
                                           id="website_url" name="website_url" value="{{ old('website_url') }}" 
                                           placeholder="https://www.exemplo.com">
                                    @error('website_url')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="api_base_url">URL Base da API</label>
                                    <input type="url" class="form-control @error('api_base_url') is-invalid @enderror" 
                                           id="api_base_url" name="api_base_url" value="{{ old('api_base_url') }}" 
                                           placeholder="https://api.exemplo.com">
                                    @error('api_base_url')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sync_interval_minutes">Intervalo de Sincronização (minutos)</label>
                                    <input type="number" class="form-control @error('sync_interval_minutes') is-invalid @enderror" 
                                           id="sync_interval_minutes" name="sync_interval_minutes" 
                                           value="{{ old('sync_interval_minutes', 60) }}" min="1" max="1440">
                                    @error('sync_interval_minutes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Configurações</label>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                                               value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            Canal Ativo
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="requires_oauth" name="requires_oauth" 
                                               value="1" {{ old('requires_oauth') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="requires_oauth">
                                            Requer Autenticação OAuth
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="auto_sync_enabled" name="auto_sync_enabled" 
                                               value="1" {{ old('auto_sync_enabled', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="auto_sync_enabled">
                                            Sincronização Automática
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Recursos Suportados</label>
                            <div class="row">
                                @php
                                    $features = ['listings', 'bookings', 'pricing', 'availability', 'messages', 'reviews', 'photos', 'calendar', 'rooms', 'hotels'];
                                @endphp
                                @foreach($features as $feature)
                                    <div class="col-md-3 col-sm-4 col-6">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="feature_{{ $feature }}" 
                                                   name="supported_features[]" value="{{ $feature }}" 
                                                   {{ in_array($feature, old('supported_features', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="feature_{{ $feature }}">
                                                {{ ucfirst($feature) }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="oauth_url">URL OAuth (se aplicável)</label>
                            <input type="url" class="form-control @error('oauth_url') is-invalid @enderror" 
                                   id="oauth_url" name="oauth_url" value="{{ old('oauth_url') }}" 
                                   placeholder="https://exemplo.com/oauth/authorize">
                            @error('oauth_url')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Escopos OAuth (se aplicável)</label>
                            <div class="row">
                                @php
                                    $scopes = ['read', 'write', 'read_listings', 'write_listings', 'read_bookings', 'write_bookings', 'read_pricing', 'write_pricing'];
                                @endphp
                                @foreach($scopes as $scope)
                                    <div class="col-md-3 col-sm-4 col-6">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="scope_{{ $scope }}" 
                                                   name="oauth_scopes[]" value="{{ $scope }}" 
                                                   {{ in_array($scope, old('oauth_scopes', [])) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="scope_{{ $scope }}">
                                                {{ str_replace('_', ' ', $scope) }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i>
                                Criar Canal
                            </button>
                            <a href="{{ route('channels.index') }}" class="btn btn-secondary ml-2">
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
    // Auto-generate slug from name
    $('#name').on('input', function() {
        const slug = $(this).val()
            .toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim('-');
        $('#slug').val(slug);
    });

    // Show/hide OAuth fields based on checkbox
    $('#requires_oauth').change(function() {
        if ($(this).is(':checked')) {
            $('#oauth_url').closest('.form-group').show();
            $('input[name="oauth_scopes[]"]').closest('.form-group').show();
        } else {
            $('#oauth_url').closest('.form-group').hide();
            $('input[name="oauth_scopes[]"]').closest('.form-group').hide();
        }
    }).trigger('change');
});
</script>
@endpush
