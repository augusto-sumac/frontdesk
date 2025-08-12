#!/bin/bash

# Script de Exemplos para Teste de Sincronização Completa
# Este script demonstra diferentes formas de executar o teste

echo "🚀 Exemplos de Uso do Teste de Sincronização Completa"
echo "=================================================="
echo ""

# Verificar se estamos no diretório correto
if [ ! -f "artisan" ]; then
    echo "❌ Erro: Execute este script no diretório raiz do projeto Laravel"
    exit 1
fi

echo "📋 Comandos disponíveis:"
echo ""

echo "1️⃣  Teste básico (usa primeiro usuário disponível):"
echo "   php artisan test:complete-sync"
echo ""

echo "2️⃣  Teste com usuário específico:"
echo "   php artisan test:complete-sync --user-id=1"
echo ""

echo "3️⃣  Teste com código de gerenciador específico:"
echo "   php artisan test:complete-sync --property-manager=SAFDK000034"
echo ""

echo "4️⃣  Teste com ambos os parâmetros:"
echo "   php artisan test:complete-sync --user-id=1 --property-manager=SAFDK000034"
echo ""

echo "5️⃣  Teste sem interação (para CI/CD):"
echo "   php artisan test:complete-sync --no-interaction"
echo ""

echo "6️⃣  Teste com saída silenciosa:"
echo "   php artisan test:complete-sync --silent"
echo ""

echo "7️⃣  Teste com saída verbosa:"
echo "   php artisan test:complete-sync --verbose"
echo ""

echo "🔧 Opções úteis:"
echo "   --help          : Mostra ajuda detalhada"
echo "   --version       : Mostra versão do comando"
echo "   --no-ansi       : Desabilita cores ANSI"
echo ""

echo "📝 Exemplo de execução:"
echo "   # Executar teste básico"
echo "   php artisan test:complete-sync"
echo ""

echo "💡 Dicas:"
echo "   - Certifique-se de que o banco de dados está configurado"
echo "   - Verifique se as credenciais da API NextPax estão válidas"
echo "   - Use --no-interaction para execução automatizada"
echo "   - Verifique os logs em storage/logs/laravel.log se houver erros"
echo ""

echo "🎯 Para executar um teste específico, descomente uma das linhas abaixo:"
echo ""

# Exemplos comentados para fácil descomentação
echo "# php artisan test:complete-sync"
echo "# php artisan test:complete-sync --user-id=1"
echo "# php artisan test:complete-sync --property-manager=SAFDK000034"
echo "# php artisan test:complete-sync --no-interaction"

echo ""
echo "✅ Script de exemplos carregado com sucesso!"
echo "   Execute um dos comandos acima para testar a sincronização completa." 