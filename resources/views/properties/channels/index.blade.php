@extends('layouts/app')

@section('title', 'Canais da Propriedade')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title">
                            <i class="fas fa-broadcast-tower mr-2"></i>
                            Canais de Distribuição
                        </h3>
                        <small class="text-muted">{{ $property->name }}</small>
                    </div>
                    <div>
                        <span class="badge badge-primary">{{ $property->getConnectedChannelsCount() }} conectados</span>
                        <span class="badge badge-success">{{ $property->getActiveChannelsCount() }} ativos</span>
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

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                    @endif

                    <!-- Canais Conectados -->
                    @if($property->channels->count() > 0)
                        <div class="mb-4">
                            <h5 class="mb-3">
                                <i class="fas fa-link mr-2"></i>
                                Canais Conectados
                            </h5>
                            <div class="row">
                                @foreach($property->channels as $channel)
                                    @php
                                        $connection = $property->getChannelConnection($channel->channel_id);
                                    @endphp
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card h-100">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <div class="d-flex align-items-center">
                                                    @if($channel->logo_url)
                                                        <img src="{{ $channel->logo_url }}" alt="{{ $channel->name }}" 
                                                             class="mr-2" style="width: 20px; height: 20px;">
                                                    @endif
                                                    <strong>{{ $channel->name }}</strong>
                                                </div>
                                                @if($connection)
                                                    @if($connection->is_active)
                                                        <span class="badge badge-success">Ativo</span>
                                                    @else
                                                        <span class="badge badge-secondary">Inativo</span>
                                                    @endif
                                                @endif
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-2">
                                                    <small class="text-muted">ID no Canal:</small>
                                                    <div class="font-weight-bold">{{ $connection->channel_property_id ?? 'N/A' }}</div>
                                                </div>
                                                
                                                @if($connection->channel_room_id)
                                                    <div class="mb-2">
                                                        <small class="text-muted">ID do Quarto:</small>
                                                        <div class="font-weight-bold">{{ $connection->channel_room_id }}</div>
                                                    </div>
                                                @endif
                                                
                                                <div class="mb-2">
                                                    <small class="text-muted">Status:</small>
                                                    {!! $connection->channel_status_badge !!}
                                                </div>
                                                
                                                <div class="mb-2">
                                                    <small class="text-muted">Conteúdo:</small>
                                                    {!! $connection->content_status_badge !!}
                                                </div>
                                                
                                                <div class="mb-2">
                                                    <small class="text-muted">Última Sincronização:</small>
                                                    <div class="small">
                                                        @if($connection->last_successful_sync_at)
                                                            {{ $connection->last_successful_sync_at->format('d/m/Y H:i') }}
                                                        @else
                                                            Nunca sincronizado
                                                        @endif
                                                    </div>
                                                </div>
                                                
                                                @if($connection->last_sync_error)
                                                    <div class="alert alert-danger py-2 px-3 mb-2">
                                                        <small><i class="fas fa-exclamation-triangle mr-1"></i>Erro na sincronização</small>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="card-footer">
                                                <div class="btn-group w-100" role="group">
                                                    <a href="{{ route('properties.channels.show', [$property, $channel]) }}" 
                                                       class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('properties.channels.edit', [$property, $channel]) }}" 
                                                       class="btn btn-outline-secondary btn-sm">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('properties.channels.sync', [$property, $channel]) }}" 
                                                          method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-outline-info btn-sm" 
                                                                title="Sincronizar">
                                                            <i class="fas fa-sync"></i>
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('properties.channels.destroy', [$property, $channel]) }}" 
                                                          method="POST" class="d-inline"
                                                          onsubmit="return confirm('Desconectar deste canal?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger btn-sm" 
                                                                title="Desconectar">
                                                            <i class="fas fa-unlink"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Canais Disponíveis -->
                    @if($availableChannels->count() > 0)
                        <div class="mb-4">
                            <h5 class="mb-3">
                                <i class="fas fa-plus mr-2"></i>
                                Canais Disponíveis
                            </h5>
                            <div class="row">
                                @foreach($availableChannels as $channel)
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card h-100">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <div class="d-flex align-items-center">
                                                    @if($channel->logo_url)
                                                        <img src="{{ $channel->logo_url }}" alt="{{ $channel->name }}" 
                                                             class="mr-2" style="width: 20px; height: 20px;">
                                                    @endif
                                                    <strong>{{ $channel->name }}</strong>
                                                </div>
                                                <span class="badge badge-secondary">Disponível</span>
                                            </div>
                                            <div class="card-body">
                                                <p class="card-text text-muted small">{{ $channel->description }}</p>
                                                
                                                @if($channel->requires_oauth)
                                                    <div class="alert alert-warning py-2 px-3 mb-2">
                                                        <small><i class="fas fa-key mr-1"></i>Requer OAuth</small>
                                                    </div>
                                                @endif
                                                
                                                <div class="mb-2">
                                                    <small class="text-muted">Recursos:</small>
                                                    <div class="mt-1">
                                                        @foreach(array_slice($channel->supported_features ?? [], 0, 3) as $feature)
                                                            <span class="badge badge-secondary mr-1 small">{{ $feature }}</span>
                                                        @endforeach
                                                        @if(count($channel->supported_features ?? []) > 3)
                                                            <span class="badge badge-light small">+{{ count($channel->supported_features) - 3 }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-footer">
                                                <a href="{{ route('properties.channels.create', [$property, $channel]) }}" 
                                                   class="btn btn-primary btn-sm w-100">
                                                    <i class="fas fa-link mr-1"></i>
                                                    Conectar
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($property->channels->count() == 0 && $availableChannels->count() == 0)
                        <div class="text-center py-5">
                            <i class="fas fa-broadcast-tower fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">Nenhum canal disponível</h4>
                            <p class="text-muted">Não há canais configurados no sistema.</p>
                            <a href="{{ route('channels.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus mr-1"></i>
                                Criar Primeiro Canal
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
});
</script>
@endpush
