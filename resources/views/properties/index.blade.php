@extends('layouts/app')

@section('title', 'FrontDesk - Propriedades')

@section('breadcrumb')
<li class="breadcrumb-item active" aria-current="page">Propriedades</li>
@endsection

@section('content')
<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <div>
        <h1 class="h2">Propriedades</h1>
        <p class="text-muted">Gerencie suas propriedades e configurações</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                <i class="fas fa-sync-alt me-1"></i> Atualizar
            </button>
        </div>
        <a href="{{ route('properties.create') }}" class="btn btn-sm btn-primary">
            <i class="fas fa-plus me-1"></i> Nova Propriedade
        </a>
    </div>
</div>

@if(isset($error))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        {{ $error }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Disclaimer de Integração NextPax removido --}}

<!-- Stats Cards -->
@php
    $apiCollection = collect($apiProperties ?? []);
    $totalProperties = $apiCollection->count();
    $activeCount = $apiCollection->filter(function ($p) {
        $status = strtolower($p['general']['status'] ?? ($p['status'] ?? ''));
        return in_array($status, ['active','online','published','enabled']);
    })->count();
    // Sincronizadas NextPax: propriedades presentes localmente com property_id (UUID NextPax) preenchido
    $syncedCount = ($localProperties ?? collect())
        ->filter(function ($prop) { return !empty($prop->property_id); })
        ->count();
@endphp

<div class="row mb-4">
    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total de Propriedades
                        </div>
                        <div class="display-6 fw-bold text-dark">{{ $totalProperties }}</div>
                        <small class="text-muted">Fonte: NextPax API</small>
                    </div>
                    <div class="text-end">
                        <i class="fas fa-home fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Propriedades Ativas
                        </div>
                        <div class="display-6 fw-bold text-dark">{{ $activeCount }}</div>
                        <small class="text-muted">Status: active/online/published</small>
                    </div>
                    <div class="text-end">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Sincronizadas NextPax
                        </div>
                        <div class="display-6 fw-bold text-dark">{{ $syncedCount }}</div>
                        <small class="text-muted">Vínculo local ↔ NextPax</small>
                    </div>
                    <div class="text-end">
                        <i class="fas fa-sync fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Aviso curto -->
<div class="mb-3">
    <small class="text-muted"><i class="fas fa-info-circle me-1"></i>As propriedades são criadas via API e já passam por validação.</small>
</div>

<!-- Properties List -->
<div class="row">
    @if(isset($localProperties) && count($localProperties) > 0)
        @foreach($localProperties as $property)
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">{{ $property->name ?? 'Propriedade sem nome' }}</h6>
                        <div class="dropdown">
                            <button class="btn btn-link btn-sm text-muted" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('properties.show', $property->id) }}">
                                    <i class="fas fa-eye me-2"></i>Ver Detalhes
                                </a></li>
                                <li><a class="dropdown-item" href="{{ route('properties.edit', $property->id) }}">
                                    <i class="fas fa-edit me-2"></i>Editar
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="deleteProperty({{ $property->id }})">
                                    <i class="fas fa-trash me-2"></i>Excluir
                                </a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-6">
                                <small class="text-muted">ID</small>
                                <div class="fw-bold">{{ $property->property_id ?? 'N/A' }}</div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Status</small>
                                <div>
                                    {!! $property->status_badge ?? '<span class="badge bg-secondary">N/A</span>' !!}
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-6">
                                <small class="text-muted">Tipo</small>
                                <div class="fw-bold">{{ $property->property_type_text ?? 'N/A' }}</div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Quartos</small>
                                <div class="fw-bold">{{ $property->bedrooms ?? 'N/A' }}</div>
                            </div>
                        </div>
                        
                        @if($property->description)
                            <div class="mb-3">
                                <small class="text-muted">Descrição</small>
                                <p class="mb-0">{{ Str::limit($property->description, 100) }}</p>
                            </div>
                        @endif

                        @if($property->base_price)
                            <div class="mb-3">
                                <small class="text-muted">Preço Base</small>
                                <div class="fw-bold text-success">{{ $property->formatted_price }}</div>
                            </div>
                        @endif
                        
                        <div class="d-grid">
                            <a href="{{ route('properties.show', $property->id) }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-eye me-1"></i> Ver Detalhes
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @endif

    @if(isset($apiProperties) && count($apiProperties) > 0)
        @foreach($apiProperties as $property)
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header py-2 d-flex flex-row align-items-center justify-content-between bg-light">
                        <h6 class="m-0 fw-semibold text-dark text-truncate" title="{{ $property['general']['name'] ?? 'Propriedade NextPax' }}">
                            {{ $property['general']['name'] ?? 'Propriedade NextPax' }}
                        </h6>
                        <span class="badge bg-secondary">API</span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-6">
                                <small class="text-muted">ID</small>
                                <div class="fw-bold text-truncate" title="{{ $property['propertyId'] ?? 'N/A' }}">{{ $property['propertyId'] ?? 'N/A' }}</div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Status</small>
                                @php
                                    $status = strtolower($property['general']['status'] ?? ($property['status'] ?? ''));
                                    $statusClass = in_array($status, ['active','online','published','enabled']) ? 'success' : (in_array($status, ['disabled','offline']) ? 'secondary' : 'warning');
                                    $statusLabel = $status ? strtoupper($status) : 'N/A';
                                @endphp
                                <div><span class="badge bg-{{ $statusClass }}">{{ $statusLabel }}</span></div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <small class="text-muted">Tipo</small>
                                @php
                                    $typeMap = ['APP'=>'Apartamento','HOU'=>'Casa','HOT'=>'Hotel','HST'=>'Hostel','RSR'=>'Resort','VIL'=>'Vila','CAB'=>'Cabana','LOF'=>'Loft'];
                                    $type = $property['general']['typeCode'] ?? 'APP';
                                @endphp
                                <div class="fw-bold">{{ $typeMap[$type] ?? $type }}</div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Ocupação</small>
                                <div class="fw-bold">{{ $property['general']['maxOccupancy'] ?? 'N/A' }}</div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Endereço</small>
                            @php
                                $addr = $property['general']['address'] ?? [];
                                $street = $addr['street'] ?? '';
                                $city = $addr['city'] ?? 'N/A';
                                $state = $addr['state'] ?? '';
                                $country = $addr['countryCode'] ?? '';
                                $postal = $addr['postalCode'] ?? '';
                                $addressFull = ($street ? $street.', ' : '').$city.($state ? ', '.$state : '').($postal ? ', '.$postal : '').($country ? ', '.$country : '');
                            @endphp
                            <div class="fw-bold text-truncate" title="{{ $addressFull }}">{{ $addressFull }}</div>
                        </div>
                        @php
                            $desc = '';
                            if (!empty($property['descriptions']) && is_array($property['descriptions'])) {
                                $pt = collect($property['descriptions'])->firstWhere('language', 'PT');
                                $first = $pt ?: $property['descriptions'][0];
                                $desc = $first['text'] ?? '';
                            }
                            $imagesCount = is_array($property['images'] ?? null) ? count($property['images']) : 0;
                        @endphp
                        @if($desc)
                            <div class="mb-3 small text-muted">{{ Str::limit($desc, 120) }}</div>
                        @endif
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">Imagens: <strong>{{ $imagesCount }}</strong></small>
                            <a href="{{ route('properties.show', $property['propertyId']) }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-external-link-alt me-1"></i> Ver na API
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @endif

    @if((!isset($localProperties) || count($localProperties) == 0) && (!isset($apiProperties) || count($apiProperties) == 0))
        <div class="col-12">
            <div class="card shadow">
                <div class="card-body text-center py-5">
                    <i class="fas fa-home fa-4x text-muted mb-4"></i>
                    <h3 class="text-muted">Nenhuma propriedade encontrada</h3>
                    <p class="text-muted mb-4">Comece adicionando sua primeira propriedade para gerenciar reservas e hóspedes.</p>
                    <a href="{{ route('properties.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i> Adicionar Primeira Propriedade
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
function deleteProperty(propertyId) {
    if (confirm('Tem certeza que deseja excluir esta propriedade? Esta ação não pode ser desfeita.')) {
        fetch(`/properties/${propertyId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Propriedade excluída com sucesso!');
                location.reload();
            } else {
                alert('Erro ao excluir propriedade: ' + data.error);
            }
        })
        .catch(error => {
            alert('Erro ao processar solicitação: ' + error);
        });
    }
}
</script>
@endsection 