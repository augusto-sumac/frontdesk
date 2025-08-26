@extends('layouts.admin')

@section('title', 'Gerenciar Usuários')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Usuários</a></li>
    <li class="breadcrumb-item active">Listar</li>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-1 text-dark fw-bold">Gerenciar Usuários</h1>
                    <p class="text-muted mb-0">Gerencie todos os usuários da plataforma</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                        <i class="fas fa-user-plus me-2"></i>Novo Usuário
                    </a>
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Voltar ao Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Users Table Card -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0 fw-bold">
                            <i class="fas fa-users me-2 text-primary"></i>Lista de Usuários
                        </h5>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary fs-6">{{ $users->total() }} usuários</span>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3">Usuário</th>
                                    <th class="px-4 py-3">Função</th>
                                    <th class="px-4 py-3">Empresa</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Criado em</th>
                                    <th class="px-4 py-3 text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $user)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-lg bg-light rounded-circle d-flex align-items-center justify-content-center me-3">
                                                    <span class="fw-bold text-muted fs-6">
                                                        {{ strtoupper(substr($user->name, 0, 1) . substr($user->last_name, 0, 1)) }}
                                                    </span>
                                                </div>
                                                <div>
                                                    <div class="fw-medium text-dark">{{ $user->name }} {{ $user->last_name }}</div>
                                                    <div class="text-muted small">{{ $user->email }}</div>
                                                    @if($user->phone)
                                                        <div class="text-muted small">
                                                            <i class="fas fa-phone me-1"></i>{{ $user->phone }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="badge 
                                                @if($user->role === 'admin') bg-primary
                                                @else bg-info
                                                @endif fs-6">
                                                @switch($user->role)
                                                    @case('admin')
                                                        <i class="fas fa-crown me-1"></i>Administrador
                                                        @break
                                                    @case('supply')
                                                        <i class="fas fa-building me-1"></i>Supply
                                                        @break
                                                @endswitch
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="text-dark fw-medium">
                                                {{ $user->company_name ?? 'N/A' }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="badge 
                                                @if($user->is_active) bg-success @else bg-danger @endif fs-6">
                                                <i class="fas fa-{{ $user->is_active ? 'check-circle' : 'times-circle' }} me-1"></i>
                                                {{ $user->is_active ? 'Ativo' : 'Inativo' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="text-muted small">
                                                <i class="fas fa-calendar me-1"></i>
                                                {{ $user->created_at->format('d/m/Y') }}
                                            </div>
                                            <div class="text-muted small">
                                                <i class="fas fa-clock me-1"></i>
                                                {{ $user->created_at->format('H:i') }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.users.edit', $user) }}" 
                                                   class="btn btn-outline-primary btn-sm" 
                                                   title="Editar usuário">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                @if($user->id !== auth()->id())
                                                    <button type="button" 
                                                            class="btn btn-outline-{{ $user->is_active ? 'warning' : 'success' }} btn-sm"
                                                            onclick="toggleUserStatus({{ $user->id }}, '{{ $user->is_active ? 'desativar' : 'ativar' }}')"
                                                            title="{{ $user->is_active ? 'Desativar' : 'Ativar' }} usuário">
                                                        <i class="fas fa-{{ $user->is_active ? 'ban' : 'check' }}"></i>
                                                    </button>
                                                    
                                                    <button type="button" 
                                                            class="btn btn-outline-danger btn-sm"
                                                            onclick="deleteUser({{ $user->id }}, '{{ $user->name }}')"
                                                            title="Deletar usuário">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @else
                                                    <span class="badge bg-secondary fs-6">Você</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="fas fa-users fa-3x mb-3 text-muted"></i>
                                                <h5>Nenhum usuário encontrado</h5>
                                                <p class="mb-0">Comece criando o primeiro usuário da plataforma.</p>
                                                <a href="{{ route('admin.users.create') }}" class="btn btn-primary mt-3">
                                                    <i class="fas fa-user-plus me-2"></i>Criar Primeiro Usuário
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Pagination -->
                @if($users->hasPages())
                    <div class="card-footer">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted small">
                                Mostrando {{ $users->firstItem() ?? 0 }} a {{ $users->lastItem() ?? 0 }} de {{ $users->total() }} usuários
                            </div>
                            <div>
                                {{ $users->links() }}
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteUserModalLabel">
                    <i class="fas fa-exclamation-triangle text-danger me-2"></i>Confirmar Exclusão
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja deletar o usuário <strong id="userNameToDelete"></strong>?</p>
                <p class="text-danger mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    Esta ação não pode ser desfeita e todos os dados do usuário serão perdidos.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancelar
                </button>
                <form id="deleteUserForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Deletar Usuário
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Toggle Status Confirmation Modal -->
<div class="modal fade" id="toggleStatusModal" tabindex="-1" aria-labelledby="toggleStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="toggleStatusModalLabel">
                    <i class="fas fa-question-circle text-warning me-2"></i>Confirmar Alteração
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="toggleStatusMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancelar
                </button>
                <form id="toggleStatusForm" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-check me-1"></i>Confirmar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-lg {
    width: 50px;
    height: 50px;
    font-size: 18px;
}

.btn-group .btn {
    margin: 0 2px;
}

.table th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
    color: #6c757d;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border: none;
}

.table tbody tr {
    transition: all 0.2s ease;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
    transform: scale(1.01);
}

.badge {
    font-weight: 500;
    padding: 0.5em 0.75em;
}

.card-footer {
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
}
</style>

<script>
function deleteUser(userId, userName) {
    document.getElementById('userNameToDelete').textContent = userName;
    document.getElementById('deleteUserForm').action = `/admin/users/${userId}`;
    
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
    deleteModal.show();
}

function toggleUserStatus(userId, action) {
    const message = `Tem certeza que deseja ${action} este usuário?`;
    document.getElementById('toggleStatusMessage').textContent = message;
    document.getElementById('toggleStatusForm').action = `/admin/users/${userId}/toggle-status`;
    
    const toggleModal = new bootstrap.Modal(document.getElementById('toggleStatusModal'));
    toggleModal.show();
}

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
</script>
@endsection
