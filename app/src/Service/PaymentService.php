<?php

namespace App\Service;


use App\DTO\PaymentRequest;
use App\DTO\PaymentResponse;
use App\Service\Provider\AciProvider;
use App\Service\Provider\Shift4Provider;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class PaymentService
{

    public function __construct(
        private readonly Shift4Provider $shift4Provider,
        private readonly AciProvider $aciProvider
    ) {}


    public function process(string $providerName, PaymentRequest $request): PaymentResponse
    {
        return match (strtolower($providerName)) {
            'shift4' => $this->shift4Provider->process($request),
            'aci'    => $this->aciProvider->process($request),
            default  => throw new \InvalidArgumentException("Unsupported provider: $providerName"),
        };
    }

}
