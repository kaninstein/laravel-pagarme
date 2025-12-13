<?php

namespace Tests\Feature;

use Orchestra\Testbench\TestCase;
use Kaninstein\LaravelPagarme\Services\TokenService;
use Kaninstein\LaravelPagarme\PagarmeServiceProvider;

class TokenServiceTest extends TestCase
{
    private TokenService $tokenService;

    protected function getPackageProviders($app)
    {
        return [PagarmeServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = new TokenService();
    }

    /**
     * @test
     */
    public function it_can_create_card_token()
    {
        $cardData = [
            'number' => '4111111111111111',
            'holder_name' => 'TESTE INTEGRATION',
            'exp_month' => 12,
            'exp_year' => 2030,
            'cvv' => '123',
        ];

        $token = $this->tokenService->createCardToken($cardData);

        $this->assertNotNull($token);
        $this->assertArrayHasKey('id', $token);
        $this->assertStringStartsWith('token_', $token['id']);
        $this->assertArrayHasKey('type', $token);
        $this->assertEquals('card', $token['type']);
    }

    /**
     * @test
     */
    public function it_can_create_token_with_billing_address()
    {
        $cardData = [
            'number' => '5555555555554444',
            'holder_name' => 'TESTE MASTERCARD',
            'exp_month' => 6,
            'exp_year' => 2029,
            'cvv' => '321',
        ];

        $billingAddress = [
            'line_1' => '100, Av. Paulista, Bela Vista',
            'zip_code' => '01310100',
            'city' => 'São Paulo',
            'state' => 'SP',
            'country' => 'BR',
        ];

        $token = $this->tokenService->createCardToken($cardData, $billingAddress);

        $this->assertNotNull($token);
        $this->assertStringStartsWith('token_', $token['id']);
    }

    /**
     * @test
     */
    public function it_validates_required_card_fields()
    {
        $this->expectException(\Exception::class);

        // Missing required fields
        $invalidCardData = [
            'number' => '4111111111111111',
            // Missing: holder_name, exp_month, exp_year, cvv
        ];

        $this->tokenService->createCardToken($invalidCardData);
    }

    /**
     * @test
     */
    public function it_can_create_token_for_different_brands()
    {
        $cards = [
            'Visa' => '4111111111111111',
            'Mastercard' => '5555555555554444',
            'Elo' => '6362970000457013',
        ];

        foreach ($cards as $brand => $number) {
            $cardData = [
                'number' => $number,
                'holder_name' => "TESTE {$brand}",
                'exp_month' => 12,
                'exp_year' => 2030,
                'cvv' => '123',
            ];

            $token = $this->tokenService->createCardToken($cardData);

            $this->assertNotNull($token);
            $this->assertStringStartsWith('token_', $token['id']);
        }
    }
}
