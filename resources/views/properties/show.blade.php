@extends('layouts.app')

@section('title', 'FrontDesk - ' . ($property->name ?? 'Propriedade'))

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('properties.index') }}">Propriedades</a></li>
<li class="breadcrumb-item active" aria-current="page">{{ $property->name ?? 'Propriedade' }}</li>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <div>
            <h1 class="h2">{{ $property->name ?? 'Propriedade' }}</h1>
            <p class="text-muted">Detalhes e configurações da propriedade</p>
        </div>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshData()">
                    <i class="fas fa-sync-alt me-1"></i> Atualizar
                </button>
            </div>
            @empty($isApiProperty)
            <a href="{{ route('properties.edit', $property->id) }}" class="btn btn-sm btn-primary me-2">
                <i class="fas fa-edit me-1"></i> Editar
            </a>
            <button type="button" class="btn btn-sm btn-danger" onclick="deleteProperty()">
                <i class="fas fa-trash me-1"></i> Excluir
            </button>
            @endempty
        </div>
    </div>

    <!-- Property Information -->
    <div class="row">
        <!-- Main Property Details -->
        <div class="col-lg-8">
            <!-- Property Overview Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle me-2"></i>Informações da Propriedade
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>ID:</strong> {{ $property->property_id ?? 'N/A' }}</p>
                            <p><strong>Supplier ID:</strong> {{ $meta['supplierPropertyId'] ?? '-' }}</p>
                            <p><strong>Gestor:</strong> {{ $meta['propertyManager'] ?? '-' }}</p>
                            <p><strong>Tipo:</strong> {{ $property->property_type_text ?? 'N/A' }}</p>
                            <p><strong>Status:</strong> {!! $property->status_badge ?? 'N/A' !!}</p>
                            <p><strong>Preço Base:</strong> {{ $property->formatted_price ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Ocupação Máxima:</strong> {{ $property->max_occupancy ?? 'N/A' }} pessoas</p>
                            <p><strong>Adultos Máximos:</strong> {{ $property->max_adults ?? 'N/A' }}</p>
                            <p><strong>Quartos:</strong> {{ $property->bedrooms ?? 'N/A' }}</p>
                            <p><strong>Banheiros:</strong> {{ $property->bathrooms ?? 'N/A' }}</p>
                        </div>
                    </div>
                    
                    @if($property->description)
                        <hr>
                        <p><strong>Descrição:</strong></p>
                        <p class="mb-0">{{ $property->description }}</p>
                    @endif
                </div>
            </div>

            <!-- Address Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-map-marker-alt me-2"></i>Endereço
                    </h5>
                </div>
                <div class="card-body">
                    <p><strong>Endereço:</strong> {{ $property->full_address ?? 'N/A' }}</p>
                    @if($property->postal_code)
                        <p><strong>CEP:</strong> {{ $property->postal_code }}</p>
                    @endif
                    @if($property->hasCoordinates())
                        <p><strong>Coordenadas:</strong> {{ $property->latitude }}, {{ $property->longitude }}</p>
                    @elseif(isset($general['geoLocation']))
                        <p><strong>Coordenadas:</strong> {{ $general['geoLocation']['latitude'] ?? '-' }}, {{ $general['geoLocation']['longitude'] ?? '-' }}</p>
                    @endif
                    @if(isset($general['baseCurrency']))
                        <p class="mb-0"><strong>Moeda:</strong> {{ $general['baseCurrency'] }}</p>
                    @endif
                </div>
            </div>

            <!-- Check-in/Check-out Times Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-clock me-2"></i>Horários de Check-in/Check-out
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-success">Check-in</h6>
                            <p><strong>Das:</strong> {{ $property->check_in_from ?? '14:00' }}</p>
                            <p><strong>Até:</strong> {{ $property->check_in_until ?? '22:00' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-danger">Check-out</h6>
                            <p><strong>Das:</strong> {{ $property->check_out_from ?? '08:00' }}</p>
                            <p><strong>Até:</strong> {{ $property->check_out_until ?? '11:00' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Amenities and Rules Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-concierge-bell me-2"></i>Comodidades, Regras e Descrições
                    </h5>
                </div>
                <div class="card-body">
                    @php
                        $amenList = $property->amenities ?? ($amenities ?? []);
                        $rulesList = $property->house_rules ?? [];
                        $descs = $descriptions ?? [];
                    @endphp
                    @if(!empty($amenList))
                        <p><strong>Comodidades:</strong>
                            @foreach($amenList as $amen)
                                @if(is_array($amen))
                                    <span class="badge bg-light text-dark me-1">{{ $amen['typeCode'] ?? 'A' }}</span>
                                @else
                                    <span class="badge bg-light text-dark me-1">{{ $amen }}</span>
                                @endif
                            @endforeach
                        </p>
                    @else
                        <p class="text-muted">Sem comodidades cadastradas.</p>
                    @endif

                    @if(!empty($rulesList))
                        <p><strong>Regras da Casa:</strong> {{ is_array($rulesList) ? implode(', ', $rulesList) : $rulesList }}</p>
                    @else
                        <p class="text-muted">Sem regras cadastradas.</p>
                    @endif

                    @if(!empty($descs))
                        <hr>
                        <p><strong>Descrições:</strong></p>
                        @foreach($descs as $d)
                            <p class="mb-1"><span class="badge bg-secondary me-2">{{ $d['language'] ?? 'PT' }}</span>{{ $d['text'] ?? '' }}</p>
                        @endforeach
                    @endif
                </div>
            </div>

            <!-- Contact Information Card -->
            @if($property->contact_name || $property->contact_phone || $property->contact_email)
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-address-book me-2"></i>Informações de Contato
                    </h5>
                </div>
                <div class="card-body">
                    @if($property->contact_name)
                        <p><strong>Nome:</strong> {{ $property->contact_name }}</p>
                    @endif
                    @if($property->contact_phone)
                        <p><strong>Telefone:</strong> {{ $property->contact_phone }}</p>
                    @endif
                    @if($property->contact_email)
                        <p><strong>E-mail:</strong> {{ $property->contact_email }}</p>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-images me-2"></i>Galeria de Imagens
                    </h5>
                </div>
                <div class="card-body">
                    @if(isset($images) && count($images) > 0)
                        @php
                            $imgs = collect($images)->sortBy('orderNumber')->values()->all();
                            $main = $imgs[0] ?? null;
                            $rest = array_slice($imgs, 1);
                        @endphp

                        @if($main)
                            <div class="text-center mb-3">
                                <img src="{{ $main['url'] ?? '#' }}" alt="{{ $main['caption'] ?? 'Imagem Principal' }}" class="img-fluid rounded" style="max-height: 220px; object-fit: cover;">
                                @if(!empty($main['caption']))
                                    <p class="text-muted small mt-2">{{ $main['caption'] }}</p>
                                @endif
                            </div>
                        @endif

                        @if(count($rest) > 0)
                            <div class="row g-2">
                                @foreach($rest as $i => $img)
                                    @if($i < 6)
                                        <div class="col-6">
                                            <img src="{{ $img['url'] ?? '#' }}" alt="{{ $img['caption'] ?? 'Imagem' }}" class="img-fluid rounded" style="height: 80px; width: 100%; object-fit: cover;">
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                            @if(count($rest) > 6)
                                <p class="text-muted small text-center mt-2">+{{ count($rest) - 6 }} mais imagens</p>
                            @endif
                        @endif
                    @else
                        <div class="text-center py-3">
                            <i class="fas fa-images fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">Nenhuma imagem disponível</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Property Status Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-line me-2"></i>Status e Estatísticas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Status:</strong>
                        <div class="mt-1">{!! $property->status_badge !!}</div>
                    </div>
                    
                    @if($property->verified_at)
                        <div class="mb-3">
                            <strong>Verificado em:</strong>
                            <div class="text-muted">{{ optional($property->verified_at)->format('d/m/Y H:i') ?? '-' }}</div>
                        </div>
                    @endif
                    
                    <div class="mb-3">
                        <strong>Criado em:</strong>
                        <div class="text-muted">{{ optional($property->created_at)->format('d/m/Y H:i') ?? '-' }}</div>
                    </div>
                    
                    <div class="mb-0">
                        <strong>Última atualização:</strong>
                        <div class="text-muted">{{ optional($property->updated_at)->format('d/m/Y H:i') ?? '-' }}</div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Card -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-bolt me-2"></i>Ações Rápidas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-success btn-sm" onclick="activateProperty()">
                            <i class="fas fa-play me-1"></i> Ativar Propriedade
                        </button>
                        <button type="button" class="btn btn-outline-warning btn-sm" onclick="deactivateProperty()">
                            <i class="fas fa-pause me-1"></i> Desativar Propriedade
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="viewBookings()">
                            <i class="fas fa-calendar me-1"></i> Ver Reservas
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="viewAnalytics()">
                            <i class="fas fa-chart-bar me-1"></i> Ver Analytics
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- API Edit Section -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0"><i class="fas fa-tools me-2"></i>Editar (API)</h5>
                    <span class="badge bg-info">API-only</span>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <!-- General Update -->
                        <div class="col-lg-6">
                            <h6 class="text-primary">Geral</h6>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label">Nome</label>
                                    <input type="text" id="gen_name" class="form-control" value="{{ $property->name }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Tipo</label>
                                    <select id="gen_type" class="form-select">
                                        @php $types=['apartment'=>'Apartamento','house'=>'Casa','hotel'=>'Hotel','hostel'=>'Hostel','resort'=>'Resort','villa'=>'Vila','cabin'=>'Cabana','loft'=>'Loft']; @endphp
                                        @foreach($types as $k=>$v)
                                            <option value="{{ $k }}" @selected($property->property_type===$k)>{{ $v }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Moeda</label>
                                    <input type="text" id="gen_currency" class="form-control" value="{{ $general['baseCurrency'] ?? ($property->currency ?? 'BRL') }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Endereço</label>
                                    <input type="text" id="gen_street" class="form-control" value="{{ $property->address }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Cidade</label>
                                    <input type="text" id="gen_city" class="form-control" value="{{ $property->city }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Estado</label>
                                    <input type="text" id="gen_state" class="form-control" value="{{ $property->state }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">País</label>
                                    <input type="text" id="gen_country" class="form-control" value="{{ $property->country ?? 'BR' }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">CEP</label>
                                    <input type="text" id="gen_postal" class="form-control" value="{{ $property->postal_code }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Lat</label>
                                    <input type="number" step="0.000001" id="gen_lat" class="form-control" value="{{ $property->latitude ?? ($general['geoLocation']['latitude'] ?? '') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Lng</label>
                                    <input type="number" step="0.000001" id="gen_lng" class="form-control" value="{{ $property->longitude ?? ($general['geoLocation']['longitude'] ?? '') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Max Pessoas</label>
                                    <input type="number" id="gen_maxocc" class="form-control" value="{{ $property->max_occupancy }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Max Adultos</label>
                                    <input type="number" id="gen_maxadults" class="form-control" value="{{ $property->max_adults }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Check-in De</label>
                                    <input type="time" id="gen_ci_from" class="form-control" value="{{ $property->check_in_from }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Check-in Até</label>
                                    <input type="time" id="gen_ci_until" class="form-control" value="{{ $property->check_in_until }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Check-out De</label>
                                    <input type="time" id="gen_co_from" class="form-control" value="{{ $property->check_out_from }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Check-out Até</label>
                                    <input type="time" id="gen_co_until" class="form-control" value="{{ $property->check_out_until }}">
                                </div>
                                <div class="col-12 mt-2">
                                    <button class="btn btn-sm btn-primary" onclick="submitGeneralUpdate()"><i class="fas fa-save me-1"></i>Salvar Geral</button>
                                </div>
                            </div>
                        </div>

                        <!-- Descriptions Update -->
                        <div class="col-lg-6">
                            <h6 class="text-primary">Descrições</h6>
                            <div class="row g-2">
                                <div class="col-md-12">
                                    <label class="form-label">PT</label>
                                    <textarea id="desc_pt" class="form-control" rows="3">@php 
                                        $pt = collect($descriptions ?? [])->firstWhere('language','PT'); echo $pt['text'] ?? '';
                                    @endphp</textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">EN</label>
                                    <textarea id="desc_en" class="form-control" rows="3">@php 
                                        $en = collect($descriptions ?? [])->firstWhere('language','EN'); echo $en['text'] ?? '';
                                    @endphp</textarea>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">ES</label>
                                    <textarea id="desc_es" class="form-control" rows="3">@php 
                                        $es = collect($descriptions ?? [])->firstWhere('language','ES'); echo $es['text'] ?? '';
                                    @endphp</textarea>
                                </div>
                                <div class="col-12 mt-2">
                                    <button class="btn btn-sm btn-primary" onclick="submitDescriptionsUpdate()"><i class="fas fa-save me-1"></i>Salvar Descrições</button>
                                </div>
                            </div>
                        </div>

                        <!-- Images Update (by URL) -->
                        <div class="col-lg-6">
                            <h6 class="text-primary">Imagens (por URL)</h6>
                            <div class="row g-2" id="imagesInputs">
                                <div class="col-md-8">
                                    <input type="url" class="form-control" placeholder="https://..." value="">
                                </div>
                                <div class="col-md-2">
                                    <select class="form-select">
                                        <option value="exterior">exterior</option>
                                        <option value="interior">interior</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" class="form-control" placeholder="#" value="0" min="0">
                                </div>
                            </div>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-outline-secondary" onclick="addImageRow()"><i class="fas fa-plus me-1"></i>Adicionar Linha</button>
                                <button class="btn btn-sm btn-primary" onclick="submitImagesUpdate()"><i class="fas fa-save me-1"></i>Salvar Imagens</button>
                            </div>
                        </div>

                        <!-- Fees / Taxes / Nearest Places -->
                        <div class="col-lg-6">
                            <h6 class="text-primary">Taxas e Impostos</h6>
                            <div class="row g-2">
                                <div class="col-md-2"><input type="text" id="fee_code" class="form-control" placeholder="feeCode (Ex: RES)"></div>
                                <div class="col-md-2"><input type="text" id="fee_chtype" class="form-control" placeholder="chargeType (Ex: MAN)"></div>
                                <div class="col-md-2"><input type="text" id="fee_chmode" class="form-control" placeholder="chargeMode (Ex: STA)"></div>
                                <div class="col-md-2"><input type="text" id="fee_currency" class="form-control" value="{{ $general['baseCurrency'] ?? 'BRL' }}"></div>
                                <div class="col-md-2"><input type="number" id="fee_flat" class="form-control" placeholder="flat (centavos)"></div>
                                <div class="col-md-2">
                                    <select id="fee_taxable" class="form-select"><option value="">Taxável?</option><option value="1">Sim</option><option value="0">Não</option></select>
                                </div>
                                <div class="col-12 mt-2">
                                    <button class="btn btn-sm btn-primary" onclick="submitFeesUpdate()"><i class="fas fa-save me-1"></i>Salvar Taxas</button>
                                </div>
                            </div>
                            <hr>
                            <h6 class="text-primary">Locais Próximos</h6>
                            <div class="row g-2">
                                <div class="col-md-4"><input type="text" id="np_type" class="form-control" placeholder="typeCode (Ex: DAR)"></div>
                                <div class="col-md-4"><input type="number" id="np_meters" class="form-control" placeholder="metros"></div>
                                <div class="col-md-4"><input type="number" id="np_feet" class="form-control" placeholder="feet (opcional)"></div>
                                <div class="col-12 mt-2">
                                    <button class="btn btn-sm btn-primary" onclick="submitNearestUpdate()"><i class="fas fa-save me-1"></i>Salvar Locais</button>
                                </div>
                            </div>
                            <hr>
                            <h6 class="text-primary">Impostos</h6>
                            <div class="row g-2">
                                <div class="col-md-3"><input type="text" id="tax_code" class="form-control" placeholder="taxCode (Ex: VAT)"></div>
                                <div class="col-md-3"><input type="number" step="0.01" id="tax_perc" class="form-control" placeholder="%"></div>
                                <div class="col-md-3"><input type="number" id="tax_flat" class="form-control" placeholder="flat (centavos)"></div>
                                <div class="col-md-3">
                                    <select id="tax_included" class="form-select"><option value="">Incluído?</option><option value="1">Sim</option><option value="0">Não</option></select>
                                </div>
                                <div class="col-12 mt-2">
                                    <button class="btn btn-sm btn-primary" onclick="submitTaxesUpdate()"><i class="fas fa-save me-1"></i>Salvar Impostos</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Bookings Section -->
    @if(isset($recentBookings) && count($recentBookings) > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Reservas Recentes</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID da Reserva</th>
                                    <th>Hóspede</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Status</th>
                                    <th>Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentBookings as $booking)
                                    <tr>
                                        <td>{{ $booking['bookingNumber'] ?? 'N/A' }}</td>
                                        <td>{{ $booking['guestName'] ?? 'N/A' }}</td>
                                        <td>{{ \Carbon\Carbon::parse($booking['checkIn'] ?? now())->format('d/m/Y') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($booking['checkOut'] ?? now())->format('d/m/Y') }}</td>
                                        <td>
                                            <span class="badge {{ $booking['state'] === 'reservation' ? 'bg-success' : ($booking['state'] === 'pending' ? 'bg-warning' : 'bg-danger') }}">
                                                {{ ucfirst($booking['state'] ?? 'unknown') }}
                                            </span>
                                        </td>
                                        <td>R$ {{ number_format($booking['totalPrice'] ?? 0, 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
    function refreshData() { location.reload(); }

    const propertyIdApi = @json($property->property_id ?? null);
    if (!propertyIdApi) { console.error('Property ID da API ausente'); }

    @if(isset($isApiProperty) && $isApiProperty)
    function deleteProperty() { alert('Exclusão disponível apenas para propriedades salvas localmente.'); }
    @else
    const deleteUrl = '{{ route("properties.delete", $property->id) }}';
    function deleteProperty() { /* ... existing ... */ }
    @endif

    // Helpers
    function postJson(url, body) {
        return fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify(body) })
            .then(async r => {
                const t = await r.text();
                try { return JSON.parse(t); } catch { return { raw: t, ok: r.ok, status: r.status }; }
            });
    }

    // General
    function submitGeneralUpdate() {
        const body = {
            name: document.getElementById('gen_name').value,
            property_type: document.getElementById('gen_type').value,
            currency: document.getElementById('gen_currency').value,
            address: document.getElementById('gen_street').value,
            city: document.getElementById('gen_city').value,
            state: document.getElementById('gen_state').value,
            country: document.getElementById('gen_country').value,
            postal_code: document.getElementById('gen_postal').value,
            latitude: document.getElementById('gen_lat').value,
            longitude: document.getElementById('gen_lng').value,
            max_occupancy: document.getElementById('gen_maxocc').value,
            max_adults: document.getElementById('gen_maxadults').value,
            check_in_from: document.getElementById('gen_ci_from').value,
            check_in_until: document.getElementById('gen_ci_until').value,
            check_out_from: document.getElementById('gen_co_from').value,
            check_out_until: document.getElementById('gen_co_until').value,
        };
        postJson(`{{ url('/properties') }}/${propertyIdApi}/general`, body)
            .then(() => { alert('Geral atualizado'); refreshData(); })
            .catch(e => alert('Erro: ' + e));
    }

    // Descriptions
    function submitDescriptionsUpdate() {
        const body = { descriptions: {
            PT: { typeCode: 'house', text: document.getElementById('desc_pt').value },
            EN: { typeCode: 'house', text: document.getElementById('desc_en').value },
            ES: { typeCode: 'house', text: document.getElementById('desc_es').value },
        }};
        postJson(`{{ url('/properties') }}/${propertyIdApi}/descriptions`, body)
            .then(() => { alert('Descrições atualizadas'); refreshData(); })
            .catch(e => alert('Erro: ' + e));
    }

    // Images
    function addImageRow() {
        const wrap = document.getElementById('imagesInputs');
        const row = document.createElement('div');
        row.className = 'row g-2 mt-1';
        row.innerHTML = `<div class="col-md-8"><input type="url" class="form-control" placeholder="https://..."></div>
                         <div class="col-md-2"><select class="form-select"><option value="exterior">exterior</option><option value="interior">interior</option></select></div>
                         <div class="col-md-2"><input type="number" class="form-control" placeholder="#" value="0" min="0"></div>`;
        wrap.appendChild(row);
    }
    function submitImagesUpdate() {
        const wrap = document.getElementById('imagesInputs');
        const rows = wrap.querySelectorAll('.row');
        const images = [];
        rows.forEach((r, idx) => {
            const url = r.querySelector('input[type="url"]').value;
            const type = r.querySelector('select').value;
            const ord = r.querySelector('input[type="number"]').value || idx;
            if (url) images.push({ url, typeCode: type, displayPriority: parseInt(ord,10)||0, lastUpdated: (new Date()).toISOString().slice(0,10) });
        });
        if (images.length === 0) { alert('Adicione ao menos uma URL de imagem'); return; }
        postJson(`{{ url('/properties') }}/${propertyIdApi}/images`, { images })
            .then(() => { alert('Imagens atualizadas'); refreshData(); })
            .catch(e => alert('Erro: ' + e));
    }

    // Fees
    function submitFeesUpdate() {
        const fee = {
            feeCode: document.getElementById('fee_code').value,
            chargeType: document.getElementById('fee_chtype').value,
            chargeMode: document.getElementById('fee_chmode').value,
            currency: document.getElementById('fee_currency').value,
            amountFlat: document.getElementById('fee_flat').value ? parseInt(document.getElementById('fee_flat').value, 10) : null,
            isTaxable: document.getElementById('fee_taxable').value === '1'
        };
        postJson(`{{ url('/properties') }}/${propertyIdApi}/fees`, { fees: [fee] })
            .then(() => { alert('Taxas atualizadas'); refreshData(); })
            .catch(e => alert('Erro: ' + e));
    }

    // Taxes
    function submitTaxesUpdate() {
        const tax = {
            taxCode: document.getElementById('tax_code').value,
            amountPercentage: document.getElementById('tax_perc').value ? parseFloat(document.getElementById('tax_perc').value) : null,
            amountFlat: document.getElementById('tax_flat').value ? parseInt(document.getElementById('tax_flat').value, 10) : null,
            rentIncluded: document.getElementById('tax_included').value === '1'
        };
        postJson(`{{ url('/properties') }}/${propertyIdApi}/taxes`, { taxes: [tax] })
            .then(() => { alert('Impostos atualizados'); refreshData(); })
            .catch(e => alert('Erro: ' + e));
    }

    // Nearest Places
    function submitNearestUpdate() {
        const np = {
            typeCode: document.getElementById('np_type').value,
            distance: {
                meters: document.getElementById('np_meters').value ? parseInt(document.getElementById('np_meters').value, 10) : null,
                feet: document.getElementById('np_feet').value ? parseInt(document.getElementById('np_feet').value, 10) : null,
            }
        };
        postJson(`{{ url('/properties') }}/${propertyIdApi}/nearest-places`, { nearestPlaces: [np] })
            .then(() => { alert('Locais próximos atualizados'); refreshData(); })
            .catch(e => alert('Erro: ' + e));
    }
</script>
@endsection 