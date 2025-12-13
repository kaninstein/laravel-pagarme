# Laravel Pagarme

[![Latest Version on Packagist](https://img.shields.io/packagist/v/kaninstein/laravel-pagarme.svg?style=flat-square)](https://packagist.org/packages/kaninstein/laravel-pagarme)
[![Total Downloads](https://img.shields.io/packagist/dt/kaninstein/laravel-pagarme.svg?style=flat-square)](https://packagist.org/packages/kaninstein/laravel-pagarme)
[![License](https://img.shields.io/packagist/l/kaninstein/laravel-pagarme.svg?style=flat-square)](https://packagist.org/packages/kaninstein/laravel-pagarme)
[![PHP Version](https://img.shields.io/packagist/php-v/kaninstein/laravel-pagarme.svg?style=flat-square)](https://packagist.org/packages/kaninstein/laravel-pagarme)

**Integração completa e não-oficial com o gateway de pagamento Pagar.me (API v5) para Laravel 10, 11 e 12.**

> 🚀 **Pronto para produção** • Testes automatizados • Código 100% em português • Sem dependências do SDK oficial

## Características

- ✅ Integração direta com a API v5 da Pagarme (sem SDK oficial)
- ✅ Suporte a Laravel 10, 11 e 12
- ✅ PHP 8.2, 8.3 e 8.4
- ✅ Autenticação Basic Auth
- ✅ DTOs type-safe para estruturação de dados
- ✅ Tratamento robusto de erros com códigos ABECS
- ✅ Retry automático em falhas temporárias
- ✅ Logging configurável
- ✅ Facade para uso simplificado
- ✅ Suporte completo a todos os métodos de pagamento:
  - 💳 Cartão de Crédito
  - 💳 Cartão de Débito
  - 🔲 PIX
  - 📄 Boleto
  - 🎫 Voucher (VR, Alelo, Sodexo, Ticket)
  - 💵 Dinheiro
  - 🔐 SafetyPay
  - 🏷️ Private Label
- ✅ Suporte a todas as principais operações:
  - 👥 Customers (Clientes)
  - 📦 Orders (Pedidos)
  - 💰 Charges (Cobranças)
  - 🔔 Webhooks
  - 💳 Cards (Cartões)
  - 🏪 SubMerchant (Facilitadores de Pagamento)
  - 🔍 BIN Lookup
  - 🔐 Tokenização
- ✅ Mapeamento completo de códigos de retorno ABECS (60+ códigos)
- ✅ Helpers brasileiros para telefones e endereços
- ✅ Simuladores de teste completos para todos os métodos de pagamento
- ✅ Testes automatizados contra API real do Pagar.me

## Instalação

Você pode instalar o pacote via composer:

```bash
composer require kaninstein/laravel-pagarme
```

Publique o arquivo de configuração:

```bash
php artisan vendor:publish --tag=pagarme-config
```

Adicione suas chaves de API no arquivo `.env`:

```env
PAGARME_SECRET_KEY=sk_test_sua_chave_secreta
PAGARME_PUBLIC_KEY=pk_test_sua_chave_publica
```

## Configuração

O arquivo de configuração `config/pagarme.php` permite customizar:

- Chaves de API (secret e public key)
- URL da API
- Timeout de requisições
- Configuração de retry
- Configurações de webhook
- Logging

## Uso Básico

### Usando a Facade

```php
use Kaninstein\LaravelPagarme\Facades\Pagarme;

// Criar um cliente
$customer = Pagarme::customers()->create([
    'name' => 'João Silva',
    'email' => 'joao@example.com',
    'type' => 'individual',
    'document' => '12345678900',
    'document_type' => 'CPF',
    'phones' => [
        'mobile_phone' => [
            'country_code' => '55',
            'area_code' => '11',
            'number' => '987654321'
        ]
    ]
]);

// Listar clientes
$customers = Pagarme::customers()->list([
    'page' => 1,
    'size' => 10
]);

// Buscar cliente por ID
$customer = Pagarme::customers()->get('cus_123456');
```

### Usando DTOs

```php
use Kaninstein\LaravelPagarme\DTOs\CustomerDTO;
use Kaninstein\LaravelPagarme\DTOs\OrderDTO;
use Kaninstein\LaravelPagarme\DTOs\OrderItemDTO;
use Kaninstein\LaravelPagarme\DTOs\PaymentDTO;
use Kaninstein\LaravelPagarme\DTOs\CreditCardDTO;
use Kaninstein\LaravelPagarme\Facades\Pagarme;

// Criar cliente usando DTO
$customerDTO = new CustomerDTO(
    name: 'João Silva',
    email: 'joao@example.com',
    document: '12345678900',
    documentType: 'CPF',
    phones: [
        'mobile_phone' => [
            'country_code' => '55',
            'area_code' => '11',
            'number' => '987654321'
        ]
    ]
);

$customer = Pagarme::customers()->create($customerDTO->toArray());

// Criar pedido com pagamento
$item = new OrderItemDTO(
    amount: 10000, // R$ 100,00 em centavos
    description: 'Produto X',
    quantity: 1,
    code: 'PROD-001'
);

$creditCard = new CreditCardDTO(
    number: '4111111111111111',
    holderName: 'JOAO SILVA',
    holderDocument: '12345678900',
    expMonth: 12,
    expYear: 2025,
    cvv: '123'
);

$payment = PaymentDTO::creditCard(
    creditCard: $creditCard,
    installments: 1
);

$order = new OrderDTO(
    items: [$item],
    customer: $customer['id'],
    payments: [$payment]
);

$createdOrder = Pagarme::orders()->create($order->toArray());
```

### Gerenciando Pedidos

```php
use Kaninstein\LaravelPagarme\Facades\Pagarme;

// Criar pedido
$order = Pagarme::orders()->create([...]);

// Buscar pedido
$order = Pagarme::orders()->get('or_123456');

// Listar pedidos
$orders = Pagarme::orders()->list([
    'page' => 1,
    'size' => 20
]);

// Fechar pedido
$closedOrder = Pagarme::orders()->close('or_123456');
```

### Gerenciando Cobranças

```php
use Kaninstein\LaravelPagarme\Facades\Pagarme;

// Buscar cobrança
$charge = Pagarme::charges()->get('ch_123456');

// Listar cobranças
$charges = Pagarme::charges()->list();

// Tentar novamente
$charge = Pagarme::charges()->retry('ch_123456');

// Capturar cobrança
$charge = Pagarme::charges()->capture('ch_123456', [
    'amount' => 10000
]);

// Cancelar cobrança
$charge = Pagarme::charges()->cancel('ch_123456');
```

### Calculadora de Taxas (Fee Calculator)

Simule taxas e valores (líquido/sugerido) via endpoint `transactions/fee-calculator`, com cache por ~1 mês por padrão.

```php
use Kaninstein\LaravelPagarme\Facades\Pagarme;

$result = Pagarme::feeCalculator()->calculate([
    'amount' => 7800, // centavos
    'fee_responsibility' => 'merchant', // ou 'buyer'
    'credit_card' => [
        'installments' => 1,
        'card_brand' => 'mastercard',
        'capture_method' => 'ecommerce', // opcional (default: ecommerce)
    ],
]);

// Desativar cache (por chamada)
$result = Pagarme::feeCalculator()->calculate($payload, useCache: false);
```

### Webhooks

#### Configuração Automática (Recomendado)

Use o comando Artisan para configurar webhooks automaticamente:

```bash
# Configurar webhooks recomendados
php artisan pagarme:setup-webhooks

# Especificar URL customizada
php artisan pagarme:setup-webhooks --url=https://seusite.com/api/webhooks/pagarme

# Configurar eventos específicos
php artisan pagarme:setup-webhooks --events=order.paid,charge.paid

# Listar webhooks existentes
php artisan pagarme:setup-webhooks --list

# Limpar e recriar todos os webhooks
php artisan pagarme:setup-webhooks --clean
```

Configure a URL no `.env`:
```env
PAGARME_WEBHOOK_URL=https://seusite.com/api/webhooks/pagarme
```

#### Configuração Manual

```php
use Kaninstein\LaravelPagarme\Facades\Pagarme;

// Criar webhook
$webhook = Pagarme::webhooks()->create([
    'url' => 'https://seusite.com/webhooks/pagarme',
    'events' => [
        'order.paid',
        'order.canceled',
        'charge.paid'
    ]
]);

// Listar webhooks
$webhooks = Pagarme::webhooks()->list();

// Atualizar webhook
$webhook = Pagarme::webhooks()->update('hook_123', [
    'url' => 'https://seusite.com/new-webhook-url'
]);

// Deletar webhook
Pagarme::webhooks()->delete('hook_123');
```

#### Segurança e Validação de Webhooks

**IMPORTANTE**: Pagar.me NÃO suporta validação HMAC nativamente. O pacote oferece múltiplas camadas de segurança:

##### 1. Validação por IP Whitelist (Recomendado)

Configure os IPs permitidos no `.env`:

```env
# Lista de IPs separados por vírgula (suporta CIDR)
PAGARME_WEBHOOK_ALLOWED_IPS=203.0.113.0/24,198.51.100.10
```

##### 2. Middleware de Validação

Adicione o middleware nas suas rotas:

```php
use Kaninstein\LaravelPagarme\Middleware\ValidateWebhookSignature;

Route::post('/api/webhooks/pagarme', [WebhookController::class, 'handle'])
    ->middleware(ValidateWebhookSignature::class);
```

##### 3. Validação Manual

```php
use Kaninstein\LaravelPagarme\Services\WebhookValidator;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        $validator = WebhookValidator::fromConfig();

        // Validação completa (IP + estrutura)
        $result = $validator->validateWebhook($request);

        if (!$result['valid']) {
            Log::warning('Webhook inválido', $result['reasons']);
            return response()->json(['error' => 'Invalid webhook'], 401);
        }

        // Processar webhook
        $payload = $request->json()->all();

        match ($payload['type']) {
            'order.paid' => $this->handleOrderPaid($payload['data']),
            'charge.paid' => $this->handleChargePaid($payload['data']),
            'charge.refunded' => $this->handleChargeRefunded($payload['data']),
            default => Log::info('Evento não tratado: ' . $payload['type']),
        };

        return response()->json(['status' => 'processed']);
    }
}
```

##### 4. Validação por Estrutura do Payload

```php
$validator = WebhookValidator::fromConfig();

$payload = $request->json()->all();

if (!$validator->validatePayloadStructure($payload)) {
    // Payload não tem estrutura válida do Pagar.me
    return response()->json(['error' => 'Invalid payload'], 400);
}
```

#### Eventos Disponíveis

**Pedidos:**
- `order.paid` - Pedido pago
- `order.payment_failed` - Falha no pagamento
- `order.canceled` - Pedido cancelado
- `order.closed` - Pedido fechado

**Cobranças:**
- `charge.paid` - Cobrança paga
- `charge.payment_failed` - Falha no pagamento
- `charge.refunded` - Estorno realizado
- `charge.chargedback` - Chargeback recebido
- `charge.underpaid` - Pago a menor
- `charge.overpaid` - Pago a maior

**Antifraude:**
- `charge.antifraud_approved` - Aprovado
- `charge.antifraud_reproved` - Reprovado
- `charge.antifraud_manual` - Análise manual

Consulte a [documentação oficial](https://docs.pagar.me/reference/eventos-de-webhook-1) para lista completa.

### Facilitadores de Pagamento (SubMerchant)

Se você opera como **Facilitador de Pagamento (Subadquirente)**, pode configurar os dados de subcredenciado para serem enviados automaticamente em todas as transações.

**Adquirentes integradas**: Stone, GetNet, Cielo 1.5, Cielo 3, PagSeguro, ERede e SafraPay.

#### Configuração Automática via .env

Configure os dados do submerchant no `.env` e eles serão incluídos automaticamente em todos os pedidos:

```env
PAGARME_SUBMERCHANT_ENABLED=true
PAGARME_SUBMERCHANT_MCC=5411
PAGARME_SUBMERCHANT_FACILITATOR_CODE=123456789
PAGARME_SUBMERCHANT_CODE=STORE-001
PAGARME_SUBMERCHANT_NAME="Minha Loja"
PAGARME_SUBMERCHANT_LEGAL_NAME="Minha Empresa LTDA"
PAGARME_SUBMERCHANT_DOCUMENT=12345678000190
PAGARME_SUBMERCHANT_TYPE=company
```

#### Uso Manual com DTO

```php
use Kaninstein\LaravelPagarme\DTOs\SubMerchantDTO;
use Kaninstein\LaravelPagarme\DTOs\PhoneDTO;
use Kaninstein\LaravelPagarme\DTOs\AddressDTO;

$submerchant = new SubMerchantDTO(
    merchantCategoryCode: '5411', // MCC - 4 dígitos
    paymentFacilitatorCode: '123456789',
    code: 'STORE-001',
    name: 'Loja do João',
    document: '12345678000190',
    type: 'company',
    legalName: 'João Comércio LTDA',
    phone: PhoneDTO::brazilian('11', '987654321'),
    address: AddressDTO::brazilian(
        street: 'Rua Exemplo, 123',
        zipCode: '01234567',
        city: 'São Paulo',
        state: 'SP'
    )
);

// Incluir no pedido
$order = new OrderDTO(
    items: $items,
    customer: $customer,
    payments: $payments,
    submerchant: $submerchant
);
```

#### Controle por Pedido

```php
// Desabilitar submerchant para um pedido específico
// (mesmo que esteja habilitado no config)
$order->withoutSubmerchant();

// Ou definir submerchant específico para um pedido
$order->withSubmerchant($customSubmerchant);
```

Veja mais exemplos em `examples/submerchant-example.php`.

## Métodos de Pagamento

### PIX

```php
use Kaninstein\LaravelPagarme\DTOs\PixPaymentDTO;
use Kaninstein\LaravelPagarme\DTOs\PaymentDTO;

// PIX com expiração de 1 hora
$pixPayment = PixPaymentDTO::withExpiresIn(3600);

$payment = PaymentDTO::pix($pixPayment);

$order = OrderDTO::create($items, $customer, $payment)
    ->withIp('192.168.1.1'); // IP é obrigatório para PIX

$result = Pagarme::orders()->create($order->toArray());

// QR Code estará em: $result['charges'][0]['last_transaction']['qr_code']
// QR Code URL: $result['charges'][0]['last_transaction']['qr_code_url']
```

### Boleto

```php
use Kaninstein\LaravelPagarme\DTOs\BoletoPaymentDTO;
use Kaninstein\LaravelPagarme\DTOs\PaymentDTO;

$boleto = BoletoPaymentDTO::create(
    dueAt: new \DateTime('+7 days'),
    instructions: 'Não aceitar após vencimento',
    documentNumber: '123456'
);

// Adicionar multa de 2%
$boleto->withFine(2.0);

// Adicionar juros de 1% ao mês
$boleto->withInterest(1.0);

$payment = PaymentDTO::boleto($boleto);

$order = OrderDTO::create($items, $customer, $payment);
$result = Pagarme::orders()->create($order->toArray());

// URL do boleto: $result['charges'][0]['last_transaction']['url']
// Linha digitável: $result['charges'][0]['last_transaction']['line']
```

### Cartão de Débito

```php
use Kaninstein\LaravelPagarme\DTOs\DebitCardDTO;
use Kaninstein\LaravelPagarme\DTOs\DebitCardPaymentDTO;
use Kaninstein\LaravelPagarme\DTOs\PaymentDTO;

$debitCard = DebitCardDTO::fromToken($token['id']);

$payment = PaymentDTO::debitCard(
    DebitCardPaymentDTO::withCard($debitCard)
);

$order = OrderDTO::create($items, $customer, $payment);
$result = Pagarme::orders()->create($order->toArray());
```

### Voucher (Vale Alimentação/Refeição)

```php
use Kaninstein\LaravelPagarme\DTOs\VoucherCardDTO;
use Kaninstein\LaravelPagarme\DTOs\VoucherPaymentDTO;
use Kaninstein\LaravelPagarme\DTOs\PaymentDTO;

$voucherCard = VoucherCardDTO::fromToken($token['id']);

$payment = PaymentDTO::voucher(
    VoucherPaymentDTO::withCard($voucherCard, 'RESTAURANTE')
);

$order = OrderDTO::create($items, $customer, $payment);
$result = Pagarme::orders()->create($order->toArray());
```

Marcas de voucher suportadas: **Alelo**, **Sodexo**, **Ticket**, **VR**, **Pluxee**.

## Tokenização

O pacote suporta tokenização de cartões para maior segurança. Veja o guia completo em [TOKENIZATION_GUIDE.md](TOKENIZATION_GUIDE.md).

```php
use Kaninstein\LaravelPagarme\Services\TokenService;

$tokenService = new TokenService();

// Criar token de cartão
$token = $tokenService->createCardToken([
    'number' => '4111111111111111',
    'holder_name' => 'JOAO SILVA',
    'exp_month' => 12,
    'exp_year' => 2030,
    'cvv' => '123',
]);

// Usar token no pagamento
$creditCard = CreditCardDTO::fromToken($token['id']);
```

## Simuladores de Teste

O pacote inclui testes completos para todos os simuladores da Pagar.me. Consulte [CODIGOS_RETORNO.md](CODIGOS_RETORNO.md) para a lista completa de cartões de teste e cenários.

### Cartões de Teste (Crédito/Débito/Voucher)

```php
// Sucesso
'4000000000000010'

// Não autorizado
'4000000000000028'

// Processing → Sucesso
'4000000000000036'

// Processing → Falha
'4000000000000044'

// CVV começando com 6 → Recusa do emissor
$cvv = '6XX' // qualquer número começando com 6

// Documento 11111111111 → Bloqueio antifraude
$cpf = '11111111111'

// Valores R$ 1,30 a R$ 1,60 → Chargeback Guarantee
$amount = 130; // 130 a 160 centavos
```

### PIX - Regras de Simulação

- **Valores até R$ 500,00**: Sucesso (pending → paid)
- **Valores acima R$ 500,00**: Falha

### Boleto - Regras por CEP

- **CEP 01046010**: Pagamento a menor
- **CEP 57400000**: Pagamento a maior
- **CEP 70070300**: Não conciliado
- **Outros CEPs**: Pagamento total

## Códigos de Retorno ABECS

O pacote mapeia todos os códigos de retorno ABECS (Associação Brasileira das Empresas de Cartões de Crédito e Serviços) com mais de 60 códigos padronizados.

```php
use Kaninstein\LaravelPagarme\Enums\AbecsReturnCode;
use Kaninstein\LaravelPagarme\Exceptions\TransactionDeclinedException;

try {
    $order = Pagarme::orders()->create([...]);
} catch (TransactionDeclinedException $e) {
    // Informações detalhadas da recusa
    $abecsCode = $e->getAbecsCode(); // Enum AbecsReturnCode
    $reason = $e->getDeclineReason(); // Mensagem em português
    $canRetry = $e->canRetry(); // Se pode tentar novamente

    // Verificações específicas
    if ($e->isFraudRelated()) {
        // Bloqueio por fraude
    }

    if ($e->isInsufficientFunds()) {
        // Saldo insuficiente
    }

    if ($e->isInvalidCard()) {
        // Cartão inválido
    }

    // Informações completas
    $info = $e->getDeclineInfo();
    // [
    //     'abecs_code' => '1002',
    //     'message' => 'Transação não autorizada - suspeita de fraude',
    //     'can_retry' => false,
    //     'acquirer_code' => 'XX',
    //     'gateway_code' => 'YY'
    // ]
}
```

### Principais Códigos ABECS

| Código | Descrição | Retry? |
|--------|-----------|--------|
| 0000 | Aprovado | - |
| 1000 | Não autorizado | ❌ |
| 1002 | Suspeita de fraude | ❌ |
| 1016 | Saldo insuficiente | ❌ |
| 1051 | Cartão vencido | ❌ |
| 1057 | Transação não permitida | ❌ |
| 1070 | Recusado antifraude | ❌ |
| 9111 | Timeout do emissor | ✅ |

Consulte [CODIGOS_RETORNO.md](CODIGOS_RETORNO.md) para a lista completa de todos os 60+ códigos.

## Tratamento de Erros

O pacote lança exceções específicas para diferentes tipos de erro:

```php
use Kaninstein\LaravelPagarme\Exceptions\AuthenticationException;
use Kaninstein\LaravelPagarme\Exceptions\ValidationException;
use Kaninstein\LaravelPagarme\Exceptions\NotFoundException;
use Kaninstein\LaravelPagarme\Exceptions\PagarmeException;

try {
    $customer = Pagarme::customers()->create([...]);
} catch (ValidationException $e) {
    // Erro de validação (422)
    $errors = $e->getErrors();
    $message = $e->getMessage();
} catch (AuthenticationException $e) {
    // Erro de autenticação (401)
} catch (NotFoundException $e) {
    // Recurso não encontrado (404)
} catch (PagarmeException $e) {
    // Outros erros da API
    $response = $e->getResponse();
}
```

## Tipos de Exceção

- `BadRequestException` - Código 400
- `AuthenticationException` - Código 401
- `ForbiddenException` - Código 403
- `NotFoundException` - Código 404
- `PreconditionFailedException` - Código 412
- `ValidationException` - Código 422
- `TooManyRequestsException` - Código 429
- `PagarmeException` - Exceção base para outros erros

## Injeção de Dependência

Você pode injetar os services diretamente nos seus controllers:

```php
use Kaninstein\LaravelPagarme\Services\CustomerService;
use Kaninstein\LaravelPagarme\Services\OrderService;

class PaymentController extends Controller
{
    public function __construct(
        private CustomerService $customerService,
        private OrderService $orderService
    ) {}

    public function createOrder()
    {
        $customer = $this->customerService->create([...]);
        $order = $this->orderService->create([...]);

        return response()->json($order);
    }
}
```

## Logging

Para habilitar logging de requisições e respostas:

```env
PAGARME_LOGGING_ENABLED=true
PAGARME_LOGGING_CHANNEL=stack
```

## Testes

```bash
composer test
```

## Changelog

Consulte [CHANGELOG](CHANGELOG.md) para mais informações sobre mudanças recentes.

## Contribuindo

Contribuições são bem-vindas! Por favor, consulte [CONTRIBUTING](CONTRIBUTING.md) para detalhes.

## Segurança

Se você descobrir alguma questão de segurança, por favor envie um email para falecom@joaopedrocoelho.com.br ao invés de usar o issue tracker.

## Créditos

- [João Pedro Coelho](https://github.com/kaninstein)

## Licença

The MIT License (MIT). Consulte [License File](LICENSE.md) para mais informações.

## Documentação Adicional

- 📖 [TOKENIZATION_GUIDE.md](TOKENIZATION_GUIDE.md) - Guia completo de tokenização de cartões
- 📖 [CODIGOS_RETORNO.md](CODIGOS_RETORNO.md) - Lista completa de códigos ABECS e simuladores
- 📖 [STRUCTURE.md](STRUCTURE.md) - Estrutura do pacote e arquitetura
- 📖 [CONTRIBUTING.md](CONTRIBUTING.md) - Guia de contribuição

## Roadmap

- ✅ Suporte completo a PIX
- ✅ Suporte a Boleto
- ✅ Suporte a Cartão de Débito
- ✅ Suporte a Voucher (VR, Alelo, Sodexo, Ticket)
- ✅ Mapeamento de códigos ABECS
- ✅ Testes automatizados completos
- ✅ Suporte a SubMerchant
- ✅ Tokenização de cartões
- ✅ Validação e segurança de webhooks (IP whitelist + estrutura)
- ✅ Comando Artisan para configuração automática de webhooks
- [ ] Suporte a assinaturas/recorrência
- [ ] Suporte a split de pagamentos
- [ ] Suporte a Google Pay
- [ ] Suporte a Apple Pay
