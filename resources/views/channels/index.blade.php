@extends('layouts/app')

@section('title', 'Canais de Distribuição')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-broadcast-tower mr-2"></i>
                        Canais de Distribuição
                    </h3>
                    <a href="{{ route('channels.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus mr-1"></i>
                        Novo Canal
                    </a>
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

                    <div class="row">
                        @forelse($channels as $channel)
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            @if($channel->logo_url)
                                                <img src="{{ $channel->logo_url }}" alt="{{ $channel->name }}" 
                                                     class="mr-2" style="width: 24px; height: 24px;">
                                            @endif
                                            <h5 class="mb-0">{{ $channel->name }}</h5>
                                        </div>
                                        {!! $channel->status_badge !!}
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text text-muted">{{ $channel->description }}</p>
                                        
                                        <div class="row text-center mb-3">
                                            <div class="col-6">
                                                <div class="border rounded p-2">
                                                    <div class="h4 mb-0 text-primary">{{ $channel->properties()->count() }}</div>
                                                    <small class="text-muted">Propriedades</small>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="border rounded p-2">
                                                    <div class="h4 mb-0 text-success">{{ $channel->bookings()->count() }}</div>
                                                    <small class="text-muted">Reservas</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-2">
                                            <small class="text-muted">Recursos:</small>
                                            <div class="mt-1">
                                                @foreach((is_array($channel->supported_features) ? $channel->supported_features : json_decode($channel->supported_features, true) ?? []) as $feature)
                                                    <span class="badge badge-secondary mr-1">{{ $feature }}</span>
                                                @endforeach
                                            </div>
                                        </div>

                                        @if($channel->requires_oauth)
                                            <div class="alert alert-warning py-2 px-3 mb-2">
                                                <small><i class="fas fa-key mr-1"></i>Requer OAuth</small>
                                            </div>
                                        @endif

                                        <div class="mb-2">
                                            <small class="text-muted">Sincronização:</small>
                                            <div class="mt-1">
                                                @if($channel->auto_sync_enabled)
                                                    <span class="badge badge-success">Automática</span>
                                                @else
                                                    <span class="badge badge-secondary">Manual</span>
                                                @endif
                                                <small class="text-muted ml-2">{{ $channel->sync_interval_minutes }}min</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <div class="btn-group w-100" role="group">
                                            <a href="{{ route('channels.show', $channel) }}" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('channels.edit', $channel) }}" class="btn btn-outline-secondary btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if($channel->website_url)
                                                <a href="{{ $channel->website_url }}" target="_blank" class="btn btn-outline-info btn-sm">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="text-center py-5">
                                    <i class="fas fa-broadcast-tower fa-3x text-muted mb-3"></i>
                                    <h4 class="text-muted">Nenhum canal encontrado</h4>
                                    <p class="text-muted">Comece criando seu primeiro canal de distribuição.</p>
                                    <a href="{{ route('channels.create') }}" class="btn btn-primary">
                                        <i class="fas fa-plus mr-1"></i>
                                        Criar Primeiro Canal
                                    </a>
                                </div>
                            </div>
                        @endforelse
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
