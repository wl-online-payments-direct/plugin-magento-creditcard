<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartInterface;
use Worldline\PaymentCore\Api\Payment\PaymentIdFormatterInterface;
use Worldline\PaymentCore\Api\PaymentInfoCleanerInterface;
use Worldline\PaymentCore\Api\Service\GetPaymentDetailsServiceInterface;
use Worldline\PaymentCore\Model\Order\RejectOrderException;

class SuccessTransactionChecker
{
    public const UNSUCCESSFUL_STATUS_CATEGORY = 'UNSUCCESSFUL';

    /**
     * @var PaymentIdFormatterInterface
     */
    private $paymentIdFormatter;

    /**
     * @var PaymentInfoCleanerInterface
     */
    private $paymentInfoCleaner;

    /**
     * @var GetPaymentDetailsServiceInterface
     */
    private $getPaymentDetailsService;

    public function __construct(
        PaymentIdFormatterInterface $paymentIdFormatter,
        PaymentInfoCleanerInterface $paymentInfoCleaner,
        GetPaymentDetailsServiceInterface $getPaymentDetailsService
    ) {
        $this->paymentIdFormatter = $paymentIdFormatter;
        $this->paymentInfoCleaner = $paymentInfoCleaner;
        $this->getPaymentDetailsService = $getPaymentDetailsService;
    }

    /**
     * @param CartInterface $quote
     * @param string $paymentId
     * @return void
     * @throws LocalizedException
     * @throws RejectOrderException
     */
    public function check(CartInterface $quote, string $paymentId): void
    {
        $paymentId = $this->paymentIdFormatter->validateAndFormat($paymentId, true);

        try {
            $response = $this->getPaymentDetailsService->execute($paymentId, (int)$quote->getStoreId());
        } catch (LocalizedException $e) {
            throw new LocalizedException(__('The payment has failed, please, try again'));
        }

        if (self::UNSUCCESSFUL_STATUS_CATEGORY === $response->getStatusOutput()->getStatusCategory()) {
            $quote->setIsActive(true);
            $this->paymentInfoCleaner->clean($quote);
            throw new RejectOrderException(__('The payment has rejected, please, try again'));
        }
    }
}
