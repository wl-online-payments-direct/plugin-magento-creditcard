<?php

declare(strict_types=1);

namespace Worldline\CreditCard\Gateway\Http\Client;

use Exception;
use OnlinePayments\Sdk\DataObject;
use OnlinePayments\Sdk\Domain\CancelPaymentResponse;
use Psr\Log\LoggerInterface;
use Worldline\PaymentCore\Gateway\Http\Client\AbstractTransaction;
use Worldline\PaymentCore\Model\ClientProvider;
use Worldline\PaymentCore\Model\Config\WorldlineConfig;

class TransactionVoid extends AbstractTransaction
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
     * @param array $data
     * @return DataObject|CancelPaymentResponse
     * @throws Exception
     */
    protected function process(array $data)
    {
        $client = $this->modelClient->getClient();
        $merchantId = $this->worldlineConfig->getMerchantId();

        $payment = $client->merchant($merchantId)->payments()->getPayment($data['transaction_id']);

        if ($payment->getStatusOutput()->getIsCancellable()) {
            return $client
                ->merchant($merchantId)
                ->payments()
                ->cancelPayment($data['transaction_id']);
        }

        return $payment;
    }
}
