<?php

declare(strict_types=1);

namespace Worldline\CreditCard\Gateway\Http\Client;

use OnlinePayments\Sdk\Domain\CapturePaymentRequest;
use OnlinePayments\Sdk\Domain\CaptureResponse;
use Psr\Log\LoggerInterface;
use Worldline\CreditCard\Gateway\Request\PaymentDataBuilder;
use Worldline\PaymentCore\Gateway\Http\Client\AbstractTransaction;
use Worldline\PaymentCore\Model\ClientProvider;
use Worldline\PaymentCore\Model\Config\WorldlineConfig;

class TransactionSubmitForSettlement extends AbstractTransaction
{
    /**
     * @var \Worldline\PaymentCore\Model\Config\WorldlineConfig
     */
    private $worldlineConfig;

    /**
     * @var ClientProvider
     */
    private $modelClient;

    public function __construct(
        LoggerInterface $logger,
        WorldlineConfig $worldlineConfig,
        ClientProvider $modelClient
    ) {
        parent::__construct($logger);
        $this->worldlineConfig = $worldlineConfig;
        $this->modelClient = $modelClient;
    }

    protected function process(array $data): CaptureResponse
    {
        $capturePaymentRequest = new CapturePaymentRequest();
        $capturePaymentRequest->setAmount($data['amount']);

        $client = $this->modelClient->getClient($data[PaymentDataBuilder::STORE_ID]);
        $merchantId = $this->worldlineConfig->getMerchantId($data[PaymentDataBuilder::STORE_ID]);
        // @TODO implement exceptions catching
        $capturePaymentResponse = $client
            ->merchant($merchantId)
            ->payments()
            ->capturePayment($data['transaction_id'], $capturePaymentRequest);

        return $capturePaymentResponse;
    }
}
