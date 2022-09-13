<?php

declare(strict_types=1);

namespace Worldline\CreditCard\Gateway\Http\Client;

use Magento\Framework\Exception\LocalizedException;
use OnlinePayments\Sdk\DataObject;
use OnlinePayments\Sdk\Domain\RefundRequestFactory;
use OnlinePayments\Sdk\Domain\RefundResponse;
use Psr\Log\LoggerInterface;
use Worldline\PaymentCore\Gateway\Http\Client\AbstractTransaction;
use Worldline\CreditCard\Gateway\Request\PaymentDataBuilder;
use Worldline\PaymentCore\Model\ClientProvider;
use Worldline\PaymentCore\Model\Config\WorldlineConfig;

class TransactionRefund extends AbstractTransaction
{
    /**
     * @var \Worldline\PaymentCore\Model\Config\WorldlineConfig
     */
    private $worldlineConfig;

    /**
     * @var ClientProvider
     */
    private $clientProvider;

    /**
     * @var RefundRequestFactory
     */
    private $refundRequestFactory;

    public function __construct(
        LoggerInterface $logger,
        WorldlineConfig $worldlineConfig,
        ClientProvider $clientProvider,
        RefundRequestFactory $refundRequestFactory
    ) {
        parent::__construct($logger);
        $this->worldlineConfig = $worldlineConfig;
        $this->clientProvider = $clientProvider;
        $this->refundRequestFactory = $refundRequestFactory;
    }

    /**
     * @param array $data
     * @return DataObject|RefundResponse
     * @throws LocalizedException
     */
    protected function process(array $data): DataObject
    {
        $refundRequest = $this->refundRequestFactory->create();
        $refundRequest->setAmountOfMoney($data[PaymentDataBuilder::AMOUNT]);

        $client = $this->clientProvider->getClient();
        $merchantId = $this->worldlineConfig->getMerchantId();

        try {
            return $client
                ->merchant($merchantId)
                ->payments()
                ->refundPayment($data['transaction_id'], $refundRequest);
        } catch (\Exception $e) {
            throw new LocalizedException(__('WorldLine refund has failed. Please contact the provider.'));
        }
    }
}
