<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pagarme API Keys
    |--------------------------------------------------------------------------
    |
    | Suas chaves de API da Pagarme. Use as chaves de teste (sk_test_*, pk_test_*)
    | para desenvolvimento e as chaves de produção (sk_*, pk_*) em produção.
    |
    */
    'secret_key' => env('PAGARME_SECRET_KEY'),
    'public_key' => env('PAGARME_PUBLIC_KEY'),

    /*
    |--------------------------------------------------------------------------
    | API Base URL
    |--------------------------------------------------------------------------
    |
    | URL base da API Pagarme v5. O mesmo endpoint é usado para teste e produção.
    | O ambiente é determinado pelo tipo de chave (test ou production).
    |
    */
    'api_url' => env('PAGARME_API_URL', 'https://api.pagar.me/core/v5'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | Tempo máximo de espera para requisições HTTP em segundos.
    |
    */
    'timeout' => env('PAGARME_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configuração de tentativas em caso de falha temporária.
    |
    */
    'retry' => [
        'times' => env('PAGARME_RETRY_TIMES', 3),
        'sleep' => env('PAGARME_RETRY_SLEEP', 1000), // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações de segurança e validação de webhooks da Pagar.me.
    |
    | IMPORTANTE: Pagar.me não suporta validação HMAC nativamente.
    | Use validação por IP whitelist como método principal de segurança.
    |
    */
    'webhook' => [
        // URL para receber webhooks (usado pelo comando setup-webhooks)
        'url' => env('PAGARME_WEBHOOK_URL'),

        // Habilitar validação de webhooks (recomendado em produção)
        'enabled' => env('PAGARME_WEBHOOK_VALIDATION_ENABLED', true),

        // Validar webhooks em ambiente local
        'validate_in_local' => env('PAGARME_WEBHOOK_VALIDATE_IN_LOCAL', false),

        // Tolerância de tempo para webhooks (em segundos)
        'tolerance' => env('PAGARME_WEBHOOK_TOLERANCE', 300),

        /*
        |--------------------------------------------------------------------------
        | IP Whitelist (Recomendado)
        |--------------------------------------------------------------------------
        |
        | Lista de IPs permitidos para receber webhooks do Pagar.me.
        | Suporta notação CIDR para ranges (ex: 192.168.1.0/24)
        |
        | Deixe vazio [] para aceitar qualquer IP (não recomendado em produção)
        | Contate o suporte Pagar.me para obter os IPs oficiais atualizados.
        |
        */
        'validate_ip' => env('PAGARME_WEBHOOK_VALIDATE_IP', true),
        'allowed_ips' => array_filter(explode(',', env('PAGARME_WEBHOOK_ALLOWED_IPS', ''))),

        /*
        |--------------------------------------------------------------------------
        | HMAC Signature Validation (Opcional)
        |--------------------------------------------------------------------------
        |
        | ATENÇÃO: Pagar.me NÃO suporta HMAC signature nativamente!
        | Use apenas se você implementou um sistema customizado de assinatura.
        |
        | Se habilitado, você precisa configurar:
        | - secret: Chave secreta para HMAC (pode usar a secret_key da API)
        | - algorithm: sha256, sha1 ou sha512 (padrão: sha256)
        | - header: Nome do header HTTP contendo a assinatura
        |
        */
        'validate_signature' => env('PAGARME_WEBHOOK_VALIDATE_SIGNATURE', false),
        'secret' => env('PAGARME_WEBHOOK_SECRET', null), // Deixe null para usar secret_key
        'signature_algorithm' => env('PAGARME_WEBHOOK_SIGNATURE_ALGORITHM', 'sha256'),
        'signature_header' => env('PAGARME_WEBHOOK_SIGNATURE_HEADER', 'X-Hub-Signature-256'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Habilitar logs de requisições e respostas para debug.
    |
    */
    'logging' => [
        'enabled' => env('PAGARME_LOGGING_ENABLED', false),
        'channel' => env('PAGARME_LOGGING_CHANNEL', 'stack'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SubMerchant (Facilitadores de Pagamento)
    |--------------------------------------------------------------------------
    |
    | Configurações para operação como Facilitador de Pagamento (Subadquirente).
    | Por determinação do Banco Central e das bandeiras, clientes de E-commerce
    | que se enquadram nesta categoria devem enviar dados de subcredenciados.
    |
    | Adquirentes integradas: Stone, GetNet, Cielo 1.5, Cielo 3, PagSeguro,
    | ERede e SafraPay.
    |
    | IMPORTANTE: Para operar como Subadquirente na Stone, realize o
    | credenciamento antes de iniciar a integração técnica.
    |
    */
    'submerchant' => [
        // Habilitar/desabilitar envio de dados de submerchant
        'enabled' => env('PAGARME_SUBMERCHANT_ENABLED', false),

        // MCC do subcredenciado - Código de categoria do estabelecimento (4 dígitos)
        'merchant_category_code' => env('PAGARME_SUBMERCHANT_MCC'),

        // Código de identificação do Facilitador cadastrado nas bandeiras
        'payment_facilitator_code' => env('PAGARME_SUBMERCHANT_FACILITATOR_CODE'),

        // Código de identificação do subcredenciado para o facilitador
        'code' => env('PAGARME_SUBMERCHANT_CODE'),

        // Nome do subcredenciado
        'name' => env('PAGARME_SUBMERCHANT_NAME'),

        // Razão social do subcredenciado
        'legal_name' => env('PAGARME_SUBMERCHANT_LEGAL_NAME'),

        // CPF ou CNPJ do subcredenciado
        'document' => env('PAGARME_SUBMERCHANT_DOCUMENT'),

        // Tipo: 'individual' ou 'company'
        'type' => env('PAGARME_SUBMERCHANT_TYPE', 'company'),

        // Telefone do subcredenciado
        'phone' => [
            'country_code' => env('PAGARME_SUBMERCHANT_PHONE_COUNTRY', '55'),
            'area_code' => env('PAGARME_SUBMERCHANT_PHONE_AREA'),
            'number' => env('PAGARME_SUBMERCHANT_PHONE_NUMBER'),
        ],

        // Endereço do subcredenciado
        'address' => [
            'street' => env('PAGARME_SUBMERCHANT_ADDRESS_STREET'),
            'number' => env('PAGARME_SUBMERCHANT_ADDRESS_NUMBER'),
            'complement' => env('PAGARME_SUBMERCHANT_ADDRESS_COMPLEMENT'),
            'neighborhood' => env('PAGARME_SUBMERCHANT_ADDRESS_NEIGHBORHOOD'),
            'city' => env('PAGARME_SUBMERCHANT_ADDRESS_CITY'),
            'state' => env('PAGARME_SUBMERCHANT_ADDRESS_STATE'),
            'country' => env('PAGARME_SUBMERCHANT_ADDRESS_COUNTRY', 'BR'),
            'zip_code' => env('PAGARME_SUBMERCHANT_ADDRESS_ZIP'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | BIN (Bank Identifier Number) Cache
    |--------------------------------------------------------------------------
    |
    | BIN information is cached to reduce API calls and improve performance.
    | Cache TTL is in seconds. Default is 1 hour (3600 seconds).
    |
    */
    'bin_cache_ttl' => env('PAGARME_BIN_CACHE_TTL', 3600),
];
