<?php

declare(strict_types=1);

namespace Worldline\CreditCard\Gateway\Http\Client;

use Magento\Framework\Exception\LocalizedException;
use OnlinePayments\Sdk\Domain\CaptureResponse;
use Psr\Log\LoggerInterface;
use Worldline\CreditCard\Api\Service\CapturePaymentInterface;
use Worldline\CreditCard\Gateway\Request\CaptureDataBuilder;
use Worldline\PaymentCore\Gateway\Http\Client\AbstractTransaction;

class TransactionSubmitForSettlement extends AbstractTransaction
{
    /**
     * @var CapturePaymentInterface
     */
    private $capturePayment;

    public function __construct(
        LoggerInterface $logger,
        CapturePaymentInterface $capturePayment
    ) {
        parent::__construct($logger);
        $this->capturePayment = $capturePayment;
    }

    /**
     * @param array $data
     * @return CaptureResponse
     * @throws LocalizedException
     */
    protected function process(array $data): CaptureResponse
    {
        return $this->capturePayment->execute(
            $data[CaptureDataBuilder::PAYMENT_ID],
            $data[CaptureDataBuilder::CAPTURE_PAYMENT_REQUEST],
            $data[CaptureDataBuilder::STORE_ID]
        );
    }
}
