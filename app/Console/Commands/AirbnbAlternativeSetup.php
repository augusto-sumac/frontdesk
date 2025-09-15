<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Property;
use App\Models\Channel;
use App\Models\PropertyChannel;

class AirbnbAlternativeSetup extends Command
{
    protected $signature = 'airbnb:alternative-setup 
                            {--property=4 : ID da propriedade}
                            {--list-manual : Listar propriedades para configuração manual}
                            {--setup-nextpax : Configurar via NextPax}';

    protected $description = 'Configuração alternativa para Airbnb quando API não está disponível';

    public function handle()
    {
        $propertyId = $this->option('property');
        $listManual = $this->option('list-manual');
        $setupNextpax = $this->option('setup-nextpax');

        $this->info('🏠 CONFIGURAÇÃO ALTERNATIVA PARA AIRBNB');
        $this->newLine();

        if ($listManual) {
            $this->showManualSetupGuide();
        } elseif ($setupNextpax) {
            $this->setupViaNextPax($propertyId);
        } else {
            $this->showOptions();
        }
    }

    private function showOptions(): void
    {
        $this->warn('⚠️  AIRBNB API NÃO DISPONÍVEL PARA NOVOS PARCEIROS');
        $this->newLine();

        $this->line('📋 OPÇÕES DISPONÍVEIS:');
        $this->newLine();

        $this->line('1️⃣ CONFIGURAÇÃO MANUAL NO AIRBNB:');
        $this->line('   - Cadastrar propriedade manualmente no Airbnb');
        $this->line('   - Configurar preços e disponibilidade manualmente');
        $this->line('   - Usar sistema apenas para receber reservas');
        $this->line('   Comando: php artisan airbnb:alternative-setup --list-manual');
        $this->newLine();

        $this->line('2️⃣ USAR NEXTPAX COMO INTERMEDIÁRIO:');
        $this->line('   - Configurar propriedade na NextPax');
        $this->line('   - NextPax gerencia Airbnb automaticamente');
        $this->line('   - Sistema se comunica apenas com NextPax');
        $this->line('   Comando: php artisan airbnb:alternative-setup --setup-nextpax');
        $this->newLine();

        $this->line('3️⃣ AGUARDAR REABERTURA DA API:');
        $this->line('   - Monitorar página do Airbnb Partner');
        $this->line('   - Cadastrar para notificações');
        $this->line('   - Manter sistema preparado');
        $this->newLine();

        $this->info('🎯 RECOMENDAÇÃO: Use NextPax como intermediário (Opção 2)');
        $this->line('   É a solução mais robusta e profissional disponível.');
    }

    private function showManualSetupGuide(): void
    {
        $this->info('📋 GUIA DE CONFIGURAÇÃO MANUAL NO AIRBNB');
        $this->newLine();

        $this->line('🔗 PASSO 1: ACESSAR AIRBNB HOST');
        $this->line('   1. Acesse: https://www.airbnb.com/host');
        $this->line('   2. Faça login com sua conta');
        $this->line('   3. Clique em "List your space"');
        $this->newLine();

        $this->line('🏠 PASSO 2: CADASTRAR PROPRIEDADE');
        $this->line('   1. Selecione tipo de propriedade');
        $this->line('   2. Adicione fotos (mínimo 5)');
        $this->line('   3. Preencha descrição detalhada');
        $this->line('   4. Configure localização');
        $this->line('   5. Defina preços base');
        $this->line('   6. Configure disponibilidade');
        $this->newLine();

        $this->line('⚙️ PASSO 3: CONFIGURAR NO SISTEMA');
        $this->line('   1. Obtenha o Listing ID do Airbnb');
        $this->line('   2. Execute: php artisan channels:manage connect --property=4 --channel=AIR298 --channel-property-id=LISTING_ID');
        $this->line('   3. Execute: php artisan channels:activate 4 AIR298 --status=active --content=enabled');
        $this->newLine();

        $this->line('📊 PASSO 4: CONFIGURAR WEBHOOKS');
        $this->line('   1. No Airbnb, vá em "Account" > "API"');
        $this->line('   2. Configure webhook URL: https://seudominio.com/webhooks/airbnb');
        $this->line('   3. Teste: php artisan webhooks:test --channel=airbnb');
        $this->newLine();

        $this->warn('⚠️  LIMITAÇÕES DA CONFIGURAÇÃO MANUAL:');
        $this->line('   - Não há sincronização automática de preços');
        $this->line('   - Não há sincronização automática de disponibilidade');
        $this->line('   - Reservas são recebidas apenas via webhooks');
        $this->line('   - Não é possível criar reservas via sistema');
    }

    private function setupViaNextPax(int $propertyId): void
    {
        $property = Property::find($propertyId);
        if (!$property) {
            $this->error("Propriedade {$propertyId} não encontrada.");
            return;
        }

        $this->info('🚀 CONFIGURAÇÃO VIA NEXTPAX (RECOMENDADO)');
        $this->newLine();

        $this->line("🏠 Propriedade: {$property->name}");
        $this->line("   ID NextPax: {$property->channel_property_id}");
        $this->line("   Supplier ID: {$property->supplier_property_id}");
        $this->newLine();

        $this->line('📋 PASSOS PARA CONFIGURAÇÃO:');
        $this->newLine();

        $this->line('1️⃣ CONFIGURAR PROPERTY MANAGER:');
        $this->line('   POST https://supply.sandbox.nextpax.app/api/v1/suppliers/property-manager');
        $this->line('   Payload:');
        $this->line('   {');
        $this->line('     "propertyManagerCode": "' . $property->property_manager_code . '",');
        $this->line('     "name": "Seu Nome",');
        $this->line('     "email": "seu@email.com"');
        $this->line('   }');
        $this->newLine();

        $this->line('2️⃣ CONFIGURAR PROPRIEDADE:');
        $this->line('   POST https://supply.sandbox.nextpax.app/api/v1/content/properties');
        $this->line('   Configure todos os dados da propriedade');
        $this->newLine();

        $this->line('3️⃣ CONFIGURAR RATES:');
        $this->line('   POST https://supply.sandbox.nextpax.app/api/v1/ari/rates-los/' . $property->channel_property_id);
        $this->line('   POST https://supply.sandbox.nextpax.app/api/v1/ari/rates-periodic/' . $property->channel_property_id);
        $this->newLine();

        $this->line('4️⃣ CONFIGURAR DISPONIBILIDADE:');
        $this->line('   POST https://supply.sandbox.nextpax.app/api/v1/ari/availability/' . $property->channel_property_id);
        $this->newLine();

        $this->line('5️⃣ CONFIGURAR POLÍTICAS:');
        $this->line('   GET https://supply.sandbox.nextpax.app/api/v1/constants/channel-policy-codes');
        $this->line('   POST https://supply.sandbox.nextpax.app/api/v1/suppliers/property-manager/' . $property->property_manager_code . '/policies');
        $this->newLine();

        $this->line('6️⃣ CONFIGURAR CHANNEL MANAGEMENT:');
        $this->line('   GET https://supply.sandbox.nextpax.app/api/v1/channel-management/property-manager-settings/');
        $this->line('   POST https://supply.sandbox.nextpax.app/api/v1/channel-management/property-manager-settings/' . $property->property_manager_code);
        $this->newLine();

        $this->line('7️⃣ ATIVAR PROPRIEDADE NO AIRBNB:');
        $this->line('   PUT https://supply.sandbox.nextpax.app/api/v1/channel-management/property/' . $property->channel_property_id);
        $this->line('   Payload:');
        $this->line('   {');
        $this->line('     "channels": [');
        $this->line('       {');
        $this->line('         "channelId": "AIR298",');
        $this->line('         "channelStatus": "enabled",');
        $this->line('         "contentStatus": "enabled",');
        $this->line('         "channelPropertyId": "SEU_AIRBNB_LISTING_ID"');
        $this->line('       }');
        $this->line('     ]');
        $this->line('   }');
        $this->newLine();

        $this->line('8️⃣ SOLICITAR AUTORIZAÇÃO NEXTPAX:');
        $this->line('   Email: support@nextpax.com');
        $this->line('   Assunto: "Authorization link for Airbnb Host account connection"');
        $this->line('   Aguarde o link de autorização');
        $this->newLine();

        $this->line('9️⃣ CONFIGURAR NO SISTEMA:');
        $this->line('   php artisan channels:manage connect --property=' . $propertyId . ' --channel=AIR298 --channel-property-id=SEU_AIRBNB_LISTING_ID');
        $this->line('   php artisan channels:activate ' . $propertyId . ' AIR298 --status=active --content=enabled --auto-sync');
        $this->newLine();

        $this->info('✅ VANTAGENS DA CONFIGURAÇÃO VIA NEXTPAX:');
        $this->line('   ✅ Sincronização automática de preços');
        $this->line('   ✅ Sincronização automática de disponibilidade');
        $this->line('   ✅ Recebimento automático de reservas');
        $this->line('   ✅ Criação de reservas via sistema');
        $this->line('   ✅ Gerenciamento centralizado');
        $this->line('   ✅ Suporte técnico da NextPax');
        $this->newLine();

        $this->info('🎯 PRÓXIMO PASSO:');
        $this->line('   Execute: php artisan nextpax:configure-properties ' . $propertyId);
        $this->line('   Para configurar automaticamente a propriedade na NextPax');
    }
}