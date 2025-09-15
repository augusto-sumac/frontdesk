@extends('layouts/app')

@section('title', 'Propriedades do Canal')

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
                            <h3 class="card-title mb-0">
                                <i class="fas fa-home mr-2"></i>
                                Propriedades Conectadas
                            </h3>
                            <small class="text-muted">{{ $channel->name }} ({{ $channel->channel_id }})</small>
                        </div>
                    </div>
                    <div>
                        <form action="{{ route('channels.sync', $channel) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm mr-2" 
                                    onclick="return confirm('Deseja sincronizar as propriedades deste canal?')">
                                <i class="fas fa-sync-alt mr-1"></i>
                                Sincronizar Propriedades
                            </button>
                        </form>
                        <a href="{{ route('channels.show', $channel) }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-arrow-left mr-1"></i>
                            Voltar ao Canal
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Estatísticas -->
                    @php
                        $totalProperties = $connectedProperties->count();
                        $activeProperties = $connectedProperties->where('is_active', true)->count();
                        $lastSyncCount = $connectedProperties->where('last_sync', '!=', null)->count();
                        $errorCount = $connectedProperties->where('sync_error', '!=', null)->count();
                    @endphp

                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total de Propriedades
                                            </div>
                                            <div class="display-6 fw-bold text-dark">{{ $totalProperties }}</div>
                                            <small class="text-muted">Conectadas ao canal</small>
                                        </div>
                                        <div class="text-end">
                                            <i class="fas fa-home fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Propriedades Ativas
                                            </div>
                                            <div class="display-6 fw-bold text-dark">{{ $activeProperties }}</div>
                                            <small class="text-muted">Sincronização ativa</small>
                                        </div>
                                        <div class="text-end">
                                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Última Sincronização
                                            </div>
                                            <div class="display-6 fw-bold text-dark">{{ $lastSyncCount }}</div>
                                            <small class="text-muted">Propriedades sincronizadas</small>
                                        </div>
                                        <div class="text-end">
                                            <i class="fas fa-sync fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-danger shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                                Erros de Sincronização
                                            </div>
                                            <div class="display-6 fw-bold text-dark">{{ $errorCount }}</div>
                                            <small class="text-muted">Precisam de atenção</small>
                                        </div>
                                        <div class="text-end">
                                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de Propriedades -->
                    @if($connectedProperties->count() > 0)
                        <div class="row">
                            @foreach($connectedProperties as $item)
                                @php
                                    $property = $item['property'];
                                    $connection = $item['connection'];
                                    $isActive = $item['is_active'];
                                    $lastSync = $item['last_sync'];
                                    $syncError = $item['sync_error'];
                                @endphp
                                
                                <div class="col-xl-4 col-md-6 mb-4">
                                    <div class="card shadow h-100">
                                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                            <h6 class="m-0 font-weight-bold text-primary">{{ $property->name ?? 'Propriedade sem nome' }}</h6>
                                            <div class="d-flex align-items-center gap-2">
                                                @if($isActive)
                                                    <span class="badge bg-success">Ativo</span>
                                                @else
                                                    <span class="badge bg-secondary">Inativo</span>
                                                @endif
                                                @if($syncError)
                                                    <span class="badge bg-danger">Erro</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="row mb-3">
                                                <div class="col-6">
                                                    <small class="text-muted">ID NextPax</small>
                                                    <div class="fw-bold text-monospace small">{{ Str::limit($property->property_id ?? 'N/A', 20) }}</div>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted">ID no Canal</small>
                                                    <div class="fw-bold text-monospace small">{{ Str::limit($connection->channel_property_id ?? 'N/A', 20) }}</div>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-6">
                                                    <small class="text-muted">Tipo</small>
                                                    <div class="fw-bold">{{ ucfirst($property->property_type ?? 'N/A') }}</div>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted">Ocupação</small>
                                                    <div class="fw-bold">{{ $property->max_occupancy ?? 'N/A' }}</div>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <small class="text-muted">Endereço</small>
                                                <div class="fw-bold small">
                                                    {{ $property->address }}, {{ $property->city }}, {{ $property->state }}
                                                </div>
                                            </div>

                                            @if($connection)
                                                <div class="mb-3">
                                                    <small class="text-muted">Status da Conexão</small>
                                                    <div class="fw-bold">
                                                        {!! $connection->channel_status_badge !!}
                                                        {!! $connection->content_status_badge !!}
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <small class="text-muted">Última Sincronização</small>
                                                    <div class="fw-bold small">
                                                        @if($lastSync)
                                                            {{ $lastSync->format('d/m/Y H:i') }}
                                                        @else
                                                            <span class="text-muted">Nunca sincronizado</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                
                                                @if($syncError)
                                                    <div class="alert alert-danger py-2 px-3 mb-3">
                                                        <small><i class="fas fa-exclamation-triangle mr-1"></i>{{ Str::limit($syncError, 100) }}</small>
                                                    </div>
                                                @endif
                                            @endif
                                            
                                            @if($property->base_price)
                                                <div class="mb-3">
                                                    <small class="text-muted">Preço Base</small>
                                                    <div class="fw-bold text-success">
                                                        {{ $property->currency }} {{ number_format($property->base_price, 2, ',', '.') }} / noite
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="card-footer">
                                            <div class="btn-group w-100" role="group">
                                                <a href="{{ route('properties.show', $property->id) }}" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('properties.channels.show', [$property->id, $channel->channel_id]) }}" class="btn btn-outline-info btn-sm">
                                                    <i class="fas fa-broadcast-tower"></i>
                                                </a>
                                                @if($connection)
                                                    <form action="{{ route('properties.channels.sync', [$property->id, $channel->channel_id]) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-outline-success btn-sm" title="Sincronizar">
                                                            <i class="fas fa-sync"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-home fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">Nenhuma propriedade conectada</h4>
                            <p class="text-muted">Este canal ainda não possui propriedades conectadas.</p>
                            <a href="{{ route('properties.index') }}" class="btn btn-primary">
                                <i class="fas fa-plus mr-1"></i>
                                Conectar Propriedades
                            </a>
                        </div>
                    @endif
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
    
    // Confirm sync action
    $('form[action*="/sync"]').on('submit', function(e) {
        if (!confirm('Tem certeza que deseja sincronizar esta propriedade?')) {
            e.preventDefault();
        }
    });
});
</script>
@endpush
