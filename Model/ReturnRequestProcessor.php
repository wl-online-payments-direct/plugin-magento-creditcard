<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Model;

use Magento\Sales\Model\OrderFactory;
use Worldline\PaymentCore\Api\Data\OrderStateInterfaceFactory;
use Worldline\PaymentCore\Api\Data\PaymentInterface;
use Worldline\PaymentCore\Api\QuoteResourceInterface;
use Worldline\PaymentCore\Api\SessionDataManagerInterface;
use Worldline\PaymentCore\Model\OrderState;

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
     * @var OrderStateInterfaceFactory
     */
    private $orderStateFactory;

    public function __construct(
        QuoteResourceInterface $quoteResource,
        SessionDataManagerInterface $sessionDataManager,
        OrderFactory $orderFactory,
        OrderStateInterfaceFactory $orderStateFactory
    ) {
        $this->quoteResource = $quoteResource;
        $this->sessionDataManager = $sessionDataManager;
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
            $this->sessionDataManager->reserveOrder($reservedOrderId);

            return $orderState;
        }

        $orderState->setState(self::SUCCESS_STATE);
        $this->sessionDataManager->setOrderData($order);

        return $orderState;
    }
}
