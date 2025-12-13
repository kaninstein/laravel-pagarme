# Guia de Tokenização de Cartões - Pagar.me

## 📋 Duas Formas de Tokenizar Cartões

### 1️⃣ tokenizecard.js (Frontend - Recomendado)
### 2️⃣ TokenService API (Backend)

---

## 🆚 Comparação Detalhada

| Característica | tokenizecard.js (Frontend) | TokenService API (Backend) |
|---|---|---|
| **PCI DSS** | ✅ Não requer (dados não passam pelo servidor) | ❌ Requer compliance |
| **Segurança** | ✅✅ Máxima (dados direto para Pagar.me) | ⚠️ Dados trafegam pelo servidor |
| **Complexidade** | ✅ Simples (apenas JavaScript) | ⚠️ Requer HTTPS + segurança |
| **Chave Usada** | PUBLIC_KEY | PUBLIC_KEY |
| **Onde Processar** | Navegador do cliente | Servidor backend |
| **Latência** | ⚡ Rápida (direto para Pagar.me) | 🐌 Mais lenta (servidor intermediário) |
| **Custo** | 💰 Menor (sem tráfego servidor) | 💰💰 Maior (tráfego + processamento) |
| **Casos de Uso** | Checkout web padrão | APIs, mobile apps, integrações |
| **Domínio** | ✅ Precisa cadastrar no dashboard | ❌ Não precisa |
| **JavaScript** | ✅ Necessário | ❌ Não necessário |

---

## ✅ Quando Usar tokenizecard.js (RECOMENDADO)

### Cenários Ideais:
1. **Checkout Web Padrão**
   - Formulário de pagamento em site/e-commerce
   - Usuário preenche dados do cartão no navegador
   - Exemplo: Loja virtual, sistema de assinaturas web

2. **Evitar PCI DSS Compliance**
   - Seu servidor não é PCI DSS compliant
   - Quer evitar custos e burocracia de compliance
   - Dados sensíveis não devem passar pelo seu servidor

3. **Melhor Experiência do Usuário**
   - Validação em tempo real
   - Detecção automática de bandeira
   - Formatação automática do cartão

4. **Menor Custo Operacional**
   - Reduz tráfego no servidor
   - Não precisa processar dados sensíveis
   - Menos responsabilidade com segurança

### Exemplo de Fluxo:
```
┌─────────┐      ┌──────────────┐      ┌──────────┐
│ Browser │─────▶│ Pagar.me API │─────▶│ Servidor │
│ (JS)    │Token │ (Tokenização)│Token │ (Laravel)│
└─────────┘      └──────────────┘      └──────────┘
    ▲                                         │
    │                                         ▼
    └────────── Dados do cartão ──────────────┘
           (nunca passa pelo servidor)
```

### Vantagens:
- ✅ **Segurança Máxima**: Dados do cartão nunca passam pelo seu servidor
- ✅ **Sem PCI DSS**: Não precisa certificação de segurança
- ✅ **Simples**: Apenas adicionar script JavaScript
- ✅ **Validação Automática**: Bandeira, formato, etc.
- ✅ **Melhor Performance**: Menos latência

### Desvantagens:
- ❌ Requer JavaScript habilitado
- ❌ Domínio precisa estar cadastrado no dashboard
- ❌ Apenas para aplicações web (não funciona em CLI/APIs puras)

---

## 🔧 Quando Usar TokenService API (Backend)

### Cenários Ideais:
1. **APIs REST Puras**
   - Backend sem frontend (headless)
   - Microsserviços
   - Integrações B2B

2. **Aplicações Mobile Nativas**
   - Apps iOS/Android que não usam WebView
   - SDKs nativos
   - Aplicações híbridas específicas

3. **Processamento em Lote**
   - Import de cartões em massa
   - Migrações de sistemas
   - Ferramentas administrativas

4. **Ambientes Sem JavaScript**
   - CLI tools
   - Cronjobs
   - Workers/Background jobs

5. **Servidor já é PCI Compliant**
   - Infraestrutura já certificada
   - Custos de compliance já pagos
   - Processos de segurança estabelecidos

### Exemplo de Fluxo:
```
┌─────────┐      ┌──────────┐      ┌──────────────┐
│ Cliente │─────▶│ Servidor │─────▶│ Pagar.me API │
│         │Dados │ (Laravel)│Dados │ (Tokenização)│
└─────────┘      └──────────┘      └──────────────┘
                      ⚠️
              Dados passam aqui!
           (Requer PCI Compliance)
```

### Vantagens:
- ✅ Funciona sem JavaScript
- ✅ Controle total do fluxo
- ✅ Não depende de domínio cadastrado
- ✅ Funciona em qualquer ambiente

### Desvantagens:
- ❌ **REQUER PCI DSS**: Servidor deve ser compliant
- ❌ **Maior Responsabilidade**: Dados sensíveis no servidor
- ❌ **Maior Custo**: Infraestrutura + compliance + segurança
- ❌ **Mais Complexo**: Implementação e manutenção

---

## 🎯 Matriz de Decisão

| Seu Caso | Solução Recomendada |
|---|---|
| E-commerce padrão | ✅ tokenizecard.js |
| Landing page de vendas | ✅ tokenizecard.js |
| Sistema de assinaturas web | ✅ tokenizecard.js |
| Checkout de marketplace | ✅ tokenizecard.js |
| **API REST pura** | ⚙️ TokenService API |
| **App mobile nativo** | ⚙️ TokenService API |
| **Processamento batch** | ⚙️ TokenService API |
| **CLI/Workers** | ⚙️ TokenService API |
| Já tem PCI compliance | ⚙️ Ambos (escolha por conveniência) |
| **Não tem PCI compliance** | ✅ tokenizecard.js (OBRIGATÓRIO) |

---

## 📝 Exemplos de Implementação

### tokenizecard.js (Frontend)

**HTML:**
```html
<form data-pagarmecheckout-form action="/process-payment" method="POST">
    <input data-pagarmecheckout-element="holder_name" name="holder-name">
    <input data-pagarmecheckout-element="number" name="card-number">
    <input data-pagarmecheckout-element="exp_month" name="exp-month">
    <input data-pagarmecheckout-element="exp_year" name="exp-year">
    <input data-pagarmecheckout-element="cvv" name="cvv">
    <button type="submit">Pagar</button>
</form>

<script src="https://checkout.pagar.me/v1/tokenizecard.js"
        data-pagarmecheckout-app-id="pk_test_YOUR_PUBLIC_KEY">
</script>

<script>
    PagarmeCheckout.init(
        function success(data) {
            console.log('Token:', data.pagarmetoken);
            return true; // Continua submit
        },
        function fail(error) {
            console.error('Error:', error);
            return false; // Aborta submit
        }
    );
</script>
```

**Backend (Laravel):**
```php
public function processPayment(Request $request)
{
    $token = $request->input('pagarmetoken'); // Token do JS

    $card = CreditCardDTO::fromToken($token);
    $payment = PaymentDTO::creditCard(
        CreditCardPaymentDTO::withCard($card)
    );

    $order = OrderDTO::create($items, $customer, $payment);
    $result = Pagarme::orders()->create($order->toArray());
}
```

### TokenService API (Backend)

```php
use Kaninstein\LaravelPagarme\Facades\Pagarme;

// Tokenizar cartão no backend
$token = Pagarme::tokens()->createCardToken([
    'number' => '4111111111111111',
    'holder_name' => 'JOÃO SILVA',
    'exp_month' => 12,
    'exp_year' => 2030,
    'cvv' => '123',
]);

// Usar token para criar pagamento
$card = CreditCardDTO::fromToken($token['id']);
$payment = PaymentDTO::creditCard(
    CreditCardPaymentDTO::withCard($card)
);
```

---

## ⚠️ Avisos Importantes

### tokenizecard.js:
1. ✅ **Sempre use PUBLIC_KEY** (pk_test_* ou pk_*)
2. ❌ **NUNCA envie SECRET_KEY** para o frontend
3. ✅ **Cadastre seu domínio** no dashboard Pagar.me
4. ✅ **Chame init() no startup** da aplicação
5. ✅ **Elementos devem estar no DOM** antes do init()

### TokenService API:
1. ⚠️ **Servidor DEVE ser PCI DSS compliant**
2. 🔒 **Use HTTPS** obrigatoriamente
3. 🔒 **Nunca logue dados de cartão**
4. 🔒 **Implemente rate limiting**
5. 🔒 **Valide origem das requisições**
6. 🔒 **Monitore tentativas suspeitas**

---

## 🔐 Segurança e Compliance

### Checklist PCI DSS (TokenService API)

Se você optar por usar TokenService API (backend), você DEVE:

- [ ] Servidor com certificado SSL/TLS válido
- [ ] Firewall configurado corretamente
- [ ] Logs de acesso e auditoria
- [ ] Criptografia de dados em trânsito
- [ ] Criptografia de dados em repouso
- [ ] Política de senhas forte
- [ ] Autenticação de dois fatores
- [ ] Monitoramento de segurança 24/7
- [ ] Testes de penetração regulares
- [ ] Treinamento de segurança para equipe
- [ ] Política de resposta a incidentes
- [ ] Backup e recuperação de desastres

**Custo estimado de compliance**: R$ 50.000 - R$ 500.000/ano

### Sem PCI DSS (tokenizecard.js)

Com tokenizecard.js, você NÃO precisa de:
- ✅ Certificação PCI DSS
- ✅ Auditorias de segurança caras
- ✅ Infraestrutura complexa
- ✅ Processos burocráticos

**Custo**: Praticamente ZERO

---

## 🎓 Recomendação Final

### Para 95% dos Casos: Use tokenizecard.js

**Por quê?**
- Mais seguro
- Mais barato
- Mais simples
- Sem compliance
- Melhor performance
- Recomendado pela Pagar.me

### Use TokenService API APENAS se:
- Você tem API REST pura sem frontend
- Aplicativo mobile nativo (não WebView)
- Já é PCI DSS compliant
- Processamento em lote/background
- Ambiente sem JavaScript

---

## 📚 Recursos Adicionais

**Documentação:**
- [tokenizecard.js](https://docs.pagar.me/docs/tokenizecard-js)
- [PCI DSS Requirements](https://www.pcisecuritystandards.org/)

**Exemplos no Pacote:**
- `examples/tokenizecard-js-example.html` - Frontend completo
- `examples/process-tokenized-payment-backend.php` - Backend Laravel
- `examples/token-example.php` - TokenService API

**Suporte:**
- GitHub: [kaninstein/laravel-pagarme](https://github.com/kaninstein/laravel-pagarme)
- Pagar.me: https://pagar.me/contato
