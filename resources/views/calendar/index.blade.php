@extends('layouts.app')

@section('title', 'FrontDesk - Calendário de Reservas')

@section('content')
<div class="container-fluid">
    <!-- Header com controles -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <div class="d-flex align-items-center">
            <h1 class="h2 me-3">📅 Calendário de Reservas</h1>
            <div class="btn-group me-3">
                <button type="button" class="btn btn-sm btn-outline-primary" id="todayBtn">Hoje</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="prevBtn">‹</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="nextBtn">›</button>
            </div>
            <h4 class="text-muted mb-0" id="currentDate"></h4>
        </div>
        
        <div class="btn-toolbar">
                        <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="monthView">Mês</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="weekView">Semana</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="dayView">Dia</button>
                        </div>
            <button type="button" class="btn btn-sm btn-primary" id="newBookingBtn">
                <i class="fas fa-plus me-1"></i>Nova Reserva
            </button>
                    </div>
                </div>

                @if(isset($error))
                    <div class="alert alert-danger" role="alert">
                        {{ $error }}
                    </div>
                @endif

    <!-- Filtros -->
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" class="form-control" id="searchInput" placeholder="Buscar por nome da propriedade, local, tags...">
            </div>
        </div>
        <div class="col-md-3">
            <select class="form-select" id="statusFilter">
                <option value="">Todos os Status</option>
                <option value="pending">Pendente</option>
                <option value="confirmed">Confirmado</option>
                <option value="cancelled">Cancelado</option>
                <option value="pending_sync">Pendente Sincronização</option>
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select" id="propertyFilter">
                <option value="">Todas as Propriedades</option>
                @foreach($properties ?? [] as $property)
                    <option value="{{ $property->id }}">{{ $property->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-outline-primary w-100" id="findAvailabilityBtn">
                <i class="fas fa-calendar-check me-1"></i>Verificar Disponibilidade
            </button>
        </div>
    </div>

    <!-- Calendário Principal -->
                <div class="row">
        <!-- Sidebar com Lista de Propriedades -->
        <div class="col-md-3">
                        <div class="card">
                            <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-building me-2"></i>Todas as Propriedades
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" id="propertiesList">
                        @foreach($properties ?? [] as $property)
                            <div class="list-group-item list-group-item-action property-item" 
                                 data-property-id="{{ $property->id }}"
                                 data-property-uuid="{{ $property->property_id }}">
                                <div class="d-flex align-items-center">
                                    <div class="property-avatar me-2">
                                        <i class="fas fa-home text-primary"></i>
                            </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">{{ $property->name }}</h6>
                                        <small class="text-muted">
                                            <i class="fas fa-bed me-1"></i>{{ $property->bedrooms ?? 1 }} quarto(s) • 
                                            <i class="fas fa-users me-1"></i>{{ $property->max_occupancy ?? 4 }} hóspedes
                                        </small>
                                        </div>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-link text-muted" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#" onclick="viewProperty('{{ $property->id }}')">
                                                <i class="fas fa-eye me-2"></i>Ver Detalhes
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" onclick="editProperty('{{ $property->id }}')">
                                                <i class="fas fa-edit me-2"></i>Editar
                                            </a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                            </div>
                                                    </div>

        <!-- Calendário Principal -->
        <div class="col-md-9">
            <div class="card">
                <div class="card-body p-0">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Detalhes da Reserva -->
<div class="modal fade" id="bookingDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes da Reserva</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="bookingDetailsContent">
                <!-- Conteúdo será preenchido via JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" id="editBookingBtn">
                    <i class="fas fa-edit me-2"></i>Editar Reserva
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Nova Reserva -->
<div class="modal fade" id="newBookingModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>Nova Reserva
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="newBookingForm">
                    <!-- Step 1: Seleção de Propriedade -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="fas fa-building me-2"></i>1. Selecionar Propriedade</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                        <div class="col-md-8">
                                            <label for="modalPropertyId" class="form-label fw-bold">Propriedade *</label>
                                            <select class="form-select form-select-lg" id="modalPropertyId" required onchange="onModalPropertyChange()">
                                                <option value="">Selecione a propriedade...</option>
                                                @foreach($properties ?? [] as $p)
                                                    <option value="{{ $p->id }}" 
                                                            data-nextpax-id="{{ $p->property_id }}"
                                                            data-currency="{{ $p->currency ?? 'BRL' }}" 
                                                            data-supplier="{{ $p->supplier_property_id ?? '' }}"
                                                            data-base-price="{{ $p->base_price ?? '' }}"
                                                            data-max-occupancy="{{ $p->max_occupancy ?? '' }}"
                                                            data-max-adults="{{ $p->max_adults ?? '' }}"
                                                            data-max-children="{{ $p->max_children ?? '' }}"
                                                            data-bedrooms="{{ $p->bedrooms ?? '' }}"
                                                            data-bathrooms="{{ $p->bathrooms ?? '' }}"
                                                            data-check-in-from="{{ $p->check_in_from ?? '' }}"
                                                            data-check-in-until="{{ $p->check_in_until ?? '' }}"
                                                            data-check-out-from="{{ $p->check_out_from ?? '' }}"
                                                            data-check-out-until="{{ $p->check_out_until ?? '' }}">
                                                        {{ $p->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <div id="modalPropertyInfo" class="d-none">
                                                <div class="card bg-light">
                                                    <div class="card-body p-2">
                                                        <small class="text-muted">
                                                            <i class="fas fa-bed me-1"></i><span id="modalPropertyBedrooms"></span> quartos<br>
                                                            <i class="fas fa-bath me-1"></i><span id="modalPropertyBathrooms"></span> banheiros<br>
                                                            <i class="fas fa-users me-1"></i>Máx: <span id="modalPropertyMaxOccupancy"></span> hóspedes
                                                        </small>
                                                    </div>
                                                </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                    <!-- Step 2: Dados da Reserva -->
                    <div id="modalBookingDetails" class="d-none">
                        <div class="row mb-4">
                    <div class="col-12">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0"><i class="fas fa-calendar me-2"></i>2. Dados da Reserva</h6>
                            </div>
                            <div class="card-body">
                                        <!-- Datas Selecionadas -->
                                        <div class="row mb-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Data Selecionada</label>
                                                <div class="alert alert-info">
                                                    <strong>Check-in:</strong> <span id="selectedCheckIn"></span><br>
                                                    <strong>Check-out:</strong> <span id="selectedCheckOut"></span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Duração</label>
                                                <div class="alert alert-secondary">
                                                    <strong>Noites:</strong> <span id="selectedNights"></span><br>
                                                    <strong>Período:</strong> <span id="selectedPeriod"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Informações do Hóspede -->
                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <label for="modalGuestFirstName" class="form-label">Nome *</label>
                                                <input type="text" class="form-control" id="modalGuestFirstName" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="modalGuestSurname" class="form-label">Sobrenome *</label>
                                                <input type="text" class="form-control" id="modalGuestSurname" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="modalGuestEmail" class="form-label">Email *</label>
                                                <input type="email" class="form-control" id="modalGuestEmail" required>
                                            </div>
                                        </div>

                                        <!-- Ocupação -->
                                        <div class="row mb-3">
                                            <div class="col-md-3">
                                                <label for="modalAdults" class="form-label">Adultos *</label>
                                                <input type="number" min="1" value="1" class="form-control" id="modalAdults" required>
                                                <small class="text-muted">Máx: <span id="modalMaxAdults">-</span></small>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="modalChildren" class="form-label">Crianças</label>
                                                <input type="number" min="0" value="0" class="form-control" id="modalChildren" required>
                                                <small class="text-muted">Máx: <span id="modalMaxChildren">-</span></small>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="modalRoomType" class="form-label">Tipo de Quarto</label>
                                                <select id="modalRoomType" class="form-select">
                                                    <option value="">Selecione...</option>
                                                    <option value="single">Quarto Individual</option>
                                                    <option value="double">Quarto Duplo</option>
                                                    <option value="suite">Suíte</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="modalPaymentType" class="form-label">Forma de Pagamento</label>
                                                <select id="modalPaymentType" class="form-select">
                                                    <option value="default" selected>Sem Pagamento</option>
                                                    <option value="creditcard">Cartão de Crédito</option>
                                                </select>
                                            </div>
                                                    </div>

                                        <!-- Detalhes Financeiros -->
                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <label for="modalTotalPrice" class="form-label">Valor Total *</label>
                                                <div class="input-group">
                                                    <select id="modalCurrency" class="form-select" style="max-width: 80px;">
                                                        <option value="BRL" selected>BRL</option>
                                                        <option value="USD">USD</option>
                                                        <option value="EUR">EUR</option>
                                                    </select>
                                                    <input type="number" class="form-control" id="modalTotalPrice" step="0.01" required>
                                                </div>
                                                <small class="text-muted">Preço base: <span id="modalBasePrice">-</span></small>
                                            </div>
                                            <div class="col-md-8">
                                                <label for="modalRemarks" class="form-label">Observações</label>
                                                <textarea class="form-control" id="modalRemarks" rows="3" placeholder="Observações sobre a reserva..."></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    </div>
                    </div>
                </form>
                </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="modalCreateBookingBtn" onclick="createBookingFromModal()" disabled>
                    <i class="fas fa-plus me-2"></i>Criar Reserva
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<!-- FullCalendar CSS -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
<!-- FontAwesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

<style>
.property-item {
    cursor: pointer;
    transition: all 0.2s ease;
}

.property-item:hover {
    background-color: #f8f9fa;
}

.property-item.active {
    background-color: #e3f2fd;
    border-left: 4px solid #2196f3;
}

.all-properties {
    background-color: #f8f9fa;
    border-left: 4px solid #28a745;
}

.all-properties:hover {
    background-color: #e8f5e8 !important;
}

.all-properties.active {
    background-color: #e8f5e8 !important;
    border-left: 4px solid #28a745;
}

.property-avatar {
    width: 32px;
    height: 32px;
    background-color: #e3f2fd;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

#calendar {
    height: 700px;
    padding: 20px;
}

.fc-event {
    cursor: pointer;
    border-radius: 4px;
    font-size: 12px;
    padding: 2px 4px;
}

.fc-event:hover {
    opacity: 0.9;
}

.fc-toolbar-title {
    font-size: 1.5rem !important;
    font-weight: 600;
}

.fc-button {
    background-color: #6c757d !important;
    border-color: #6c757d !important;
}

.fc-button:hover {
    background-color: #5a6268 !important;
    border-color: #545b62 !important;
}

.fc-button-primary {
    background-color: #007bff !important;
    border-color: #007bff !important;
}

.fc-button-primary:hover {
    background-color: #0056b3 !important;
    border-color: #0056b3 !important;
}

.fc-daygrid-day-number {
    font-weight: 600;
}

.fc-col-header-cell {
    background-color: #f8f9fa;
    font-weight: 600;
}

.fc-daygrid-day {
    cursor: pointer;
    transition: background-color 0.2s ease;
}

.fc-daygrid-day:hover {
    background-color: #f8f9fa !important;
}

.fc-daygrid-day-number {
    font-weight: 600;
    pointer-events: none;
}

.booking-status-pending { background-color: #ffc107 !important; }
.booking-status-confirmed { background-color: #28a745 !important; }
.booking-status-cancelled { background-color: #dc3545 !important; }
.booking-status-failed { background-color: #6c757d !important; }
.booking-status-request { background-color: #17a2b8 !important; }
.booking-status-pending_sync { background-color: #fd7e14 !important; }

.sync-status-pending { border-left: 4px solid #dc3545 !important; }
.sync-status-failed { border-left: 4px solid #fd7e14 !important; }
.sync-status-synced { border-left: 4px solid #28a745 !important; }
</style>
@endsection

@section('scripts')
<!-- FullCalendar JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<!-- Moment.js para formatação de datas -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/locale/pt-br.min.js"></script>

    <script>
// Configuração do idioma
moment.locale('pt-br');

// Dados das reservas do backend
const bookingsData = @json($bookings ?? []);
const propertiesData = @json($properties ?? []);

// Inicialização do calendário
    document.addEventListener('DOMContentLoaded', function() {
    initializeCalendar();
    setupEventListeners();
    updateCurrentDate();
});

function initializeCalendar() {
    const calendarEl = document.getElementById('calendar');
    
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'pt-br',
        headerToolbar: false, // Removemos o header padrão para usar nosso customizado
        height: 'auto',
        expandRows: true,
        dayMaxEvents: true,
        events: bookingsData,
        eventClick: function(info) {
            showBookingDetails(info.event);
        },
        eventDidMount: function(info) {
            // Adicionar classes CSS baseadas no status
            info.el.classList.add(`booking-status-${info.event.extendedProps.status || 'pending'}`);
            info.el.classList.add(`sync-status-${info.event.extendedProps.sync_status || 'pending'}`);
        },
        dateClick: function(info) {
            // Abrir modal de nova reserva quando clicar em um dia
            console.log('Dia clicado:', info.date);
            console.log('Data formatada:', moment(info.date).format('DD/MM/YYYY'));
            openNewBookingModal(info.date);
        }
    });
    
    calendar.render();
    window.calendar = calendar;
}

function setupEventListeners() {
    // Botões de navegação
    document.getElementById('todayBtn').addEventListener('click', () => {
        window.calendar.today();
        updateCurrentDate();
    });
    
    document.getElementById('prevBtn').addEventListener('click', () => {
        window.calendar.prev();
        updateCurrentDate();
    });
    
    document.getElementById('nextBtn').addEventListener('click', () => {
        window.calendar.next();
        updateCurrentDate();
    });
    
    // Botões de visualização
    document.getElementById('monthView').addEventListener('click', () => {
        window.calendar.changeView('dayGridMonth');
    });
    
    document.getElementById('weekView').addEventListener('click', () => {
        window.calendar.changeView('timeGridWeek');
    });
    
    document.getElementById('dayView').addEventListener('click', () => {
        window.calendar.changeView('timeGridDay');
    });
    
    // Botões de ação
    document.getElementById('newBookingBtn').addEventListener('click', () => {
        // Abrir modal com data atual
        openNewBookingModal(new Date());
    });
    
    document.getElementById('findAvailabilityBtn').addEventListener('click', () => {
        // Implementar verificação de disponibilidade
        alert('Funcionalidade de verificação de disponibilidade será implementada em breve!');
    });
    
    // Filtros
    document.getElementById('searchInput').addEventListener('input', filterProperties);
    document.getElementById('statusFilter').addEventListener('change', filterBookings);
    document.getElementById('propertyFilter').addEventListener('change', filterBookings);
    
    // Lista de propriedades
    document.querySelectorAll('.property-item').forEach(item => {
        item.addEventListener('click', () => {
            document.querySelectorAll('.property-item').forEach(i => i.classList.remove('active'));
            item.classList.add('active');
            filterBookingsByProperty(item.dataset.propertyId);
        });
    });
    
    // Adicionar opção para mostrar todas as propriedades
    const showAllProperties = () => {
        document.querySelectorAll('.property-item').forEach(i => i.classList.remove('active'));
        window.calendar.removeAllEvents();
        window.calendar.addEventSource(bookingsData);
        console.log('Mostrando todas as reservas');
    };
    
    // Adicionar botão "Todas as Propriedades" na sidebar
    const propertiesList = document.getElementById('propertiesList');
    if (propertiesList) {
        const allPropertiesItem = document.createElement('div');
        allPropertiesItem.className = 'list-group-item list-group-item-action property-item all-properties';
        allPropertiesItem.innerHTML = `
            <div class="d-flex align-items-center">
                <div class="property-avatar me-2">
                    <i class="fas fa-th-large text-success"></i>
                </div>
                <div class="flex-grow-1">
                    <h6 class="mb-1">Todas as Propriedades</h6>
                    <small class="text-muted">Mostrar todas as reservas</small>
                </div>
            </div>
        `;
        allPropertiesItem.addEventListener('click', showAllProperties);
        propertiesList.insertBefore(allPropertiesItem, propertiesList.firstChild);
    }
}

function updateCurrentDate() {
    const currentDate = window.calendar.getDate();
    const formattedDate = moment(currentDate).format('MMMM YYYY');
    document.getElementById('currentDate').textContent = formattedDate;
}

function showBookingDetails(event) {
    const booking = event;
    const content = `
        <div class="row">
            <div class="col-md-3 text-center">
                <div class="avatar-placeholder mb-3">
                    <i class="fas fa-user fa-3x text-primary"></i>
                </div>
                <h5>${booking.title}</h5>
                <p class="text-muted mb-1">${booking.extendedProps.guest_email || 'N/A'}</p>
                <p class="text-muted">${booking.extendedProps.guest_phone || 'N/A'}</p>
            </div>
            <div class="col-md-9">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-calendar me-2"></i>Datas</h6>
                        <p><strong>Check-in:</strong> ${moment(booking.start).format('DD/MM/YYYY')}</p>
                        <p><strong>Check-out:</strong> ${moment(booking.end).format('DD/MM/YYYY')}</p>
                        <p><strong>Noites:</strong> ${booking.extendedProps.nights}</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-users me-2"></i>Hóspedes</h6>
                        <p><strong>Total:</strong> ${booking.extendedProps.total_occupancy}</p>
                        <p><strong>Adultos:</strong> ${booking.extendedProps.adults}</p>
                        <p><strong>Crianças:</strong> ${booking.extendedProps.children}</p>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <h6><i class="fas fa-building me-2"></i>Propriedade</h6>
                        <p><strong>Nome:</strong> ${booking.extendedProps.property_name}</p>
                        <p><strong>Quarto:</strong> ${booking.extendedProps.room_type || 'N/A'}</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-money-bill me-2"></i>Financeiro</h6>
                        <p><strong>Valor Total:</strong> ${booking.extendedProps.currency} ${booking.extendedProps.total_amount}</p>
                        <p><strong>Status:</strong> <span class="badge bg-${getStatusBadgeColor(booking.extendedProps.status)}">${getStatusLabel(booking.extendedProps.status)}</span></p>
                        <p><strong>Sincronização:</strong> <span class="badge bg-${getSyncStatusBadgeColor(booking.extendedProps.sync_status)}">${getSyncStatusLabel(booking.extendedProps.sync_status)}</span></p>
                    </div>
                </div>
                ${booking.extendedProps.remarks ? `
                <div class="row mt-3">
                    <div class="col-12">
                        <h6><i class="fas fa-comment me-2"></i>Observações</h6>
                        <p>${booking.extendedProps.remarks}</p>
                    </div>
                </div>
                ` : ''}
            </div>
        </div>
    `;
    
    document.getElementById('bookingDetailsContent').innerHTML = content;
    document.getElementById('editBookingBtn').onclick = () => editBooking(booking.id);
    
    const modal = new bootstrap.Modal(document.getElementById('bookingDetailsModal'));
    modal.show();
}

function filterProperties() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('.property-item').forEach(item => {
        const propertyName = item.querySelector('h6').textContent.toLowerCase();
        if (propertyName.includes(searchTerm)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

function filterBookings() {
    const statusFilter = document.getElementById('statusFilter').value;
    const propertyFilter = document.getElementById('propertyFilter').value;
    
    let filteredEvents = bookingsData;
    
    if (statusFilter) {
        filteredEvents = filteredEvents.filter(event => event.extendedProps.status === statusFilter);
    }
    
    if (propertyFilter) {
        // Buscar a propriedade selecionada para obter o UUID do NextPax
        const selectedProperty = propertiesData.find(prop => prop.id == propertyFilter);
        if (selectedProperty) {
            filteredEvents = filteredEvents.filter(event => event.property_id === selectedProperty.property_id);
        }
    }
    
    window.calendar.removeAllEvents();
    window.calendar.addEventSource(filteredEvents);
}

function filterBookingsByProperty(propertyId) {
    if (!propertyId) {
        window.calendar.removeAllEvents();
        window.calendar.addEventSource(bookingsData);
        return;
    }
    
    // Buscar a propriedade selecionada para obter o UUID do NextPax
    const selectedProperty = propertiesData.find(prop => prop.id == propertyId);
    
    if (!selectedProperty) {
        console.log('Propriedade não encontrada:', propertyId);
        return;
    }
    
    // Filtrar reservas pelo UUID do NextPax da propriedade
    const filteredEvents = bookingsData.filter(event => event.property_id === selectedProperty.property_id);
    
    console.log('Filtrando por propriedade:', selectedProperty.name);
    console.log('UUID da propriedade:', selectedProperty.property_id);
    console.log('Total de reservas encontradas:', filteredEvents.length);
    
    window.calendar.removeAllEvents();
    window.calendar.addEventSource(filteredEvents);
}

function getStatusBadgeColor(status) {
    const colors = {
        'pending': 'warning',
        'confirmed': 'success',
        'cancelled': 'danger',
        'failed': 'dark',
        'request': 'info',
        'pending_sync': 'warning'
    };
    return colors[status] || 'secondary';
}

function getStatusLabel(status) {
    const labels = {
        'pending': 'Pendente',
        'confirmed': 'Confirmado',
        'cancelled': 'Cancelado',
        'failed': 'Falhou',
        'request': 'Solicitação',
        'pending_sync': 'Pendente Sincronização'
    };
    return labels[status] || status;
}

function getSyncStatusBadgeColor(syncStatus) {
    const colors = {
        'pending': 'warning',
        'synced': 'success',
        'failed': 'danger'
    };
    return colors[syncStatus] || 'secondary';
}

function getSyncStatusLabel(syncStatus) {
    const labels = {
        'pending': 'Pendente',
        'synced': 'Sincronizado',
        'failed': 'Falhou'
    };
    return labels[syncStatus] || syncStatus;
}

function editBooking(bookingId) {
    // Redirecionar para a página de edição de reservas
    window.location.href = `{{ route('bookings.index') }}?edit=${bookingId}`;
}

function viewProperty(propertyId) {
    // Redirecionar para a página de detalhes da propriedade
    window.location.href = `/properties/${propertyId}`;
}

function editProperty(propertyId) {
    // Redirecionar para a página de edição da propriedade
    window.location.href = `/properties/${propertyId}/edit`;
}

// Funções para o modal de nova reserva
function openNewBookingModal(selectedDate) {
    // Verificar se o modal existe
    const modal = document.getElementById('newBookingModal');
    const form = document.getElementById('newBookingForm');
    
    if (!modal || !form) {
        console.error('Modal ou formulário não encontrado');
        alert('Erro: Modal não encontrado. Recarregue a página.');
        return;
    }
    
    // Resetar o modal
    form.reset();
    document.getElementById('modalCreateBookingBtn').disabled = true;
    document.getElementById('modalBookingDetails').classList.add('d-none');
    document.getElementById('modalPropertyInfo').classList.add('d-none');
    document.getElementById('modalPropertyId').value = '';
    
    // Resetar campos de informação
    const basePriceEl = document.getElementById('modalBasePrice');
    const maxAdultsEl = document.getElementById('modalMaxAdults');
    const maxChildrenEl = document.getElementById('modalMaxChildren');
    
    if (basePriceEl) basePriceEl.textContent = '-';
    if (maxAdultsEl) maxAdultsEl.textContent = '-';
    if (maxChildrenEl) maxChildrenEl.textContent = '-';
    
    // Definir datas padrão (check-in no dia selecionado, check-out no dia seguinte)
    const checkInDate = moment(selectedDate).format('YYYY-MM-DD');
    const checkOutDate = moment(selectedDate).add(1, 'day').format('YYYY-MM-DD');
    
    const selectedCheckInEl = document.getElementById('selectedCheckIn');
    const selectedCheckOutEl = document.getElementById('selectedCheckOut');
    const selectedNightsEl = document.getElementById('selectedNights');
    const selectedPeriodEl = document.getElementById('selectedPeriod');
    
    if (selectedCheckInEl) selectedCheckInEl.textContent = moment(checkInDate).format('DD/MM/YYYY');
    if (selectedCheckOutEl) selectedCheckOutEl.textContent = moment(checkOutDate).format('DD/MM/YYYY');
    if (selectedNightsEl) selectedNightsEl.textContent = '1';
    if (selectedPeriodEl) selectedPeriodEl.textContent = '1 noite';
    
    // Armazenar datas para uso posterior
    window.selectedCheckInDate = checkInDate;
    window.selectedCheckOutDate = checkOutDate;
    
    // Mostrar modal
    const bootstrapModal = new bootstrap.Modal(modal);
    bootstrapModal.show();
}

function onModalPropertyChange() {
    const propertySelect = document.getElementById('modalPropertyId');
    const selectedOption = propertySelect.options[propertySelect.selectedIndex];
    const createBookingBtn = document.getElementById('modalCreateBookingBtn');
    const bookingDetails = document.getElementById('modalBookingDetails');
    const propertyInfo = document.getElementById('modalPropertyInfo');
    
    if (!selectedOption) {
        createBookingBtn.disabled = true;
        bookingDetails.classList.add('d-none');
        propertyInfo.classList.add('d-none');
        return;
    }
    
    // Obter dados da propriedade selecionada
    const nextPaxId = selectedOption.getAttribute('data-nextpax-id');
    const currency = selectedOption.getAttribute('data-currency');
    const supplier = selectedOption.getAttribute('data-supplier');
    const basePrice = selectedOption.getAttribute('data-base-price');
    const maxOccupancy = selectedOption.getAttribute('data-max-occupancy');
    const maxAdults = selectedOption.getAttribute('data-max-adults');
    const maxChildren = selectedOption.getAttribute('data-max-children');
    const bedrooms = selectedOption.getAttribute('data-bedrooms');
    const bathrooms = selectedOption.getAttribute('data-bathrooms');
    
    // Mostrar informações da propriedade
    if (bedrooms && bathrooms && maxOccupancy) {
        document.getElementById('modalPropertyBedrooms').textContent = bedrooms;
        document.getElementById('modalPropertyBathrooms').textContent = bathrooms;
        document.getElementById('modalPropertyMaxOccupancy').textContent = maxOccupancy;
        propertyInfo.classList.remove('d-none');
    }
    
    // Definir moeda
    if (currency) {
        document.getElementById('modalCurrency').value = currency;
    }
    
    // Definir preço base
    if (basePrice) {
        document.getElementById('modalTotalPrice').value = basePrice;
        document.getElementById('modalBasePrice').textContent = currency + ' ' + parseFloat(basePrice).toFixed(2);
    } else {
        document.getElementById('modalBasePrice').textContent = 'Não informado';
    }
    
    // Definir limites de ocupação
    if (maxAdults) {
        document.getElementById('modalMaxAdults').textContent = maxAdults;
        document.getElementById('modalAdults').max = maxAdults;
    }
    if (maxChildren) {
        document.getElementById('modalMaxChildren').textContent = maxChildren;
        document.getElementById('modalChildren').max = maxChildren;
    }
    
    // Mostrar seção de detalhes da reserva
    bookingDetails.classList.remove('d-none');
    
    // Habilitar botão de criar reserva
    createBookingBtn.disabled = false;
}

function createBookingFromModal() {
    const propertySelect = document.getElementById('modalPropertyId');
    const selectedOption = propertySelect.options[propertySelect.selectedIndex];
    
    if (!selectedOption) {
        alert('Por favor, selecione uma propriedade primeiro.');
        return;
    }
    
    const formData = {
        guestFirstName: document.getElementById('modalGuestFirstName').value,
        guestSurname: document.getElementById('modalGuestSurname').value,
        guestEmail: document.getElementById('modalGuestEmail').value,
        checkIn: window.selectedCheckInDate,
        checkOut: window.selectedCheckOutDate,
        adults: parseInt(document.getElementById('modalAdults').value, 10),
        children: parseInt(document.getElementById('modalChildren').value, 10),
        roomType: document.getElementById('modalRoomType').value,
        totalPrice: parseFloat(document.getElementById('modalTotalPrice').value),
        currency: document.getElementById('modalCurrency').value,
        paymentType: document.getElementById('modalPaymentType').value,
        propertyId: selectedOption.value, // ID local da propriedade
        remarks: document.getElementById('modalRemarks').value,
        supplierPropertyId: selectedOption.getAttribute('data-supplier') || null,
        nextPaxId: selectedOption.getAttribute('data-nextpax-id') || null
    };

    // Validações
    if (!formData.guestFirstName || !formData.guestSurname || !formData.guestEmail || 
        !formData.checkIn || !formData.checkOut || !formData.propertyId) {
        alert('Por favor, preencha todos os campos obrigatórios.');
        return;
    }

    if (new Date(formData.checkIn) >= new Date(formData.checkOut)) {
        alert('A data de check-out deve ser posterior à data de check-in.');
        return;
    }

    // Validação de ocupação
    const maxAdults = parseInt(selectedOption.getAttribute('data-max-adults') || 0);
    const maxChildren = parseInt(selectedOption.getAttribute('data-max-children') || 0);
    
    if (maxAdults > 0 && formData.adults > maxAdults) {
        alert(`O número máximo de adultos para esta propriedade é ${maxAdults}.`);
        return;
    }
    
    if (maxChildren > 0 && formData.children > maxChildren) {
        alert(`O número máximo de crianças para esta propriedade é ${maxChildren}.`);
        return;
    }

    // Estado de loading
    const createBtn = document.getElementById('modalCreateBookingBtn');
    const originalText = createBtn.innerHTML;
    createBtn.disabled = true;
    createBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Criando...';

    // Enviar para API
    fetch('{{ route("bookings.create") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.syncStatus === 'pending') {
                alert('✅ Reserva criada com sucesso!\n\n📝 Status: Salva localmente\n🔄 Sincronização: Pendente\n\nA reserva será sincronizada com o NextPax automaticamente.');
            } else {
                alert('✅ Reserva criada e sincronizada com sucesso!');
            }
            
            // Fechar modal e recarregar calendário
            const modal = bootstrap.Modal.getInstance(document.getElementById('newBookingModal'));
            modal.hide();
            
            // Recarregar página para mostrar a nova reserva
            location.reload();
        } else {
            alert('❌ Erro ao criar reserva: ' + (data.error || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Erro ao processar solicitação: ' + error);
    })
    .finally(() => {
        // Restaurar estado do botão
        createBtn.disabled = false;
        createBtn.innerHTML = originalText;
    });
}
</script>
@endsection 