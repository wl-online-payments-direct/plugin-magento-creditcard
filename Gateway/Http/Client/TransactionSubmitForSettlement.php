<?php

declare(strict_types=1);

namespace Worldline\CreditCard\Gateway\Http\Client;

use OnlinePayments\Sdk\Domain\CapturePaymentRequest;
use Psr\Log\LoggerInterface;
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

    /**
     * @inheritdoc
     */
    protected function process(array $data)
    {
        $capturePaymentRequest = new CapturePaymentRequest();
        $capturePaymentRequest->setAmount($data['amount']);

        $client = $this->modelClient->getClient();
        $merchantId = $this->worldlineConfig->getMerchantId();
        // @TODO implement exceptions catching
        $capturePaymentResponse = $client
            ->merchant($merchantId)
            ->payments()
            ->capturePayment($data['transaction_id'], $capturePaymentRequest);

        return $capturePaymentResponse;
    }
}
