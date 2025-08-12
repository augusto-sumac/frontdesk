<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FrontDesk - Criar Conta</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center min-vh-100 align-items-center">
            <div class="col-lg-6">
                <div class="card shadow-lg border-0">
                    <div class="card-body p-5">
                        <!-- Logo e Título -->
                        <div class="text-center mb-4">
                            <img src="{{ asset('img/logo-frontdesk.svg') }}" alt="FrontDesk" height="45" class="mb-3">
                            <h2 class="text-primary fw-bold">FrontDesk</h2>
                            <p class="text-muted">Sistema de Gestão Hoteleira</p>
                            <h4 class="mb-3">Criar Nova Conta</h4>
                        </div>

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('register') }}">
                            @csrf
                            
                            <!-- Informações Pessoais -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="text-primary mb-3">Informações Pessoais</h5>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Nome *</label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                               id="name" name="name" value="{{ old('name') }}" required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="last_name" class="form-label">Sobrenome *</label>
                                        <input type="text" class="form-control @error('last_name') is-invalid @enderror" 
                                               id="last_name" name="last_name" value="{{ old('last_name') }}" required>
                                        @error('last_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">E-mail *</label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                               id="email" name="email" value="{{ old('email') }}" required>
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Telefone *</label>
                                        <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                                               id="phone" name="phone" value="{{ old('phone') }}" required>
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="company_name" class="form-label">Nome da Empresa *</label>
                                        <input type="text" class="form-control @error('company_name') is-invalid @enderror" 
                                               id="company_name" name="company_name" value="{{ old('company_name') }}" required>
                                        @error('company_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Configuração do Tenant (gerado automaticamente) -->
                            <div class="alert alert-info mb-4">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-cloud text-primary me-2 mt-1"></i>
                                    <div>
                                        <strong>Provisionamento NextPax</strong><br>
                                        <small class="text-muted">
                                            Ao criar sua conta, provisionaremos automaticamente seu tenant (Property Manager) na API NextPax usando seus dados de empresa e contato. O código será exibido no seu perfil.
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- Dados da Empresa (opcional) -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="text-primary mb-3">Dados da Empresa (opcional)</h5>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="website" class="form-label">Website</label>
                                        <input type="url" class="form-control @error('website') is-invalid @enderror" id="website" name="website" value="{{ old('website') }}" placeholder="https://minhaempresa.com">
                                        @error('website')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="address" class="form-label">Endereço</label>
                                        <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address') }}" placeholder="Rua, número, bairro">
                                        @error('address')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="city" class="form-label">Cidade</label>
                                        <input type="text" class="form-control @error('city') is-invalid @enderror" id="city" name="city" value="{{ old('city') }}" placeholder="São Paulo">
                                        @error('city')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>


                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="company_postal_code" class="form-label">CEP</label>
                                        <input type="text" class="form-control @error('company_postal_code') is-invalid @enderror" id="company_postal_code" name="company_postal_code" value="{{ old('company_postal_code') }}" placeholder="01000-000">
                                        @error('company_postal_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>


                                <div class="col-12">
                                    <div class="mb-3">
                                        <label for="host_information" class="form-label">Descrição da Empresa / Host Information</label>
                                        <textarea class="form-control @error('host_information') is-invalid @enderror" id="host_information" name="host_information" rows="3" placeholder="Fale brevemente sobre sua empresa">{{ old('host_information') }}</textarea>
                                        @error('host_information')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Senha -->
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h5 class="text-primary mb-3">Segurança</h5>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Senha *</label>
                                        <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                               id="password" name="password" required>
                                        @error('password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <small class="form-text text-muted">Mínimo de 8 caracteres</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password_confirmation" class="form-label">Confirmar Senha *</label>
                                        <input type="password" class="form-control" 
                                               id="password_confirmation" name="password_confirmation" required>
                                    </div>
                                </div>
                            </div>



                            <!-- Informação sobre API -->
                            <div class="alert alert-info mb-4">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-sync-alt text-primary me-2 mt-1"></i>
                                    <div>
                                        <strong>Integração Automática</strong><br>
                                        <small class="text-muted">
                                            Após o registro, todos os dados da propriedade (nome, endereço, quartos, etc.) 
                                            serão sincronizados automaticamente via API do NextPax usando o código fornecido.
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- Botões -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-rocket me-2"></i>
                                    Criar Conta
                                </button>
                                <a href="{{ route('login') }}" class="btn btn-outline-secondary">
                                    Já tenho uma conta
                                </a>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <small class="text-muted">
                                © 2025 FrontDesk - Gestão Hoteleira Inteligente
                            </small>
                        </div>


                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 