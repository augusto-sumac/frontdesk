@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-title-sm font-semibold text-gray-900 dark:text-white">Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Visão geral das suas propriedades e reservas</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <button onclick="location.reload()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-theme-sm text-white bg-brand-500 hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 dark:focus:ring-offset-gray-800">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Atualizar
            </button>
        </div>
    </div>

    <!-- Alertas -->
    @if(isset($error))
        <div class="rounded-lg bg-warning-50 border border-warning-200 p-4 dark:bg-warning-900/20 dark:border-warning-800">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-warning-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-warning-800 dark:text-warning-200">{{ $error }}</p>
                </div>
            </div>
        </div>
    @endif

    @if(session('success'))
        <div class="rounded-lg bg-success-50 border border-success-200 p-4 dark:bg-success-900/20 dark:border-success-800">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-success-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-success-800 dark:text-success-200">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Métricas Financeiras -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <!-- Total de Reservas -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-theme-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-brand-100 dark:bg-brand-900 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-brand-600 dark:text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Total de Reservas</dt>
                            <dd class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $financialReport['total_bookings'] ?? 0 }}</dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center text-sm text-success-600 dark:text-success-400">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        {{ $financialReport['confirmed_bookings'] ?? 0 }} confirmadas
                    </div>
                    @if(($financialReport['pending_sync_bookings'] ?? 0) > 0)
                        <div class="flex items-center text-sm text-warning-600 dark:text-warning-400 mt-1">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            {{ $financialReport['pending_sync_bookings'] ?? 0 }} aguardando sincronização
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Receita Total -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-theme-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-success-100 dark:bg-success-900 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-success-600 dark:text-success-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Receita Total</dt>
                            <dd class="text-2xl font-semibold text-gray-900 dark:text-white">R$ {{ number_format($financialReport['total_revenue'] ?? 0, 2, ',', '.') }}</dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center text-sm text-success-600 dark:text-success-400">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                        </svg>
                        Taxa média: R$ {{ number_format($financialReport['average_daily_rate'] ?? 0, 2, ',', '.') }}
                    </div>
                    @if(($financialReport['pending_sync_revenue'] ?? 0) > 0)
                        <div class="flex items-center text-sm text-warning-600 dark:text-warning-400 mt-1">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            R$ {{ number_format($financialReport['pending_sync_revenue'] ?? 0, 2, ',', '.') }} aguardando sincronização
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Reservas Pendentes -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-theme-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-warning-100 dark:bg-warning-900 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-warning-600 dark:text-warning-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Reservas Pendentes</dt>
                            <dd class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $financialReport['pending_bookings'] ?? 0 }}</dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center text-sm text-warning-600 dark:text-warning-400">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Aguardando confirmação
                    </div>
                    @if(($financialReport['failed_sync_bookings'] ?? 0) > 0)
                        <div class="flex items-center text-sm text-error-600 dark:text-error-400 mt-1">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                            {{ $financialReport['failed_sync_bookings'] ?? 0 }} falharam na sincronização
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Taxa de Ocupação -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-theme-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">Taxa de Ocupação</dt>
                            <dd class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $financialReport['occupancy_rate'] ?? 0 }}%</dd>
                        </dl>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center text-sm text-blue-600 dark:text-blue-400">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                        </svg>
                        Baseado em reservas confirmadas
                    </div>
                    @if(($financialReport['synced_revenue'] ?? 0) > 0)
                        <div class="flex items-center text-sm text-success-600 dark:text-success-400 mt-1">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            R$ {{ number_format($financialReport['synced_revenue'] ?? 0, 2, ',', '.') }} sincronizadas
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Reservas Recentes -->
        <div class="lg:col-span-2">
            <div class="bg-white dark:bg-gray-800 shadow-theme-sm rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Reservas Recentes</h3>
                        <a href="{{ route('bookings.index') }}" class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-lg text-brand-600 bg-brand-50 hover:bg-brand-100 dark:bg-brand-900 dark:text-brand-400 dark:hover:bg-brand-800">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            Ver Todas
                        </a>
                    </div>
                </div>
                <div class="p-6">
                    @if(isset($bookings) && count($bookings) > 0)
                        <div class="overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Hóspede</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Propriedade</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Check-in</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Valor</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($bookings as $booking)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $booking['guest_name'] }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">#{{ $booking['booking_number'] }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $booking['property_name'] }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $booking['currency'] }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $booking['check_in'] }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @switch($booking['status'])
                                                @case('reservation')
                                                @case('confirmed')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success-100 text-success-800 dark:bg-success-900 dark:text-success-200">Confirmada</span>
                                                    @break
                                                @case('pending')
                                                @case('request')
                                                @case('request-accepted')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-warning-100 text-warning-800 dark:bg-warning-900 dark:text-warning-200">Pendente</span>
                                                    @break
                                                @case('cancelled')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-error-100 text-error-800 dark:bg-error-900 dark:text-error-200">Cancelada</span>
                                                    @break
                                                @default
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">{{ ucfirst($booking['status']) }}</span>
                                            @endswitch
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">R$ {{ number_format($booking['amount'], 2, ',', '.') }}</div>
                                            @if($booking['last_modified'])
                                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::parse($booking['last_modified'])->diffForHumans() }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <a href="{{ route('bookings.show', $booking['id']) }}" class="text-brand-600 hover:text-brand-900 dark:text-brand-400 dark:hover:text-brand-300" title="Ver detalhes">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                </a>
                                                @if($booking['sync_status'] === 'failed')
                                                    <button onclick="retrySync({{ $booking['id'] }})" class="text-warning-600 hover:text-warning-900 dark:text-warning-400 dark:hover:text-warning-300" title="Tentar sincronizar novamente">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                        </svg>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Nenhuma reserva encontrada</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">As reservas aparecerão aqui quando forem criadas via interface ou API.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Mensagens Recentes -->
        <div class="lg:col-span-1">
            <div class="bg-white dark:bg-gray-800 shadow-theme-sm rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Sistema de Mensagens</h3>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Em Desenvolvimento</span>
                    </div>
                </div>
                <div class="p-6">
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Sistema de Mensagens</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">O sistema de mensagens com hóspedes está sendo desenvolvido e estará disponível em breve.</p>
                        <div class="mt-4">
                            <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200">Funcionalidades planejadas:</h4>
                                        <ul class="mt-2 text-sm text-blue-700 dark:text-blue-300 list-disc list-inside space-y-1">
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
    </div>
</div>

<script>
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
    toast.className = `fixed top-4 right-4 z-50 max-w-sm w-full bg-white dark:bg-gray-800 shadow-theme-lg rounded-lg border border-gray-200 dark:border-gray-700 p-4`;
    toast.setAttribute('role', 'alert');
    
    const bgColor = type === 'error' ? 'bg-error-50 border-error-200 dark:bg-error-900/20 dark:border-error-800' : 
                   type === 'success' ? 'bg-success-50 border-success-200 dark:bg-success-900/20 dark:border-success-800' :
                   'bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-800';
    
    toast.className += ` ${bgColor}`;
    
    toast.innerHTML = `
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 ${type === 'error' ? 'text-error-400' : type === 'success' ? 'text-success-400' : 'text-blue-400'}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    ${type === 'error' ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>' :
                      type === 'success' ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>' :
                      '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>'}
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium ${type === 'error' ? 'text-error-800 dark:text-error-200' : type === 'success' ? 'text-success-800 dark:text-success-200' : 'text-blue-800 dark:text-blue-200'}">${title}</h3>
                <div class="mt-1 text-sm ${type === 'error' ? 'text-error-700 dark:text-error-300' : type === 'success' ? 'text-success-700 dark:text-success-300' : 'text-blue-700 dark:text-blue-300'}">${message}</div>
            </div>
            <div class="ml-auto pl-3">
                <button onclick="this.parentElement.parentElement.parentElement.remove()" class="inline-flex ${type === 'error' ? 'text-error-400 hover:text-error-600' : type === 'success' ? 'text-success-400 hover:text-success-600' : 'text-blue-400 hover:text-blue-600'}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    // Remove toast after 5 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.remove();
        }
    }, 5000);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'fixed top-4 right-4 z-50 space-y-2';
    document.body.appendChild(container);
    return container;
}

// Inicializar quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    console.log('Dashboard carregado com sucesso');
});
</script>
@endsection