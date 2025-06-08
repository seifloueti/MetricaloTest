<?php

namespace App\DTO;

class PaymentRequest
{

    public function __construct(
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $cardNumber,
        public readonly int $cardExpMonth,
        public readonly int $cardExpYear,
        public readonly string $cardCvv
    ) {}

}
