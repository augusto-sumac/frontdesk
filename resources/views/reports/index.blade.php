@extends('layouts.app')

@section('title', 'FrontDesk - Relatórios')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i class="fas fa-chart-bar me-2"></i>Relatórios
        </h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="exportReport()">
                    <i class="fas fa-download me-1"></i>Exportar
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                    <i class="fas fa-sync-alt me-1"></i>Atualizar
                </button>
            </div>
        </div>
    </div>

    @if(isset($error))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ $error }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Financial Summary -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card text-center border-left-primary">
                <div class="card-body">
                    <h5 class="card-title text-primary">R$ {{ number_format($financialReport['weekly'] ?? 0, 2, ',', '.') }}</h5>
                    <p class="card-text">Faturamento Semanal</p>
                    <small class="text-muted">
                        <i class="fas fa-calendar-week me-1"></i>Esta semana
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center border-left-success">
                <div class="card-body">
                    <h5 class="card-title text-success">R$ {{ number_format($financialReport['monthly'] ?? 0, 2, ',', '.') }}</h5>
                    <p class="card-text">Faturamento Mensal</p>
                    <small class="text-muted">
                        <i class="fas fa-calendar-alt me-1"></i>Este mês
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center border-left-info">
                <div class="card-body">
                    <h5 class="card-title text-info">R$ {{ number_format($financialReport['yearly'] ?? 0, 2, ',', '.') }}</h5>
                    <p class="card-text">Faturamento Anual</p>
                    <small class="text-muted">
                        <i class="fas fa-calendar me-1"></i>Este ano
                    </small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card text-center border-left-warning">
                <div class="card-body">
                    <h5 class="card-title text-warning">{{ $financialReport['total_bookings'] ?? 0 }}</h5>
                    <p class="card-text">Total de Reservas</p>
                    <small class="text-muted">
                        <i class="fas fa-calendar-check me-1"></i>Valor médio: R$ {{ number_format($financialReport['average_booking_value'] ?? 0, 2, ',', '.') }}
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-line me-2"></i>Faturamento por Mês
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Ocupação por Mês
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="occupancyChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Reports -->
    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-building me-2"></i>Top Propriedades
                    </h5>
                </div>
                <div class="card-body">
                    @if(count($topProperties ?? []) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Propriedade</th>
                                        <th>Reservas</th>
                                        <th>Faturamento</th>
                                        <th>Valor Médio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($topProperties as $property)
                                    <tr>
                                        <td>
                                            <div class="fw-bold">{{ $property['name'] ?? 'N/A' }}</div>
                                            <small class="text-muted">
                                                <span class="badge bg-success">{{ $property['sync_status']['synced'] ?? 0 }} sinc</span>
                                                <span class="badge bg-warning">{{ $property['sync_status']['pending'] ?? 0 }} pend</span>
                                                <span class="badge bg-danger">{{ $property['sync_status']['failed'] ?? 0 }} falharam</span>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary fs-6">{{ $property['bookings'] ?? 0 }}</span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-success">R$ {{ number_format($property['revenue'] ?? 0, 2, ',', '.') }}</div>
                                        </td>
                                        <td>
                                            <small class="text-muted">R$ {{ number_format($property['average_value'] ?? 0, 2, ',', '.') }}</small>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-building fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Nenhuma propriedade encontrada</h5>
                            <p class="text-muted">As propriedades aparecerão aqui quando houver reservas.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card shadow">
                <div class="card-header bg-warning text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-pie me-2"></i>Reservas por Status
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" width="400" height="200"></canvas>
                    <div class="mt-3">
                        <div class="row text-center">
                            <div class="col-3">
                                <div class="h5 text-success">{{ $bookingsByStatus['by_status']['confirmed'] ?? 0 }}</div>
                                <small class="text-muted">Confirmadas</small>
                            </div>
                            <div class="col-3">
                                <div class="h5 text-warning">{{ $bookingsByStatus['by_status']['pending'] ?? 0 }}</div>
                                <small class="text-muted">Pendentes</small>
                            </div>
                            <div class="col-3">
                                <div class="h5 text-danger">{{ $bookingsByStatus['by_status']['cancelled'] ?? 0 }}</div>
                                <small class="text-muted">Canceladas</small>
                            </div>
                            <div class="col-3">
                                <div class="h5 text-secondary">{{ $bookingsByStatus['by_status']['failed'] ?? 0 }}</div>
                                <small class="text-muted">Falharam</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sync Status Summary -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-secondary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-sync-alt me-2"></i>Resumo de Sincronização
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="h3">{{ $bookingsByStatus['by_sync_status']['synced'] ?? 0 }}</div>
                                    <div>Reservas Sincronizadas</div>
                                    <small>R$ {{ number_format($financialReport['synced_revenue'] ?? 0, 2, ',', '.') }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="h3">{{ $bookingsByStatus['by_sync_status']['pending'] ?? 0 }}</div>
                                    <div>Pendentes de Sincronização</div>
                                    <small>R$ {{ number_format($financialReport['pending_sync_revenue'] ?? 0, 2, ',', '.') }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <div class="h3">{{ $bookingsByStatus['by_sync_status']['failed'] ?? 0 }}</div>
                                    <div>Falharam na Sincronização</div>
                                    <small>Requer atenção</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="h3">{{ $bookingsByStatus['total'] ?? 0 }}</div>
                                    <div>Total de Reservas</div>
                                    <small>R$ {{ number_format($financialReport['total_revenue'] ?? 0, 2, ',', '.') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
.border-left-primary {
    border-left: 4px solid #007bff !important;
}

.border-left-success {
    border-left: 4px solid #28a745 !important;
}

.border-left-info {
    border-left: 4px solid #17a2b8 !important;
}

.border-left-warning {
    border-left: 4px solid #ffc107 !important;
}

.card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
}

.card-header {
    border-bottom: none;
}

.table-hover tbody tr:hover {
    background-color: rgba(0,123,255,0.05);
}

.badge {
    font-size: 0.75em;
}

.fs-6 {
    font-size: 1rem !important;
}

.text-success {
    color: #28a745 !important;
}

.text-warning {
    color: #ffc107 !important;
}

.text-danger {
    color: #dc3545 !important;
}

.text-info {
    color: #17a2b8 !important;
}

.text-secondary {
    color: #6c757d !important;
}

.bg-success {
    background-color: #28a745 !important;
}

.bg-warning {
    background-color: #ffc107 !important;
}

.bg-danger {
    background-color: #dc3545 !important;
}

.bg-info {
    background-color: #17a2b8 !important;
}

.bg-primary {
    background-color: #007bff !important;
}

.bg-secondary {
    background-color: #6c757d !important;
}

/* Responsividade para cards pequenos */
@media (max-width: 768px) {
    .col-md-3, .col-md-6 {
        margin-bottom: 1rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .h3 {
        font-size: 1.5rem;
    }
    
    .h5 {
        font-size: 1.1rem;
    }
}

/* Animações para os gráficos */
canvas {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Estilo para o botão de exportar */
.btn-outline-primary:hover {
    background-color: #007bff;
    border-color: #007bff;
    color: white;
}

/* Estilo para as métricas */
.metric-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
}

.metric-card .card-body {
    padding: 1.5rem;
}

.metric-card .h3 {
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.metric-card small {
    opacity: 0.9;
}
</style>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Dados reais do backend - definidos de forma segura
    @php
        $monthlyRevenueData = $monthlyRevenue ?? [];
        $monthlyOccupancyData = $monthlyOccupancy ?? [];
        $bookingsByStatusData = $bookingsByStatus ?? [];
    @endphp
    
    let monthlyRevenue = @json($monthlyRevenueData);
    let monthlyOccupancy = @json($monthlyOccupancyData);
    let bookingsByStatus = @json($bookingsByStatusData);
    
    // Garantir que as variáveis sejam objetos válidos
    if (!monthlyRevenue || typeof monthlyRevenue !== 'object') {
        monthlyRevenue = {};
    }
    if (!monthlyOccupancy || typeof monthlyOccupancy !== 'object') {
        monthlyOccupancy = {};
    }
    if (!bookingsByStatus || typeof bookingsByStatus !== 'object') {
        bookingsByStatus = {
            by_status: { confirmed: 0, pending: 0, cancelled: 0, failed: 0 },
            by_sync_status: { synced: 0, pending: 0, failed: 0, syncing: 0 },
            total: 0
        };
    }

    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    const revenueChart = new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: Object.keys(monthlyRevenue || {}),
            datasets: [{
                label: 'Faturamento (R$)',
                data: Object.values(monthlyRevenue || {}),
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.1,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Faturamento Mensal - Últimos 12 Meses'
                },
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'R$ ' + value.toLocaleString('pt-BR');
                        }
                    }
                }
            }
        }
    });

    // Occupancy Chart
    const occupancyCtx = document.getElementById('occupancyChart').getContext('2d');
    const occupancyChart = new Chart(occupancyCtx, {
        type: 'bar',
        data: {
            labels: Object.keys(monthlyOccupancy || {}),
            datasets: [{
                label: 'Taxa de Ocupação (%)',
                data: Object.values(monthlyOccupancy || {}),
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgb(54, 162, 235)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Taxa de Ocupação Mensal - Últimos 12 Meses'
                },
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    }
                }
            }
        }
    });

    // Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Confirmadas', 'Pendentes', 'Canceladas', 'Falharam'],
            datasets: [{
                data: [
                    (bookingsByStatus.by_status && bookingsByStatus.by_status.confirmed) || 0,
                    (bookingsByStatus.by_status && bookingsByStatus.by_status.pending) || 0,
                    (bookingsByStatus.by_status && bookingsByStatus.by_status.cancelled) || 0,
                    (bookingsByStatus.by_status && bookingsByStatus.by_status.failed) || 0
                ],
                backgroundColor: [
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(255, 205, 86, 0.8)',
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(108, 117, 125, 0.8)'
                ],
                borderColor: [
                    'rgb(75, 192, 192)',
                    'rgb(255, 205, 86)',
                    'rgb(255, 99, 132)',
                    'rgb(108, 117, 125)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Distribuição de Reservas por Status'
                },
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Função para exportar relatório
    function exportReport() {
        // Criar um objeto com todos os dados do relatório
        const reportData = {
            financialReport: @json($financialReport ?? []),
            topProperties: @json($topProperties ?? []),
            bookingsByStatus: @json($bookingsByStatus ?? (object)[]),
            monthlyRevenue: @json($monthlyRevenue ?? []),
            monthlyOccupancy: @json($monthlyOccupancy ?? []),
            generatedAt: new Date().toLocaleString('pt-BR')
        };

        // Garantir que todos os campos sejam válidos
        if (!reportData.financialReport) reportData.financialReport = {};
        if (!reportData.topProperties) reportData.topProperties = [];
        if (!reportData.bookingsByStatus) reportData.bookingsByStatus = {};
        if (!reportData.monthlyRevenue) reportData.monthlyRevenue = {};
        if (!reportData.monthlyOccupancy) reportData.monthlyOccupancy = {};

        // Converter para JSON
        const dataStr = JSON.stringify(reportData, null, 2);
        const dataBlob = new Blob([dataStr], {type: 'application/json'});

        // Criar link de download
        const link = document.createElement('a');
        link.href = URL.createObjectURL(dataBlob);
        link.download = `relatorio-frontdesk-${new Date().toISOString().split('T')[0]}.json`;
        link.click();

        // Mostrar mensagem de sucesso
        showToast('Sucesso!', 'Relatório exportado com sucesso!', 'success');
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
        console.log('Relatórios carregados com sucesso');
        console.log('Dados mensais:', monthlyRevenue);
        console.log('Ocupação mensal:', monthlyOccupancy);
        console.log('Status das reservas:', bookingsByStatus);
    });
</script>
@endsection 