@extends('layouts.app')

@section('title', 'FrontDesk - Detalhes da Reserva')

@section('content')
<div class="pt-3 pb-2 mb-3 border-bottom d-flex justify-content-between align-items-center">
  <h1 class="h2">Detalhes da Reserva</h1>
  <a href="{{ route('bookings.index') }}" class="btn btn-outline-secondary btn-sm">Voltar</a>
</div>

<div class="row">
  <div class="col-md-6">
    <div class="card mb-3">
      <div class="card-header">Reserva</div>
      <div class="card-body">
        <p><strong>ID:</strong> {{ $booking['bookingId'] ?? 'N/A' }}</p>
        <p><strong>Número:</strong> {{ $booking['bookingNumber'] ?? 'N/A' }}</p>
        <p><strong>Status:</strong> {{ $booking['status'] ?? $booking['state'] ?? 'N/A' }}</p>
        <p><strong>Check-in:</strong> {{ $booking['period']['arrivalDate'] ?? 'N/A' }}</p>
        <p><strong>Check-out:</strong> {{ $booking['period']['departureDate'] ?? 'N/A' }}</p>
        <p><strong>Observações:</strong> {{ $booking['remarks'] ?? '-' }}</p>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card mb-3">
      <div class="card-header">Pagamento</div>
      <div class="card-body">
        <p><strong>Tipo:</strong> {{ $paymentDetails['payment']['type'] ?? 'N/A' }}</p>
        <p><strong>Saldo Máximo:</strong> {{ $paymentDetails['payment']['details']['amountBalance'] ?? '-' }}</p>
        <p><strong>Cartão Virtual:</strong> {{ isset($paymentDetails['payment']['details']['isVirtual']) ? ($paymentDetails['payment']['details']['isVirtual'] ? 'Sim' : 'Não') : '-' }}</p>
      </div>
    </div>
  </div>
</div>
@endsection 