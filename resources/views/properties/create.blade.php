@extends('layouts.app')

@section('title', 'FrontDesk - Nova Propriedade')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('properties.index') }}">Propriedades</a></li>
<li class="breadcrumb-item active" aria-current="page">Nova Propriedade</li>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <div>
            <h1 class="h2">Nova Propriedade</h1>
            <p class="text-muted">Crie uma nova propriedade com informações essenciais</p>
        </div>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="{{ route('properties.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Voltar
            </a>
        </div>
    </div>

    <!-- Property Creation Form -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-home me-2"></i>Informações da Propriedade
                    </h5>
                </div>
                <div class="card-body">
                    <form id="propertyForm">
                        @csrf
                        
                        <!-- Basic Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">Informações Básicas</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nome da Propriedade *</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="property_type" class="form-label">Tipo de Propriedade *</label>
                                    <select class="form-select" id="property_type" name="property_type" required>
                                        <option value="">Selecione o tipo</option>
                                        <option value="apartment">Apartamento</option>
                                        <option value="house">Casa</option>
                                        <option value="hotel">Hotel</option>
                                        <option value="hostel">Hostel</option>
                                        <option value="resort">Resort</option>
                                        <option value="villa">Vila</option>
                                        <option value="cabin">Cabana</option>
                                        <option value="loft">Loft</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Address Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">Endereço</h6>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="address" class="form-label">Endereço *</label>
                                    <input type="text" class="form-control" id="address" name="address" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="city" class="form-label">Cidade *</label>
                                    <input type="text" class="form-control" id="city" name="city" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="state" class="form-label">Estado *</label>
                                    <select class="form-select" id="state" name="state" required>
                                        <option value="">Selecione o estado</option>
                                        <option value="SP">São Paulo</option>
                                        <option value="RJ">Rio de Janeiro</option>
                                        <option value="MG">Minas Gerais</option>
                                        <option value="RS">Rio Grande do Sul</option>
                                        <option value="PR">Paraná</option>
                                        <option value="SC">Santa Catarina</option>
                                        <option value="BA">Bahia</option>
                                        <option value="GO">Goiás</option>
                                        <option value="PE">Pernambuco</option>
                                        <option value="CE">Ceará</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="postal_code" class="form-label">CEP *</label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" placeholder="00000-000" required>
                                </div>
                            </div>
                        </div>

                        <!-- Capacity Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">Capacidade</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="max_occupancy" class="form-label">Ocupação Máxima *</label>
                                    <input type="number" class="form-control" id="max_occupancy" name="max_occupancy" min="1" max="20" value="2" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="max_adults" class="form-label">Adultos Máximos *</label>
                                    <input type="number" class="form-control" id="max_adults" name="max_adults" min="1" max="20" value="2" required>
                                </div>
                            </div>
                        </div>

                        <!-- Coordinates -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">Coordenadas Geográficas</h6>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    As coordenadas são obrigatórias para a criação da propriedade na NextPax
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="latitude" class="form-label">Latitude *</label>
                                    <input type="number" class="form-control" id="latitude" name="latitude" step="0.00000001" min="-90" max="90" required>
                                    <div class="form-text">Ex: -23.5505 (São Paulo)</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="longitude" class="form-label">Longitude *</label>
                                    <input type="number" class="form-control" id="longitude" name="longitude" step="0.00000001" min="-180" max="180" required>
                                    <div class="form-text">Ex: -46.6333 (São Paulo)</div>
                                </div>
                            </div>
                        </div>

                        <!-- Check-in/Check-out Times Info -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="alert alert-success">
                                    <h6 class="alert-heading">
                                        <i class="fas fa-clock me-2"></i>Horários Padrão
                                    </h6>
                                    <p class="mb-0">
                                        <strong>Check-in:</strong> 14:00 às 22:00<br>
                                        <strong>Check-out:</strong> 08:00 às 11:00
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="row">
                            <div class="col-12">
                                <hr class="my-4">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('properties.index') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-2"></i>Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Criar Propriedade
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar with Help -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-question-circle me-2"></i>Ajuda
                    </h6>
                </div>
                <div class="card-body">
                    <h6 class="text-primary">Campos Obrigatórios</h6>
                    <ul class="list-unstyled small">
                        <li><i class="fas fa-check text-success me-2"></i>Nome da propriedade</li>
                        <li><i class="fas fa-check text-success me-2"></i>Tipo de propriedade</li>
                        <li><i class="fas fa-check text-success me-2"></i>Endereço completo</li>
                        <li><i class="fas fa-check text-success me-2"></i>Capacidade (ocupação e adultos)</li>
                        <li><i class="fas fa-check text-success me-2"></i>Coordenadas geográficas</li>
                    </ul>
                    
                    <hr>
                    
                    <h6 class="text-primary">Próximos Passos</h6>
                    <p class="small text-muted">
                        Após criar a propriedade, você poderá:
                    </p>
                    <ul class="list-unstyled small">
                        <li><i class="fas fa-plus text-info me-2"></i>Adicionar imagens</li>
                        <li><i class="fas fa-plus text-info me-2"></i>Configurar preços</li>
                        <li><i class="fas fa-plus text-info me-2"></i>Definir disponibilidade</li>
                        <li><i class="fas fa-plus text-info me-2"></i>Adicionar comodidades</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('propertyForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Disable button and show loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Criando...';
    
    fetch('{{ route("properties.store") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Success message
            Swal.fire({
                icon: 'success',
                title: 'Sucesso!',
                text: data.message,
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = '{{ route("properties.index") }}';
            });
        } else {
            throw new Error(data.error || 'Erro desconhecido');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        // Error message
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: error.message || 'Erro ao criar propriedade',
            confirmButtonText: 'OK'
        });
    })
    .finally(() => {
        // Re-enable button
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});

// Auto-fill coordinates for common cities
document.getElementById('city').addEventListener('change', function() {
    const city = this.value.toLowerCase();
    const coordinates = {
        'são paulo': { lat: -23.5505, lng: -46.6333 },
        'rio de janeiro': { lat: -22.9068, lng: -43.1729 },
        'belo horizonte': { lat: -19.9167, lng: -43.9345 },
        'salvador': { lat: -12.9714, lng: -38.5011 },
        'brasília': { lat: -15.7942, lng: -47.8822 },
        'fortaleza': { lat: -3.7319, lng: -38.5267 },
        'curitiba': { lat: -25.4289, lng: -49.2671 },
        'manaus': { lat: -3.1190, lng: -60.0217 },
        'recife': { lat: -8.0476, lng: -34.8770 },
        'porto alegre': { lat: -30.0346, lng: -51.2177 }
    };
    
    if (coordinates[city]) {
        document.getElementById('latitude').value = coordinates[city].lat;
        document.getElementById('longitude').value = coordinates[city].lng;
    }
});
</script>
@endsection 