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

<!-- Stats Cards -->
@php
    $totalProperties = count($mappedProperties ?? []);
    $activeCount = collect($mappedProperties ?? [])->filter(function ($p) {
        return ($p['local']['is_active'] ?? false) && ($p['local']['status'] ?? '') === 'active';
    })->count();
    $syncedCount = collect($mappedProperties ?? [])->filter(function ($p) {
        return $p['is_synced'] ?? false;
    })->count();
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
                        <small class="text-muted">Criadas via NextPax</small>
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
                        <small class="text-muted">Status: Ativo</small>
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
    <small class="text-muted"><i class="fas fa-info-circle me-1"></i>As propriedades são criadas via API NextPax e sincronizadas automaticamente.</small>
</div>

<!-- Properties List -->
<div class="row">
    @if(isset($mappedProperties) && count($mappedProperties) > 0)
        @foreach($mappedProperties as $mappedProperty)
            @php
                $property = $mappedProperty['local'];
                $apiData = $mappedProperty['api'];
                $isSynced = $mappedProperty['is_synced'];
                $status = $mappedProperty['status'];
            @endphp
            
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">{{ $property->name ?? 'Propriedade sem nome' }}</h6>
                        <div class="d-flex align-items-center gap-2">
                            @if($isSynced)
                                <span class="badge bg-success">Sincronizada</span>
                            @else
                                <span class="badge bg-warning">Pendente</span>
                            @endif
                            @if($status === 'active')
                                <span class="badge bg-primary">Ativa</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($status) }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-6">
                                <small class="text-muted">ID NextPax</small>
                                <div class="fw-bold text-monospace small">{{ Str::limit($property->channel_property_id ?? 'N/A', 20) }}</div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Tipo</small>
                                <div class="fw-bold">{{ ucfirst($property->property_type ?? 'N/A') }}</div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-6">
                                <small class="text-muted">Ocupação</small>
                                <div class="fw-bold">{{ $property->max_occupancy ?? 'N/A' }}</div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Adultos</small>
                                <div class="fw-bold">{{ $property->max_adults ?? 'N/A' }}</div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted">Endereço</small>
                            <div class="fw-bold small">
                                {{ $property->address }}, {{ $property->city }}, {{ $property->state }}, {{ $property->postal_code }}
                            </div>
                        </div>

                        @if($status === 'active' && $property->base_price)
                            <div class="mb-3">
                                <small class="text-muted">Preços</small>
                                <div class="fw-bold text-success">
                                    {{ $property->currency }} {{ number_format($property->base_price, 2, ',', '.') }} / noite
                                </div>
                                @if($property->cleaning_fee)
                                    <div class="text-muted small">
                                        Limpeza: {{ $property->currency }} {{ number_format($property->cleaning_fee, 2, ',', '.') }}
                                    </div>
                                @endif
                                @if($property->security_deposit)
                                    <div class="text-muted small">
                                        Caução: {{ $property->currency }} {{ number_format($property->security_deposit, 2, ',', '.') }}
                                    </div>
                                @endif
                            </div>
                        @endif
                        
                        @if($apiData)
                            <div class="mb-3">
                                <small class="text-muted">Dados da API</small>
                                <div class="text-success small">
                                    <i class="fas fa-check-circle me-1"></i>Dados atualizados da NextPax
                                </div>
                            </div>
                        @endif
                        
                        <div class="d-grid gap-2">
                            <a href="{{ route('properties.show', $property->id) }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-eye me-1"></i> Ver Detalhes
                            </a>
                            <a href="{{ route('properties.channels.index', $property->id) }}" class="btn btn-info btn-sm">
                                <i class="fas fa-broadcast-tower me-1"></i> Gerenciar Canais
                            </a>
                            
                            @if($status === 'draft')
                                <button class="btn btn-success btn-sm" onclick="activateProperty({{ $property->id }})">
                                    <i class="fas fa-play me-1"></i> Ativar Propriedade
                                </button>
                                <button class="btn btn-warning btn-sm" onclick="configurePricing({{ $property->id }})">
                                    <i class="fas fa-dollar-sign me-1"></i> Configurar Preços
                                </button>
                            @elseif($status === 'active')
                                <button class="btn btn-outline-info btn-sm" onclick="viewInApi({{ $property->id }})">
                                    <i class="fas fa-external-link-alt me-1"></i> Ver na API
                                </button>
                                <button class="btn btn-outline-warning btn-sm" onclick="configurePricing({{ $property->id }})">
                                    <i class="fas fa-edit me-1"></i> Editar Preços
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @else
        <div class="col-12">
            <div class="text-center py-5">
                <i class="fas fa-home fa-3x text-muted mb-3"></i>
                <h5>Nenhuma propriedade encontrada</h5>
                <p class="text-muted">Comece criando sua primeira propriedade na NextPax.</p>
                <a href="{{ route('properties.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Criar Primeira Propriedade
                </a>
            </div>
        </div>
    @endif
</div>

<script>
function viewInApi(propertyId) {
    // Mostrar loading
    Swal.fire({
        title: 'Buscando dados da API...',
        text: 'Aguarde enquanto consultamos a NextPax',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Buscar dados da API
    fetch(`/properties/${propertyId}/api-data`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showApiData(data.data, data.property);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: data.error || 'Erro ao buscar dados da API',
                    confirmButtonText: 'OK'
                });
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: 'Erro ao processar solicitação: ' + error,
                confirmButtonText: 'OK'
            });
        });
}

function showApiData(apiData, localProperty) {
    let html = `
        <div class="text-start">
            <h6 class="mb-3">Dados da API NextPax</h6>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <strong>Status na API:</strong>
                        <span class="badge bg-${apiData.status === 'active' ? 'success' : 'warning'} ms-2">
                            ${apiData.status === 'active' ? 'Ativa' : 'Inativa'}
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <strong>Status Local:</strong>
                        <span class="badge bg-${localProperty.status === 'active' ? 'success' : 'secondary'} ms-2">
                            ${localProperty.status === 'active' ? 'Ativa' : 'Draft'}
                        </span>
                    </div>
                </div>
            </div>
    `;

    // Dados da propriedade
    if (apiData.property) {
        html += `
            <div class="mb-3">
                <strong>Dados da Propriedade:</strong>
                <div class="small text-muted mt-1">
                    <div>Nome: ${apiData.property.general?.name || 'N/A'}</div>
                    <div>Tipo: ${apiData.property.general?.typeCode || 'N/A'}</div>
                    <div>Status: ${apiData.property.general?.status || 'N/A'}</div>
                </div>
            </div>
        `;
    }

    // Dados de preços
    if (apiData.pricing && apiData.pricing.length > 0) {
        html += `
            <div class="mb-3">
                <strong>Preços Configurados:</strong>
                <div class="small text-muted mt-1">
                    <div>Moeda: ${apiData.pricing[0]?.currency || 'N/A'}</div>
                    <div>Tipo: ${apiData.pricing[0]?.pricingType || 'N/A'}</div>
                    <div>Rates: ${apiData.pricing.length} configuração(ões)</div>
                </div>
            </div>
        `;
    } else {
        html += `
            <div class="mb-3">
                <strong>Preços:</strong>
                <span class="text-warning">Não configurados na API</span>
            </div>
        `;
    }

    // Dados de disponibilidade
    if (apiData.availability && apiData.availability.length > 0) {
        html += `
            <div class="mb-3">
                <strong>Disponibilidade:</strong>
                <div class="small text-muted mt-1">
                    <div>Períodos: ${apiData.availability.length}</div>
                    <div>Status: ${apiData.status === 'active' ? 'Disponível' : 'Indisponível'}</div>
                </div>
            </div>
        `;
    } else {
        html += `
            <div class="mb-3">
                <strong>Disponibilidade:</strong>
                <span class="text-warning">Não configurada na API</span>
            </div>
        `;
    }

    // Comparação com dados locais
    html += `
        <hr>
        <div class="mb-3">
            <strong>Comparação Local vs API:</strong>
            <div class="small text-muted mt-1">
                <div>Preço Base: ${localProperty.base_price ? 'R$ ' + localProperty.base_price : 'Não definido'}</div>
                <div>Moeda: ${localProperty.currency || 'Não definida'}</div>
                <div>Status: ${localProperty.status === 'active' ? 'Ativa' : 'Draft'}</div>
            </div>
        </div>
    `;

    html += '</div>';

    Swal.fire({
        title: 'Dados da API NextPax',
        html: html,
        width: '600px',
        confirmButtonText: 'Fechar',
        showCloseButton: true
    });
}

function activateProperty(propertyId) {
    if (confirm('Tem certeza que deseja ativar esta propriedade na NextPax? Ela ficará disponível para reservas.')) {
        fetch(`/properties/${propertyId}/activate`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: data.message,
                    confirmButtonText: 'OK'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: data.error,
                    confirmButtonText: 'OK'
                });
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Erro!',
                text: 'Erro ao processar solicitação: ' + error,
                confirmButtonText: 'OK'
            });
        });
    }
}

function configurePricing(propertyId) {
    // Abrir modal de configuração de preços
    Swal.fire({
        title: 'Configurar Preços da Propriedade',
        html: `
            <form id="pricingForm">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Preço Base (por noite)</label>
                            <input type="number" class="form-control" id="base_price" name="base_price" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Moeda</label>
                            <select class="form-select" id="currency" name="currency" required>
                                <option value="BRL">BRL (Real)</option>
                                <option value="USD">USD (Dólar)</option>
                                <option value="EUR">EUR (Euro)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Taxa de Limpeza</label>
                            <input type="number" class="form-control" id="cleaning_fee" name="cleaning_fee" step="0.01" min="0">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Caução</label>
                            <input type="number" class="form-control" id="security_deposit" name="security_deposit" step="0.01" min="0">
                        </div>
                    </div>
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Salvar Preços',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const form = document.getElementById('pricingForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            
            // Validação básica
            if (!data.base_price || data.base_price <= 0) {
                Swal.showValidationMessage('Preço base é obrigatório e deve ser maior que zero');
                return false;
            }
            
            return data;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Enviar dados para o servidor
            fetch(`/properties/${propertyId}/pricing`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(result.value)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sucesso!',
                        text: data.message,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erro!',
                        text: data.error,
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Erro!',
                    text: 'Erro ao processar solicitação: ' + error,
                    confirmButtonText: 'OK'
                });
            });
        }
    });
}
</script>
@endsection 