@extends('layouts.app')

@section('title', 'FrontDesk - Editar Propriedade')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('properties.index') }}">Propriedades</a></li>
<li class="breadcrumb-item"><a href="{{ route('properties.show', $property->id) }}">{{ $property->name }}</a></li>
<li class="breadcrumb-item active" aria-current="page">Editar</li>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <div>
            <h1 class="h2">Editar Propriedade</h1>
            <p class="text-muted">Modifique as informações da propriedade "{{ $property->name }}"</p>
        </div>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="{{ route('properties.show', $property->id) }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Voltar
            </a>
        </div>
    </div>

    <!-- Property Edit Form -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-edit me-2"></i>Informações da Propriedade
                    </h5>
                </div>
                <div class="card-body">
                    <form id="propertyEditForm" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <!-- Basic Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">Informações Básicas</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nome da Propriedade *</label>
                                    <input type="text" class="form-control" id="name" name="name" value="{{ $property->name }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="property_type" class="form-label">Tipo de Propriedade *</label>
                                    <select class="form-select" id="property_type" name="property_type" required>
                                        <option value="">Selecione o tipo</option>
                                        <option value="apartment" {{ $property->property_type === 'apartment' ? 'selected' : '' }}>Apartamento</option>
                                        <option value="house" {{ $property->property_type === 'house' ? 'selected' : '' }}>Casa</option>
                                        <option value="hotel" {{ $property->property_type === 'hotel' ? 'selected' : '' }}>Hotel</option>
                                        <option value="hostel" {{ $property->property_type === 'hostel' ? 'selected' : '' }}>Hostel</option>
                                        <option value="resort" {{ $property->property_type === 'resort' ? 'selected' : '' }}>Resort</option>
                                        <option value="villa" {{ $property->property_type === 'villa' ? 'selected' : '' }}>Vila</option>
                                        <option value="cabin" {{ $property->property_type === 'cabin' ? 'selected' : '' }}>Cabana</option>
                                        <option value="loft" {{ $property->property_type === 'loft' ? 'selected' : '' }}>Loft</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="description" class="form-label">Descrição</label>
                                    <textarea class="form-control" id="description" name="description" rows="3" maxlength="1000" placeholder="Descreva sua propriedade...">{{ $property->description }}</textarea>
                                    <div class="form-text">Máximo 1000 caracteres</div>
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
                                    <input type="text" class="form-control" id="address" name="address" value="{{ $property->address }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="city" class="form-label">Cidade *</label>
                                    <input type="text" class="form-control" id="city" name="city" value="{{ $property->city }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="state" class="form-label">Estado *</label>
                                    <input type="text" class="form-control" id="state" name="state" value="{{ $property->state }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="country" class="form-label">País *</label>
                                    <input type="text" class="form-control" id="country" name="country" value="{{ $property->country }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="postal_code" class="form-label">CEP</label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" value="{{ $property->postal_code }}" placeholder="00000-000">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="latitude" class="form-label">Latitude</label>
                                    <input type="number" class="form-control" id="latitude" name="latitude" step="0.00000001" min="-90" max="90" value="{{ $property->latitude }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="longitude" class="form-label">Longitude</label>
                                    <input type="number" class="form-control" id="longitude" name="longitude" step="0.00000001" min="-180" max="180" value="{{ $property->longitude }}">
                                </div>
                            </div>
                        </div>

                        <!-- Capacity Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">Capacidade e Quartos</h6>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="max_occupancy" class="form-label">Ocupação Máxima *</label>
                                    <input type="number" class="form-control" id="max_occupancy" name="max_occupancy" min="1" max="20" value="{{ $property->max_occupancy }}" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="max_adults" class="form-label">Adultos Máximos *</label>
                                    <input type="number" class="form-control" id="max_adults" name="max_adults" min="1" max="20" value="{{ $property->max_adults }}" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="max_children" class="form-label">Crianças Máximas</label>
                                    <input type="number" class="form-control" id="max_children" name="max_children" min="0" max="20" value="{{ $property->max_children }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="bedrooms" class="form-label">Quartos *</label>
                                    <input type="number" class="form-control" id="bedrooms" name="bedrooms" min="1" max="20" value="{{ $property->bedrooms }}" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="bathrooms" class="form-label">Banheiros *</label>
                                    <input type="number" class="form-control" id="bathrooms" name="bathrooms" min="1" max="20" value="{{ $property->bathrooms }}" required>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">Preços</h6>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="base_price" class="form-label">Preço Base (por noite)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">R$</span>
                                        <input type="number" class="form-control" id="base_price" name="base_price" min="0" step="0.01" value="{{ $property->base_price }}" placeholder="0.00">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="currency" class="form-label">Moeda</label>
                                    <select class="form-select" id="currency" name="currency">
                                        <option value="BRL" {{ $property->currency === 'BRL' ? 'selected' : '' }}>Real Brasileiro (R$)</option>
                                        <option value="USD" {{ $property->currency === 'USD' ? 'selected' : '' }}>Dólar Americano ($)</option>
                                        <option value="EUR" {{ $property->currency === 'EUR' ? 'selected' : '' }}>Euro (€)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Check-in/Check-out Times -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">Horários de Check-in/Check-out</h6>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="check_in_from" class="form-label">Check-in a partir de *</label>
                                    <input type="time" class="form-control" id="check_in_from" name="check_in_from" value="{{ $property->check_in_from }}" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="check_in_until" class="form-label">Check-in até *</label>
                                    <input type="time" class="form-control" id="check_in_until" name="check_in_until" value="{{ $property->check_in_until }}" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="check_out_from" class="form-label">Check-out a partir de *</label>
                                    <input type="time" class="form-control" id="check_out_from" name="check_out_from" value="{{ $property->check_out_from }}" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="check_out_until" class="form-label">Check-out até *</label>
                                    <input type="time" class="form-control" id="check_out_until" name="check_out_until" value="{{ $property->check_out_until }}" required>
                                </div>
                            </div>
                        </div>

                        <!-- Amenities -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">Comodidades</h6>
                                <div class="row">
                                    @php
                                        $amenities = [
                                            'Wi-Fi', 'Ar Condicionado', 'TV', 'Cozinha', 'Frigobar', 'Café da Manhã',
                                            'Estacionamento', 'Piscina', 'Academia', 'Spa', 'Restaurante', 'Bar',
                                            'Room Service', 'Lavanderia', 'Secador de Cabelo', 'Ferro de Passar',
                                            'Berço', 'Elevador', 'Terraço', 'Jardim', 'Churrasqueira', 'Quadra de Tênis'
                                        ];
                                        $propertyAmenities = $property->amenities ?? [];
                                    @endphp
                                    @foreach($amenities as $amenity)
                                        <div class="col-md-4 col-sm-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="amenities[]" value="{{ $amenity }}" id="amenity_{{ $loop->index }}" {{ in_array($amenity, $propertyAmenities) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="amenity_{{ $loop->index }}">
                                                    {{ $amenity }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- House Rules -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">Regras da Casa</h6>
                                <div class="row">
                                    @php
                                        $houseRules = [
                                            'Não é permitido fumar', 'Não é permitido animais', 'Não é permitido festas',
                                            'Check-in pontual', 'Respeitar o silêncio', 'Não é permitido visitas',
                                            'Manter limpeza', 'Economizar energia', 'Respeitar vizinhos'
                                        ];
                                        $propertyHouseRules = $property->house_rules ?? [];
                                    @endphp
                                    @foreach($houseRules as $rule)
                                        <div class="col-md-4 col-sm-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="house_rules[]" value="{{ $rule }}" id="rule_{{ $loop->index }}" {{ in_array($rule, $propertyHouseRules) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="rule_{{ $loop->index }}">
                                                    {{ $rule }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">Informações de Contato</h6>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="contact_name" class="form-label">Nome do Contato</label>
                                    <input type="text" class="form-control" id="contact_name" name="contact_name" value="{{ $property->contact_name }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="contact_phone" class="form-label">Telefone</label>
                                    <input type="tel" class="form-control" id="contact_phone" name="contact_phone" value="{{ $property->contact_phone }}" placeholder="(11) 99999-9999">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="contact_email" class="form-label">E-mail</label>
                                    <input type="email" class="form-control" id="contact_email" name="contact_email" value="{{ $property->contact_email }}">
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="row">
                            <div class="col-12">
                                <hr class="my-4">
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('properties.show', $property->id) }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i> Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Salvar Alterações
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Image Management Sidebar -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-images me-2"></i>Gerenciar Imagens
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Current Images -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-3">Imagens Atuais</h6>
                        
                        @if($property->main_image_url)
                            <div class="mb-3">
                                <label class="form-label">Imagem Principal</label>
                                <div class="position-relative">
                                    <img src="{{ $property->main_image_url }}" alt="Imagem Principal" class="img-fluid rounded" style="max-height: 150px;">
                                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" onclick="removeMainImage()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        @endif
                        
                        @if(count($property->gallery_images_urls) > 0)
                            <div class="mb-3">
                                <label class="form-label">Galeria</label>
                                <div class="row g-2">
                                    @foreach($property->gallery_images_urls as $index => $imageUrl)
                                        @if($index < 4)
                                            <div class="col-6 position-relative">
                                                <img src="{{ $imageUrl }}" alt="Imagem {{ $index + 1 }}" class="img-fluid rounded" style="height: 80px; width: 100%; object-fit: cover;">
                                                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" onclick="removeGalleryImage({{ $index }})">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                                @if(count($property->gallery_images_urls) > 4)
                                    <p class="text-muted small text-center mt-2">+{{ count($property->gallery_images_urls) - 4 }} mais imagens</p>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- Upload New Images -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-3">Adicionar Novas Imagens</h6>
                        
                        <!-- Main Image Upload -->
                        <div class="mb-3">
                            <label for="new_main_image" class="form-label">Nova Imagem Principal</label>
                            <input type="file" class="form-control" id="new_main_image" name="new_main_image" accept="image/*">
                        </div>

                        <!-- Gallery Images Upload -->
                        <div class="mb-3">
                            <label for="new_gallery_images" class="form-label">Novas Imagens da Galeria</label>
                            <input type="file" class="form-control" id="new_gallery_images" name="new_gallery_images[]" accept="image/*" multiple>
                        </div>
                    </div>

                    <!-- Image Tips -->
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-title">
                                <i class="fas fa-lightbulb me-2"></i>Dicas
                            </h6>
                            <ul class="list-unstyled mb-0 small">
                                <li class="mb-1">
                                    <i class="fas fa-check text-success me-1"></i>
                                    Use imagens de alta qualidade
                                </li>
                                <li class="mb-1">
                                    <i class="fas fa-check text-success me-1"></i>
                                    A imagem principal deve ser atrativa
                                </li>
                                <li class="mb-0">
                                    <i class="fas fa-check text-success me-1"></i>
                                    Mostre diferentes ângulos da propriedade
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center py-4">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
                <h5>Salvando Alterações...</h5>
                <p class="text-muted mb-0">Aguarde enquanto processamos suas informações</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form submission
    const form = document.getElementById('propertyEditForm');
    const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validateForm()) {
            loadingModal.show();
            submitForm();
        }
    });
    
    // Form validation
    setupFormValidation();
});

function validateForm() {
    const requiredFields = ['name', 'property_type', 'address', 'city', 'state', 'country'];
    
    for (const field of requiredFields) {
        const input = document.getElementById(field);
        if (!input.value.trim()) {
            input.focus();
            alert(`Por favor, preencha o campo "${input.previousElementSibling.textContent.replace('*', '')}"`);
            return false;
        }
    }
    
    return true;
}

function submitForm() {
    const formData = new FormData(document.getElementById('propertyEditForm'));
    
    // Add new images if selected
    const newMainImage = document.getElementById('new_main_image').files[0];
    if (newMainImage) {
        formData.append('main_image', newMainImage);
    }
    
    const newGalleryImages = document.getElementById('new_gallery_images').files;
    for (let i = 0; i < newGalleryImages.length; i++) {
        formData.append('gallery_images[]', newGalleryImages[i]);
    }
    
    fetch('{{ route("properties.update", $property->id) }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Propriedade atualizada com sucesso!');
            window.location.href = '{{ route("properties.show", $property->id) }}';
        } else {
            alert('Erro ao atualizar propriedade: ' + (data.error || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erro ao processar solicitação. Tente novamente.');
    })
    .finally(() => {
        const loadingModal = bootstrap.Modal.getInstance(document.getElementById('loadingModal'));
        if (loadingModal) loadingModal.hide();
    });
}

function removeMainImage() {
    if (confirm('Tem certeza que deseja remover a imagem principal?')) {
        // Implementar remoção da imagem principal
        alert('Funcionalidade de remoção de imagem será implementada');
    }
}

function removeGalleryImage(index) {
    if (confirm('Tem certeza que deseja remover esta imagem da galeria?')) {
        // Implementar remoção da imagem da galeria
        alert('Funcionalidade de remoção de imagem será implementada');
    }
}

function setupFormValidation() {
    // Real-time validation
    const inputs = document.querySelectorAll('input[required], select[required]');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (!this.value.trim()) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
        
        input.addEventListener('input', function() {
            if (this.value.trim()) {
                this.classList.remove('is-invalid');
            }
        });
    });
}
</script>
@endsection 