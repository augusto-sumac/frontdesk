<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Channel;
use App\Models\Property;
use App\Models\PropertyChannel;
use Illuminate\Support\Facades\Http;

class SetupOAuthChannels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'oauth:setup-channels 
                            {--property= : ID da propriedade para configurar}
                            {--channel= : ID do canal específico}
                            {--client-id= : Client ID do OAuth}
                            {--client-secret= : Client Secret do OAuth}
                            {--redirect-uri= : URI de redirecionamento}
                            {--list-all : Listar todos os canais OAuth}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configura autenticação OAuth para canais que requerem (Airbnb, HomeAway, VRBO)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('list-all')) {
            $this->listOAuthChannels();
            return;
        }

        $propertyId = $this->option('property');
        $channelId = $this->option('channel');
        $clientId = $this->option('client-id');
        $clientSecret = $this->option('client-secret');
        $redirectUri = $this->option('redirect-uri');

        $this->info('🔐 Configurando autenticação OAuth para canais...');
        $this->newLine();

        if (!$propertyId || !$channelId) {
            $this->error('Parâmetros obrigatórios: --property e --channel');
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

        if (!$channel->requires_oauth) {
            $this->error("Canal {$channelId} não requer autenticação OAuth.");
            return;
        }

        $propertyChannel = $property->getChannelConnection($channelId);
        if (!$propertyChannel) {
            $this->error("Propriedade não está conectada ao canal {$channelId}.");
            return;
        }

        $this->setupOAuthForChannel($property, $channel, $propertyChannel, $clientId, $clientSecret, $redirectUri);
    }

    private function listOAuthChannels(): void
    {
        $this->info('🔐 Canais que Requerem OAuth:');
        $this->newLine();

        $oauthChannels = Channel::requiresOauth()->active()->get();

        if ($oauthChannels->isEmpty()) {
            $this->warn('Nenhum canal OAuth encontrado.');
            return;
        }

        $headers = ['ID', 'Nome', 'OAuth URL', 'Escopos', 'Propriedades'];
        $rows = [];

        foreach ($oauthChannels as $channel) {
            $rows[] = [
                $channel->channel_id,
                $channel->name,
                $channel->oauth_url ? 'Configurado' : 'Não configurado',
                implode(', ', array_slice($channel->oauth_scopes ?? [], 0, 3)) . 
                (count($channel->oauth_scopes ?? []) > 3 ? '...' : ''),
                $channel->properties()->count()
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();

        $this->line('Para configurar OAuth para um canal específico:');
        $this->line('php artisan oauth:setup-channels --property=4 --channel=AIR298 --client-id=seu-client-id --client-secret=seu-secret');
    }

    private function setupOAuthForChannel(Property $property, Channel $channel, PropertyChannel $propertyChannel, ?string $clientId, ?string $clientSecret, ?string $redirectUri): void
    {
        $this->line("🏠 Propriedade: {$property->name}");
        $this->line("📡 Canal: {$channel->name}");
        $this->line("🔗 ID no Canal: {$propertyChannel->channel_property_id}");
        $this->newLine();

        // Configurar OAuth baseado no canal
        switch ($channel->channel_id) {
            case 'AIR298':
                $this->setupAirbnbOAuth($property, $channel, $propertyChannel, $clientId, $clientSecret, $redirectUri);
                break;
            case 'HOM143':
                $this->setupHomeAwayOAuth($property, $channel, $propertyChannel, $clientId, $clientSecret, $redirectUri);
                break;
            case 'VRB001':
                $this->setupVrboOAuth($property, $channel, $propertyChannel, $clientId, $clientSecret, $redirectUri);
                break;
            default:
                $this->error("Canal {$channel->channel_id} não suportado para OAuth.");
        }
    }

    private function setupAirbnbOAuth(Property $property, Channel $channel, PropertyChannel $propertyChannel, ?string $clientId, ?string $clientSecret, ?string $redirectUri): void
    {
        $this->info('🏠 Configurando OAuth para Airbnb...');

        if (!$clientId || !$clientSecret) {
            $this->warn('Client ID e Client Secret não fornecidos. Configurando manualmente...');
            
            $this->line('Para configurar OAuth do Airbnb:');
            $this->line('1. Acesse: https://www.airbnb.com/partner');
            $this->line('2. Crie uma aplicação');
            $this->line('3. Obtenha Client ID e Client Secret');
            $this->line('4. Configure redirect URI: ' . url('/oauth/airbnb/callback'));
            $this->newLine();
            
            $clientId = $this->ask('Client ID do Airbnb');
            $clientSecret = $this->secret('Client Secret do Airbnb');
        }

        $redirectUri = $redirectUri ?: url('/oauth/airbnb/callback');

        // Atualizar configurações do canal
        $channelConfig = $propertyChannel->getChannelConfig() ?: [];
        $channelConfig['oauth'] = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'auth_url' => 'https://www.airbnb.com/oauth/authorize',
            'token_url' => 'https://api.airbnb.com/v1/oauth/token',
            'scopes' => $channel->oauth_scopes ?? ['read', 'write']
        ];

        $propertyChannel->update([
            'channel_config' => $channelConfig,
            'channel_status' => 'inactive', // Será ativado após OAuth
        ]);

        $this->info('✅ Configuração OAuth do Airbnb salva!');
        $this->line("   Client ID: {$clientId}");
        $this->line("   Redirect URI: {$redirectUri}");
        $this->newLine();

        // Gerar URL de autorização
        $authUrl = $this->generateAuthUrl($channel, $channelConfig);
        $this->line("🔗 URL de Autorização:");
        $this->line($authUrl);
        $this->newLine();

        $this->line('📋 Próximos passos:');
        $this->line('1. Acesse a URL de autorização acima');
        $this->line('2. Autorize o acesso à sua conta Airbnb');
        $this->line('3. Copie o código de autorização');
        $this->line('4. Execute: php artisan oauth:complete-auth --property=' . $property->id . ' --channel=AIR298 --code=CODIGO_AQUI');
    }

    private function setupHomeAwayOAuth(Property $property, Channel $channel, PropertyChannel $propertyChannel, ?string $clientId, ?string $clientSecret, ?string $redirectUri): void
    {
        $this->info('🏡 Configurando OAuth para HomeAway...');

        if (!$clientId || !$clientSecret) {
            $this->warn('Client ID e Client Secret não fornecidos. Configurando manualmente...');
            
            $this->line('Para configurar OAuth do HomeAway:');
            $this->line('1. Acesse: https://developer.homeaway.com/');
            $this->line('2. Crie uma aplicação');
            $this->line('3. Obtenha Client ID e Client Secret');
            $this->line('4. Configure redirect URI: ' . url('/oauth/homeaway/callback'));
            $this->newLine();
            
            $clientId = $this->ask('Client ID do HomeAway');
            $clientSecret = $this->secret('Client Secret do HomeAway');
        }

        $redirectUri = $redirectUri ?: url('/oauth/homeaway/callback');

        // Atualizar configurações do canal
        $channelConfig = $propertyChannel->getChannelConfig() ?: [];
        $channelConfig['oauth'] = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'auth_url' => 'https://www.homeaway.com/oauth/authorize',
            'token_url' => 'https://api.homeaway.com/v1/oauth/token',
            'scopes' => $channel->oauth_scopes ?? ['read', 'write']
        ];

        $propertyChannel->update([
            'channel_config' => $channelConfig,
            'channel_status' => 'inactive', // Será ativado após OAuth
        ]);

        $this->info('✅ Configuração OAuth do HomeAway salva!');
        $this->line("   Client ID: {$clientId}");
        $this->line("   Redirect URI: {$redirectUri}");
        $this->newLine();

        // Gerar URL de autorização
        $authUrl = $this->generateAuthUrl($channel, $channelConfig);
        $this->line("🔗 URL de Autorização:");
        $this->line($authUrl);
        $this->newLine();

        $this->line('📋 Próximos passos:');
        $this->line('1. Acesse a URL de autorização acima');
        $this->line('2. Autorize o acesso à sua conta HomeAway');
        $this->line('3. Copie o código de autorização');
        $this->line('4. Execute: php artisan oauth:complete-auth --property=' . $property->id . ' --channel=HOM143 --code=CODIGO_AQUI');
    }

    private function setupVrboOAuth(Property $property, Channel $channel, PropertyChannel $propertyChannel, ?string $clientId, ?string $clientSecret, ?string $redirectUri): void
    {
        $this->info('🏖️ Configurando OAuth para VRBO...');

        if (!$clientId || !$clientSecret) {
            $this->warn('Client ID e Client Secret não fornecidos. Configurando manualmente...');
            
            $this->line('Para configurar OAuth do VRBO:');
            $this->line('1. Acesse: https://developer.vrbo.com/');
            $this->line('2. Crie uma aplicação');
            $this->line('3. Obtenha Client ID e Client Secret');
            $this->line('4. Configure redirect URI: ' . url('/oauth/vrbo/callback'));
            $this->newLine();
            
            $clientId = $this->ask('Client ID do VRBO');
            $clientSecret = $this->secret('Client Secret do VRBO');
        }

        $redirectUri = $redirectUri ?: url('/oauth/vrbo/callback');

        // Atualizar configurações do canal
        $channelConfig = $propertyChannel->getChannelConfig() ?: [];
        $channelConfig['oauth'] = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'auth_url' => 'https://www.vrbo.com/oauth/authorize',
            'token_url' => 'https://api.vrbo.com/v1/oauth/token',
            'scopes' => $channel->oauth_scopes ?? ['read', 'write']
        ];

        $propertyChannel->update([
            'channel_config' => $channelConfig,
            'channel_status' => 'inactive', // Será ativado após OAuth
        ]);

        $this->info('✅ Configuração OAuth do VRBO salva!');
        $this->line("   Client ID: {$clientId}");
        $this->line("   Redirect URI: {$redirectUri}");
        $this->newLine();

        // Gerar URL de autorização
        $authUrl = $this->generateAuthUrl($channel, $channelConfig);
        $this->line("🔗 URL de Autorização:");
        $this->line($authUrl);
        $this->newLine();

        $this->line('📋 Próximos passos:');
        $this->line('1. Acesse a URL de autorização acima');
        $this->line('2. Autorize o acesso à sua conta VRBO');
        $this->line('3. Copie o código de autorização');
        $this->line('4. Execute: php artisan oauth:complete-auth --property=' . $property->id . ' --channel=VRB001 --code=CODIGO_AQUI');
    }

    private function generateAuthUrl(Channel $channel, array $config): string
    {
        $params = [
            'client_id' => $config['oauth']['client_id'],
            'redirect_uri' => $config['oauth']['redirect_uri'],
            'response_type' => 'code',
            'scope' => implode(' ', $config['oauth']['scopes']),
            'state' => base64_encode(json_encode([
                'channel_id' => $channel->channel_id,
                'timestamp' => time()
            ]))
        ];

        return $config['oauth']['auth_url'] . '?' . http_build_query($params);
    }

    private function showHelp(): void
    {
        $this->line('Comandos disponíveis:');
        $this->line('');
        $this->line('  Listar canais OAuth:');
        $this->line('    php artisan oauth:setup-channels --list-all');
        $this->line('');
        $this->line('  Configurar OAuth para Airbnb:');
        $this->line('    php artisan oauth:setup-channels --property=4 --channel=AIR298 --client-id=seu-client-id --client-secret=seu-secret');
        $this->line('');
        $this->line('  Configurar OAuth para HomeAway:');
        $this->line('    php artisan oauth:setup-channels --property=4 --channel=HOM143 --client-id=seu-client-id --client-secret=seu-secret');
        $this->line('');
        $this->line('  Configurar OAuth para VRBO:');
        $this->line('    php artisan oauth:setup-channels --property=4 --channel=VRB001 --client-id=seu-client-id --client-secret=seu-secret');
    }
}