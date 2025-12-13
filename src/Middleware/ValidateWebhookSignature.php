<?php

namespace Kaninstein\LaravelPagarme\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kaninstein\LaravelPagarme\Services\WebhookValidator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validate Webhook Security Middleware
 *
 * Validates incoming webhook requests from Pagar.me using multiple security layers:
 * - IP whitelist validation (recommended for Pagar.me)
 * - Payload structure validation
 * - HMAC signature validation (optional, for custom implementations)
 *
 * Usage in routes/api.php:
 * Route::post('/webhooks/pagarme', [WebhookController::class, 'handle'])
 *     ->middleware(ValidateWebhookSignature::class);
 */
class ValidateWebhookSignature
{
    /**
     * Handle an incoming request
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip validation if disabled in config
        if (!config('pagarme.webhook.enabled', true)) {
            return $next($request);
        }

        // Skip validation in local environment (optional)
        if (app()->environment('local') && !config('pagarme.webhook.validate_in_local', false)) {
            return $next($request);
        }

        $validator = WebhookValidator::fromConfig();
        $result = $validator->validateWebhook($request);

        if (!$result['valid']) {
            \Log::warning('Invalid webhook received from Pagar.me', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
                'reasons' => $result['reasons'],
                'payload_preview' => substr($request->getContent(), 0, 200),
            ]);

            return response()->json([
                'error' => 'Webhook validation failed',
                'reasons' => config('app.debug') ? $result['reasons'] : null,
            ], 401);
        }

        return $next($request);
    }
}
