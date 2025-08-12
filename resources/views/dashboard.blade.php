@extends('layouts.app')

@section('title', 'Dashboard')

@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </h1>
            <p class="text-muted">Visão geral das suas propriedades e reservas</p>
        </div>
        <div>
            <button class="btn btn-primary" onclick="location.reload()">
                <i class="fas fa-sync-alt me-1"></i>Atualizar
                        </button>
                    </div>
                                </div>

    <!-- Alertas -->
    @if(isset($error))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ $error }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

    <!-- Métricas Financeiras -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total de Reservas
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $financialReport['total_bookings'] ?? 0 }}
                            </div>
                            <div class="text-success small">
                                <i class="fas fa-check-circle"></i> {{ $financialReport['confirmed_bookings'] ?? 0 }} confirmadas
                            </div>
                            @if(($financialReport['pending_sync_bookings'] ?? 0) > 0)
                                <div class="text-warning small">
                                    <i class="fas fa-sync-alt"></i> {{ $financialReport['pending_sync_bookings'] ?? 0 }} aguardando sincronização
                                </div>
                            @endif
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Receita Total
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                R$ {{ number_format($financialReport['total_revenue'] ?? 0, 2, ',', '.') }}
                            </div>
                            <div class="text-success small">
                                <i class="fas fa-arrow-up"></i> Taxa média: R$ {{ number_format($financialReport['average_daily_rate'] ?? 0, 2, ',', '.') }}
                            </div>
                            @if(($financialReport['pending_sync_revenue'] ?? 0) > 0)
                                <div class="text-warning small">
                                    <i class="fas fa-clock"></i> R$ {{ number_format($financialReport['pending_sync_revenue'] ?? 0, 2, ',', '.') }} aguardando sincronização
                                </div>
                            @endif
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Reservas Pendentes
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $financialReport['pending_bookings'] ?? 0 }}
                            </div>
                            <div class="text-warning small">
                                <i class="fas fa-clock"></i> Aguardando confirmação
                            </div>
                            @if(($financialReport['failed_sync_bookings'] ?? 0) > 0)
                                <div class="text-danger small">
                                    <i class="fas fa-exclamation-triangle"></i> {{ $financialReport['failed_sync_bookings'] ?? 0 }} falharam na sincronização
                                </div>
                            @endif
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Taxa de Ocupação
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $financialReport['occupancy_rate'] ?? 0 }}%
                            </div>
                            <div class="text-info small">
                                <i class="fas fa-percentage"></i> Baseado em reservas confirmadas
                            </div>
                            @if(($financialReport['synced_revenue'] ?? 0) > 0)
                                <div class="text-success small">
                                    <i class="fas fa-sync"></i> R$ {{ number_format($financialReport['synced_revenue'] ?? 0, 2, ',', '.') }} sincronizadas
                                </div>
                            @endif
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-percent fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Reservas Recentes -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-calendar-check me-2"></i>Reservas Recentes
                    </h6>
                    <a href="{{ route('bookings.index') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye me-1"></i>Ver Todas
                    </a>
                                            </div>
                <div class="card-body">
                    @if(isset($bookings) && count($bookings) > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Hóspede</th>
                                        <th>Propriedade</th>
                                        <th>Check-in</th>
                                        <th>Check-out</th>
                                        <th>Status</th>
                                        <th>Sincronização</th>
                                        <th>Valor</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($bookings as $booking)
                                    <tr>
                                        <td>
                                            <div class="fw-bold">{{ $booking['guest_name'] }}</div>
                                            <small class="text-muted">#{{ $booking['booking_number'] }}</small>
                                        </td>
                                        <td>
                                            <div class="fw-bold">{{ $booking['property_name'] }}</div>
                                            <small class="text-muted">{{ $booking['currency'] }}</small>
                                        </td>
                                        <td>{{ $booking['check_in'] }}</td>
                                        <td>{{ $booking['check_out'] }}</td>
                                        <td>
                                            @switch($booking['status'])
                                                @case('reservation')
                                                @case('confirmed')
                                                    <span class="badge bg-success">Confirmada</span>
                                                    @break
                                                @case('pending')
                                                @case('request')
                                                @case('request-accepted')
                                                    <span class="badge bg-warning">Pendente</span>
                                                    @break
                                                @case('cancelled')
                                                    <span class="badge bg-danger">Cancelada</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-secondary">{{ ucfirst($booking['status']) }}</span>
                                            @endswitch
                                        </td>
                                        <td>
                                            @switch($booking['sync_status'])
                                                @case('synced')
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>Sincronizada
                                                    </span>
                                                    @break
                                                @case('pending')
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-clock me-1"></i>Pendente
                                                    </span>
                                                    @break
                                                @case('failed')
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>Falhou
                                                    </span>
                                                    @break
                                                @case('syncing')
                                                    <span class="badge bg-info">
                                                        <i class="fas fa-sync-alt me-1"></i>Sincronizando
                                                    </span>
                                                    @break
                                                @default
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-question me-1"></i>{{ ucfirst($booking['sync_status']) }}
                                                    </span>
                                            @endswitch
                                        </td>
                                        <td>
                                            <div class="fw-bold">R$ {{ number_format($booking['amount'], 2, ',', '.') }}</div>
                                            @if($booking['last_modified'])
                                                <small class="text-muted">{{ \Carbon\Carbon::parse($booking['last_modified'])->diffForHumans() }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('bookings.show', $booking['id']) }}" class="btn btn-sm btn-outline-primary" title="Ver detalhes">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($booking['sync_status'] === 'failed')
                                                <button class="btn btn-sm btn-outline-warning ms-1" title="Tentar sincronizar novamente" onclick="retrySync({{ $booking['id'] }})">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Nenhuma reserva encontrada</h5>
                            <p class="text-muted">As reservas aparecerão aqui quando forem criadas via interface ou API.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Mensagens Recentes -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-comments me-2"></i>Sistema de Mensagens
                    </h6>
                    <span class="badge bg-info">Em Desenvolvimento</span>
                </div>
                <div class="card-body">
                    <div class="text-center py-4">
                        <i class="fas fa-comment-slash fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Sistema de Mensagens</h5>
                        <p class="text-muted">O sistema de mensagens com hóspedes está sendo desenvolvido e estará disponível em breve.</p>
                        <div class="mt-3">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Funcionalidades planejadas:</strong>
                                <ul class="mb-0 mt-2 text-start">
                                    <li>Chat em tempo real com hóspedes</li>
                                    <li>Notificações automáticas</li>
                                    <li>Histórico de conversas</li>
                                    <li>Integração com reservas</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
        // Funções para aceitar/rejeitar reservas
        function acceptBooking(bookingId, channelId) {
            if (confirm('Tem certeza que deseja aceitar esta reserva?')) {
                fetch('{{ route("bookings.accept") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        bookingId: bookingId,
                        channelId: channelId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                    // Show success toast
                    showToast('Sucesso!', 'Reserva aceita com sucesso!', 'success');
                    setTimeout(() => location.reload(), 1500);
                    } else {
                    showToast('Erro!', 'Erro ao aceitar reserva: ' + data.error, 'error');
                    }
                })
                .catch(error => {
                showToast('Erro!', 'Erro ao processar solicitação: ' + error, 'error');
                });
            }
        }

        function rejectBooking(bookingId, channelId) {
            const reason = prompt('Digite o motivo da recusa:');
            if (reason) {
                fetch('{{ route("bookings.reject") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        bookingId: bookingId,
                        channelId: channelId,
                        reason: reason
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                    showToast('Sucesso!', 'Reserva recusada com sucesso!', 'success');
                    setTimeout(() => location.reload(), 1500);
                    } else {
                    showToast('Erro!', 'Erro ao recusar reserva: ' + data.error, 'error');
                    }
                })
                .catch(error => {
                showToast('Erro!', 'Erro ao processar solicitação: ' + error, 'error');
                });
            }
        }

        // Função para tentar sincronizar reservas que falharam
        function retrySync(bookingId) {
            if (confirm('Tem certeza que deseja tentar sincronizar esta reserva novamente?')) {
                fetch('{{ route("bookings.sync-pending") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        bookingIds: [bookingId]
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Sucesso!', 'Sincronização iniciada com sucesso!', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast('Erro!', 'Erro ao sincronizar: ' + (data.error || 'Erro desconhecido'), 'error');
                    }
                })
                .catch(error => {
                    showToast('Erro!', 'Erro ao processar solicitação: ' + error, 'error');
                });
            }
        }
    
    // Toast notification function
    function showToast(title, message, type = 'info') {
        const toastContainer = document.getElementById('toastContainer') || createToastContainer();
        
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${title}</strong><br>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        // Remove toast after it's hidden
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }
    
    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '1055';
        document.body.appendChild(container);
        return container;
        }
        
        // Inicializar quando a página carregar
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Dashboard carregado com sucesso');
        });
</script>
@endsection 