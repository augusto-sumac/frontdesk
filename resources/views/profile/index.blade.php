@extends('layouts.app')

@section('title', 'Meu Perfil')

@section('breadcrumb')
    <li class="breadcrumb-item active">Meu Perfil</li>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-user me-2"></i>Meu Perfil
            </h1>
            <p class="text-muted">Gerencie suas informações pessoais e configurações da conta</p>
        </div>
    </div>

    <!-- Alertas -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>Por favor, corrija os erros abaixo:
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <!-- Informações do Perfil -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-user-edit me-2"></i>Informações Pessoais
                    </h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.update') }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nome *</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                           id="name" name="name" value="{{ old('name', $user->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Sobrenome *</label>
                                    <input type="text" class="form-control @error('last_name') is-invalid @enderror" 
                                           id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" required>
                                    @error('last_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                           id="email" name="email" value="{{ old('email', $user->email) }}" required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Telefone</label>
                                    <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                           id="phone" name="phone" value="{{ old('phone', $user->phone) }}" 
                                           placeholder="(11) 99999-9999">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="company_name" class="form-label">Nome da Empresa</label>
                                    <input type="text" class="form-control @error('company_name') is-invalid @enderror" 
                                           id="company_name" name="company_name" value="{{ old('company_name', $user->company_name) }}">
                                    @error('company_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role" class="form-label">Função</label>
                                    <input type="text" class="form-control" id="role" value="{{ ucfirst($user->role ?? 'Usuário') }}" readonly>
                                    <small class="text-muted">A função não pode ser alterada</small>
                                </div>
                            </div>
                        </div>

                        <!-- Dados da Empresa (NextPax) -->
                        @if(isset($propertyManager))
                        <div class="row">
                            <div class="col-12">
                                <h6 class="text-primary mt-4 mb-3">
                                    <i class="fas fa-building me-2"></i>Dados da Empresa (NextPax)
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="website" class="form-label">Website</label>
                                    <input type="url" class="form-control @error('website') is-invalid @enderror" 
                                           id="website" name="website" value="{{ old('website', $propertyManager['general']['website'] ?? '') }}" 
                                           placeholder="https://minhaempresa.com">
                                    @error('website')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="company_phone" class="form-label">Telefone da Empresa</label>
                                    <input type="text" class="form-control @error('company_phone') is-invalid @enderror" 
                                           id="company_phone" name="company_phone" 
                                           value="{{ old('company_phone', $propertyManager['general']['companyPhone'] ?? '') }}" 
                                           placeholder="(11) 99999-9999">
                                    @error('company_phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="address" class="form-label">Endereço</label>
                                    <input type="text" class="form-control @error('address') is-invalid @enderror" 
                                           id="address" name="address" value="{{ old('address', $propertyManager['general']['address'] ?? '') }}" 
                                           placeholder="Rua, número, bairro">
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="city" class="form-label">Cidade</label>
                                    <input type="text" class="form-control @error('city') is-invalid @enderror" 
                                           id="city" name="city" value="{{ old('city', $propertyManager['general']['city'] ?? '') }}" 
                                           placeholder="São Paulo">
                                    @error('city')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="company_postal_code" class="form-label">CEP</label>
                                    <input type="text" class="form-control @error('company_postal_code') is-invalid @enderror" 
                                           id="company_postal_code" name="company_postal_code" 
                                           value="{{ old('company_postal_code', $propertyManager['general']['companyPostalCode'] ?? '') }}" 
                                           placeholder="01000-000">
                                    @error('company_postal_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="country_code" class="form-label">País</label>
                                    <input type="text" class="form-control" 
                                           id="country_code" value="{{ $propertyManager['general']['countryCode'] ?? 'BR' }}" readonly>
                                    <small class="text-muted">Brasil (fixo)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="main_currency" class="form-label">Moeda Principal</label>
                                    <input type="text" class="form-control" 
                                           id="main_currency" value="{{ $propertyManager['general']['mainCurrency'] ?? 'BRL' }}" readonly>
                                    <small class="text-muted">Real Brasileiro (fixo)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="accepted_currencies" class="form-label">Moedas Aceitas</label>
                                    <input type="text" class="form-control" 
                                           id="accepted_currencies" value="{{ implode(', ', $propertyManager['general']['acceptedCurrencies'] ?? ['BRL']) }}" readonly>
                                    <small class="text-muted">Moedas aceitas (fixo)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="spoken_languages" class="form-label">Idiomas Falados</label>
                                    <input type="text" class="form-control" 
                                           id="spoken_languages" value="{{ implode(', ', $propertyManager['general']['spokenLanguages'] ?? ['pt', 'en']) }}" readonly>
                                    <small class="text-muted">Português e Inglês (fixo)</small>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="host_information" class="form-label">Descrição da Empresa</label>
                                    <textarea class="form-control @error('host_information') is-invalid @enderror" 
                                              id="host_information" name="host_information" rows="3" 
                                              placeholder="Fale brevemente sobre sua empresa">{{ old('host_information', $propertyManager['general']['hostInformation'] ?? '') }}</textarea>
                                    @error('host_information')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Dados de Contato (NextPax) -->
                        @if(isset($propertyManager) && isset($propertyManager['contacts'][0]))
                        <div class="row">
                            <div class="col-12">
                                <h6 class="text-primary mt-4 mb-3">
                                    <i class="fas fa-address-book me-2"></i>Dados de Contato (NextPax)
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contact_first_name" class="form-label">Nome do Contato</label>
                                    <input type="text" class="form-control @error('contact_first_name') is-invalid @enderror" 
                                           id="contact_first_name" name="contact_first_name" 
                                           value="{{ old('contact_first_name', $propertyManager['contacts'][0]['firstName'] ?? '') }}" 
                                           placeholder="Nome">
                                    @error('contact_first_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contact_last_name" class="form-label">Sobrenome do Contato</label>
                                    <input type="text" class="form-control @error('contact_last_name') is-invalid @enderror" 
                                           id="contact_last_name" name="contact_last_name" 
                                           value="{{ old('contact_last_name', $propertyManager['contacts'][0]['lastName'] ?? '') }}" 
                                           placeholder="Sobrenome">
                                    @error('contact_last_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contact_telephone" class="form-label">Telefone do Contato</label>
                                    <input type="text" class="form-control @error('contact_telephone') is-invalid @enderror" 
                                           id="contact_telephone" name="contact_telephone" 
                                           value="{{ old('contact_telephone', $propertyManager['contacts'][0]['telephone'] ?? '') }}" 
                                           placeholder="(11) 99999-9999">
                                    @error('contact_telephone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contact_email" class="form-label">Email do Contato</label>
                                    <input type="email" class="form-control @error('contact_email') is-invalid @enderror" 
                                           id="contact_email" name="contact_email" 
                                           value="{{ old('contact_email', $propertyManager['contacts'][0]['email'] ?? '') }}" 
                                           placeholder="contato@empresa.com">
                                    @error('contact_email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contact_address" class="form-label">Endereço do Contato</label>
                                    <input type="text" class="form-control @error('contact_address') is-invalid @enderror" 
                                           id="contact_address" name="contact_address" 
                                           value="{{ old('contact_address', $propertyManager['contacts'][0]['address'] ?? '') }}" 
                                           placeholder="Rua, número, bairro">
                                    @error('contact_address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contact_city" class="form-label">Cidade do Contato</label>
                                    <input type="text" class="form-control @error('contact_city') is-invalid @enderror" 
                                           id="contact_city" name="contact_city" 
                                           value="{{ old('contact_city', $propertyManager['contacts'][0]['city'] ?? '') }}" 
                                           placeholder="São Paulo">
                                    @error('contact_city')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contact_postal_code" class="form-label">CEP do Contato</label>
                                    <input type="text" class="form-control @error('contact_postal_code') is-invalid @enderror" 
                                           id="contact_postal_code" name="contact_postal_code" 
                                           value="{{ old('contact_postal_code', $propertyManager['contacts'][0]['postalCode'] ?? '') }}" 
                                           placeholder="01000-000">
                                    @error('contact_postal_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="contact_role" class="form-label">Função do Contato</label>
                                    <input type="text" class="form-control" 
                                           id="contact_role" value="{{ $propertyManager['contacts'][0]['role'] ?? 'main' }}" readonly>
                                    <small class="text-muted">Contato principal (fixo)</small>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Horários de Check-in/Check-out -->
                        @if(isset($propertyManager))
                        <div class="row">
                            <div class="col-12">
                                <h6 class="text-primary mt-4 mb-3">
                                    <i class="fas fa-clock me-2"></i>Horários de Check-in/Check-out
                                </h6>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="checkin_from" class="form-label">Check-in a partir de</label>
                                    <input type="time" class="form-control @error('checkin_from') is-invalid @enderror" 
                                           id="checkin_from" name="checkin_from" 
                                           value="{{ old('checkin_from', $propertyManager['general']['checkInOutTimes']['checkInFrom'] ?? '14:00') }}">
                                    @error('checkin_from')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="checkin_until" class="form-label">Check-in até</label>
                                    <input type="time" class="form-control @error('checkin_until') is-invalid @enderror" 
                                           id="checkin_until" name="checkin_until" 
                                           value="{{ old('checkin_until', $propertyManager['general']['checkInOutTimes']['checkInUntil'] ?? '22:00') }}">
                                    @error('checkin_until')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="checkout_from" class="form-label">Check-out a partir de</label>
                                    <input type="time" class="form-control @error('checkout_from') is-invalid @enderror" 
                                           id="checkout_from" name="checkout_from" 
                                           value="{{ old('checkout_from', $propertyManager['general']['checkInOutTimes']['checkOutFrom'] ?? '08:00') }}">
                                    @error('checkout_from')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="checkout_until" class="form-label">Check-out até</label>
                                    <input type="time" class="form-control @error('checkout_until') is-invalid @enderror" 
                                           id="checkout_until" name="checkout_until" 
                                           value="{{ old('checkout_until', $propertyManager['general']['checkInOutTimes']['checkOutUntil'] ?? '11:00') }}">
                                    @error('checkout_until')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Configurações de Reserva -->
                        <div class="row">
                            <div class="col-12">
                                <h6 class="text-primary mt-4 mb-3">
                                    <i class="fas fa-calendar-alt me-2"></i>Configurações de Reserva
                                </h6>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="default_min_stay" class="form-label">Estadia Mínima (dias)</label>
                                    <input type="number" class="form-control @error('default_min_stay') is-invalid @enderror" 
                                           id="default_min_stay" name="default_min_stay" min="1" 
                                           value="{{ old('default_min_stay', $propertyManager['ratesAndAvailabilitySettings']['defaultMinStay'] ?? 1) }}">
                                    @error('default_min_stay')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="default_max_stay" class="form-label">Estadia Máxima (dias)</label>
                                    <input type="number" class="form-control @error('default_max_stay') is-invalid @enderror" 
                                           id="default_max_stay" name="default_max_stay" min="1" 
                                           value="{{ old('default_max_stay', $propertyManager['ratesAndAvailabilitySettings']['defaultMaxStay'] ?? 30) }}">
                                    @error('default_max_stay')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="month_length" class="form-label">Duração do Mês (dias)</label>
                                    <input type="number" class="form-control @error('month_length') is-invalid @enderror" 
                                           id="month_length" name="month_length" min="1" max="31" 
                                           value="{{ old('month_length', $propertyManager['ratesAndAvailabilitySettings']['monthLength'] ?? 30) }}">
                                    @error('month_length')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="min_booking_offset" class="form-label">Antecedência Mínima (dias)</label>
                                    <input type="number" class="form-control @error('min_booking_offset') is-invalid @enderror" 
                                           id="min_booking_offset" name="min_booking_offset" min="0" 
                                           value="{{ old('min_booking_offset', $propertyManager['ratesAndAvailabilitySettings']['minBookingOffset'] ?? 0) }}">
                                    @error('min_booking_offset')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="max_booking_offset" class="form-label">Antecedência Máxima (meses)</label>
                                    <input type="number" class="form-control @error('max_booking_offset') is-invalid @enderror" 
                                           id="max_booking_offset" name="max_booking_offset" min="1" 
                                           value="{{ old('max_booking_offset', $propertyManager['ratesAndAvailabilitySettings']['maxBookingOffset'] ?? 12) }}">
                                    @error('max_booking_offset')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Dados de Faturamento (NextPax) -->
                        @if(isset($propertyManager) && isset($propertyManager['invoiceDetails']))
                        <div class="row">
                            <div class="col-12">
                                <h6 class="text-primary mt-4 mb-3">
                                    <i class="fas fa-file-invoice me-2"></i>Dados de Faturamento (NextPax)
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="invoice_entity_name" class="form-label">Nome da Entidade</label>
                                    <input type="text" class="form-control @error('invoice_entity_name') is-invalid @enderror" 
                                           id="invoice_entity_name" name="invoice_entity_name" 
                                           value="{{ old('invoice_entity_name', $propertyManager['invoiceDetails']['entityName'] ?? '') }}" 
                                           placeholder="Nome da empresa para faturamento">
                                    @error('invoice_entity_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="invoice_first_name" class="form-label">Nome para Faturamento</label>
                                    <input type="text" class="form-control @error('invoice_first_name') is-invalid @enderror" 
                                           id="invoice_first_name" name="invoice_first_name" 
                                           value="{{ old('invoice_first_name', $propertyManager['invoiceDetails']['firstName'] ?? '') }}" 
                                           placeholder="Nome">
                                    @error('invoice_first_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="invoice_last_name" class="form-label">Sobrenome para Faturamento</label>
                                    <input type="text" class="form-control @error('invoice_last_name') is-invalid @enderror" 
                                           id="invoice_last_name" name="invoice_last_name" 
                                           value="{{ old('invoice_last_name', $propertyManager['invoiceDetails']['lastName'] ?? '') }}" 
                                           placeholder="Sobrenome">
                                    @error('invoice_last_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="invoice_telephone" class="form-label">Telefone para Faturamento</label>
                                    <input type="text" class="form-control @error('invoice_telephone') is-invalid @enderror" 
                                           id="invoice_telephone" name="invoice_telephone" 
                                           value="{{ old('invoice_telephone', $propertyManager['invoiceDetails']['telephone'] ?? '') }}" 
                                           placeholder="(11) 99999-9999">
                                    @error('invoice_telephone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="invoice_email" class="form-label">Email para Faturamento</label>
                                    <input type="email" class="form-control @error('invoice_email') is-invalid @enderror" 
                                           id="invoice_email" name="invoice_email" 
                                           value="{{ old('invoice_email', $propertyManager['invoiceDetails']['email'] ?? '') }}" 
                                           placeholder="faturamento@empresa.com">
                                    @error('invoice_email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="invoice_address" class="form-label">Endereço para Faturamento</label>
                                    <input type="text" class="form-control @error('invoice_address') is-invalid @enderror" 
                                           id="invoice_address" name="invoice_address" 
                                           value="{{ old('invoice_address', $propertyManager['invoiceDetails']['entityAddress1'] ?? '') }}" 
                                           placeholder="Rua, número, bairro">
                                    @error('invoice_address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="invoice_city" class="form-label">Cidade para Faturamento</label>
                                    <input type="text" class="form-control @error('invoice_city') is-invalid @enderror" 
                                           id="invoice_city" name="invoice_city" 
                                           value="{{ old('invoice_city', $propertyManager['invoiceDetails']['entityCity'] ?? '') }}" 
                                           placeholder="São Paulo">
                                    @error('invoice_city')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="invoice_postal_code" class="form-label">CEP para Faturamento</label>
                                    <input type="text" class="form-control @error('invoice_postal_code') is-invalid @enderror" 
                                           id="invoice_postal_code" name="invoice_postal_code" 
                                           value="{{ old('invoice_postal_code', $propertyManager['invoiceDetails']['entityPostalCode'] ?? '') }}" 
                                           placeholder="01000-000">
                                    @error('invoice_postal_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        @endif



                        <!-- Dados de Faturamento -->
                        <div class="row">
                            <div class="col-12">
                                <h6 class="text-primary mt-4 mb-3">
                                    <i class="fas fa-file-invoice me-2"></i>Dados de Faturamento
                                </h6>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="invoice_address" class="form-label">Endereço de Faturamento</label>
                                    <input type="text" class="form-control @error('invoice_address') is-invalid @enderror" 
                                           id="invoice_address" name="invoice_address" 
                                           value="{{ old('invoice_address', $propertyManager['invoiceDetails']['entityAddress1'] ?? '') }}" 
                                           placeholder="Endereço para faturamento">
                                    @error('invoice_address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="invoice_city" class="form-label">Cidade de Faturamento</label>
                                    <input type="text" class="form-control @error('invoice_city') is-invalid @enderror" 
                                           id="invoice_city" name="invoice_city" 
                                           value="{{ old('invoice_city', $propertyManager['invoiceDetails']['entityCity'] ?? '') }}" 
                                           placeholder="Cidade para faturamento">
                                    @error('invoice_city')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="invoice_postal_code" class="form-label">CEP de Faturamento</label>
                                    <input type="text" class="form-control @error('invoice_postal_code') is-invalid @enderror" 
                                           id="invoice_postal_code" name="invoice_postal_code" 
                                           value="{{ old('invoice_postal_code', $propertyManager['invoiceDetails']['entityPostalCode'] ?? '') }}" 
                                           placeholder="CEP para faturamento">
                                    @error('invoice_postal_code')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="property_manager_code" class="form-label">Código do Gerenciador (NextPax)</label>
                                    <input type="text" class="form-control" 
                                           id="property_manager_code" 
                                           value="{{ $user->property_manager_code ?? 'Não definido' }}" 
                                           readonly>
                                    <small class="text-muted">Código do gerenciador de propriedades na API NextPax (não pode ser alterado)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="created_at" class="form-label">Membro desde</label>
                                    <input type="text" class="form-control" id="created_at" 
                                           value="{{ $user->created_at ? $user->created_at->format('d/m/Y') : 'N/A' }}" readonly>
                                </div>
                            </div>
                        </div>



                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Alterar Senha -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-lock me-2"></i>Alterar Senha
                    </h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.update') }}" method="POST" id="passwordForm">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Senha Atual</label>
                                    <input type="password" class="form-control @error('current_password') is-invalid @enderror" 
                                           id="current_password" name="current_password">
                                    @error('current_password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Nova Senha</label>
                                    <input type="password" class="form-control @error('new_password') is-invalid @enderror" 
                                           id="new_password" name="new_password" minlength="8">
                                    @error('new_password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Mínimo 8 caracteres</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="new_password_confirmation" class="form-label">Confirmar Nova Senha</label>
                                    <input type="password" class="form-control" 
                                           id="new_password_confirmation" name="new_password_confirmation">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-key me-1"></i>Alterar Senha
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar com Avatar e Informações -->
        <div class="col-lg-4">
            <!-- Avatar -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-image me-2"></i>Foto do Perfil
                    </h6>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <div class="user-avatar mx-auto" style="width: 120px; height: 120px; font-size: 3rem;">
                            {{ substr($user->name ?? 'U', 0, 1) }}
                        </div>
                    </div>
                    
                    <form action="{{ route('profile.avatar') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="avatar" class="form-label">Selecionar Nova Foto</label>
                            <input type="file" class="form-control @error('avatar') is-invalid @enderror" 
                                   id="avatar" name="avatar" accept="image/*">
                            @error('avatar')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Formatos: JPG, PNG, GIF. Máximo 2MB</small>
                        </div>
                        
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-upload me-1"></i>Enviar Foto
                        </button>
                    </form>
                </div>
            </div>

            <!-- Informações da Conta -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle me-2"></i>Informações da Conta
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label text-muted">Status da Conta</label>
                        <div>
                            @if($user->is_active)
                                <span class="badge bg-success">
                                    <i class="fas fa-check-circle me-1"></i>Ativa
                                </span>
                            @else
                                <span class="badge bg-danger">
                                    <i class="fas fa-times-circle me-1"></i>Inativa
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted">Email Verificado</label>
                        <div>
                            @if($user->email_verified_at)
                                <span class="badge bg-success">
                                    <i class="fas fa-check-circle me-1"></i>Verificado
                                </span>
                            @else
                                <span class="badge bg-warning">
                                    <i class="fas fa-exclamation-triangle me-1"></i>Não Verificado
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted">Último Login</label>
                        <div class="text-dark">
                            {{ $user->updated_at ? $user->updated_at->format('d/m/Y H:i') : 'N/A' }}
                        </div>
                    </div>

                    @if($user->property_manager_code)
                    <div class="mb-3">
                        <label class="form-label text-muted">Código do Gerenciador (NextPax)</label>
                        <div class="text-dark">
                            <code>{{ $user->property_manager_code }}</code>
                        </div>
                    </div>
                    @endif

                    @if(isset($propertyManager))
                    <hr>
                    <div class="mb-2 fw-bold">Dados do Tenant (NextPax)</div>
                    
                    <div class="small text-muted">Empresa</div>
                    <div class="mb-2">{{ $propertyManager['companyName'] ?? '—' }}</div>
                    
                    <div class="small text-muted">Email da Empresa</div>
                    <div class="mb-2">{{ $propertyManager['general']['companyEmail'] ?? '—' }}</div>
                    
                    <div class="small text-muted">Telefone</div>
                    <div class="mb-2">{{ $propertyManager['general']['companyPhone'] ?? '—' }}</div>
                    
                    <div class="small text-muted">Website</div>
                    <div class="mb-2">
                        @if(isset($propertyManager['general']['website']))
                            <a href="{{ $propertyManager['general']['website'] }}" target="_blank" class="text-decoration-none">
                                {{ $propertyManager['general']['website'] }}
                            </a>
                        @else
                            —
                        @endif
                    </div>
                    
                    <div class="small text-muted">Endereço</div>
                    <div class="mb-2">{{ $propertyManager['general']['address'] ?? '—' }}</div>
                    
                    <div class="small text-muted">Cidade</div>
                    <div class="mb-2">{{ $propertyManager['general']['city'] ?? '—' }}</div>
                    
                    <div class="small text-muted">CEP</div>
                    <div class="mb-2">{{ $propertyManager['general']['companyPostalCode'] ?? '—' }}</div>
                    
                    <div class="small text-muted">Moeda Padrão</div>
                    <div class="mb-2">{{ $propertyManager['general']['mainCurrency'] ?? '—' }}</div>
                    
                    <div class="small text-muted">Idiomas</div>
                    <div class="mb-2">
                        @if(isset($propertyManager['general']['spokenLanguages']))
                            {{ implode(', ', $propertyManager['general']['spokenLanguages']) }}
                        @else
                            —
                        @endif
                    </div>
                    
                    <div class="small text-muted">Moedas Aceitas</div>
                    <div class="mb-2">
                        @if(isset($propertyManager['general']['acceptedCurrencies']))
                            {{ implode(', ', $propertyManager['general']['acceptedCurrencies']) }}
                        @else
                            —
                        @endif
                    </div>
                    
                    @if(isset($propertyManager['general']['hostInformation']))
                    <div class="small text-muted">Descrição</div>
                    <div class="mb-2 small">{{ $propertyManager['general']['hostInformation'] }}</div>
                    @endif

                    <!-- Horários de Check-in/Check-out -->
                    @if(isset($propertyManager['general']['checkInOutTimes']))
                    <hr>
                    <div class="mb-2 fw-bold">Horários de Check-in/Check-out</div>
                    <div class="small text-muted">Check-in</div>
                    <div class="mb-2">{{ $propertyManager['general']['checkInOutTimes']['checkInFrom'] ?? '—' }} - {{ $propertyManager['general']['checkInOutTimes']['checkInUntil'] ?? '—' }}</div>
                    <div class="small text-muted">Check-out</div>
                    <div class="mb-2">{{ $propertyManager['general']['checkInOutTimes']['checkOutFrom'] ?? '—' }} - {{ $propertyManager['general']['checkInOutTimes']['checkOutUntil'] ?? '—' }}</div>
                    @endif

                    <!-- Configurações de Reserva -->
                    @if(isset($propertyManager['ratesAndAvailabilitySettings']))
                    <hr>
                    <div class="mb-2 fw-bold">Configurações de Reserva</div>
                    <div class="small text-muted">Estadia Mínima</div>
                    <div class="mb-2">{{ $propertyManager['ratesAndAvailabilitySettings']['defaultMinStay'] ?? '—' }} dias</div>
                    <div class="small text-muted">Estadia Máxima</div>
                    <div class="mb-2">{{ $propertyManager['ratesAndAvailabilitySettings']['defaultMaxStay'] ?? '—' }} dias</div>
                    <div class="small text-muted">Antecedência Mínima</div>
                    <div class="mb-2">{{ $propertyManager['ratesAndAvailabilitySettings']['minBookingOffset'] ?? '—' }} dias</div>
                    <div class="small text-muted">Antecedência Máxima</div>
                    <div class="mb-2">{{ $propertyManager['ratesAndAvailabilitySettings']['maxBookingOffset'] ?? '—' }} meses</div>
                    @endif

                    <!-- Dados de Contato -->
                    @if(isset($propertyManager['contacts'][0]))
                    <hr>
                    <div class="mb-2 fw-bold">Dados de Contato</div>
                    <div class="small text-muted">Nome</div>
                    <div class="mb-2">{{ $propertyManager['contacts'][0]['firstName'] ?? '—' }} {{ $propertyManager['contacts'][0]['lastName'] ?? '—' }}</div>
                    <div class="small text-muted">Email</div>
                    <div class="mb-2">{{ $propertyManager['contacts'][0]['email'] ?? '—' }}</div>
                    <div class="small text-muted">Telefone</div>
                    <div class="mb-2">{{ $propertyManager['contacts'][0]['telephone'] ?? '—' }}</div>
                    <div class="small text-muted">Endereço</div>
                    <div class="mb-2">{{ $propertyManager['contacts'][0]['address'] ?? '—' }}</div>
                    <div class="small text-muted">Cidade</div>
                    <div class="mb-2">{{ $propertyManager['contacts'][0]['city'] ?? '—' }}</div>
                    <div class="small text-muted">CEP</div>
                    <div class="mb-2">{{ $propertyManager['contacts'][0]['postalCode'] ?? '—' }}</div>
                    @endif

                    <!-- Dados de Faturamento -->
                    @if(isset($propertyManager['invoiceDetails']))
                    <hr>
                    <div class="mb-2 fw-bold">Dados de Faturamento</div>
                    <div class="small text-muted">Entidade</div>
                    <div class="mb-2">{{ $propertyManager['invoiceDetails']['entityName'] ?? '—' }}</div>
                    <div class="small text-muted">Nome</div>
                    <div class="mb-2">{{ $propertyManager['invoiceDetails']['firstName'] ?? '—' }} {{ $propertyManager['invoiceDetails']['lastName'] ?? '—' }}</div>
                    <div class="small text-muted">Email</div>
                    <div class="mb-2">{{ $propertyManager['invoiceDetails']['email'] ?? '—' }}</div>
                    <div class="small text-muted">Telefone</div>
                    <div class="mb-2">{{ $propertyManager['invoiceDetails']['telephone'] ?? '—' }}</div>
                    <div class="small text-muted">Endereço</div>
                    <div class="mb-2">{{ $propertyManager['invoiceDetails']['entityAddress1'] ?? '—' }}</div>
                    <div class="small text-muted">Cidade</div>
                    <div class="mb-2">{{ $propertyManager['invoiceDetails']['entityCity'] ?? '—' }}</div>
                    <div class="small text-muted">CEP</div>
                    <div class="mb-2">{{ $propertyManager['invoiceDetails']['entityPostalCode'] ?? '—' }}</div>
                    @endif
                    @endif
                </div>
            </div>

            <!-- Ações Rápidas -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-cogs me-2"></i>Ações Rápidas
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-primary">
                            <i class="fas fa-tachometer-alt me-2"></i>Voltar ao Dashboard
                        </a>
                        <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="fas fa-sign-out-alt me-2"></i>Sair da Conta
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Formatação do telefone
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
                value = value.replace(/(\d)(\d{4})$/, '$1-$2');
            }
            e.target.value = value;
        });
    }

    // Formatação do CEP
    const cepInputs = ['company_postal_code', 'contact_postal_code', 'invoice_postal_code'];
    cepInputs.forEach(function(inputId) {
        const cepInput = document.getElementById(inputId);
        if (cepInput) {
            cepInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length > 0) {
                    value = value.replace(/^(\d{5})(\d)/, '$1-$2');
                }
                e.target.value = value;
            });
        }
    });

    // Validação dos horários de check-in/check-out
    const checkinFrom = document.getElementById('checkin_from');
    const checkinUntil = document.getElementById('checkin_until');
    const checkoutFrom = document.getElementById('checkout_from');
    const checkoutUntil = document.getElementById('checkout_until');

    function validateTimes() {
        if (checkinFrom && checkinUntil) {
            const checkinFromTime = checkinFrom.value;
            const checkinUntilTime = checkinUntil.value;

            if (checkinFromTime && checkinUntilTime && checkinFromTime >= checkinUntilTime) {
                checkinUntil.setCustomValidity('Check-in até deve ser depois do check-in a partir de');
            } else {
                checkinUntil.setCustomValidity('');
            }
        }

        if (checkoutFrom && checkoutUntil) {
            const checkoutFromTime = checkoutFrom.value;
            const checkoutUntilTime = checkoutUntil.value;

            if (checkoutFromTime && checkoutUntilTime && checkoutFromTime >= checkoutUntilTime) {
                checkoutUntil.setCustomValidity('Check-out até deve ser depois do check-out a partir de');
            } else {
                checkoutUntil.setCustomValidity('');
            }
        }
    }

    // Adicionar event listeners para validação de horários
    [checkinFrom, checkinUntil, checkoutFrom, checkoutUntil].forEach(function(input) {
        if (input) {
            input.addEventListener('change', validateTimes);
        }
    });

    // Validar na inicialização
    validateTimes();

    // Validação do formulário de senha
    const passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('new_password_confirmation').value;

            if (newPassword && !currentPassword) {
                e.preventDefault();
                alert('Por favor, informe sua senha atual para alterar a senha.');
                return false;
            }

            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('A confirmação da nova senha não confere.');
                return false;
            }
        });
    }

    // Preview da imagem do avatar
    const avatarInput = document.getElementById('avatar');
    if (avatarInput) {
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) { // 2MB
                    alert('A imagem deve ter no máximo 2MB.');
                    e.target.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    // Aqui você pode adicionar preview da imagem se desejar
                    console.log('Imagem selecionada:', e.target.result);
                };
                reader.readAsDataURL(file);
            }
        });
    }
});
</script>
@endsection 