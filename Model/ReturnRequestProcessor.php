<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Model;

use Magento\Checkout\Model\Session;
use Magento\Sales\Model\OrderFactory;
use Worldline\PaymentCore\Api\Data\OrderStateInterfaceFactory;
use Worldline\PaymentCore\Api\Data\PaymentInterface;
use Worldline\PaymentCore\Model\OrderState;
use Worldline\PaymentCore\Model\ResourceModel\Quote as QuoteResource;

/**
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class ReturnRequestProcessor
{
    public const SUCCESS_STATE = 'success';
    public const WAITING_STATE = 'waiting';
    public const FAIL_STATE = 'fail';

    /**
     * @var QuoteResource
     */
    private $quoteResource;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var OrderStateInterfaceFactory
     */
    private $orderStateFactory;

    public function __construct(
        QuoteResource $quoteResource,
        Session $checkoutSession,
        OrderFactory $orderFactory,
        OrderStateInterfaceFactory $orderStateFactory
    ) {
        $this->quoteResource = $quoteResource;
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->orderStateFactory = $orderStateFactory;
    }

    public function processRequest(string $paymentId): OrderState
    {
        $quote = $this->quoteResource->getQuoteByWorldlinePaymentId($paymentId);
        $payment = $quote->getPayment();
        $reservedOrderId = (string)$quote->getReservedOrderId();
        /** @var OrderState $orderState */
        $orderState = $this->orderStateFactory->create();
        $orderState->setIncrementId($reservedOrderId);
        $orderState->setPaymentMethod((string)$payment->getMethod());
        $orderState->setPaymentProductId((int)$payment->getAdditionalInformation(PaymentInterface::PAYMENT_PRODUCT_ID));

        $order = $this->orderFactory->create()->loadByIncrementId($reservedOrderId);
        if (!$order->getId()) {
            $orderState->setState(self::WAITING_STATE);
            $this->checkoutSession->clearStorage();
            $this->checkoutSession->setLastRealOrderId($reservedOrderId);

            return $orderState;
        }

        $orderState->setState(self::SUCCESS_STATE);
        $this->checkoutSession->setLastOrderId((int) $order->getId());
        $this->checkoutSession->setLastRealOrderId($reservedOrderId);
        $this->checkoutSession->setLastQuoteId($quote->getId());
        $this->checkoutSession->setLastSuccessQuoteId($quote->getId());

        return $orderState;
    }
}
