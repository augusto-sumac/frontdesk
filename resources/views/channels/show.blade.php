@extends('layouts/app')

@section('title', 'Detalhes do Canal')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        @if($channel->logo_url)
                            <img src="{{ $channel->logo_url }}" alt="{{ $channel->name }}" 
                                 class="mr-3" style="width: 32px; height: 32px;">
                        @endif
                        <div>
                            <h3 class="card-title mb-0">{{ $channel->name }}</h3>
                            <small class="text-muted">{{ $channel->channel_id }}</small>
                        </div>
                    </div>
                    <div>
                        {!! $channel->status_badge !!}
                        <a href="{{ route('channels.edit', $channel) }}" class="btn btn-outline-primary btn-sm ml-2">
                            <i class="fas fa-edit mr-1"></i>
                            Editar
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        Informações do Canal
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <dl class="row">
                                        <dt class="col-sm-3">Descrição:</dt>
                                        <dd class="col-sm-9">{{ $channel->description ?: 'Não informado' }}</dd>
                                        
                                        <dt class="col-sm-3">Website:</dt>
                                        <dd class="col-sm-9">
                                            @if($channel->website_url)
                                                <a href="{{ $channel->website_url }}" target="_blank">
                                                    {{ $channel->website_url }}
                                                    <i class="fas fa-external-link-alt ml-1"></i>
                                                </a>
                                            @else
                                                Não informado
                                            @endif
                                        </dd>
                                        
                                        <dt class="col-sm-3">API Base URL:</dt>
                                        <dd class="col-sm-9">{{ $channel->api_base_url ?: 'Não configurado' }}</dd>
                                        
                                        <dt class="col-sm-3">OAuth:</dt>
                                        <dd class="col-sm-9">
                                            @if($channel->requires_oauth)
                                                <span class="badge badge-warning">Requerido</span>
                                                @if($channel->oauth_url)
                                                    <br><small class="text-muted">URL: {{ $channel->oauth_url }}</small>
                                                @endif
                                            @else
                                                <span class="badge badge-secondary">Não requerido</span>
                                            @endif
                                        </dd>
                                        
                                        <dt class="col-sm-3">Sincronização:</dt>
                                        <dd class="col-sm-9">
                                            @if($channel->auto_sync_enabled)
                                                <span class="badge badge-success">Automática</span>
                                            @else
                                                <span class="badge badge-secondary">Manual</span>
                                            @endif
                                            <small class="text-muted ml-2">{{ $channel->sync_interval_minutes }} minutos</small>
                                        </dd>
                                    </dl>
                                </div>
                            </div>

                            <div class="card mt-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-cogs mr-2"></i>
                                        Recursos Suportados
                                    </h5>
                                </div>
                                <div class="card-body">
                                    @if($channel->supported_features)
                                        @foreach((is_array($channel->supported_features) ? $channel->supported_features : json_decode($channel->supported_features, true) ?? []) as $feature)
                                            <span class="badge badge-primary mr-2 mb-2">{{ ucfirst($feature) }}</span>
                                        @endforeach
                                    @else
                                        <p class="text-muted">Nenhum recurso configurado</p>
                                    @endif
                                </div>
                            </div>

                            @if($channel->requires_oauth && $channel->oauth_scopes)
                                <div class="card mt-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-key mr-2"></i>
                                            Escopos OAuth
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        @foreach((is_array($channel->oauth_scopes) ? $channel->oauth_scopes : json_decode($channel->oauth_scopes, true) ?? []) as $scope)
                                            <span class="badge badge-info mr-2 mb-2">{{ str_replace('_', ' ', $scope) }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-bar mr-2"></i>
                                        Estatísticas
                                    </h5>
                                </div>
                                <div class="card-body text-center">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="border rounded p-3 mb-3">
                                                <div class="h3 mb-0 text-primary">{{ $channel->properties()->count() }}</div>
                                                <small class="text-muted">Propriedades</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="border rounded p-3 mb-3">
                                                <div class="h3 mb-0 text-success">{{ $channel->bookings()->count() }}</div>
                                                <small class="text-muted">Reservas</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="border rounded p-3">
                                        <div class="h3 mb-0 text-info">{{ $channel->propertyChannels()->where('is_active', true)->count() }}</div>
                                        <small class="text-muted">Conexões Ativas</small>
                                    </div>
                                </div>
                            </div>

                            <div class="card mt-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-link mr-2"></i>
                                        Propriedades Conectadas
                                    </h5>
                                </div>
                                <div class="card-body">
                                    @if($channel->properties->count() > 0)
                                        @foreach($channel->properties as $property)
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <div>
                                                    <strong>{{ $property->name }}</strong>
                                                    <br><small class="text-muted">{{ $property->channel_property_id }}</small>
                                                </div>
                                                <div>
                                                    @php
                                                        $connection = $property->getChannelConnection($channel->channel_id);
                                                    @endphp
                                                    @if($connection)
                                                        @if($connection->is_active)
                                                            <span class="badge badge-success">Ativo</span>
                                                        @else
                                                            <span class="badge badge-secondary">Inativo</span>
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <p class="text-muted text-center">Nenhuma propriedade conectada</p>
                                    @endif
                                </div>
                            </div>

                            <div class="card mt-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-tools mr-2"></i>
                                        Ações
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="{{ route('channels.properties', $channel) }}" class="btn btn-outline-primary">
                                            <i class="fas fa-home mr-1"></i>
                                            Ver Propriedades Conectadas
                                        </a>
                                        
                                        <a href="{{ route('channels.edit', $channel) }}" class="btn btn-outline-secondary">
                                            <i class="fas fa-edit mr-1"></i>
                                            Editar Canal
                                        </a>
                                        
                                        @if($channel->website_url)
                                            <a href="{{ $channel->website_url }}" target="_blank" class="btn btn-outline-info">
                                                <i class="fas fa-external-link-alt mr-1"></i>
                                                Visitar Website
                                            </a>
                                        @endif
                                        
                                        <form action="{{ route('channels.destroy', $channel) }}" method="POST" 
                                              onsubmit="return confirm('Tem certeza que deseja remover este canal?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger w-100">
                                                <i class="fas fa-trash mr-1"></i>
                                                Remover Canal
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
});
</script>
@endpush
