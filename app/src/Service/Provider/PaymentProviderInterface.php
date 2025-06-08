<?php

namespace App\Service\Provider;

use App\DTO\PaymentRequest;
use App\DTO\PaymentResponse;
interface PaymentProviderInterface
{
    public function process(PaymentRequest $request): PaymentResponse;

}
