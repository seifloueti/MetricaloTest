<?php

namespace App\Tests\Integration\Service\Provider;

use App\DTO\PaymentRequest;
use App\Service\Provider\Shift4Provider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\HttpClient;

class Shift4ProviderIntegrationTest extends TestCase
{
    private Shift4Provider $provider;

    protected function setUp(): void
    {
        $httpClient = HttpClient::create();
        $apiKey = $_ENV['SHIFT4_SECRET_KEY'] ;


        $this->provider = new Shift4Provider($httpClient, $apiKey);
    }

    public function testProcessWithRealApi(): void
    {
        $request = new PaymentRequest(
            amount: 5000,
            currency: 'USD',
            cardNumber: '4242424242424242',
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
