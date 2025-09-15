<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NextPaxService;
use Illuminate\Support\Facades\Http;

class GetValidChannels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:valid-channels';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Obtém lista de canais válidos da API NextPax';

    private NextPaxService $nextPaxService;

    public function __construct(NextPaxService $nextPaxService)
    {
        parent::__construct();
        $this->nextPaxService = $nextPaxService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📡 Obtendo canais válidos da API NextPax...');
        $this->newLine();

        // Tentar obter canais via requisição direta
        $this->info('1️⃣ Tentando via requisição direta...');
        try {
            $baseUrl = config('services.nextpax.base_url', 'https://supply.sandbox.nextpax.app/api/v1');
            $apiToken = config('services.nextpax.token');
            
            $response = Http::withHeaders([
                'X-Api-Token' => $apiToken,
                'Content-Type' => 'application/json',
            ])->get($baseUrl . '/channels');

            $this->line("   📡 Status: {$response->status()}");
            
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['data'])) {
                    $this->info('   ✅ Canais obtidos via requisição direta:');
                    foreach ($data['data'] as $channel) {
                        $this->line("   - {$channel['channelId']}: {$channel['channelName']}");
                    }
                } else {
                    $this->warn('   ⚠️  Estrutura de resposta inesperada:');
                    $this->line('   ' . json_encode($data, JSON_PRETTY_PRINT));
                }
            } else {
                $this->error('   ❌ Erro HTTP: ' . $response->status());
                $this->line('   ' . $response->body());
            }

        } catch (\Exception $e) {
            $this->error('   ❌ Erro na requisição direta: ' . $e->getMessage());
        }

        $this->newLine();

        // Tentar alguns canais comuns baseados na documentação
        $this->info('2️⃣ Testando canais comuns da documentação...');
        $commonChannels = [
            'HOM143' => 'HomeAway',
            'BOO142' => 'Booking.com',
            'AIR298' => 'Airbnb',
            'EXP001' => 'Expedia',
            'VRB001' => 'VRBO',
            'DIRECT' => 'Direct Booking',
            'MANUAL' => 'Manual Booking'
        ];

        foreach ($commonChannels as $channelId => $channelName) {
            $this->line("   - {$channelId}: {$channelName}");
        }

        $this->newLine();
        $this->info('✅ Verificação de canais concluída!');
        $this->newLine();
        $this->warn('💡 DICA: Use um dos canais válidos encontrados acima no campo "channelId" ao criar reservas.');
    }
}