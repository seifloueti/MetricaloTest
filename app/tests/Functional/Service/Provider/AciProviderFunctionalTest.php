<?php

namespace App\Tests\Functional\Service\Provider;

use App\DTO\PaymentRequest;
use App\Service\Provider\AciProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AciProviderFunctionalTest extends KernelTestCase
{
    public function testProcessPayment(): void
    {
        $mockResponseData = [
            'id' => 'payment_123456',
            'timestamp' => '2025-06-01T12:00:00+00:00',
            'amount' => '10000',
            'currency' => 'USD',
            'card' => ['bin' => '411111']
        ];

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('toArray')->willReturn($mockResponseData);

        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        $mockHttpClient->method('request')->willReturn($mockResponse);

        $provider = new AciProvider($mockHttpClient, 'test_auth_key', 'test_entity_id');

        $paymentRequest = new PaymentRequest(
            amount: 100.00,
            currency: 'USD',
            cardNumber: '4111111111111111',
            cardExpMonth: '06',
            cardExpYear: '2025',
            cardCvv: '123'
        );

        $response = $provider->process($paymentRequest);

        $this->assertEquals('payment_123456', $response->transactionId);
        $this->assertEqualsWithDelta(100.00, $response->amount, 0.001);
        $this->assertEquals('USD', $response->currency);
        $this->assertEquals('411111', $response->cardBin);
    }
}
