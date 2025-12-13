# Códigos de Retorno e Motivos de Recusa - Pagar.me

Este documento detalha todos os códigos de retorno ABECS, cartões de teste e como lidar com recusas no pacote Laravel Pagar.me.

## 📚 Índice

- [Códigos ABECS](#códigos-abecs)
- [Cartões de Teste](#cartões-de-teste)
- [Tratamento de Recusas](#tratamento-de-recusas)
- [Simulador PSP](#simulador-psp)
- [Exemplos de Uso](#exemplos-de-uso)

---

## 🎯 Códigos ABECS

A ABECS (Associação Brasileira das Empresas de Cartões de Crédito e Serviços) padronizou os códigos de retorno das adquirentes. O pacote mapeia todos esses códigos no enum `AbecsReturnCode`.

### Transações Aprovadas (0000-0013)

| Código | Descrição | Retry? |
|--------|-----------|--------|
| `0000` | Transação aprovada com sucesso | ✅ |
| `0001` | Transação aprovada com valor parcial | ✅ |
| `0002` | Transação aprovada VIP | ✅ |
| `0013` | Transação aprovada offline | ✅ |

### Recusas Genéricas (1000-1099)

| Código | Descrição | Retry? | Categoria |
|--------|-----------|--------|-----------|
| `1000` | Transação não autorizada - Contate o emissor | ✅ | Genérica |
| `1001` | Cartão vencido ou data incorreta | ❌ | Cartão inválido |
| `1002` | Transação com suspeita de fraude | ❌ | Fraude |
| `1016` | Saldo/limite insuficiente | ✅ | Saldo |
| `1019` | CVV inválido | ✅ | Dados |
| `1032` | Cartão bloqueado | ❌ | Cartão bloqueado |
| `1033` | Cartão vencido | ❌ | Cartão inválido |
| `1043` | Fraude confirmada | ❌ | Fraude |
| `1070` | Transação rejeitada pelo antifraude | ❌ | Antifraude |
| `1071` | Falha na autenticação 3D Secure | ❌ | 3DS |

### Erros Internos (5000-5097)

| Código | Descrição |
|--------|-----------|
| `5000` | Erro genérico |
| `5002` | Meio de pagamento não habilitado |
| `5021` | ID do pedido duplicado |
| `5025` | CVV obrigatório |

### Erros de Sistema (9000-9999)

| Código | Descrição |
|--------|-----------|
| `9111` | Timeout - Emissor não respondeu |
| `9200` | Recusa irreversível - NÃO tente novamente |
| `9999` | Erro de sistema |

---

## 🧪 Cartões de Teste

### Cartões Específicos da Pagar.me

Use estes cartões no ambiente **sandbox** para testar diferentes cenários:

| Número do Cartão | Cenário | Status Final |
|------------------|---------|--------------|
| `4000000000000010` | ✅ Operação realizada com sucesso | `paid` |
| `4000000000000028` | ❌ Transação não autorizada | `failed` |
| `4000000000000036` | ⏳ Erro inicial → Confirmação posterior | `paid` |
| `4000000000000044` | ⏳ Erro → Falha confirmada | `failed` |
| `4000000000000077` | ♻️ Sucesso → Erro ao cancelar → Estornada | `paid` |
| `4000000000000051` | ⏳ Pendente → Cancelado | `canceled` |
| `4000000000000069` | 💳 Pago → Chargeback | `chargedback` |
| **Outros números** | ❌ Não autorizado | `failed` |

### Bandeiras Suportadas

| Bandeira | Número de Teste |
|----------|-----------------|
| Visa | `4111111111111111` |
| Visa | `4242424242424242` |
| Mastercard | `5555555555554444` |
| Elo | `6362970000457013` |

**Regras importantes:**
- ✅ Use CVV `123` para aprovação
- ❌ Use CVV começando com `6` (ex: `612`) para simular recusa do emissor
- 📅 Use qualquer data futura para expiração
- 👤 Use documento `11111111111` para simular bloqueio por antifraude

---

## 🛡️ Tratamento de Recusas

### Usando a Exception `TransactionDeclinedException`

```php
use Kaninstein\LaravelPagarme\Exceptions\TransactionDeclinedException;
use Kaninstein\LaravelPagarme\Enums\AbecsReturnCode;

try {
    $result = Pagarme::orders()->create($order->toArray());
} catch (TransactionDeclinedException $e) {
    // Obter informações completas da recusa
    $declineInfo = $e->getDeclineInfo();

    /*
    Array com:
    - abecs_code: Código ABECS (ex: '1016')
    - abecs_message: Mensagem humanizada (ex: 'Saldo/limite insuficiente')
    - acquirer_code: Código da adquirente
    - acquirer_message: Mensagem da adquirente
    - gateway_code: Código do gateway
    - gateway_message: Mensagem do gateway
    - can_retry: Se pode tentar novamente (true/false)
    - is_fraud: Se é relacionado a fraude (true/false)
    */

    // Verificar tipo específico de recusa
    if ($e->isInsufficientFunds()) {
        return response()->json([
            'error' => 'Saldo insuficiente. Use outro cartão.',
            'can_retry' => true
        ], 402);
    }

    if ($e->isFraudRelated()) {
        Log::warning('Tentativa de fraude detectada', [
            'order_id' => $order->id,
            'customer' => $customer->email,
        ]);

        return response()->json([
            'error' => 'Transação bloqueada por segurança.',
            'can_retry' => false
        ], 403);
    }

    if ($e->isInvalidCard()) {
        return response()->json([
            'error' => 'Cartão inválido, bloqueado ou vencido.',
            'can_retry' => false
        ], 400);
    }

    // Verificar se pode tentar novamente
    if ($e->canRetry()) {
        return response()->json([
            'error' => $e->getDeclineReason(),
            'can_retry' => true
        ], 402);
    } else {
        return response()->json([
            'error' => $e->getDeclineReason(),
            'can_retry' => false
        ], 400);
    }
}
```

### Verificando Código ABECS Específico

```php
$abecsCode = $e->getAbecsCode();

if ($abecsCode === AbecsReturnCode::DECLINED_INSUFFICIENT_FUNDS) {
    // Saldo insuficiente
    $message = 'Seu cartão não tem saldo suficiente';
}

if ($abecsCode === AbecsReturnCode::DECLINED_LOST_CARD) {
    // Cartão reportado como perdido
    $message = 'Este cartão foi reportado como perdido';
}

// Obter mensagem humanizada
$message = $abecsCode->getMessage();

// Verificar categoria
$isApproved = $abecsCode->isApproved();      // Começa com 0
$isDeclined = $abecsCode->isDeclined();      // Começa com 1
$isInternalError = $abecsCode->isInternalError(); // Começa com 5
$isSystemError = $abecsCode->isSystemError();     // Começa com 9
```

### Processando Resposta da Order/Charge

```php
$result = Pagarme::orders()->create($order->toArray());

$charge = $result['charges'][0] ?? null;

if ($charge && $charge['status'] === 'failed') {
    // Criar exception a partir da charge
    $exception = TransactionDeclinedException::fromCharge($charge);

    $declineInfo = $exception->getDeclineInfo();

    // Log para análise
    Log::info('Transação recusada', [
        'order_id' => $result['id'],
        'charge_id' => $charge['id'],
        'abecs_code' => $declineInfo['abecs_code'],
        'reason' => $declineInfo['abecs_message'],
        'can_retry' => $declineInfo['can_retry'],
    ]);
}
```

---

## 🔬 Simulador PSP

Para clientes PSP (Payment Service Provider), há regras específicas de teste:

### Aprovações
- ✅ Use qualquer cartão Luhn-válido com CVV `123`
- Exemplo: `4000000000000010` com CVV `123`

### Recusas do Emissor
- ❌ Envie CVV começando com `6`
- Exemplo: CVV `612`, `623`, `645`

### Bloqueio por Antifraude
- 🚫 Use documento do comprador: `11111111111`

### Chargeback Guarantee
- 💰 Use valores em centavos entre **130 e 160** (R$ 1,30 a R$ 1,60)
- Exemplo: `145` centavos = R$ 1,45

**Prioridade:** Se enviar informação de recusa, ela sempre terá prioridade sobre outros status.

---

## 💡 Exemplos de Uso

### Exemplo 1: Tratamento Completo de Pagamento

```php
use Kaninstein\LaravelPagarme\Facades\Pagarme;
use Kaninstein\LaravelPagarme\Exceptions\TransactionDeclinedException;
use Kaninstein\LaravelPagarme\Exceptions\ValidationException;

public function processPa payment(Request $request)
{
    try {
        $order = OrderDTO::create($items, $customer, $payment);
        $result = Pagarme::orders()->create($order->toArray());

        $charge = $result['charges'][0] ?? null;

        if ($charge['status'] === 'paid') {
            return response()->json([
                'success' => true,
                'order_id' => $result['id'],
                'message' => 'Pagamento aprovado!'
            ]);
        }

        if ($charge['status'] === 'failed') {
            $exception = TransactionDeclinedException::fromCharge($charge);

            return response()->json([
                'success' => false,
                'error' => $exception->getDeclineReason(),
                'code' => $exception->getAcquirerReturnCode(),
                'can_retry' => $exception->canRetry(),
            ], 402);
        }

        // Pending, processing, etc
        return response()->json([
            'success' => false,
            'status' => $charge['status'],
            'message' => 'Pagamento em processamento'
        ]);

    } catch (ValidationException $e) {
        return response()->json([
            'success' => false,
            'error' => 'Dados inválidos',
            'errors' => $e->getErrors()
        ], 422);

    } catch (TransactionDeclinedException $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getDeclineReason(),
            'can_retry' => $e->canRetry(),
        ], 402);
    }
}
```

### Exemplo 2: Retry Logic com Backoff

```php
use Illuminate\Support\Facades\Cache;

public function attemptPayment($orderId, $paymentData, $attempt = 1)
{
    $maxAttempts = 3;
    $cacheKey = "payment_attempts:{$orderId}";

    try {
        $result = Pagarme::orders()->create($orderData);

        // Limpar tentativas em caso de sucesso
        Cache::forget($cacheKey);

        return $result;

    } catch (TransactionDeclinedException $e) {

        // Não retenta se não for permitido
        if (!$e->canRetry()) {
            throw $e;
        }

        // Não retenta fraudes
        if ($e->isFraudRelated()) {
            Log::alert('Fraude detectada', ['order' => $orderId]);
            throw $e;
        }

        // Incrementa tentativas
        $attempts = Cache::increment($cacheKey, 1);

        if ($attempts >= $maxAttempts) {
            Cache::forget($cacheKey);
            throw $e;
        }

        // Backoff exponencial: 2^attempt segundos
        sleep(pow(2, $attempt));

        return $this->attemptPayment($orderId, $paymentData, $attempt + 1);
    }
}
```

### Exemplo 3: Análise de Motivos de Recusa

```php
public function analyzeDeclineReasons()
{
    $declinedOrders = Order::where('status', 'failed')
        ->whereBetween('created_at', [now()->subDays(30), now()])
        ->get();

    $reasons = [];

    foreach ($declinedOrders as $order) {
        $charge = $order->pagarme_data['charges'][0] ?? null;

        if ($charge) {
            $code = $charge['last_transaction']['acquirer_return_code'] ?? 'unknown';
            $abecsCode = AbecsReturnCode::tryFrom($code);

            if ($abecsCode) {
                $reason = $abecsCode->getMessage();
                $reasons[$reason] = ($reasons[$reason] ?? 0) + 1;
            }
        }
    }

    // Ordenar por mais comum
    arsort($reasons);

    return $reasons;

    /*
    Resultado exemplo:
    [
        'Saldo/limite insuficiente' => 45,
        'Transação não autorizada' => 23,
        'Cartão bloqueado' => 12,
        'Suspeita de fraude' => 8,
        ...
    ]
    */
}
```

---

## 📖 Referências

- [Simulador de Cartão de Crédito - Pagar.me](https://docs.pagar.me/docs/simulador-de-cartão-de-crédito)
- [Simulador PSP - Pagar.me](https://docs.pagar.me/docs/simulador-psp)
- [Motivos de Recusa - Pagar.me](https://pagarme.helpjuice.com/pt_BR/p1-transações-e-estornos/transação-motivos-de-recusa-de-uma-transação)
- [Códigos ABECS](https://www.abecs.org.br/)

---

## 🧪 Executando os Testes

Para rodar os testes com todos os cartões de teste:

```bash
# Todos os testes
./vendor/bin/phpunit

# Apenas testes de cenários de cartão
./vendor/bin/phpunit tests/Feature/TestCardsScenarios.php --testdox

# Com output detalhado
./vendor/bin/phpunit tests/Feature/TestCardsScenarios.php -v
```

---

## 🤝 Contribuindo

Se encontrar algum código ABECS não mapeado ou comportamento inesperado, por favor:

1. Abra uma issue no GitHub
2. Inclua o código de retorno
3. Inclua a mensagem da adquirente
4. Descreva o cenário que gerou a recusa

---

**Última atualização:** 2025-12-13
