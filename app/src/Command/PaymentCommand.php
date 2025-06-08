<?php

namespace App\Command;

use App\DTO\PaymentRequest;
use App\Service\PaymentService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:payment',
    description: 'Process a payment using Shift4 or ACI.'
)]
class PaymentCommand extends Command
{
    public function __construct(private readonly PaymentService $paymentService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('provider', InputArgument::REQUIRED, 'The payment provider (shift4 or aci)')
            ->addArgument('amount', InputArgument::REQUIRED)
            ->addArgument('currency', InputArgument::REQUIRED)
            ->addArgument('card_number', InputArgument::REQUIRED)
            ->addArgument('card_exp_month', InputArgument::REQUIRED)
            ->addArgument('card_exp_year', InputArgument::REQUIRED)
            ->addArgument('card_cvv', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $paymentRequest = new PaymentRequest(
                amount: (int) $input->getArgument('amount'),
                currency: $input->getArgument('currency'),
                cardNumber: $input->getArgument('card_number'),
                cardExpMonth: (int) $input->getArgument('card_exp_month'),
                cardExpYear: (int) $input->getArgument('card_exp_year'),
                cardCvv: $input->getArgument('card_cvv')
            );

            $provider = $input->getArgument('provider');

            $response = $this->paymentService->process($provider, $paymentRequest);

            $output->writeln('Transaction Successful!');
            $output->writeln('-----------------------');
            $output->writeln('Transaction ID: ' . $response->transactionId);
            $output->writeln('Date: ' . $response->createdAt->format(DATE_ATOM));
            $output->writeln('Amount: ' . $response->amount);
            $output->writeln('Currency: ' . $response->currency);
            $output->writeln('Card BIN: ' . $response->cardBin);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
