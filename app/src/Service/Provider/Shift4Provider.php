<?php

namespace App\Service\Provider;

use App\DTO\PaymentRequest;
use App\DTO\PaymentResponse;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Shift4Provider implements PaymentProviderInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiKey
    ) {}

    public function process(PaymentRequest $request): PaymentResponse
    {
        if (!is_numeric($request->amount) || $request->amount <= 0) {
            throw new \InvalidArgumentException('Invalid amount: must be a positive number (in dollars)');
        }

        if (!ctype_digit($request->cardNumber) || strlen($request->cardNumber) < 12) {
            throw new \InvalidArgumentException('Invalid card number');
        }

        if (!ctype_digit($request->cardCvv) || strlen($request->cardCvv) < 3) {
            throw new \InvalidArgumentException('Invalid CVV');
        }

        if ((int)$request->cardExpMonth < 1 || (int)$request->cardExpMonth > 12) {
            throw new \InvalidArgumentException('Invalid expiration month');
        }

        if ((int)$request->cardExpYear < (int)date('Y')) {
            throw new \InvalidArgumentException('Card expiration year is in the past');
        }

        $expYear = (int) $request->cardExpYear;
        $expMonth = (int) $request->cardExpMonth;
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('n');

        if ($expYear < $currentYear || ($expYear === $currentYear && $expMonth < $currentMonth)) {
            throw new \InvalidArgumentException('Card is expired');
        }

        $amountInCents = (int) round($request->amount * 100);

        $payload = [
            'amount' => $amountInCents,
            'currency' => $request->currency,
            'card' => [
                'number' => $request->cardNumber,
                'expMonth' => $request->cardExpMonth,
                'expYear' => $request->cardExpYear,
                'cvc' => $request->cardCvv,
            ],
        ];

        try {
            $response = $this->httpClient->request('POST', 'https://api.shift4.com/charges', [
                'auth_basic' => [$this->apiKey, ''],
                'json' => $payload,
            ]);
        } catch (
        TransportExceptionInterface |
        ServerExceptionInterface |
        RedirectionExceptionInterface |
        DecodingExceptionInterface |
        ClientExceptionInterface $e
        ) {
            throw new \RuntimeException('Shift4 API request failed: ' . $e->getMessage(), 0, $e);
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200) {
            throw new \RuntimeException("Shift4 API call failed with status $statusCode");
        }

        $data = $response->toArray();

        if (!isset($data['id'], $data['created'], $data['amount'], $data['currency'])) {
            throw new \RuntimeException('Invalid response from Shift4 API: missing required fields');
        }

        return new PaymentResponse(
            transactionId: $data['id'],
            createdAt: (new \DateTimeImmutable())->setTimestamp($data['created']),
            amount: (float) ($data['amount'] / 100),
            currency: $data['currency'],
            cardBin: substr($request->cardNumber, 0, 6),
        );
    }
}
