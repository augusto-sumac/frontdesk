@extends('layouts.admin')

@section('title', 'Administração - Dashboard')

@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">Dashboard Administrativo</h1>
                    <p class="text-muted mb-0">Visão geral da plataforma FrontDesk Pro</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-primary">
                        <i class="fas fa-users me-2"></i>Gerenciar Usuários
                    </a>
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Voltar ao Sistema
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-5">
        <div class="col-xl-3 col-md-6">
            <div class="stats-card h-100">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="ms-3">
                        <h3 class="h2 mb-1 fw-bold text-dark">{{ \App\Models\User::count() }}</h3>
                        <p class="text-muted mb-0 small">Total de Usuários</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="stats-card h-100">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-success bg-opacity-10 text-success">
                        <i class="fas fa-home"></i>
                    </div>
                    <div class="ms-3">
                        <h3 class="h2 mb-1 fw-bold text-dark">{{ \App\Models\Property::count() }}</h3>
                        <p class="text-muted mb-0 small">Propriedades</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="stats-card h-100">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-info bg-opacity-10 text-info">
                        <i class="fas fa-bed"></i>
                    </div>
                    <div class="ms-3">
                        <h3 class="h2 mb-1 fw-bold text-dark">{{ \App\Models\Booking::count() }}</h3>
                        <p class="text-muted mb-0 small">Reservas</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="stats-card h-100">
                <div class="d-flex align-items-center">
                    <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="ms-3">
                        <h3 class="h2 mb-1 fw-bold text-dark">{{ \App\Models\User::where('is_active', true)->count() }}</h3>
                        <p class="text-muted mb-0 small">Usuários Ativos</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Management Section -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-bold">
                        <i class="fas fa-users-cog me-2 text-primary"></i>Gerenciamento de Usuários
                    </h5>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-cog me-1"></i>Gerenciar
                    </a>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="h3 mb-1 text-primary fw-bold">{{ \App\Models\User::where('role', 'owner')->count() }}</div>
                                <div class="text-muted small">Proprietários</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="h3 mb-1 text-info fw-bold">{{ \App\Models\User::where('role', 'manager')->count() }}</div>
                                <div class="text-muted small">Gerentes</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3 bg-light rounded">
                                <div class="h3 mb-1 text-secondary fw-bold">{{ \App\Models\User::where('role', 'staff')->count() }}</div>
                                <div class="text-muted small">Funcionários</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Platform Statistics -->
    <div class="row g-4 mb-5">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">
                        <i class="fas fa-home me-2 text-primary"></i>Propriedades
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Total de propriedades:</span>
                        <span class="fw-bold">{{ \App\Models\Property::count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Com imagens:</span>
                        <span class="fw-bold">{{ \App\Models\Property::whereHas('images')->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Última criada:</span>
                        <span class="fw-bold">
                            @if($lastProperty = \App\Models\Property::latest()->first())
                                {{ $lastProperty->created_at->diffForHumans() }}
                            @else
                                N/A
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="card-title mb-0 fw-bold">
                        <i class="fas fa-bed me-2 text-success"></i>Reservas
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Total de reservas:</span>
                        <span class="fw-bold">{{ \App\Models\Booking::count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">Reservas hoje:</span>
                        <span class="fw-bold">{{ \App\Models\Booking::whereDate('created_at', today())->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Última reserva:</span>
                        <span class="fw-bold">
                            @if($lastBooking = \App\Models\Booking::latest()->first())
                                {{ $lastBooking->created_at->diffForHumans() }}
                            @else
                                N/A
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Users Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0 fw-bold">
                        <i class="fas fa-clock me-2 text-primary"></i>Usuários Recentes
                    </h6>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-primary btn-sm">
                        Ver todos <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Usuário</th>
                                    <th>Função</th>
                                    <th>Status</th>
                                    <th>Criado em</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(\App\Models\User::latest()->take(5)->get() as $user)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center me-3">
                                                    <span class="fw-bold text-muted">
                                                        {{ strtoupper(substr($user->name, 0, 1) . substr($user->last_name, 0, 1)) }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <div class="fw-medium text-dark">{{ $user->name }} {{ $user->last_name }}</div>
                                                    <div class="text-muted small">{{ $user->email }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge 
                                                @if($user->role === 'owner') bg-primary
                                                @elseif($user->role === 'manager') bg-info
                                                @else bg-secondary
                                                @endif">
                                                @switch($user->role)
                                                    @case('owner')
                                                        Proprietário
                                                        @break
                                                    @case('manager')
                                                        Gerente
                                                        @break
                                                    @case('staff')
                                                        Funcionário
                                                        @break
                                                @endswitch
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge 
                                                @if($user->is_active) bg-success @else bg-danger @endif">
                                                {{ $user->is_active ? 'Ativo' : 'Inativo' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-muted small">
                                                {{ $user->created_at->format('d/m/Y H:i') }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-sm {
    width: 40px;
    height: 40px;
    font-size: 14px;
}

.stats-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 16px;
    padding: 1.5rem;
    border: 1px solid #dee2e6;
    transition: all 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
}

.stats-icon {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}
</style>
@endsection
