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

class AciProvider implements PaymentProviderInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $authKey,
        private readonly string $entityId
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

        try {
            $response = $this->httpClient->request('POST', 'https://eu-test.oppwa.com/v1/payments', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->authKey,
                    'Content-Type'  => 'application/x-www-form-urlencoded',
                ],
                'body' => http_build_query([
                    'entityId'         => $this->entityId,
                    'amount'           => $amountInCents,
                    'currency'         => $request->currency,
                    'paymentBrand'     => 'VISA',
                    'paymentType'      => 'DB',
                    'card.number'      => $request->cardNumber,
                    'card.holder'      => 'Jane Jones',
                    'card.expiryMonth' => str_pad($request->cardExpMonth, 2, '0', STR_PAD_LEFT),
                    'card.expiryYear'  => (string) $request->cardExpYear,
                    'card.cvv'         => $request->cardCvv,
                ])
            ]);
        } catch (TransportExceptionInterface |
        ServerExceptionInterface |
        RedirectionExceptionInterface |
        DecodingExceptionInterface |
        ClientExceptionInterface $e) {
            throw new \RuntimeException('ACI API request failed: ' . $e->getMessage(), 0, $e);
        }

        $data = $response->toArray(false);

        if (!isset($data['id'], $data['timestamp'], $data['amount'], $data['currency'])) {
            throw new \RuntimeException('Invalid response from ACI API: missing required fields');
        }

        return new PaymentResponse(
            transactionId: $data['id'],
            createdAt: new \DateTimeImmutable($data['timestamp']),
            amount: (float) ($data['amount'] / 100),
            currency: $data['currency'],
            cardBin: $data['card']['bin'] ?? substr($request->cardNumber, 0, 6)
        );
    }
}
