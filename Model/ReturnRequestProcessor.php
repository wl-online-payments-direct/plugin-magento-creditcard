<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Model;

use Magento\Sales\Model\OrderFactory;
use Worldline\PaymentCore\Api\Data\PaymentInterface;
use Worldline\PaymentCore\Api\OrderStateManagerInterface;
use Worldline\PaymentCore\Api\Payment\PaymentIdFormatterInterface;
use Worldline\PaymentCore\Api\QuoteResourceInterface;
use Worldline\PaymentCore\Api\SessionDataManagerInterface;
use Worldline\PaymentCore\Model\OrderState\OrderState;

class ReturnRequestProcessor
{
    public const SUCCESS_STATE = 'success';
    public const WAITING_STATE = 'waiting';
    public const FAIL_STATE = 'fail';

    /**
     * @var QuoteResourceInterface
     */
    private $quoteResource;

    /**
     * @var SessionDataManagerInterface
     */
    private $sessionDataManager;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var OrderStateManagerInterface
     */
    private $orderStateManager;

    /**
     * @var PaymentIdFormatterInterface
     */
    private $paymentIdFormatter;

    /**
     * @var SuccessTransactionChecker
     */
    private $successTransactionChecker;

    public function __construct(
        QuoteResourceInterface $quoteResource,
        SessionDataManagerInterface $sessionDataManager,
        OrderFactory $orderFactory,
        OrderStateManagerInterface $orderStateManager,
        PaymentIdFormatterInterface $paymentIdFormatter,
        SuccessTransactionChecker $successTransactionChecker
    ) {
        $this->quoteResource = $quoteResource;
        $this->sessionDataManager = $sessionDataManager;
        $this->orderFactory = $orderFactory;
        $this->orderStateManager = $orderStateManager;
        $this->paymentIdFormatter = $paymentIdFormatter;
        $this->successTransactionChecker = $successTransactionChecker;
    }

    public function processRequest(string $paymentId = null, string $hostedTokenizationId = null): OrderState
    {
        if ($paymentId) {
            $paymentId = $this->paymentIdFormatter->validateAndFormat($paymentId);
            $quote = $this->quoteResource->getQuoteByWorldlinePaymentId($paymentId);
            $this->successTransactionChecker->check($quote, $paymentId);
        } else {
            $quote = $this->quoteResource->getQuoteByWorldlinePaymentId($hostedTokenizationId);
        }

        $payment = $quote->getPayment();
        $paymentCode = (string)$payment->getMethod();
        $paymentProductId = (int)$payment->getAdditionalInformation(PaymentInterface::PAYMENT_PRODUCT_ID);

        $incrementId = (string)$quote->getReservedOrderId();
        $order = $this->orderFactory->create()->loadByIncrementId($incrementId);
        if (!$order->getId()) {
            $this->sessionDataManager->reserveOrder($incrementId);
            return $this->orderStateManager->create($incrementId, $paymentCode, self::WAITING_STATE, $paymentProductId);
        }

        $this->sessionDataManager->setOrderData($order);

        return $this->orderStateManager->create($incrementId, $paymentCode, self::SUCCESS_STATE, $paymentProductId);
    }
}
