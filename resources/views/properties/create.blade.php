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
            <p class="text-muted">Crie uma nova propriedade com todas as informações necessárias</p>
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
                    <form id="propertyForm" enctype="multipart/form-data">
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
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="description" class="form-label">Descrição</label>
                                    <textarea class="form-control" id="description" name="description" rows="3" maxlength="1000" placeholder="Descreva sua propriedade..."></textarea>
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
                                    <input type="text" class="form-control" id="state" name="state" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="country" class="form-label">País *</label>
                                    <input type="text" class="form-control" id="country" name="country" value="BR" disabled="disabled">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="postal_code" class="form-label">CEP</label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" placeholder="00000-000">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="latitude" class="form-label">Latitude</label>
                                    <input type="number" class="form-control" id="latitude" name="latitude" step="0.00000001" min="-90" max="90">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="longitude" class="form-label">Longitude</label>
                                    <input type="number" class="form-control" id="longitude" name="longitude" step="0.00000001" min="-180" max="180">
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
                                    <input type="number" class="form-control" id="max_occupancy" name="max_occupancy" min="1" max="20" value="2" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="max_adults" class="form-label">Adultos Máximos *</label>
                                    <input type="number" class="form-control" id="max_adults" name="max_adults" min="1" max="20" value="2" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="max_children" class="form-label">Crianças Máximas</label>
                                    <input type="number" class="form-control" id="max_children" name="max_children" min="0" max="20" value="0">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="bedrooms" class="form-label">Quartos *</label>
                                    <input type="number" class="form-control" id="bedrooms" name="bedrooms" min="1" max="20" value="1" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="bathrooms" class="form-label">Banheiros *</label>
                                    <input type="number" class="form-control" id="bathrooms" name="bathrooms" min="1" max="20" value="1" required>
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
                                        <input type="number" class="form-control" id="base_price" name="base_price" min="0" step="0.01" placeholder="0.00">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="currency" class="form-label">Moeda</label>
                                    <select class="form-select" id="currency" name="currency">
                                        <option value="BRL" selected>Real Brasileiro (R$)</option>
                                        <option value="USD">Dólar Americano ($)</option>
                                        <option value="EUR">Euro (€)</option>
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
                                    <input type="time" class="form-control" id="check_in_from" name="check_in_from" value="14:00" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="check_in_until" class="form-label">Check-in até *</label>
                                    <input type="time" class="form-control" id="check_in_until" name="check_in_until" value="22:00" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="check_out_from" class="form-label">Check-out a partir de *</label>
                                    <input type="time" class="form-control" id="check_out_from" name="check_out_from" value="08:00" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="check_out_until" class="form-label">Check-out até *</label>
                                    <input type="time" class="form-control" id="check_out_until" name="check_out_until" value="11:00" required>
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
                                    @endphp
                                    @foreach($amenities as $amenity)
                                        <div class="col-md-4 col-sm-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="amenities[]" value="{{ $amenity }}" id="amenity_{{ $loop->index }}">
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
                                    @endphp
                                    @foreach($houseRules as $rule)
                                        <div class="col-md-4 col-sm-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="house_rules[]" value="{{ $rule }}" id="rule_{{ $loop->index }}">
                                                <label class="form-check-label" for="rule_{{ $loop->index }}">
                                                    {{ $rule }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Descriptions (Multilíngue) -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">Descrições (Multilíngue)</h6>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="desc_pt" class="form-label">Descrição (PT)</label>
                                    <textarea class="form-control" id="desc_pt" name="descriptions[PT][text]" rows="3" placeholder="Descrição em Português"></textarea>
                                    <input type="hidden" name="descriptions[PT][typeCode]" value="house">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="desc_en" class="form-label">Descrição (EN)</label>
                                    <textarea class="form-control" id="desc_en" name="descriptions[EN][text]" rows="3" placeholder="Description in English"></textarea>
                                    <input type="hidden" name="descriptions[EN][typeCode]" value="house">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="desc_es" class="form-label">Descripción (ES)</label>
                                    <textarea class="form-control" id="desc_es" name="descriptions[ES][text]" rows="3" placeholder="Descripción en Español"></textarea>
                                    <input type="hidden" name="descriptions[ES][typeCode]" value="house">
                                </div>
                            </div>
                        </div>

                        <!-- Fees (Taxas de Serviço) -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">Taxas (Fees) - Opcional</h6>
                                <div class="row g-3">
                                    <div class="col-md-2">
                                        <label class="form-label">Código</label>
                                        <input type="text" class="form-control" name="fees[0][feeCode]" placeholder="Ex: RES">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Charge Type</label>
                                        <input type="text" class="form-control" name="fees[0][chargeType]" placeholder="Ex: MAN">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Charge Mode</label>
                                        <input type="text" class="form-control" name="fees[0][chargeMode]" placeholder="Ex: STA">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Moeda</label>
                                        <input type="text" class="form-control" name="fees[0][currency]" value="BRL">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Flat (centavos)</label>
                                        <input type="number" class="form-control" name="fees[0][amountFlat]" min="0" placeholder="Ex: 30000">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Taxável?</label>
                                        <select class="form-select" name="fees[0][isTaxable]"><option value="">-</option><option value="1">Sim</option><option value="0">Não</option></select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Taxes (Impostos) -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">Impostos (Taxes) - Opcional</h6>
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Código</label>
                                        <input type="text" class="form-control" name="taxes[0][taxCode]" placeholder="Ex: VAT">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">% (0-100)</label>
                                        <input type="number" step="0.01" min="0" max="100" class="form-control" name="taxes[0][amountPercentage]" placeholder="Ex: 10">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Flat (centavos)</label>
                                        <input type="number" min="0" class="form-control" name="taxes[0][amountFlat]" placeholder="Ex: 1000">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Incluído no aluguel?</label>
                                        <select class="form-select" name="taxes[0][rentIncluded]"><option value="">-</option><option value="1">Sim</option><option value="0">Não</option></select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Nearest Places -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary mb-3">Locais Próximos (Opcional)</h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Tipo (typeCode)</label>
                                        <input type="text" class="form-control" name="nearestPlaces[0][typeCode]" placeholder="Ex: DAR (praia), BUS (ponto ônibus)">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Distância (metros)</label>
                                        <input type="number" class="form-control" name="nearestPlaces[0][distance][meters]" min="0" placeholder="Ex: 300">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Distância (feet)</label>
                                        <input type="number" class="form-control" name="nearestPlaces[0][distance][feet]" min="0" placeholder="Opcional">
                                    </div>
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
                                    <input type="text" class="form-control" id="contact_name" name="contact_name">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="contact_phone" class="form-label">Telefone</label>
                                    <input type="tel" class="form-control" id="contact_phone" name="contact_phone" placeholder="(11) 99999-9999">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="contact_email" class="form-label">E-mail</label>
                                    <input type="email" class="form-control" id="contact_email" name="contact_email">
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="row">
                            <div class="col-12">
                                <hr class="my-4">
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('properties.index') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i> Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Criar Propriedade
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Image Upload Sidebar -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-images me-2"></i>Imagens da Propriedade
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Main Image Upload -->
                    <div class="mb-4">
                        <label for="main_image" class="form-label">Imagem Principal *</label>
                        <div class="drop-zone" id="mainImageDropZone">
                            <div class="drop-zone-content">
                                <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                <p class="mb-1">Arraste a imagem principal aqui</p>
                                <p class="text-muted small">ou clique para selecionar</p>
                                <input type="file" id="main_image" name="main_image" accept="image/*" class="drop-zone-input" required>
                            </div>
                        </div>
                        <div class="form-text">Esta será a imagem principal exibida na listagem</div>
                    </div>

                    <!-- Gallery Images Upload -->
                    <div class="mb-4">
                        <label for="gallery_images" class="form-label">Galeria de Imagens</label>
                        <div class="drop-zone" id="galleryDropZone">
                            <div class="drop-zone-content">
                                <i class="fas fa-images fa-2x text-muted mb-2"></i>
                                <p class="mb-1">Arraste múltiplas imagens aqui</p>
                                <p class="text-muted small">ou clique para selecionar</p>
                                <input type="file" id="gallery_images" name="gallery_images[]" accept="image/*" class="drop-zone-input" multiple>
                            </div>
                        </div>
                        <div class="form-text">Adicione até 10 imagens para a galeria</div>
                    </div>

                    <!-- Image Preview -->
                    <div id="imagePreview" class="d-none">
                        <h6 class="text-primary mb-3">Prévia das Imagens</h6>
                        <div id="mainImagePreview" class="mb-3"></div>
                        <div id="galleryPreview"></div>
                    </div>
                </div>
            </div>

            <!-- Help Card -->
            <div class="card shadow-sm mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-question-circle me-2"></i>Dicas
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Use imagens de alta qualidade
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            A imagem principal deve ser atrativa
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-check text-success me-2"></i>
                            Mostre diferentes ângulos da propriedade
                        </li>
                        <li class="mb-0">
                            <i class="fas fa-check text-success me-2"></i>
                            Inclua fotos das comodidades
                        </li>
                    </ul>
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
                <h5>Criando Propriedade...</h5>
                <p class="text-muted mb-0">Aguarde enquanto processamos suas informações</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
.drop-zone {
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background-color: #f8f9fa;
}

.drop-zone:hover {
    border-color: #007bff;
    background-color: #e7f3ff;
}

.drop-zone.dragover {
    border-color: #007bff;
    background-color: #e7f3ff;
    transform: scale(1.02);
}

.drop-zone-input {
    display: none;
}

.drop-zone-content {
    pointer-events: none;
}

.image-preview {
    position: relative;
    display: inline-block;
    margin: 0.5rem;
}

.image-preview img {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #dee2e6;
}

.image-preview .remove-image {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    font-size: 12px;
    cursor: pointer;
}

.image-preview .remove-image:hover {
    background: #c82333;
}
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form submission
    const form = document.getElementById('propertyForm');
    const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validateForm()) {
            loadingModal.show();
            submitForm();
        }
    });

    // Drop zone functionality
    setupDropZones();
    
    // Form validation
    setupFormValidation();
});

function setupDropZones() {
    const mainImageDropZone = document.getElementById('mainImageDropZone');
    const galleryDropZone = document.getElementById('galleryDropZone');
    
    // Main image drop zone
    setupDropZone(mainImageDropZone, 'main_image', true);
    
    // Gallery drop zone
    setupDropZone(galleryDropZone, 'gallery_images', false);
}

function setupDropZone(dropZone, inputId, isMain) {
    const input = dropZone.querySelector('input');
    
    dropZone.addEventListener('click', () => input.click());
    
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });
    
    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('dragover');
    });
    
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        
        const files = e.dataTransfer.files;
        if (isMain) {
            input.files = files;
            handleMainImageUpload(files[0]);
        } else {
            input.files = files;
            handleGalleryUpload(files);
        }
    });
    
    input.addEventListener('change', (e) => {
        if (isMain) {
            handleMainImageUpload(e.target.files[0]);
        } else {
            handleGalleryUpload(e.target.files);
        }
    });
}

function handleMainImageUpload(file) {
    if (!file) return;
    
    if (!validateImage(file)) return;
    
    const preview = document.getElementById('mainImagePreview');
    preview.innerHTML = createImagePreview(file, true);
    
    document.getElementById('imagePreview').classList.remove('d-none');
}

function handleGalleryUpload(files) {
    if (!files || files.length === 0) return;
    
    const preview = document.getElementById('galleryPreview');
    preview.innerHTML = '';
    
    Array.from(files).forEach((file, index) => {
        if (validateImage(file)) {
            preview.innerHTML += createImagePreview(file, false, index);
        }
    });
    
    document.getElementById('imagePreview').classList.remove('d-none');
}

function createImagePreview(file, isMain, index = 0) {
    const reader = new FileReader();
    const previewId = isMain ? 'mainPreview' : `galleryPreview${index}`;
    
    reader.onload = function(e) {
        const img = document.querySelector(`#${previewId} img`);
        if (img) img.src = e.target.result;
    };
    
    reader.readAsDataURL(file);
    
    return `
        <div class="image-preview" id="${previewId}">
            <img src="" alt="Preview">
            <button type="button" class="remove-image" onclick="removeImage('${previewId}')">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
}

function removeImage(previewId) {
    const preview = document.getElementById(previewId);
    if (preview) {
        preview.remove();
    }
    
    // Check if there are any images left
    const mainPreview = document.getElementById('mainImagePreview');
    const galleryPreview = document.getElementById('galleryPreview');
    
    if (mainPreview.children.length === 0 && galleryPreview.children.length === 0) {
        document.getElementById('imagePreview').classList.add('d-none');
    }
}

function validateImage(file) {
    const maxSize = 5 * 1024 * 1024; // 5MB
    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
    
    if (!allowedTypes.includes(file.type)) {
        alert('Tipo de arquivo não suportado. Use apenas JPEG, PNG, JPG ou GIF.');
        return false;
    }
    
    if (file.size > maxSize) {
        alert('Arquivo muito grande. Tamanho máximo: 5MB.');
        return false;
    }
    
    return true;
}

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
    
    const mainImage = document.getElementById('main_image');
    if (!mainImage.files || mainImage.files.length === 0) {
        alert('Por favor, selecione uma imagem principal para a propriedade.');
        mainImage.focus();
        return false;
    }
    
    return true;
}

function submitForm() {
    const formData = new FormData(document.getElementById('propertyForm'));
    
    fetch('{{ route("properties.store") }}', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Propriedade criada com sucesso!');
            window.location.href = '{{ route("properties.index") }}';
        } else {
            alert('Erro ao criar propriedade: ' + (data.error || 'Erro desconhecido'));
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