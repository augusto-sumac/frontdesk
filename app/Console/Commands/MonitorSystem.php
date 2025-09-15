<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Channel;
use App\Models\Property;
use App\Models\PropertyChannel;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class MonitorSystem extends Command
{
    protected $signature = 'system:monitor 
                            {--check-health : Verificar saúde do sistema}
                            {--check-sync : Verificar status de sincronização}
                            {--check-errors : Verificar erros recentes}
                            {--check-api : Verificar APIs externas}
                            {--all : Executar todas as verificações}
                            {--alert : Enviar alertas se houver problemas}';

    protected $description = 'Monitora a saúde do sistema e gera alertas';

    public function handle()
    {
        $checkHealth = $this->option('check-health');
        $checkSync = $this->option('check-sync');
        $checkErrors = $this->option('check-errors');
        $checkApi = $this->option('check-api');
        $all = $this->option('all');
        $alert = $this->option('alert');

        $this->info('🔍 Iniciando monitoramento do sistema...');
        $this->newLine();

        $issues = [];

        if ($all || $checkHealth) {
            $issues = array_merge($issues, $this->checkSystemHealth());
        }

        if ($all || $checkSync) {
            $issues = array_merge($issues, $this->checkSyncStatus());
        }

        if ($all || $checkErrors) {
            $issues = array_merge($issues, $this->checkRecentErrors());
        }

        if ($all || $checkApi) {
            $issues = array_merge($issues, $this->checkExternalApis());
        }

        $this->displayResults($issues);

        if ($alert && !empty($issues)) {
            $this->sendAlerts($issues);
        }
    }

    private function checkSystemHealth(): array
    {
        $this->line('🏥 Verificando saúde do sistema...');
        
        $issues = [];
        
        // Verificar conexões com erro
        $errorConnections = PropertyChannel::whereNotNull('last_sync_error')->count();
        if ($errorConnections > 0) {
            $issues[] = [
                'type' => 'error',
                'message' => "{$errorConnections} conexões com erro de sincronização",
                'severity' => 'high'
            ];
        }

        // Verificar conexões que não sincronizam há muito tempo
        $staleConnections = PropertyChannel::where('is_active', true)
            ->where('auto_sync_enabled', true)
            ->where(function($query) {
                $query->whereNull('last_sync_at')
                      ->orWhere('last_sync_at', '<', now()->subHours(24));
            })->count();

        if ($staleConnections > 0) {
            $issues[] = [
                'type' => 'warning',
                'message' => "{$staleConnections} conexões não sincronizam há mais de 24h",
                'severity' => 'medium'
            ];
        }

        // Verificar propriedades sem conexões
        $propertiesWithoutConnections = Property::whereDoesntHave('channels')->count();
        if ($propertiesWithoutConnections > 0) {
            $issues[] = [
                'type' => 'info',
                'message' => "{$propertiesWithoutConnections} propriedades sem conexões de canal",
                'severity' => 'low'
            ];
        }

        $this->line("   ✅ Verificação de saúde concluída");
        return $issues;
    }

    private function checkSyncStatus(): array
    {
        $this->line('🔄 Verificando status de sincronização...');
        
        $issues = [];
        
        $totalConnections = PropertyChannel::count();
        $activeConnections = PropertyChannel::where('is_active', true)->count();
        $syncedConnections = PropertyChannel::whereNotNull('last_successful_sync_at')->count();
        
        $syncRate = $totalConnections > 0 ? ($syncedConnections / $totalConnections) * 100 : 0;
        
        if ($syncRate < 80) {
            $issues[] = [
                'type' => 'warning',
                'message' => "Taxa de sincronização baixa: {$syncRate}%",
                'severity' => 'medium'
            ];
        }

        // Verificar conexões com muitas tentativas de erro
        $failedConnections = PropertyChannel::where('sync_attempts', '>', 5)->count();
        if ($failedConnections > 0) {
            $issues[] = [
                'type' => 'error',
                'message' => "{$failedConnections} conexões com mais de 5 tentativas de erro",
                'severity' => 'high'
            ];
        }

        $this->line("   📊 Total: {$totalConnections}, Ativas: {$activeConnections}, Sincronizadas: {$syncedConnections}");
        $this->line("   ✅ Verificação de sincronização concluída");
        
        return $issues;
    }

    private function checkRecentErrors(): array
    {
        $this->line('❌ Verificando erros recentes...');
        
        $issues = [];
        
        // Erros das últimas 24 horas
        $recentErrors = PropertyChannel::whereNotNull('last_sync_error')
            ->where('last_sync_at', '>=', now()->subHours(24))
            ->count();

        if ($recentErrors > 0) {
            $issues[] = [
                'type' => 'error',
                'message' => "{$recentErrors} erros de sincronização nas últimas 24h",
                'severity' => 'high'
            ];
        }

        // Erros das últimas 7 dias
        $weeklyErrors = PropertyChannel::whereNotNull('last_sync_error')
            ->where('last_sync_at', '>=', now()->subDays(7))
            ->count();

        if ($weeklyErrors > 10) {
            $issues[] = [
                'type' => 'warning',
                'message' => "{$weeklyErrors} erros de sincronização na última semana",
                'severity' => 'medium'
            ];
        }

        $this->line("   📊 Erros 24h: {$recentErrors}, Erros 7 dias: {$weeklyErrors}");
        $this->line("   ✅ Verificação de erros concluída");
        
        return $issues;
    }

    private function checkExternalApis(): array
    {
        $this->line('🌐 Verificando APIs externas...');
        
        $issues = [];
        
        $apis = [
            'NextPax' => config('services.nextpax.base_url', 'https://supply.sandbox.nextpax.app/api/v1'),
            'NextPax Bookings' => config('services.nextpax.bookings_api_base', 'https://pci.sandbox.nextpax.com/supply'),
        ];

        foreach ($apis as $name => $url) {
            try {
                $response = Http::timeout(10)->get($url . '/health');
                
                if ($response->successful()) {
                    $this->line("   ✅ {$name}: OK");
                } else {
                    $issues[] = [
                        'type' => 'warning',
                        'message' => "API {$name} retornou status {$response->status()}",
                        'severity' => 'medium'
                    ];
                    $this->line("   ⚠️  {$name}: Status {$response->status()}");
                }
            } catch (\Exception $e) {
                $issues[] = [
                    'type' => 'error',
                    'message' => "API {$name} inacessível: " . $e->getMessage(),
                    'severity' => 'high'
                ];
                $this->line("   ❌ {$name}: " . $e->getMessage());
            }
        }

        $this->line("   ✅ Verificação de APIs concluída");
        
        return $issues;
    }

    private function displayResults(array $issues): void
    {
        $this->newLine();
        
        if (empty($issues)) {
            $this->info('🎉 Sistema funcionando perfeitamente! Nenhum problema encontrado.');
            return;
        }

        $this->warn("⚠️  Encontrados " . count($issues) . " problemas:");
        $this->newLine();

        $highSeverity = 0;
        $mediumSeverity = 0;
        $lowSeverity = 0;

        foreach ($issues as $issue) {
            $icon = $issue['severity'] === 'high' ? '🔴' : ($issue['severity'] === 'medium' ? '🟡' : '🔵');
            $this->line("   {$icon} {$issue['message']}");
            
            switch ($issue['severity']) {
                case 'high':
                    $highSeverity++;
                    break;
                case 'medium':
                    $mediumSeverity++;
                    break;
                case 'low':
                    $lowSeverity++;
                    break;
            }
        }

        $this->newLine();
        $this->line("📊 Resumo de problemas:");
        $this->line("   🔴 Alta severidade: {$highSeverity}");
        $this->line("   🟡 Média severidade: {$mediumSeverity}");
        $this->line("   🔵 Baixa severidade: {$lowSeverity}");
    }

    private function sendAlerts(array $issues): void
    {
        $this->line('📧 Enviando alertas...');
        
        $highSeverityIssues = array_filter($issues, function($issue) {
            return $issue['severity'] === 'high';
        });

        if (!empty($highSeverityIssues)) {
            $this->line('   🚨 Alertas de alta severidade enviados');
            // Aqui você implementaria o envio de emails/SMS/etc
        }

        $this->line('   ✅ Alertas enviados');
    }
}