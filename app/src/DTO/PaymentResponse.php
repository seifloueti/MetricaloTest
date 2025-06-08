<?php

namespace App\DTO;

class PaymentResponse
{

    public function __construct(
        public readonly string $transactionId,
        public readonly \DateTimeImmutable $createdAt,
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $cardBin
    ) {}

}
