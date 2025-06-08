<?php

namespace App\Tests\Integration\Service\Provider;

use App\DTO\PaymentRequest;
use App\Service\Provider\AciProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\HttpClient;

class AciProviderIntegrationTest extends TestCase
{
    private AciProvider $provider;

    protected function setUp(): void
    {
        $httpClient = HttpClient::create();
        $authKey = $_ENV['ACI_AUTH_KEY'] ;
        $entityId = $_ENV['ACI_ENTITY_ID'] ;

        $this->provider = new AciProvider($httpClient, $authKey, $entityId);
    }

    public function testProcessWithRealApi(): void
    {
        $request = new PaymentRequest(
            amount: 50.00,
            currency: 'EUR',
            cardNumber: '4111111111111111',
            cardExpMonth: '12',
            cardExpYear: '2030',
            cardCvv: '123'
        );

        $response = $this->provider->process($request);

        $this->assertNotEmpty($response->transactionId);
        $this->assertInstanceOf(\DateTimeImmutable::class, $response->createdAt);
        $this->assertEquals($request->amount, $response->amount);
        $this->assertEquals($request->currency, $response->currency);
        $this->assertEquals(substr($request->cardNumber, 0, 6), $response->cardBin);
    }
}
