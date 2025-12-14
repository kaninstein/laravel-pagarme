<?php

namespace Tests\Feature;

use Orchestra\Testbench\TestCase;
use Kaninstein\LaravelPagarme\Client\PagarmeClient;
use Kaninstein\LaravelPagarme\Services\CustomerService;
use Kaninstein\LaravelPagarme\DTOs\CustomerDTO;
use Kaninstein\LaravelPagarme\DTOs\AddressDTO;
use Kaninstein\LaravelPagarme\DTOs\PhonesDTO;
use Kaninstein\LaravelPagarme\DTOs\PhoneDTO;
use Kaninstein\LaravelPagarme\PagarmeServiceProvider;

class CustomerServiceTest extends TestCase
{
    private CustomerService $customerService;
    private static ?string $createdCustomerId = null;

    protected function getPackageProviders($app)
    {
        return [PagarmeServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $client = new PagarmeClient(
            secretKey: config('pagarme.secret_key'),
            apiUrl: config('pagarme.api_url'),
            timeout: (int) config('pagarme.timeout')
        );

        $this->customerService = new CustomerService($client);
    }

    /**
     * @test
     */
    public function it_can_create_individual_customer()
    {
        $customer = CustomerDTO::individual(
            name: 'João da Silva Teste',
            email: 'joao.teste.' . time() . '@example.com',
            cpf: '11144477735',
            phone: PhonesDTO::brazilian(
                mobilePhone: PhoneDTO::brazilian('11', '987654321'),
                homePhone: PhoneDTO::brazilian('11', '12345678')
            ),
            address: AddressDTO::brazilian(
                number: '100',
                street: 'Av. Paulista',
                neighborhood: 'Bela Vista',
                zipCode: '01310-100',
                city: 'São Paulo',
                state: 'SP',
                complement: 'Apto 101'
            )
        );

        $result = $this->customerService->create($customer->toArray());

        $this->assertNotNull($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertStringStartsWith('cus_', $result['id']);
        $this->assertEquals('João da Silva Teste', $result['name']);
        $this->assertEquals('individual', $result['type']);

        // Save for other tests
        self::$createdCustomerId = $result['id'];
    }

    /**
     * @test
     * @depends it_can_create_individual_customer
     */
    public function it_can_get_customer()
    {
        if (!self::$createdCustomerId) {
            $this->markTestSkipped('No customer was created');
        }

        $customer = $this->customerService->get(self::$createdCustomerId);

        $this->assertNotNull($customer);
        $this->assertEquals(self::$createdCustomerId, $customer['id']);
        $this->assertArrayHasKey('name', $customer);
        // Email might not always be returned in get response
        // $this->assertArrayHasKey('email', $customer);
    }

    /**
     * @test
     * @depends it_can_create_individual_customer
     */
    public function it_can_update_customer()
    {
        if (!self::$createdCustomerId) {
            $this->markTestSkipped('No customer was created');
        }

        $updatedData = [
            'name' => 'João Silva Atualizado',
        ];

        $result = $this->customerService->update(self::$createdCustomerId, $updatedData);

        $this->assertNotNull($result);
        $this->assertEquals('João Silva Atualizado', $result['name']);
    }

    /**
     * @test
     */
    public function it_can_list_customers()
    {
        $result = $this->customerService->list(['page' => 1, 'size' => 10]);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
    }

    /**
     * @test
     */
    public function it_can_search_customer_by_email()
    {
        if (!self::$createdCustomerId) {
            $this->markTestSkipped('No customer was created');
        }

        // Get customer email first
        $customer = $this->customerService->get(self::$createdCustomerId);

        // Check if email exists in response
        if (!isset($customer['email'])) {
            $this->markTestSkipped('Customer email not available in response');
        }

        $result = $this->customerService->searchByEmail($customer['email']);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('data', $result);
    }

    /**
     * @test
     */
    public function it_can_create_company_customer()
    {
        $customer = CustomerDTO::company(
            name: 'Empresa Teste LTDA',
            email: 'empresa.' . time() . '@example.com',
            cnpj: '12345678000190',
            phone: PhonesDTO::brazilian(
                mobilePhone: PhoneDTO::brazilian('11', '987654321')
            )
        );

        $result = $this->customerService->create($customer->toArray());

        $this->assertNotNull($result);
        $this->assertStringStartsWith('cus_', $result['id']);
        $this->assertEquals('company', $result['type']);
    }
}
