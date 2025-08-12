# Sistema de Gerenciamento de Propriedades - FrontDesk MVP

## Visão Geral

Este sistema implementa um gerenciamento completo de propriedades para o FrontDesk MVP, incluindo cadastro, edição, exclusão e gerenciamento de imagens. O sistema integra-se com a API NextPax para sincronização de dados e mantém um banco de dados local para operações offline.

## Funcionalidades Principais

### 🏠 Gerenciamento de Propriedades
- **CRUD Completo**: Criar, visualizar, editar e excluir propriedades
- **Campos Abrangentes**: Informações básicas, endereço, capacidade, preços, horários, comodidades e regras
- **Status de Propriedade**: Draft, Pending, Active, Inactive, Suspended
- **Validação de Dados**: Validação completa de formulários com mensagens de erro
- **Integração NextPax**: Sincronização automática com a API NextPax

### 📸 Sistema de Imagens
- **Imagem Principal**: Upload e gerenciamento da imagem principal da propriedade
- **Galeria de Imagens**: Suporte a múltiplas imagens com ordenação
- **Thumbnails Automáticos**: Geração automática de miniaturas para otimização
- **Tipos de Imagem**: Main, Gallery, Floorplan, Amenity
- **Drag & Drop**: Interface intuitiva para upload de imagens

### 🎨 Interface do Usuário
- **Design Responsivo**: Interface adaptável para diferentes dispositivos
- **Bootstrap 5**: Framework CSS moderno e responsivo
- **FontAwesome**: Ícones intuitivos e consistentes
- **Validação em Tempo Real**: Feedback imediato para o usuário
- **Modais de Confirmação**: Confirmações para ações destrutivas

## Estrutura do Banco de Dados

### Tabela `properties`
```sql
- id (Primary Key)
- name (Nome da propriedade)
- property_id (ID único da propriedade)
- channel_type (Tipo de canal - nextpax, airbnb, etc.)
- channel_property_id (ID da propriedade no canal externo)
- address, city, state, country (Endereço completo)
- postal_code, latitude, longitude (Localização)
- description (Descrição detalhada)
- property_type (Tipo: apartment, house, hotel, etc.)
- max_occupancy, max_adults, max_children (Capacidade)
- bedrooms, bathrooms (Quartos e banheiros)
- base_price, currency (Preços)
- amenities, house_rules (JSON arrays)
- check_in_from, check_in_until, check_out_from, check_out_until (Horários)
- contact_name, contact_phone, contact_email (Contato)
- status (Status da propriedade)
- verified_at (Data de verificação)
- timestamps (created_at, updated_at)
```

### Tabela `property_images`
```sql
- id (Primary Key)
- property_id (Foreign Key para properties)
- image_path (Caminho do arquivo)
- image_name (Nome original do arquivo)
- alt_text (Texto alternativo para SEO)
- type (Tipo: main, gallery, floorplan, amenity)
- sort_order (Ordem de exibição)
- is_active (Status ativo/inativo)
- timestamps
```

## Rotas da API

### Propriedades
- `GET /properties` - Listar propriedades
- `GET /properties/create` - Formulário de criação
- `POST /properties` - Criar propriedade
- `GET /properties/{id}` - Visualizar propriedade
- `GET /properties/{id}/edit` - Formulário de edição
- `PUT /properties/{id}` - Atualizar propriedade
- `DELETE /properties/{id}` - Excluir propriedade

### Imagens
- `POST /properties/{id}/images` - Upload de imagens
- `DELETE /properties/{id}/images/{imageId}` - Excluir imagem
- `POST /properties/{id}/images/reorder` - Reordenar imagens

## Controllers

### PropertyController
Controlador principal responsável por todas as operações de propriedades:

- **index()**: Lista propriedades locais e da API
- **show()**: Exibe detalhes de uma propriedade
- **create()**: Formulário de criação
- **store()**: Salva nova propriedade
- **edit()**: Formulário de edição
- **update()**: Atualiza propriedade existente
- **destroy()**: Exclui propriedade
- **uploadImages()**: Gerencia upload de imagens
- **deleteImage()**: Remove imagem específica
- **reorderImages()**: Reordena imagens da galeria

## Models

### Property
Modelo principal com relacionamentos e acessors:

- **Relacionamentos**: users, images, mainImage, galleryImages
- **Acessors**: fullAddress, mainImageUrl, galleryImagesUrls, statusBadge
- **Scopes**: active, verified, byType
- **Métodos**: isActive, isVerified, canBeBooked, hasCoordinates

### PropertyImage
Modelo para gerenciamento de imagens:

- **Relacionamentos**: property
- **Acessors**: imageUrl, thumbnailUrl, imageSize, imageDimensions
- **Scopes**: active, byType, main, gallery, ordered
- **Métodos**: deleteImage, moveToPosition

## Migrações

### 2025_08_12_000000_add_images_and_enhance_properties_table
Adiciona campos para:
- Imagens (main_image, gallery_images)
- Detalhes da propriedade (description, property_type, etc.)
- Capacidade (max_occupancy, bedrooms, bathrooms)
- Localização (latitude, longitude)
- Preços e comodidades
- Horários de check-in/check-out
- Informações de contato
- Status e verificação

### 2025_08_12_000001_create_property_images_table
Cria tabela para:
- Relacionamento com propriedades
- Metadados das imagens
- Tipos e ordenação
- Controle de status

## Views

### properties/index.blade.php
- Lista todas as propriedades
- Estatísticas em cards
- Integração com NextPax
- Ações rápidas (ver, editar, excluir)

### properties/create.blade.php
- Formulário completo de criação
- Upload de imagens com drag & drop
- Validação em tempo real
- Preview de imagens

### properties/show.blade.php
- Visualização detalhada da propriedade
- Galeria de imagens
- Informações organizadas em cards
- Ações rápidas

### properties/edit.blade.php
- Formulário de edição
- Gerenciamento de imagens existentes
- Upload de novas imagens
- Validação e feedback

## Funcionalidades de Imagem

### Upload de Imagens
- **Formatos Suportados**: JPEG, PNG, JPG, GIF
- **Tamanho Máximo**: 5MB por imagem
- **Validação**: Tipo, tamanho e dimensões
- **Thumbnails**: Geração automática de miniaturas

### Gerenciamento de Imagens
- **Ordenação**: Drag & drop para reordenar
- **Exclusão**: Remoção individual de imagens
- **Tipos**: Categorização por tipo (main, gallery, etc.)
- **Status**: Controle de ativo/inativo

## Integração NextPax

### Sincronização Automática
- Criação de propriedades na API NextPax
- Atualização de dados existentes
- Mapeamento de campos entre sistemas
- Tratamento de erros de sincronização

### Estrutura de Dados
- **Payload NextPax**: Formatação automática dos dados
- **Mapeamento de Campos**: Conversão entre formatos
- **Fallback**: Operação offline quando API indisponível

## Validações

### Campos Obrigatórios
- Nome da propriedade
- Tipo de propriedade
- Endereço completo
- Capacidade básica
- Horários de check-in/check-out

### Validações Específicas
- **Coordenadas**: Latitude (-90 a 90), Longitude (-180 a 180)
- **Capacidade**: Valores mínimos e máximos
- **Preços**: Valores positivos
- **Imagens**: Tipos e tamanhos permitidos

## Segurança

### CSRF Protection
- Tokens CSRF em todos os formulários
- Validação automática de tokens
- Proteção contra ataques CSRF

### Validação de Entrada
- Sanitização de dados
- Validação de tipos e formatos
- Escape de HTML em saída

### Controle de Acesso
- Middleware de autenticação
- Verificação de propriedade do usuário
- Controle de tenant

## Configuração

### Variáveis de Ambiente
```env
NEXTPAX_CLIENT_ID=your_client_id
NEXTPAX_CLIENT_SECRET=your_client_secret
NEXTPAX_SENDER_ID=your_sender_id
NEXTPAX_SUPPLY_API_BASE=https://supply.sandbox.nextpax.app/api/v1
```

### Storage
- **Local**: Configuração padrão do Laravel
- **Thumbnails**: Geração automática em subdiretórios
- **Organização**: Estrutura hierárquica por propriedade

## Uso

### 1. Acessar o Sistema
- Navegar para `/properties`
- Fazer login com credenciais válidas

### 2. Criar Propriedade
- Clicar em "Nova Propriedade"
- Preencher formulário completo
- Upload de imagem principal
- Upload de imagens da galeria
- Salvar propriedade

### 3. Gerenciar Propriedades
- Visualizar lista de propriedades
- Acessar detalhes individuais
- Editar informações existentes
- Gerenciar imagens
- Excluir propriedades

### 4. Sincronização NextPax
- Propriedades criadas automaticamente sincronizadas
- Dados atualizados em tempo real
- Fallback para operação offline

## Manutenção

### Limpeza de Imagens
- Remoção automática de arquivos órfãos
- Limpeza de thumbnails não utilizados
- Manutenção de integridade do banco

### Logs e Monitoramento
- Logs de operações de propriedades
- Monitoramento de sincronização NextPax
- Tratamento de erros e exceções

## Próximas Funcionalidades

### Roadmap
- [ ] Sistema de avaliações e comentários
- [ ] Calendário de disponibilidade
- [ ] Sistema de preços dinâmicos
- [ ] Integração com múltiplos canais
- [ ] Relatórios e analytics
- [ ] Sistema de notificações
- [ ] API REST para terceiros

### Melhorias Técnicas
- [ ] Cache de imagens
- [ ] Otimização de consultas
- [ ] Sistema de backup automático
- [ ] Testes automatizados
- [ ] Documentação da API

## Suporte

Para dúvidas ou problemas:
- Verificar logs em `storage/logs/laravel.log`
- Consultar documentação da API NextPax
- Verificar configurações de ambiente
- Validar permissões de diretórios de storage

## Conclusão

Este sistema fornece uma base sólida para gerenciamento de propriedades, com funcionalidades completas de CRUD, gerenciamento de imagens e integração com APIs externas. A arquitetura modular permite fácil extensão e manutenção, enquanto a interface intuitiva garante uma boa experiência do usuário. 