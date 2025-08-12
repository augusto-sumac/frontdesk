# Teste de Sincronização Completa - Documentação

## Visão Geral

O comando `TestCompleteSync` é um teste automatizado que valida todo o fluxo de sincronização do sistema, desde a criação de propriedades até a criação de reservas, incluindo a verificação da sincronização com o banco de dados.

## Funcionalidades

### ✅ O que o teste faz:

1. **Setup do Ambiente**: Configura um usuário de teste com código de gerenciador de propriedades
2. **Criação de Propriedade**: Cria uma propriedade de teste via API NextPax
3. **Criação de Reserva**: Cria uma reserva usando a propriedade criada
4. **Verificação de Dados**: Confirma que os dados foram sincronizados corretamente no banco
5. **Teste de Listagem**: Verifica se as listagens funcionam corretamente
6. **Limpeza Opcional**: Oferece a opção de limpar os dados de teste

### 🔧 Melhorias Implementadas:

- **Modularização**: Código dividido em métodos privados para melhor manutenção
- **Tratamento de Erros**: Melhor captura e exibição de erros
- **Validação de Dados**: Verificação mais robusta dos dados retornados
- **Logging**: Registro de erros para debugging
- **Dados Únicos**: Geração de dados únicos para evitar conflitos
- **Opções de Linha de Comando**: Flexibilidade para especificar usuário e código de gerenciador
- **Limpeza Automática**: Opção de limpar dados de teste automaticamente

## Como Usar

### Comando Básico

```bash
php artisan test:complete-sync
```

### Comando com Usuário Específico

```bash
php artisan test:complete-sync --user-id=1
```

### Comando com Código de Gerenciador Específico

```bash
php artisan test:complete-sync --property-manager=SAFDK000034
```

### Comando com Ambos os Parâmetros

```bash
php artisan test:complete-sync --user-id=1 --property-manager=SAFDK000034
```

## Parâmetros Disponíveis

| Parâmetro | Descrição | Obrigatório | Padrão |
|-----------|-----------|-------------|---------|
| `--user-id` | ID específico do usuário para teste | Não | Primeiro usuário com `property_manager_code` |
| `--property-manager` | Código específico do gerenciador de propriedades | Não | `SAFDK000034` |

## Estrutura do Teste

### Step 0: Setup do Ambiente
- Validação do usuário
- Configuração do código de gerenciador
- Autenticação

### Step 1: Criação de Propriedade
- Geração de dados únicos para propriedade
- Chamada para API NextPax
- Salvamento no banco local
- Validação da resposta

### Step 2: Criação de Reserva
- Geração de dados únicos para reserva
- Uso da propriedade criada
- Chamada para API de reservas
- Salvamento no banco local

### Step 3: Verificação de Dados
- Confirmação de que propriedade existe no banco
- Confirmação de que reserva existe no banco
- Validação de relacionamentos entre entidades

### Step 4: Teste de Listagem
- Verificação de funcionalidade de listagem
- Contagem de reservas por gerenciador

### Step 5: Limpeza (Opcional)
- Remoção de dados de teste
- Confirmação do usuário

## Dados de Teste Gerados

### Propriedade
- Nome único com timestamp
- Endereço com número aleatório
- Coordenadas com variação mínima
- Preço base com variação
- Contatos únicos

### Reserva
- Nome de hóspede único
- Email único
- Datas de check-in/out futuras
- Número de hóspedes variável
- Preço variável

## Tratamento de Erros

### Níveis de Erro
1. **Erro de Setup**: Usuário não encontrado ou sem código de gerenciador
2. **Erro de Criação**: Falha na API ou validação
3. **Erro de Verificação**: Dados não sincronizados corretamente
4. **Erro de Listagem**: Problemas na recuperação de dados

### Logs
- Erros são registrados no log do Laravel
- Stack trace completo para debugging
- Códigos de retorno apropriados (0 = sucesso, 1 = falha)

## Exemplo de Saída

```
🚀 Testing complete synchronization flow...

Step 0: Setting up test environment...
✅ Using user: João Silva (PM: SAFDK000034)

Step 1: Creating property...
✅ Property created successfully!
  Property ID: 123e4567-e89b-12d3-a456-426614174000
  Supplier Property ID: prop-abc123def456
  Local Property ID: 15

Step 2: Creating booking...
✅ Booking created successfully!
  Local Booking ID: 23
  NextPax Booking ID: 987654321

Step 3: Verifying data in database...
✅ Data synchronized to database successfully!
  Property in DB: Apartamento Sincronização Teste xyz789 (NextPax: 123e4567-e89b-12d3-a456-426614174000)
  Booking in DB: João Sincronização abc1 (Status: pending)

Step 4: Testing listing and retrieval...
✅ Listings retrieved successfully!
  Total bookings found: 5

Do you want to clean up the test data? (yes/no) [no]:
🎉 Complete synchronization test finished successfully!
```

## Troubleshooting

### Problemas Comuns

1. **Usuário não encontrado**
   - Verifique se existe um usuário com `property_manager_code`
   - Use `--user-id` para especificar um usuário específico

2. **Falha na criação de propriedade**
   - Verifique a conectividade com a API NextPax
   - Valide as credenciais e permissões

3. **Falha na criação de reserva**
   - Confirme que a propriedade foi criada com sucesso
   - Verifique os dados obrigatórios da reserva

4. **Dados não sincronizados**
   - Verifique a configuração do banco de dados
   - Confirme que as migrations foram executadas

### Debug

- Use `--verbose` para mais detalhes (se implementado)
- Verifique os logs do Laravel em `storage/logs/laravel.log`
- Execute com `--help` para ver todas as opções disponíveis

## Integração com CI/CD

O comando pode ser integrado em pipelines de CI/CD:

```yaml
# Exemplo para GitHub Actions
- name: Run Complete Sync Test
  run: php artisan test:complete-sync --property-manager=${{ secrets.TEST_PROPERTY_MANAGER }}
```

## Manutenção

### Adicionando Novos Testes
1. Crie um novo método privado na classe
2. Adicione a chamada no método `handle()`
3. Implemente validação e tratamento de erros
4. Atualize esta documentação

### Modificando Dados de Teste
1. Edite os métodos `createTestProperty()` ou `createTestBooking()`
2. Mantenha a unicidade dos dados
3. Teste as modificações localmente antes de commitar

## Contribuição

Para contribuir com melhorias no teste:

1. Fork o repositório
2. Crie uma branch para sua feature
3. Implemente as mudanças
4. Adicione testes se necessário
5. Atualize a documentação
6. Submeta um pull request 