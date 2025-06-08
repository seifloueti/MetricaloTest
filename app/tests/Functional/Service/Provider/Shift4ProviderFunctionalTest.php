<?php

namespace App\Tests\Functional\Service\Provider;

use App\DTO\PaymentRequest;
use App\Service\Provider\Shift4Provider;
use App\DTO\PaymentResponse;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class Shift4ProviderFunctionalTest extends TestCase
{
    public function testSuccessfulProcess(): void
    {
        $paymentRequest = new PaymentRequest(
            amount: 150.00,
            currency: 'USD',
            cardNumber: '4242424242424242',
            cardExpMonth: '12',
            cardExpYear: '2026',
            cardCvv: '123'
        );

        $mockResponseData = [
            'id' => 'charge_123456789',
            'created' => time(),
            'amount' => 15000,
            'currency' => 'USD',
        ];

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('toArray')->willReturn($mockResponseData);

        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        $mockHttpClient->method('request')->willReturn($mockResponse);

        $apiKey = 'test_api_key';

        $provider = new Shift4Provider($mockHttpClient, $apiKey);

        $paymentResponse = $provider->process($paymentRequest);

        $this->assertInstanceOf(PaymentResponse::class, $paymentResponse);
        $this->assertEquals($mockResponseData['id'], $paymentResponse->transactionId);
        $this->assertEquals((new \DateTimeImmutable())->setTimestamp($mockResponseData['created']), $paymentResponse->createdAt);
        $this->assertEquals(150.00, $paymentResponse->amount);
        $this->assertEquals($mockResponseData['currency'], $paymentResponse->currency);
        $this->assertEquals(substr($paymentRequest->cardNumber, 0, 6), $paymentResponse->cardBin);
    }

    public function testNon200StatusCodeThrowsException(): void
    {
        $paymentRequest = new PaymentRequest(
            amount: 100.00,
            currency: 'USD',
            cardNumber: '4242424242424242',
            cardExpMonth: '12',
            cardExpYear: '2026',
            cardCvv: '123'
        );

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(500);

        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        $mockHttpClient->method('request')->willReturn($mockResponse);

        $apiKey = 'test_api_key';

        $provider = new Shift4Provider($mockHttpClient, $apiKey);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Shift4 API call failed with status 500');

        $provider->process($paymentRequest);
    }

    public function testHttpClientExceptionIsHandled(): void
    {
        $paymentRequest = new PaymentRequest(
            amount: 100.00,
            currency: 'USD',
            cardNumber: '4242424242424242',
            cardExpMonth: '12',
            cardExpYear: '2026',
            cardCvv: '123'
        );

        $mockHttpClient = $this->createMock(HttpClientInterface::class);
        $mockHttpClient->method('request')->willThrowException(new class extends \Exception implements TransportExceptionInterface {});

        $apiKey = 'test_api_key';

        $provider = new Shift4Provider($mockHttpClient, $apiKey);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Shift4 API request failed:');

        $provider->process($paymentRequest);
    }
}
