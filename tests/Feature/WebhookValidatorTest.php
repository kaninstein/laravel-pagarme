<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Kaninstein\LaravelPagarme\PagarmeServiceProvider;
use Kaninstein\LaravelPagarme\Services\WebhookValidator;
use Orchestra\Testbench\TestCase;

class WebhookValidatorTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [PagarmeServiceProvider::class];
    }

    public function testValidateAcceptsRequest(): void
    {
        config([
            'pagarme.webhook.validate_ip' => false,
            'pagarme.webhook.validate_signature' => false,
        ]);

        $validator = WebhookValidator::fromConfig();

        $payload = [
            'id' => 'hook_test_123',
            'type' => 'charge.pending',
            'created_at' => '2025-01-01T00:00:00Z',
            'data' => [],
        ];

        $request = Request::create(
            uri: '/api/webhooks/pagarme',
            method: 'POST',
            content: json_encode($payload)
        );
        $request->headers->set('Content-Type', 'application/json');

        $this->assertTrue($validator->validate($request));
    }

    public function testValidateSignatureStillWorks(): void
    {
        config([
            'pagarme.webhook.secret' => 'secret_test',
        ]);

        $validator = WebhookValidator::fromConfig();

        $payload = '{"id":"hook_test_123"}';
        $signature = $validator->generateSignature($payload);

        $this->assertTrue($validator->validate($payload, $signature));
        $this->assertFalse($validator->validate($payload, ''));
        $this->assertFalse($validator->validate($payload, null));
    }
}

