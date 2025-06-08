<?php

namespace App\Tests\Unit\Service\Provider;

use App\DTO\PaymentRequest;
use App\DTO\PaymentResponse;
use App\Service\Provider\AciProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AciProviderTest extends TestCase
{
    private HttpClientInterface $mockHttpClient;

    protected function setUp(): void
    {
        $this->mockHttpClient = $this->createMock(HttpClientInterface::class);
    }

    public function testSuccessfulProcess(): void
    {
        $paymentRequest = new PaymentRequest(
            amount: 123.45,
            currency: 'EUR',
            cardNumber: '4111111111111111',
            cardExpMonth: '04',
            cardExpYear: (string)(date('Y') + 1),
            cardCvv: '321'
        );

        $mockResponseData = [
            'id' => 'payment_987654321',
            'timestamp' => '2025-12-01T10:15:30+00:00',
            'amount' => '12345',
            'currency' => 'EUR',
            'card' => [
                'bin' => '411111'
            ],
        ];

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('toArray')->with(false)->willReturn($mockResponseData);

        $this->mockHttpClient->method('request')->willReturn($mockResponse);

        $provider = new AciProvider($this->mockHttpClient, 'test_auth_key', 'test_entity_id');

        $paymentResponse = $provider->process($paymentRequest);

        $this->assertInstanceOf(PaymentResponse::class, $paymentResponse);
        $this->assertEquals($mockResponseData['id'], $paymentResponse->transactionId);
        $this->assertEquals(new \DateTimeImmutable($mockResponseData['timestamp']), $paymentResponse->createdAt);
        $this->assertEquals(123.45, $paymentResponse->amount);
        $this->assertEquals('EUR', $paymentResponse->currency);
        $this->assertEquals('411111', $paymentResponse->cardBin);
    }

    public function testInvalidAmountThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $request = new PaymentRequest(
            amount: 0,
            currency: 'EUR',
            cardNumber: '4111111111111111',
            cardExpMonth: '04',
            cardExpYear: (string)(date('Y') + 1),
            cardCvv: '321'
        );

        $provider = new AciProvider($this->mockHttpClient, 'auth', 'entity');
        $provider->process($request);
    }

    public function testInvalidCardNumberThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $request = new PaymentRequest(
            amount: 10.00,
            currency: 'EUR',
            cardNumber: '1234abcd',
            cardExpMonth: '04',
            cardExpYear: (string)(date('Y') + 1),
            cardCvv: '321'
        );

        $provider = new AciProvider($this->mockHttpClient, 'auth', 'entity');
        $provider->process($request);
    }

    public function testInvalidCvvThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $request = new PaymentRequest(
            amount: 10.00,
            currency: 'EUR',
            cardNumber: '4111111111111111',
            cardExpMonth: '04',
            cardExpYear: (string)(date('Y') + 1),
            cardCvv: '1'
        );

        $provider = new AciProvider($this->mockHttpClient, 'auth', 'entity');
        $provider->process($request);
    }

    public function testInvalidMonthThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $request = new PaymentRequest(
            amount: 10.00,
            currency: 'EUR',
            cardNumber: '4111111111111111',
            cardExpMonth: '13',
            cardExpYear: (string)(date('Y') + 1),
            cardCvv: '321'
        );

        $provider = new AciProvider($this->mockHttpClient, 'auth', 'entity');
        $provider->process($request);
    }

    public function testExpiredCardThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $request = new PaymentRequest(
            amount: 10.00,
            currency: 'EUR',
            cardNumber: '4111111111111111',
            cardExpMonth: '04',
            cardExpYear: (string)(date('Y') - 1),
            cardCvv: '321'
        );

        $provider = new AciProvider($this->mockHttpClient, 'auth', 'entity');
        $provider->process($request);
    }
}
