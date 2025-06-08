<?php

namespace App\Tests\Unit\Service\Provider;

use App\DTO\PaymentRequest;
use App\DTO\PaymentResponse;
use App\Service\Provider\Shift4Provider;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class Shift4ProviderTest extends TestCase
{
    private function createValidRequest(): PaymentRequest
    {
        return new PaymentRequest(
            amount: 10.00,
            currency: 'USD',
            cardNumber: '4111111111111111',
            cardExpMonth: '12',
            cardExpYear: (string)(date('Y') + 1),
            cardCvv: '123'
        );
    }

    public function testSuccessfulPayment(): void
    {
        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(200);
        $responseMock->method('toArray')->willReturn([
            'id' => 'tx_123',
            'created' => time(),
            'amount' => 1000,
            'currency' => 'USD',
        ]);

        $clientMock = $this->createMock(HttpClientInterface::class);
        $clientMock->method('request')->willReturn($responseMock);

        $provider = new Shift4Provider($clientMock, 'api-key');

        $request = $this->createValidRequest();
        $response = $provider->process($request);

        $this->assertInstanceOf(PaymentResponse::class, $response);
        $this->assertEquals('tx_123', $response->transactionId);
        $this->assertEquals(10.00, $response->amount);
        $this->assertEquals('USD', $response->currency);
        $this->assertEquals('411111', $response->cardBin);
    }

    public function testZeroAmountThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $provider = new Shift4Provider($this->createMock(HttpClientInterface::class), 'key');
        $request = new PaymentRequest(
            amount: 0,
            currency: 'USD',
            cardNumber: '4111111111111111',
            cardExpMonth: '12',
            cardExpYear: (string)(date('Y') + 1),
            cardCvv: '123'
        );
        $provider->process($request);
    }

    public function testInvalidCardNumber(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $provider = new Shift4Provider($this->createMock(HttpClientInterface::class), 'key');
        $request = new PaymentRequest(
            amount: 10.00,
            currency: 'USD',
            cardNumber: 'abcd123',
            cardExpMonth: '12',
            cardExpYear: (string)(date('Y') + 1),
            cardCvv: '123'
        );
        $provider->process($request);
    }

    public function testInvalidCvv(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $provider = new Shift4Provider($this->createMock(HttpClientInterface::class), 'key');
        $request = new PaymentRequest(
            amount: 10.00,
            currency: 'USD',
            cardNumber: '4111111111111111',
            cardExpMonth: '12',
            cardExpYear: (string)(date('Y') + 1),
            cardCvv: '1'
        );
        $provider->process($request);
    }

    public function testInvalidMonth(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $provider = new Shift4Provider($this->createMock(HttpClientInterface::class), 'key');
        $request = new PaymentRequest(
            amount: 10.00,
            currency: 'USD',
            cardNumber: '4111111111111111',
            cardExpMonth: '13',
            cardExpYear: (string)(date('Y') + 1),
            cardCvv: '123'
        );
        $provider->process($request);
    }

    public function testExpiredCard(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $provider = new Shift4Provider($this->createMock(HttpClientInterface::class), 'key');
        $request = new PaymentRequest(
            amount: 10.00,
            currency: 'USD',
            cardNumber: '4111111111111111',
            cardExpMonth: '12',
            cardExpYear: (string)(date('Y') - 1),
            cardCvv: '123'
        );
        $provider->process($request);
    }

    public function testApiFailureStatusCode(): void
    {
        $this->expectException(\RuntimeException::class);

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(500);

        $clientMock = $this->createMock(HttpClientInterface::class);
        $clientMock->method('request')->willReturn($responseMock);

        $provider = new Shift4Provider($clientMock, 'key');

        $request = $this->createValidRequest();
        $provider->process($request);
    }

    public function testMissingApiResponseFields(): void
    {
        $this->expectException(\RuntimeException::class);

        $responseMock = $this->createMock(ResponseInterface::class);
        $responseMock->method('getStatusCode')->willReturn(200);
        $responseMock->method('toArray')->willReturn([
            // missing required fields like id, created, etc.
            'amount' => 1000,
            'currency' => 'USD',
        ]);

        $clientMock = $this->createMock(HttpClientInterface::class);
        $clientMock->method('request')->willReturn($responseMock);

        $provider = new Shift4Provider($clientMock, 'key');

        $request = $this->createValidRequest();
        $provider->process($request);
    }
}
