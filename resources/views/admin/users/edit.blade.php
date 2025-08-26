@extends('layouts.admin')

@section('title', 'Editar Usuário')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Usuários</a></li>
    <li class="breadcrumb-item active">Editar</li>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">Editar Usuário</h1>
                    <p class="text-muted mb-0">Atualize as informações do usuário</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Voltar à Lista
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- User Info Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-lg bg-primary rounded-circle d-flex align-items-center justify-content-center me-3">
                            <span class="fw-bold text-white fs-6">
                                {{ strtoupper(substr($user->name, 0, 1) . substr($user->last_name, 0, 1)) }}
                            </span>
                        </div>
                        <div>
                            <h5 class="mb-1 fw-bold">{{ $user->name }} {{ $user->last_name }}</h5>
                            <p class="mb-1 text-muted">{{ $user->email }}</p>
                            <div class="d-flex gap-2">
                                <span class="badge 
                                    @if($user->role === 'admin') bg-primary
                                    @else bg-info
                                    @endif">
                                    @switch($user->role)
                                        @case('admin')
                                            👑 Administrador
                                            @break
                                        @case('supply')
                                            🏢 Supply
                                            @break
                                    @endswitch
                                </span>
                                <span class="badge 
                                    @if($user->is_active) bg-success @else bg-danger @endif">
                                    {{ $user->is_active ? '✅ Ativo' : '❌ Inativo' }}
                                </span>
                            </div>
                            
                            <!-- Integration Info -->
                            @if($user->property_manager_code || $user->booking_id || $user->airbnb_id)
                                <div class="mt-2">
                                    @if($user->property_manager_code)
                                        <div class="badge bg-success me-1 mb-1">
                                            <i class="fas fa-check-circle me-1"></i>NextPax: {{ $user->property_manager_code }}
                                        </div>
                                    @endif
                                    @if($user->booking_id)
                                        <div class="badge bg-info me-1 mb-1">
                                            <i class="fas fa-calendar-check me-1"></i>Booking: {{ $user->booking_id }}
                                        </div>
                                    @endif
                                    @if($user->airbnb_id)
                                        <div class="badge bg-warning me-1 mb-1">
                                            <i class="fas fa-home me-1"></i>Airbnb: {{ $user->airbnb_id }}
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Card -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="fas fa-edit me-2 text-primary"></i>Editar Informações
                    </h5>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Erro!</strong> Corrija os seguintes campos:
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form action="{{ route('admin.users.update', $user) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <!-- Personal Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary fw-bold mb-3">
                                    <i class="fas fa-user me-2"></i>Informações Pessoais
                                </h6>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label fw-medium">
                                    Nome <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name', $user->name) }}"
                                       placeholder="Digite o nome completo"
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label fw-medium">
                                    Sobrenome <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('last_name') is-invalid @enderror" 
                                       id="last_name" 
                                       name="last_name" 
                                       value="{{ old('last_name', $user->last_name) }}"
                                       placeholder="Digite o sobrenome"
                                       required>
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label fw-medium">
                                    Email <span class="text-danger">*</span>
                                </label>
                                <input type="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email', $user->email) }}"
                                       placeholder="usuario@exemplo.com"
                                       required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label fw-medium">
                                    Telefone
                                </label>
                                <input type="text" 
                                       class="form-control @error('phone') is-invalid @enderror" 
                                       id="phone" 
                                       name="phone" 
                                       value="{{ old('phone', $user->phone) }}"
                                       placeholder="(11) 99999-9999">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Account Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary fw-bold mb-3">
                                    <i class="fas fa-shield-alt me-2"></i>Informações da Conta
                                </h6>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="role" class="form-label fw-medium">
                                    Função <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('role') is-invalid @enderror" 
                                        id="role" 
                                        name="role" 
                                        required>
                                    <option value="">Selecione uma função</option>
                                    <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>
                                        👑 Administrador - Acesso total ao sistema
                                    </option>
                                    <option value="supply" {{ old('role', $user->role) == 'supply' ? 'selected' : '' }}>
                                        🏢 Supply - Cliente SaaS com acesso às funcionalidades
                                    </option>
                                </select>
                                @error('role')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="company_name" class="form-label fw-medium">
                                    Nome da Empresa
                                </label>
                                <input type="text" 
                                       class="form-control @error('company_name') is-invalid @enderror" 
                                       id="company_name" 
                                       name="company_name" 
                                       value="{{ old('company_name', $user->company_name) }}"
                                       placeholder="Nome da empresa ou organização">
                                @error('company_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Integration IDs -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary fw-bold mb-3">
                                    <i class="fas fa-link me-2"></i>IDs de Integração
                                </h6>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="booking_id" class="form-label fw-medium">
                                    Booking ID
                                </label>
                                <input type="text" 
                                       class="form-control @error('booking_id') is-invalid @enderror" 
                                       id="booking_id" 
                                       name="booking_id" 
                                       value="{{ old('booking_id', $user->booking_id) }}"
                                       placeholder="ID da reserva principal">
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    ID da reserva principal do usuário (opcional)
                                </div>
                                @error('booking_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="airbnb_id" class="form-label fw-medium">
                                    Airbnb ID
                                </label>
                                <input type="text" 
                                       class="form-control @error('airbnb_id') is-invalid @enderror" 
                                       id="airbnb_id" 
                                       name="airbnb_id" 
                                       value="{{ old('airbnb_id', $user->airbnb_id) }}"
                                       placeholder="ID do Airbnb">
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    ID do Airbnb do usuário (opcional)
                                </div>
                                @error('airbnb_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Address Information (for NextPax integration) -->
                        <div class="row mb-4" id="addressSection" style="display: {{ $user->role === 'supply' ? 'block' : 'none' }};">
                            <div class="col-12">
                                <h6 class="text-primary fw-bold mb-3">
                                    <i class="fas fa-map-marker-alt me-2"></i>Informações de Endereço (NextPax)
                                </h6>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Estas informações são necessárias para criar o Property Manager na NextPax
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="address" class="form-label fw-medium">
                                    Endereço <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('address') is-invalid @enderror" 
                                       id="address" 
                                       name="address" 
                                       value="{{ old('address') }}"
                                       placeholder="Rua, número, complemento"
                                       {{ $user->role === 'supply' ? 'required' : '' }}>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label fw-medium">
                                    Cidade <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('city') is-invalid @enderror" 
                                       id="city" 
                                       name="city" 
                                       value="{{ old('city', 'São Paulo') }}"
                                       placeholder="Nome da cidade"
                                       {{ $user->role === 'supply' ? 'required' : '' }}>
                                @error('city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="state" class="form-label fw-medium">
                                    Estado <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('state') is-invalid @enderror" 
                                        id="state" 
                                        name="state" 
                                        {{ $user->role === 'supply' ? 'required' : '' }}>
                                    <option value="BR_SP" {{ old('state', $user->state) == 'BR_SP' ? 'selected' : 'selected' }}>São Paulo</option>
                                    <option value="BR_RJ" {{ old('state', $user->state) == 'BR_RJ' ? 'selected' : '' }}>Rio de Janeiro</option>
                                    <option value="BR_MG" {{ old('state', $user->state) == 'BR_MG' ? 'selected' : '' }}>Minas Gerais</option>
                                    <option value="BR_RS" {{ old('state', $user->state) == 'BR_RS' ? 'selected' : '' }}>Rio Grande do Sul</option>
                                    <option value="BR_PR" {{ old('state', $user->state) == 'BR_PR' ? 'selected' : '' }}>Paraná</option>
                                    <option value="BR_SC" {{ old('state', $user->state) == 'BR_SC' ? 'selected' : '' }}>Santa Catarina</option>
                                    <option value="BR_BA" {{ old('state', $user->state) == 'BR_BA' ? 'selected' : '' }}>Bahia</option>
                                    <option value="BR_GO" {{ old('state', $user->state) == 'BR_GO' ? 'selected' : '' }}>Goiás</option>
                                    <option value="BR_PE" {{ old('state', $user->state) == 'BR_PE' ? 'selected' : '' }}>Pernambuco</option>
                                    <option value="BR_CE" {{ old('state', $user->state) == 'BR_CE' ? 'selected' : '' }}>Ceará</option>
                                </select>
                                @error('state')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="postal_code" class="form-label fw-medium">
                                    CEP <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('postal_code') is-invalid @enderror" 
                                       id="postal_code" 
                                       name="postal_code" 
                                       value="{{ old('postal_code') }}"
                                       placeholder="00000-000"
                                       {{ $user->role === 'supply' ? 'required' : '' }}>
                                @error('postal_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="website" class="form-label fw-medium">
                                    Website
                                </label>
                                <input type="url" 
                                       class="form-control @error('website') is-invalid @enderror" 
                                       id="website" 
                                       name="website" 
                                       value="{{ old('website') }}"
                                       placeholder="https://exemplo.com">
                                @error('website')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Account Status -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary fw-bold mb-3">
                                    <i class="fas fa-toggle-on me-2"></i>Status da Conta
                                </h6>
                            </div>
                            
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="is_active" 
                                           name="is_active" 
                                           value="1" 
                                           {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-medium" for="is_active">
                                        Usuário ativo
                                    </label>
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Usuários inativos não conseguem fazer login no sistema
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="row">
                            <div class="col-12">
                                <hr class="my-4">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-2"></i>Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Atualizar Usuário
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password Card -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="fas fa-key me-2 text-warning"></i>Alterar Senha
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.users.change-password', $user) }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="new_password" class="form-label fw-medium">
                                    Nova Senha <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control @error('password') is-invalid @enderror" 
                                           id="new_password" 
                                           name="password" 
                                           placeholder="Mínimo 8 caracteres"
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                                        <i class="fas fa-eye" id="new_password-icon"></i>
                                    </button>
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    A senha deve ter pelo menos 8 caracteres
                                </div>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="new_password_confirmation" class="form-label fw-medium">
                                    Confirmar Nova Senha <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control @error('password_confirmation') is-invalid @enderror" 
                                           id="new_password_confirmation" 
                                           name="password_confirmation" 
                                           placeholder="Confirme a nova senha"
                                           required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password_confirmation')">
                                        <i class="fas fa-eye" id="new_password_confirmation-icon"></i>
                                    </button>
                                </div>
                                @error('password_confirmation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-key me-2"></i>Alterar Senha
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '-icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Controlar visibilidade da seção de endereço baseado no tipo de usuário
document.getElementById('role').addEventListener('change', function() {
    const addressSection = document.getElementById('addressSection');
    const isSupply = this.value === 'supply';
    
    if (isSupply) {
        addressSection.style.display = 'block';
        // Marcar campos obrigatórios
        addressSection.querySelectorAll('input[required], select[required]').forEach(field => {
            field.required = true;
        });
    } else {
        addressSection.style.display = 'none';
        // Remover obrigatoriedade dos campos
        addressSection.querySelectorAll('input, select').forEach(field => {
            field.required = false;
        });
    }
});

// Password strength indicator for new password
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    const strength = calculatePasswordStrength(password);
    updatePasswordStrengthIndicator(strength);
});

function calculatePasswordStrength(password) {
    let score = 0;
    
    if (password.length >= 8) score++;
    if (password.match(/[a-z]/)) score++;
    if (password.match(/[A-Z]/)) score++;
    if (password.match(/[0-9]/)) score++;
    if (password.match(/[^a-zA-Z0-9]/)) score++;
    
    return score;
}

function updatePasswordStrengthIndicator(strength) {
    const field = document.getElementById('new_password');
    const feedback = field.parentNode.querySelector('.form-text');
    
    if (strength >= 4) {
        feedback.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i>Senha forte';
        feedback.className = 'form-text text-success';
    } else if (strength >= 2) {
        feedback.innerHTML = '<i class="fas fa-exclamation-triangle text-warning me-1"></i>Senha média';
        feedback.className = 'form-text text-warning';
    } else {
        feedback.innerHTML = '<i class="fas fa-times-circle text-danger me-1"></i>Senha fraca';
        feedback.className = 'form-text text-danger';
    }
}
</script>

<style>
.avatar-lg {
    width: 60px;
    height: 60px;
    font-size: 20px;
}

.card-header {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-bottom: 1px solid #dee2e6;
    border-radius: 12px 12px 0 0 !important;
    font-weight: 600;
}
</style>
@endsection
