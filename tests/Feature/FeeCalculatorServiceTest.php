<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Kaninstein\LaravelPagarme\Facades\Pagarme;
use Kaninstein\LaravelPagarme\PagarmeServiceProvider;
use Kaninstein\LaravelPagarme\Exceptions\PagarmeException;
use Orchestra\Testbench\TestCase;

class FeeCalculatorServiceTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [PagarmeServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('cache.default', 'array');
        $this->app['config']->set('pagarme.secret_key', 'sk_test_dummy');
        $this->app['config']->set('pagarme.api_url', 'https://api.pagar.me/core/v5');
        $this->app['config']->set('pagarme.fee_calculator.cache_ttl', 'month');

        Cache::flush();
    }

    /**
     * @test
     */
    public function it_caches_fee_calculator_requests_for_the_same_payload()
    {
        Http::fake([
            'https://api.pagar.me/core/v5/transactions/fee-calculator*' => Http::response([
                'delay' => [30],
                'total_fee_percentage' => 3.5,
                'total_fee_amount' => 273,
                'amount_received' => 7527,
                'amount_charged' => 7800,
            ], 200),
        ]);

        $payload = [
            'amount' => 7800,
            'fee_responsibility' => 'merchant',
            'credit_card' => [
                'installments' => 1,
                'card_brand' => 'mastercard',
                'capture_method' => 'ecommerce',
            ],
        ];

        $result1 = Pagarme::feeCalculator()->calculate($payload);
        $result2 = Pagarme::feeCalculator()->calculate($payload);

        $this->assertEquals($result1, $result2);
        $this->assertEquals(7527, $result1['amount_received']);

        Http::assertSentCount(1);
    }

    /**
     * @test
     */
    public function it_validates_payload()
    {
        $this->expectException(PagarmeException::class);

        Pagarme::feeCalculator()->calculate([
            'amount' => 0,
            'fee_responsibility' => 'merchant',
            'credit_card' => [
                'installments' => 1,
                'card_brand' => 'visa',
            ],
        ]);
    }
}
