<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Property;
use App\Models\Channel;
use App\Models\PropertyChannel;
use Illuminate\Support\Facades\Http;

class CompleteOAuthAuth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'oauth:complete-auth 
                            {--property= : ID da propriedade}
                            {--channel= : ID do canal}
                            {--code= : Código de autorização OAuth}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Completa a autenticação OAuth trocando código por token de acesso';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $propertyId = $this->option('property');
        $channelId = $this->option('channel');
        $authCode = $this->option('code');

        $this->info('🔐 Completando autenticação OAuth...');
        $this->newLine();

        if (!$propertyId || !$channelId || !$authCode) {
            $this->error('Parâmetros obrigatórios: --property, --channel e --code');
            $this->showHelp();
            return;
        }

        $property = Property::find($propertyId);
        if (!$property) {
            $this->error("Propriedade {$propertyId} não encontrada.");
            return;
        }

        $channel = Channel::where('channel_id', $channelId)->first();
        if (!$channel) {
            $this->error("Canal {$channelId} não encontrado.");
            return;
        }

        $propertyChannel = $property->getChannelConnection($channelId);
        if (!$propertyChannel) {
            $this->error("Propriedade não está conectada ao canal {$channelId}.");
            return;
        }

        $channelConfig = $propertyChannel->getChannelConfig();
        if (!isset($channelConfig['oauth'])) {
            $this->error("Configuração OAuth não encontrada para este canal.");
            return;
        }

        $this->line("🏠 Propriedade: {$property->name}");
        $this->line("📡 Canal: {$channel->name}");
        $this->line("🔗 ID no Canal: {$propertyChannel->channel_property_id}");
        $this->line("🔑 Código de Autorização: {$authCode}");
        $this->newLine();

        try {
            $tokenData = $this->exchangeCodeForToken($channel, $channelConfig, $authCode);
            
            // Salvar token de acesso
            $channelConfig['oauth']['access_token'] = $tokenData['access_token'];
            $channelConfig['oauth']['refresh_token'] = $tokenData['refresh_token'] ?? null;
            $channelConfig['oauth']['expires_at'] = now()->addSeconds($tokenData['expires_in'] ?? 3600);
            $channelConfig['oauth']['token_type'] = $tokenData['token_type'] ?? 'Bearer';

            $propertyChannel->update([
                'channel_config' => $channelConfig,
                'channel_status' => 'active',
                'content_status' => 'enabled',
                'is_active' => true,
            ]);

            $this->info('✅ Autenticação OAuth concluída com sucesso!');
            $this->line("   Token de Acesso: " . substr($tokenData['access_token'], 0, 20) . "...");
            $this->line("   Tipo: " . ($tokenData['token_type'] ?? 'Bearer'));
            $this->line("   Expira em: " . ($tokenData['expires_in'] ?? 3600) . " segundos");
            $this->newLine();

            $this->line('🎉 Canal ativado e pronto para uso!');
            $this->line('Você pode agora sincronizar dados e criar reservas através deste canal.');

        } catch (\Exception $e) {
            $this->error('❌ Erro na autenticação OAuth: ' . $e->getMessage());
            
            $propertyChannel->update([
                'channel_status' => 'error',
                'last_sync_error' => 'Erro OAuth: ' . $e->getMessage(),
            ]);
        }
    }

    private function exchangeCodeForToken(Channel $channel, array $config, string $authCode): array
    {
        $this->info("🔄 Trocando código por token de acesso...");

        $tokenUrl = $config['oauth']['token_url'];
        $clientId = $config['oauth']['client_id'];
        $clientSecret = $config['oauth']['client_secret'];
        $redirectUri = $config['oauth']['redirect_uri'];

        $response = Http::asForm()->post($tokenUrl, [
            'grant_type' => 'authorization_code',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'code' => $authCode,
        ]);

        if (!$response->successful()) {
            throw new \Exception("Erro na requisição de token: " . $response->status() . " - " . $response->body());
        }

        $tokenData = $response->json();

        if (isset($tokenData['error'])) {
            throw new \Exception("Erro OAuth: " . $tokenData['error_description'] ?? $tokenData['error']);
        }

        if (!isset($tokenData['access_token'])) {
            throw new \Exception("Token de acesso não encontrado na resposta");
        }

        $this->line("   ✅ Token obtido com sucesso!");
        
        return $tokenData;
    }

    private function showHelp(): void
    {
        $this->line('Para completar a autenticação OAuth:');
        $this->line('');
        $this->line('1. Execute o comando de configuração OAuth:');
        $this->line('   php artisan oauth:setup-channels --property=4 --channel=AIR298');
        $this->line('');
        $this->line('2. Acesse a URL de autorização fornecida');
        $this->line('');
        $this->line('3. Autorize o acesso e copie o código');
        $this->line('');
        $this->line('4. Execute este comando com o código:');
        $this->line('   php artisan oauth:complete-auth --property=4 --channel=AIR298 --code=CODIGO_AQUI');
    }
}