@extends('layouts.app')

@section('title', 'FrontDesk - Reservas')

@section('content')
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Reservas</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshBookings()">
                                <i class="fas fa-sync-alt"></i> Atualizar
                            </button>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary" onclick="openCreateModal()">
                            <i class="fas fa-plus"></i> Nova Reserva
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
                    <div class="col-md-2">
                        <select class="form-select" id="statusFilter">
                            <option value="">Todos os Status</option>
                            <option value="reservation">Confirmada</option>
                            <option value="request">Pendente</option>
                            <option value="cancelled">Cancelada</option>
                            <option value="pending_sync">Pendente Sincronização</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" id="syncFilter">
                            <option value="">Todos os Sync</option>
                            <option value="synced">Sincronizado</option>
                            <option value="pending">Pendente</option>
                            <option value="failed">Falhou</option>
                            <option value="syncing">Sincronizando</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control" id="dateFilter" placeholder="Filtrar por data">
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control" id="searchFilter" placeholder="Buscar por ID ou hóspede">
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-outline-secondary me-2" onclick="applyFilters()">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        <button class="btn btn-outline-primary" onclick="syncPendingBookings()">
                            <i class="fas fa-sync-alt"></i> Sincronizar Pendentes
                        </button>
                    </div>
                </div>

<!-- Tabela de Reservas -->
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID da Reserva</th>
                                            <th>Hóspede</th>
                                            <th>Check-in</th>
                                            <th>Check-out</th>
                                            <th>Status</th>
                                            <th>Sincronização</th>
                                            <th>Valor</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
        <tbody id="bookingsTableBody">
            @if(count($bookings ?? []) > 0)
                                        @foreach($bookings as $booking)
                                            <tr>
                                                <td>{{ $booking['id'] ?? 'N/A' }}</td>
                                                <td>{{ $booking['guest_first_name'] }} {{ $booking['guest_surname'] }}</td>
                                                <td>{{ \Carbon\Carbon::parse($booking['check_in_date'] ?? now())->format('d/m/Y') }}</td>
                                                <td>{{ \Carbon\Carbon::parse($booking['check_out_date'] ?? now())->format('d/m/Y') }}</td>
                                                <td>
                                                    <span class="badge {{ (($booking['status'] ?? '') === 'confirmed' ? 'bg-success' : (($booking['status'] ?? '') === 'pending' ? 'bg-warning' : 'bg-danger')) }}">
                                                        {{ ucfirst($booking['status'] ?? 'unknown') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @php
                                                        $syncStatus = $booking['sync_status'] ?? 'pending';
                                                        $syncBadgeClass = match($syncStatus) {
                                                            'synced' => 'bg-success',
                                                            'pending' => 'bg-warning',
                                                            'failed' => 'bg-danger',
                                                            'syncing' => 'bg-info',
                                                            default => 'bg-secondary'
                                                        };
                                                        $syncStatusText = match($syncStatus) {
                                                            'synced' => 'Sincronizado',
                                                            'pending' => 'Pendente',
                                                            'failed' => 'Falhou',
                                                            'syncing' => 'Sincronizando',
                                                            default => 'Desconhecido'
                                                        };
                                                    @endphp
                                                    <span class="badge {{ $syncBadgeClass }}" title="{{ $booking['sync_error'] ?? '' }}">
                                                        {{ $syncStatusText }}
                                                    </span>
                                                    @if($syncStatus === 'failed' && !empty($booking['sync_error']))
                                                        <i class="fas fa-exclamation-triangle text-danger ms-1" title="{{ $booking['sync_error'] }}"></i>
                                                    @endif
                                                </td>
                                                <td>{{ $booking['currency'] ?? 'BRL' }} {{ number_format($booking['total_amount'] ?? 0, 2, ',', '.') }}</td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewBooking('{{ $booking['id'] }}')">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        @if(($booking['status'] ?? '') === 'pending')
                                    <button type="button" class="btn btn-sm btn-success" onclick="acceptBooking('{{ $booking['id'] }}', '{{ $booking['channel_id'] ?? 'AIR298' }}')">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="rejectBooking('{{ $booking['id'] }}', '{{ $booking['channel_id'] ?? 'AIR298' }}')">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
            @else
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <i class="fas fa-bed fa-2x text-muted mb-2"></i>
                        <p class="text-muted mb-0">Nenhuma reserva encontrada</p>
                    </td>
                </tr>
            @endif
                                    </tbody>
                                </table>
    </div>

<!-- Modal para criar nova reserva -->
<div class="modal fade" id="createBookingModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nova Reserva</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createBookingForm">
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
                                            <label for="propertyId" class="form-label fw-bold">Propriedade *</label>
                                            <select class="form-select form-select-lg" id="propertyId" required onchange="onPropertyChange()">
                                                <option value="">Selecione a propriedade...</option>
                                                @foreach(($properties ?? []) as $p)
                                                    <option value="{{ $p['localId'] }}" 
                                                            data-nextpax-id="{{ $p['id'] }}"
                                                            data-currency="{{ $p['baseCurrency'] ?? 'BRL' }}" 
                                                            data-supplier="{{ $p['supplierPropertyId'] ?? '' }}"
                                                            data-base-price="{{ $p['basePrice'] ?? '' }}"
                                                            data-max-occupancy="{{ $p['max_occupancy'] ?? '' }}"
                                                            data-max-adults="{{ $p['max_adults'] ?? '' }}"
                                                            data-max-children="{{ $p['max_children'] ?? '' }}"
                                                            data-bedrooms="{{ $p['bedrooms'] ?? '' }}"
                                                            data-bathrooms="{{ $p['bathrooms'] ?? '' }}"
                                                            data-check-in-from="{{ $p['check_in_from'] ?? '' }}"
                                                            data-check-in-until="{{ $p['check_in_until'] ?? '' }}"
                                                            data-check-out-from="{{ $p['check_out_from'] ?? '' }}"
                                                            data-check-out-until="{{ $p['check_out_until'] ?? '' }}">
                                                        {{ $p['name'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <div id="propertyInfo" class="d-none">
                                                <div class="card bg-light">
                                                    <div class="card-body p-2">
                                                        <small class="text-muted">
                                                            <i class="fas fa-bed me-1"></i><span id="propertyBedrooms"></span> quartos<br>
                                                            <i class="fas fa-bath me-1"></i><span id="propertyBathrooms"></span> banheiros<br>
                                                            <i class="fas fa-users me-1"></i>Máx: <span id="propertyMaxOccupancy"></span> hóspedes
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

                    <!-- Step 2: Dados da Reserva (inicialmente desabilitado) -->
                    <div id="bookingDetails" class="d-none">
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0"><i class="fas fa-calendar me-2"></i>2. Dados da Reserva</h6>
                                    </div>
                                    <div class="card-body">
                                        <!-- Informações do Hóspede -->
                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <label for="guestFirstName" class="form-label">Nome *</label>
                                                <input type="text" class="form-control" id="guestFirstName" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="guestSurname" class="form-label">Sobrenome *</label>
                                                <input type="text" class="form-control" id="guestSurname" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="guestEmail" class="form-label">Email *</label>
                                                <input type="email" class="form-control" id="guestEmail" required>
                                            </div>
                                        </div>

                                        <!-- Datas e Ocupação -->
                                        <div class="row mb-3">
                                            <div class="col-md-3">
                                                <label for="checkIn" class="form-label">Check-in *</label>
                                                <input type="date" class="form-control" id="checkIn" required>
                                                <small class="text-muted" id="checkInInfo"></small>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="checkOut" class="form-label">Check-out *</label>
                                                <input type="date" class="form-control" id="checkOut" required>
                                                <small class="text-muted" id="checkOutInfo"></small>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="adults" class="form-label">Adultos *</label>
                                                <input type="number" min="1" value="1" class="form-control" id="adults" required>
                                                <small class="text-muted">Máx: <span id="maxAdults">-</span></small>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="children" class="form-label">Crianças</label>
                                                <input type="number" min="0" value="0" class="form-control" id="children" required>
                                                <small class="text-muted">Máx: <span id="maxChildren">-</span></small>
                                            </div>
                                        </div>

                                        <!-- Detalhes Financeiros -->
                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <label for="totalPrice" class="form-label">Valor Total *</label>
                                                <div class="input-group">
                                                    <select id="currency" class="form-select" style="max-width: 80px;">
                                                        <option value="BRL" selected>BRL</option>
                                                        <option value="USD">USD</option>
                                                        <option value="EUR">EUR</option>
                                                    </select>
                                                    <input type="number" class="form-control" id="totalPrice" step="0.01" required>
                                                </div>
                                                <small class="text-muted">Preço base: <span id="basePrice">-</span></small>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="paymentType" class="form-label">Forma de Pagamento</label>
                                                <select id="paymentType" class="form-select">
                                                    <option value="default" selected>Sem Pagamento</option>
                                                    <option value="creditcard">Cartão de Crédito</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="roomType" class="form-label">Tipo de Quarto</label>
                                                <select id="roomType" class="form-select">
                                                    <option value="">Selecione...</option>
                                                    <option value="single">Quarto Individual</option>
                                                    <option value="double">Quarto Duplo</option>
                                                    <option value="suite">Suíte</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Observações -->
                                        <div class="row">
                                            <div class="col-12">
                                                <label for="remarks" class="form-label">Observações</label>
                                                <textarea class="form-control" id="remarks" rows="3" placeholder="Observações sobre a reserva..."></textarea>
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
                <button type="button" class="btn btn-primary" id="createBookingBtn" onclick="createBooking()" disabled>
                    <i class="fas fa-plus me-2"></i>Criar Reserva
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script>
    function refreshBookings() {
        location.reload();
    }

        function openCreateModal() {
            // Reset form
            document.getElementById('createBookingForm').reset();
            document.getElementById('createBookingBtn').disabled = true;
            document.getElementById('bookingDetails').classList.add('d-none');
            document.getElementById('propertyInfo').classList.add('d-none');
            document.getElementById('propertyId').value = '';
            
            // Reset info fields
            document.getElementById('basePrice').textContent = '-';
            document.getElementById('maxAdults').textContent = '-';
            document.getElementById('maxChildren').textContent = '-';
            document.getElementById('checkInInfo').textContent = '';
            document.getElementById('checkOutInfo').textContent = '';
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('createBookingModal'));
            modal.show();
        }

        function onPropertyChange() {
            const sel = document.getElementById('propertyId');
            const propertyId = sel.value;
            const createBookingBtn = document.getElementById('createBookingBtn');
            const bookingDetails = document.getElementById('bookingDetails');
            const propertyInfo = document.getElementById('propertyInfo');
            
            if (!propertyId) {
                // Reset form when no property is selected
                createBookingBtn.disabled = true;
                bookingDetails.classList.add('d-none');
                propertyInfo.classList.add('d-none');
                return;
            }
            
            // Get selected option data
            const opt = sel.options[sel.selectedIndex];
            const nextPaxId = opt.getAttribute('data-nextpax-id'); // UUID do NextPax
            const currency = opt.getAttribute('data-currency');
            const supplier = opt.getAttribute('data-supplier');
            const basePrice = opt.getAttribute('data-base-price');
            const maxOccupancy = opt.getAttribute('data-max-occupancy');
            const maxAdults = opt.getAttribute('data-max-adults');
            const maxChildren = opt.getAttribute('data-max-children');
            const bedrooms = opt.getAttribute('data-bedrooms');
            const bathrooms = opt.getAttribute('data-bathrooms');
            const checkInFrom = opt.getAttribute('data-check-in-from');
            const checkInUntil = opt.getAttribute('data-check-in-until');
            const checkOutFrom = opt.getAttribute('data-check-out-from');
            const checkOutUntil = opt.getAttribute('data-check-out-until');
            
            // Show property info
            if (bedrooms && bathrooms && maxOccupancy) {
                document.getElementById('propertyBedrooms').textContent = bedrooms;
                document.getElementById('propertyBathrooms').textContent = bathrooms;
                document.getElementById('propertyMaxOccupancy').textContent = maxOccupancy;
                propertyInfo.classList.remove('d-none');
            }
            
            // Set currency
            if (currency) {
                document.getElementById('currency').value = currency;
            }
            
            // Set base price
            if (basePrice) {
                document.getElementById('totalPrice').value = basePrice;
                document.getElementById('basePrice').textContent = currency + ' ' + parseFloat(basePrice).toFixed(2);
            } else {
                document.getElementById('basePrice').textContent = 'Não informado';
            }
            
            // Set max occupancy limits
            if (maxAdults) {
                document.getElementById('maxAdults').textContent = maxAdults;
                document.getElementById('adults').max = maxAdults;
            }
            if (maxChildren) {
                document.getElementById('maxChildren').textContent = maxChildren;
                document.getElementById('children').max = maxChildren;
            }
            
            // Set check-in/out info
            if (checkInFrom && checkInUntil) {
                document.getElementById('checkInInfo').textContent = `Horário: ${checkInFrom} - ${checkInUntil}`;
            }
            if (checkOutFrom && checkOutUntil) {
                document.getElementById('checkOutInfo').textContent = `Horário: ${checkOutFrom} - ${checkOutUntil}`;
            }
            
            // Show booking details section
            bookingDetails.classList.remove('d-none');
            
            // Enable create booking button
            createBookingBtn.disabled = false;
            
            // Try to get additional data from API if available
            if (propertyId) {
                fetch(`{{ url('/bookings/context') }}/${propertyId}`)
                    .then(r => r.json())
                    .then(res => {
                        if (res && res.success && res.data) {
                            // Update with API data if available
                            if (res.data.baseCurrency && !currency) {
                                document.getElementById('currency').value = res.data.baseCurrency;
                            }
                            if (res.data.basePrice && !basePrice) {
                                document.getElementById('totalPrice').value = res.data.basePrice;
                                document.getElementById('basePrice').textContent = (res.data.baseCurrency || 'BRL') + ' ' + parseFloat(res.data.basePrice).toFixed(2);
                            }
                            if (res.data.supplierPropertyId && !supplier) {
                                opt.setAttribute('data-supplier', res.data.supplierPropertyId);
                            }
                            if (res.data.paymentProvider && res.data.paymentProvider !== 'none') {
                                document.getElementById('paymentType').value = 'creditcard';
                            }
                            
                            // Log successful API call (debug only)
                            console.log('Dados adicionais carregados da API:', res.data);
                        } else if (res && res.error) {
                            // API returned error, but continue with local data
                            console.log('API retornou erro (não crítico):', res.error);
                        }
                    })
                    .catch((error) => {
                        // API call failed, continue with local data (this is normal)
                        console.log('API não disponível, usando dados locais (normal)');
                    });
            }
        }

    function applyFilters() {
        const status = document.getElementById('statusFilter').value;
        const syncStatus = document.getElementById('syncFilter').value;
        const date = document.getElementById('dateFilter').value;
        const search = document.getElementById('searchFilter').value;
        
        // Implementar lógica de filtro
        console.log('Aplicando filtros:', { status, syncStatus, date, search });
        }

        function syncPendingBookings() {
            if (confirm('Tem certeza que deseja sincronizar todas as reservas pendentes?')) {
                fetch('{{ route("bookings.sync-pending") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Reservas pendentes sincronizadas com sucesso!');
                        location.reload();
                    } else {
                        alert('Erro ao sincronizar reservas pendentes: ' + data.error);
                    }
                })
                .catch(error => {
                    alert('Erro ao processar solicitação: ' + error);
                });
            }
        }

        function viewBooking(bookingId) {
        window.location.href = `{{ url('/bookings') }}/${bookingId}`;
        }

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
                        alert('Reserva aceita com sucesso!');
                        location.reload();
                    } else {
                        alert('Erro ao aceitar reserva: ' + data.error);
                    }
                })
                .catch(error => {
                    alert('Erro ao processar solicitação: ' + error);
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
                        alert('Reserva recusada com sucesso!');
                        location.reload();
                    } else {
                        alert('Erro ao recusar reserva: ' + data.error);
                    }
                })
                .catch(error => {
                    alert('Erro ao processar solicitação: ' + error);
                });
            }
        }

    function createBooking() {
        const propertySelect = document.getElementById('propertyId');
        const selectedOption = propertySelect.options[propertySelect.selectedIndex];
        
        if (!selectedOption) {
            alert('Por favor, selecione uma propriedade primeiro.');
            return;
        }
        
        const formData = {
            guestFirstName: document.getElementById('guestFirstName').value,
            guestSurname: document.getElementById('guestSurname').value,
            guestEmail: document.getElementById('guestEmail').value,
            checkIn: document.getElementById('checkIn').value,
            checkOut: document.getElementById('checkOut').value,
            adults: parseInt(document.getElementById('adults').value, 10),
            children: parseInt(document.getElementById('children').value, 10),
            roomType: document.getElementById('roomType').value,
            totalPrice: parseFloat(document.getElementById('totalPrice').value),
            currency: document.getElementById('currency').value,
            paymentType: document.getElementById('paymentType').value,
            propertyId: selectedOption.value, // Este é o ID local (1, 3, 4, 5, 6)
            remarks: document.getElementById('remarks').value,
            supplierPropertyId: selectedOption.getAttribute('data-supplier') || null,
            nextPaxId: selectedOption.getAttribute('data-nextpax-id') || null // UUID do NextPax
        };

        // Validate required fields
        if (!formData.guestFirstName || !formData.guestSurname || !formData.guestEmail || 
            !formData.checkIn || !formData.checkOut || !formData.propertyId) {
            alert('Por favor, preencha todos os campos obrigatórios.');
            return;
        }

        // Validate dates
        if (new Date(formData.checkIn) >= new Date(formData.checkOut)) {
            alert('A data de check-out deve ser posterior à data de check-in.');
            return;
        }

        // Validate occupancy
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

        // Show loading state
        const createBtn = document.getElementById('createBookingBtn');
        const originalText = createBtn.innerHTML;
        createBtn.disabled = true;
        createBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Criando...';

        // Debug: log the data being sent
        console.log('Enviando dados para criação de reserva:', formData);

        fetch('{{ route("bookings.create") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(formData)
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
                if (data.syncStatus === 'pending') {
                    // Reserva salva localmente, sincronização pendente
                    alert('✅ Reserva criada com sucesso!\n\n📝 Status: Salva localmente\n🔄 Sincronização: Pendente\n\nA reserva será sincronizada com o NextPax automaticamente. Você pode usar o botão "Sincronizar Pendentes" para tentar sincronizar agora.');
                } else {
                    // Reserva sincronizada com sucesso
                    alert('✅ Reserva criada e sincronizada com sucesso!');
                }
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
            // Restore button state
            createBtn.disabled = false;
            createBtn.innerHTML = originalText;
        });
    }
    </script>
@endsection 