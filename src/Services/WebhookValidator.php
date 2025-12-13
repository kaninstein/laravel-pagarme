<?php

namespace Kaninstein\LaravelPagarme\Services;

/**
 * Webhook Security Validator
 *
 * IMPORTANT: Pagar.me does NOT natively support HMAC signature validation.
 * This class provides multiple security methods to validate webhooks:
 *
 * 1. IP Whitelist - Validate webhook origin IP (recommended)
 * 2. HMAC Signature - For custom implementations
 * 3. Payload Structure - Validate expected Pagar.me structure
 *
 * Recommended approach:
 * - Use IP whitelist validation (Pagar.me IPs)
 * - Validate payload structure
 * - Use HTTPS only
 * - Implement idempotency checks (duplicate prevention)
 */
class WebhookValidator
{
    private string $secret;
    private string $algorithm;
    private string $headerName;

    /**
     * Create a new WebhookValidator instance
     *
     * @param string $secret Secret key for HMAC validation
     * @param string $algorithm HMAC algorithm (sha256, sha1, sha512)
     * @param string $headerName HTTP header name containing signature
     */
    public function __construct(
        ?string $secret = null,
        string $algorithm = 'sha256',
        string $headerName = 'X-Hub-Signature-256'
    ) {
        $this->secret = $secret ?? config('pagarme.webhook.secret') ?? config('pagarme.secret_key');
        $this->algorithm = $algorithm;
        $this->headerName = $headerName;
    }

    /**
     * Validate webhook signature
     *
     * @param string $payload Raw webhook payload (JSON string)
     * @param string $signature Signature from webhook header
     * @return bool True if signature is valid
     */
    public function validate(string $payload, string $signature): bool
    {
        if (empty($this->secret)) {
            throw new \RuntimeException('Webhook secret key is not configured');
        }

        if (empty($signature)) {
            return false;
        }

        $expectedSignature = $this->generateSignature($payload);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Validate webhook from HTTP request
     *
     * @param \Illuminate\Http\Request $request Laravel HTTP request
     * @return bool True if signature is valid
     */
    public function validateRequest(\Illuminate\Http\Request $request): bool
    {
        $signature = $this->extractSignature($request);
        $payload = $request->getContent();

        return $this->validate($payload, $signature);
    }

    /**
     * Generate HMAC signature for payload
     *
     * @param string $payload Webhook payload
     * @return string HMAC signature
     */
    public function generateSignature(string $payload): string
    {
        $hash = hash_hmac($this->algorithm, $payload, $this->secret);

        // Format depends on algorithm
        return match ($this->algorithm) {
            'sha256' => 'sha256=' . $hash,
            'sha1' => 'sha1=' . $hash,
            'sha512' => 'sha512=' . $hash,
            default => $hash,
        };
    }

    /**
     * Extract signature from request headers
     *
     * Supports multiple header formats:
     * - X-Hub-Signature-256: sha256=hash
     * - X-Signature: hash
     * - X-Pagarme-Signature: hash
     *
     * @param \Illuminate\Http\Request $request
     * @return string Signature from header
     */
    protected function extractSignature(\Illuminate\Http\Request $request): string
    {
        // Try configured header first
        $signature = $request->header($this->headerName);

        if ($signature) {
            return $signature;
        }

        // Try common alternative headers
        $alternativeHeaders = [
            'X-Signature',
            'X-Pagarme-Signature',
            'X-Hub-Signature',
            'X-Webhook-Signature',
        ];

        foreach ($alternativeHeaders as $header) {
            $signature = $request->header($header);
            if ($signature) {
                return $signature;
            }
        }

        return '';
    }

    /**
     * Set custom secret key
     *
     * @param string $secret
     * @return self
     */
    public function setSecret(string $secret): self
    {
        $this->secret = $secret;
        return $this;
    }

    /**
     * Set HMAC algorithm
     *
     * @param string $algorithm (sha256, sha1, sha512)
     * @return self
     */
    public function setAlgorithm(string $algorithm): self
    {
        $this->algorithm = $algorithm;
        return $this;
    }

    /**
     * Set signature header name
     *
     * @param string $headerName
     * @return self
     */
    public function setHeaderName(string $headerName): self
    {
        $this->headerName = $headerName;
        return $this;
    }

    /**
     * Get configured secret (masked for security)
     *
     * @return string
     */
    public function getSecretMasked(): string
    {
        if (empty($this->secret)) {
            return '[NOT SET]';
        }

        return substr($this->secret, 0, 4) . '****' . substr($this->secret, -4);
    }

    /**
     * Validate webhook by IP whitelist
     *
     * Recommended method for Pagar.me webhooks since they don't
     * support HMAC signatures natively.
     *
     * @param string $requestIp IP address from request
     * @param array|null $allowedIps List of allowed IPs (null = use config)
     * @return bool True if IP is whitelisted
     */
    public function validateByIp(string $requestIp, ?array $allowedIps = null): bool
    {
        $allowedIps = $allowedIps ?? config('pagarme.webhook.allowed_ips', []);

        if (empty($allowedIps)) {
            // If no IPs configured, log warning but allow
            \Log::warning('Webhook IP whitelist is empty - consider configuring allowed IPs');
            return true;
        }

        foreach ($allowedIps as $allowedIp) {
            // Support for CIDR notation
            if ($this->ipInRange($requestIp, $allowedIp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate webhook payload structure
     *
     * Ensures the payload has expected Pagar.me webhook structure
     *
     * @param array $payload Decoded webhook payload
     * @return bool True if structure is valid
     */
    public function validatePayloadStructure(array $payload): bool
    {
        // Check required Pagar.me webhook fields
        $requiredFields = ['id', 'type', 'created_at', 'data'];

        foreach ($requiredFields as $field) {
            if (!isset($payload[$field])) {
                return false;
            }
        }

        // Validate webhook ID format
        if (!str_starts_with($payload['id'], 'hook_')) {
            return false;
        }

        // Validate event type format (e.g., 'order.paid', 'charge.created')
        if (!str_contains($payload['type'], '.')) {
            return false;
        }

        // Validate account structure if present
        if (isset($payload['account'])) {
            if (!isset($payload['account']['id']) || !isset($payload['account']['name'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Comprehensive webhook validation
     *
     * Validates webhook using multiple security checks:
     * 1. IP whitelist (if configured)
     * 2. Payload structure
     * 3. HMAC signature (if configured)
     *
     * @param \Illuminate\Http\Request $request
     * @return array ['valid' => bool, 'reasons' => array]
     */
    public function validateWebhook(\Illuminate\Http\Request $request): array
    {
        $reasons = [];

        // 1. Validate IP whitelist
        if (config('pagarme.webhook.validate_ip', true)) {
            if (!$this->validateByIp($request->ip())) {
                $reasons[] = 'IP not whitelisted: ' . $request->ip();
            }
        }

        // 2. Validate payload structure
        try {
            $payload = $request->json()->all();

            if (!$this->validatePayloadStructure($payload)) {
                $reasons[] = 'Invalid payload structure';
            }
        } catch (\Exception $e) {
            $reasons[] = 'Invalid JSON payload';
        }

        // 3. Validate HMAC signature (if enabled and configured)
        if (config('pagarme.webhook.validate_signature', false)) {
            if (!$this->validateRequest($request)) {
                $reasons[] = 'Invalid HMAC signature';
            }
        }

        return [
            'valid' => empty($reasons),
            'reasons' => $reasons,
        ];
    }

    /**
     * Check if IP is in range (supports CIDR notation)
     *
     * @param string $ip IP to check
     * @param string $range IP or CIDR range
     * @return bool
     */
    protected function ipInRange(string $ip, string $range): bool
    {
        // Exact match
        if ($ip === $range) {
            return true;
        }

        // CIDR notation
        if (str_contains($range, '/')) {
            [$subnet, $mask] = explode('/', $range);

            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);
            $maskLong = -1 << (32 - (int)$mask);

            return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
        }

        return false;
    }

    /**
     * Create validator instance from config
     *
     * @return self
     */
    public static function fromConfig(): self
    {
        return new self(
            secret: config('pagarme.webhook.secret'),
            algorithm: config('pagarme.webhook.signature_algorithm', 'sha256'),
            headerName: config('pagarme.webhook.signature_header', 'X-Hub-Signature-256')
        );
    }
}
