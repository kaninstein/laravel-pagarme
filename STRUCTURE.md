# Estrutura do Pacote Laravel Pagarme

```
laravel-pagarme/
├── src/
│   ├── Client/
│   │   └── PagarmeClient.php          # HTTP Client com autenticação Basic Auth
│   │
│   ├── Services/
│   │   ├── CustomerService.php         # Gerenciamento de clientes
│   │   ├── OrderService.php            # Gerenciamento de pedidos
│   │   ├── ChargeService.php           # Gerenciamento de cobranças
│   │   └── WebhookService.php          # Gerenciamento de webhooks
│   │
│   ├── DTOs/
│   │   ├── CustomerDTO.php             # DTO para clientes
│   │   ├── OrderDTO.php                # DTO para pedidos
│   │   ├── OrderItemDTO.php            # DTO para itens do pedido
│   │   ├── PaymentDTO.php              # DTO para pagamentos
│   │   ├── CreditCardDTO.php           # DTO para cartão de crédito
│   │   ├── PhoneDTO.php                # DTO para telefone individual
│   │   ├── PhonesDTO.php               # DTO para conjunto de telefones
│   │   ├── AddressDTO.php              # DTO para endereço
│   │   └── PaginatedResponse.php       # DTO para resposta paginada
│   │
│   ├── Exceptions/
│   │   ├── PagarmeException.php        # Exceção base
│   │   ├── BadRequestException.php     # 400
│   │   ├── AuthenticationException.php # 401
│   │   ├── ForbiddenException.php      # 403
│   │   ├── NotFoundException.php       # 404
│   │   ├── PreconditionFailedException.php # 412
│   │   ├── ValidationException.php     # 422
│   │   └── TooManyRequestsException.php # 429
│   │
│   ├── Facades/
│   │   └── Pagarme.php                 # Facade para uso simplificado
│   │
│   ├── Events/                         # (Pasta preparada para eventos futuros)
│   │
│   └── PagarmeServiceProvider.php      # Service Provider do Laravel
│
├── config/
│   └── pagarme.php                     # Arquivo de configuração
│
├── examples/
│   ├── complete-order-example.php      # Exemplo completo de pedido
│   ├── webhook-example.php             # Exemplo de webhook handler
│   └── typed-dtos-example.php          # Exemplo com DTOs tipados
│
├── tests/                              # (Preparado para testes)
│
├── .env.example                        # Exemplo de variáveis de ambiente
├── .gitignore                          # Git ignore
├── CHANGELOG.md                        # Histórico de mudanças
├── CONTRIBUTING.md                     # Guia de contribuição
├── LICENSE.md                          # Licença MIT
├── README.md                           # Documentação principal
├── STRUCTURE.md                        # Este arquivo
├── composer.json                       # Dependências do projeto
└── phpunit.xml                         # Configuração de testes

```

## Componentes Principais

### Client
- **PagarmeClient**: Cliente HTTP com autenticação Basic Auth, retry automático, logging e tratamento de erros

### Services
Cada service encapsula operações relacionadas a um recurso específico da API:
- **CustomerService**: CRUD de clientes, cartões e endereços
- **OrderService**: CRUD de pedidos e consulta de cobranças
- **ChargeService**: Gerenciamento de cobranças (retry, capture, cancel)
- **WebhookService**: CRUD de webhooks

### DTOs (Data Transfer Objects)
Classes type-safe para estruturar dados:
- **CustomerDTO**: Dados de clientes com suporte a phones e address tipados
- **OrderDTO**: Pedidos completos com itens e pagamentos
- **PaymentDTO**: Pagamentos (crédito, boleto, pix)
- **PhoneDTO/PhonesDTO**: Telefones com helpers para formato brasileiro
- **AddressDTO**: Endereços com helper para formato brasileiro
- **PaginatedResponse**: Respostas paginadas da API

### Exceptions
Hierarquia de exceções mapeadas aos códigos HTTP da API Pagarme.

### Facade
Facade `Pagarme` para acesso simplificado aos services e client.

## Fluxo de Uso

1. **Configuração**: Publicar config e definir variáveis de ambiente
2. **Uso via Facade**: `Pagarme::customers()->create([...])`
3. **Uso via Injeção**: Injetar services nos controllers
4. **DTOs**: Usar DTOs para type-safety e validação
5. **Tratamento de Erros**: Capturar exceções específicas

## Características Técnicas

- ✅ PHP 8.2+
- ✅ Laravel 10, 11, 12
- ✅ PSR-4 Autoloading
- ✅ Type Hints estritos
- ✅ Union Types
- ✅ Named Arguments
- ✅ Constructor Property Promotion
- ✅ Match Expressions
