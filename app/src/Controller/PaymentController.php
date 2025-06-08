<?php

namespace App\Controller;

use App\DTO\PaymentRequest;
use App\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class PaymentController  extends AbstractController
{
    public function __construct(private readonly PaymentService $paymentService) {}

    #[Route('/app/payment/{provider}', name: 'api_payment', methods: ['POST'])]
    public function process(Request $request, string $provider): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            $paymentRequest = new PaymentRequest(
                amount: (float) $data['amount'],
                currency: $data['currency'],
                cardNumber: $data['card_number'],
                cardExpMonth: (int) $data['card_exp_month'],
                cardExpYear: (int) $data['card_exp_year'],
                cardCvv: $data['card_cvv']
            );

            $response = $this->paymentService->process($provider, $paymentRequest);

            return new JsonResponse([
                'transaction_id' => $response->transactionId,
                'created_at'     => $response->createdAt->format(DATE_ATOM),
                'amount'         => $response->amount,
                'currency'       => $response->currency,
                'card_bin'       => $response->cardBin,
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }
}
