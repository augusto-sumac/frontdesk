<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Channel;
use App\Models\Property;
use App\Models\PropertyChannel;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    /**
     * Dashboard principal do admin
     */
    public function index()
    {
        $stats = $this->getDashboardStats();
        $recentBookings = $this->getRecentBookings();
        $channelStats = $this->getChannelStats();
        $propertyStats = $this->getPropertyStats();
        $syncStatus = $this->getSyncStatus();

        return view('admin.dashboard', compact(
            'stats',
            'recentBookings',
            'channelStats',
            'propertyStats',
            'syncStatus'
        ));
    }

    /**
     * Estatísticas dos canais
     */
    public function channels()
    {
        $channels = Channel::withCount(['properties', 'bookings'])
            ->with(['propertyChannels' => function($query) {
                $query->where('is_active', true);
            }])
            ->get();

        $channelStats = $this->getChannelStats();

        return view('admin.channels', compact('channels', 'channelStats'));
    }

    /**
     * Relatórios de sincronização
     */
    public function reports()
    {
        $syncReports = $this->getSyncReports();
        $bookingReports = $this->getBookingReports();
        $errorReports = $this->getErrorReports();

        return view('admin.reports', compact(
            'syncReports',
            'bookingReports',
            'errorReports'
        ));
    }

    /**
     * Monitoramento em tempo real
     */
    public function monitoring()
    {
        $activeConnections = $this->getActiveConnections();
        $recentErrors = $this->getRecentErrors();
        $systemHealth = $this->getSystemHealth();

        return view('admin.monitoring', compact(
            'activeConnections',
            'recentErrors',
            'systemHealth'
        ));
    }

    /**
     * Obter estatísticas do dashboard
     */
    private function getDashboardStats(): array
    {
        return [
            'total_channels' => Channel::count(),
            'active_channels' => Channel::active()->count(),
            'total_properties' => Property::count(),
            'active_properties' => Property::where('status', 'active')->count(),
            'total_bookings' => Booking::count(),
            'pending_bookings' => Booking::where('status', 'pending')->count(),
            'confirmed_bookings' => Booking::where('status', 'confirmed')->count(),
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'total_connections' => PropertyChannel::count(),
            'active_connections' => PropertyChannel::where('is_active', true)->count(),
        ];
    }

    /**
     * Obter reservas recentes
     */
    private function getRecentBookings(): array
    {
        return Booking::with(['property'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($booking) {
                return [
                    'id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'property_name' => $booking->property->name ?? 'N/A',
                    'channel_id' => $booking->channel_id,
                    'guest_name' => $booking->guest_full_name,
                    'check_in' => $booking->check_in_date,
                    'check_out' => $booking->check_out_date,
                    'total_amount' => $booking->total_amount,
                    'currency' => $booking->currency,
                    'status' => $booking->status,
                    'created_at' => $booking->created_at,
                ];
            })
            ->toArray();
    }

    /**
     * Obter estatísticas dos canais
     */
    private function getChannelStats(): array
    {
        $channels = Channel::all();
        $stats = [];

        foreach ($channels as $channel) {
            $connections = PropertyChannel::where('channel_id', $channel->id)->count();
            $activeConnections = PropertyChannel::where('channel_id', $channel->id)
                ->where('is_active', true)->count();
            $bookings = Booking::where('channel_id', $channel->channel_id)->count();
            $recentBookings = Booking::where('channel_id', $channel->channel_id)
                ->where('created_at', '>=', now()->subDays(7))->count();

            $stats[] = [
                'channel_id' => $channel->channel_id,
                'name' => $channel->name,
                'is_active' => $channel->is_active,
                'requires_oauth' => $channel->requires_oauth,
                'connections' => $connections,
                'active_connections' => $activeConnections,
                'total_bookings' => $bookings,
                'recent_bookings' => $recentBookings,
                'sync_enabled' => $channel->auto_sync_enabled,
            ];
        }

        return $stats;
    }

    /**
     * Obter estatísticas das propriedades
     */
    private function getPropertyStats(): array
    {
        return Property::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function($item) {
                return [$item->status => $item->count];
            })
            ->toArray();
    }

    /**
     * Obter status de sincronização
     */
    private function getSyncStatus(): array
    {
        $totalConnections = PropertyChannel::count();
        $syncedConnections = PropertyChannel::whereNotNull('last_successful_sync_at')->count();
        $errorConnections = PropertyChannel::whereNotNull('last_sync_error')->count();
        $pendingSync = PropertyChannel::where('auto_sync_enabled', true)
            ->where(function($query) {
                $query->whereNull('last_sync_at')
                      ->orWhere('last_sync_at', '<', now()->subMinutes(60));
            })->count();

        return [
            'total_connections' => $totalConnections,
            'synced_connections' => $syncedConnections,
            'error_connections' => $errorConnections,
            'pending_sync' => $pendingSync,
            'sync_percentage' => $totalConnections > 0 ? round(($syncedConnections / $totalConnections) * 100, 2) : 0,
        ];
    }

    /**
     * Obter relatórios de sincronização
     */
    private function getSyncReports(): array
    {
        return PropertyChannel::with(['property', 'channel'])
            ->whereNotNull('last_sync_at')
            ->orderBy('last_sync_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function($connection) {
                return [
                    'id' => $connection->id,
                    'property_name' => $connection->property->name,
                    'channel_name' => $connection->channel->name,
                    'channel_property_id' => $connection->channel_property_id,
                    'last_sync_at' => $connection->last_sync_at,
                    'last_successful_sync_at' => $connection->last_successful_sync_at,
                    'last_sync_error' => $connection->last_sync_error,
                    'sync_attempts' => $connection->sync_attempts,
                    'is_active' => $connection->is_active,
                ];
            })
            ->toArray();
    }

    /**
     * Obter relatórios de reservas
     */
    private function getBookingReports(): array
    {
        return Booking::select(
                'channel_id',
                'status',
                DB::raw('count(*) as count'),
                DB::raw('sum(total_amount) as total_amount')
            )
            ->groupBy('channel_id', 'status')
            ->get()
            ->groupBy('channel_id')
            ->map(function($channelBookings) {
                return [
                    'total_bookings' => $channelBookings->sum('count'),
                    'total_amount' => $channelBookings->sum('total_amount'),
                    'by_status' => $channelBookings->mapWithKeys(function($item) {
                        return [$item->status => [
                            'count' => $item->count,
                            'amount' => $item->total_amount
                        ]];
                    })
                ];
            })
            ->toArray();
    }

    /**
     * Obter relatórios de erros
     */
    private function getErrorReports(): array
    {
        return PropertyChannel::whereNotNull('last_sync_error')
            ->with(['property', 'channel'])
            ->orderBy('last_sync_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function($connection) {
                return [
                    'id' => $connection->id,
                    'property_name' => $connection->property->name,
                    'channel_name' => $connection->channel->name,
                    'error' => $connection->last_sync_error,
                    'sync_attempts' => $connection->sync_attempts,
                    'last_sync_at' => $connection->last_sync_at,
                ];
            })
            ->toArray();
    }

    /**
     * Obter conexões ativas
     */
    private function getActiveConnections(): array
    {
        return PropertyChannel::where('is_active', true)
            ->with(['property', 'channel'])
            ->get()
            ->map(function($connection) {
                return [
                    'id' => $connection->id,
                    'property_name' => $connection->property->name,
                    'channel_name' => $connection->channel->name,
                    'channel_property_id' => $connection->channel_property_id,
                    'channel_status' => $connection->channel_status,
                    'content_status' => $connection->content_status,
                    'last_sync_at' => $connection->last_sync_at,
                    'auto_sync_enabled' => $connection->auto_sync_enabled,
                ];
            })
            ->toArray();
    }

    /**
     * Obter erros recentes
     */
    private function getRecentErrors(): array
    {
        return PropertyChannel::whereNotNull('last_sync_error')
            ->with(['property', 'channel'])
            ->orderBy('last_sync_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($connection) {
                return [
                    'id' => $connection->id,
                    'property_name' => $connection->property->name,
                    'channel_name' => $connection->channel->name,
                    'error' => $connection->last_sync_error,
                    'sync_attempts' => $connection->sync_attempts,
                    'last_sync_at' => $connection->last_sync_at,
                ];
            })
            ->toArray();
    }

    /**
     * Obter saúde do sistema
     */
    private function getSystemHealth(): array
    {
        $totalConnections = PropertyChannel::count();
        $activeConnections = PropertyChannel::where('is_active', true)->count();
        $errorConnections = PropertyChannel::whereNotNull('last_sync_error')->count();
        $recentErrors = PropertyChannel::whereNotNull('last_sync_error')
            ->where('last_sync_at', '>=', now()->subHours(24))->count();

        $healthScore = 100;
        if ($totalConnections > 0) {
            $errorRate = ($errorConnections / $totalConnections) * 100;
            $healthScore = max(0, 100 - $errorRate);
        }

        return [
            'total_connections' => $totalConnections,
            'active_connections' => $activeConnections,
            'error_connections' => $errorConnections,
            'recent_errors' => $recentErrors,
            'health_score' => round($healthScore, 2),
            'status' => $healthScore >= 90 ? 'excellent' : ($healthScore >= 70 ? 'good' : ($healthScore >= 50 ? 'warning' : 'critical')),
        ];
    }
}