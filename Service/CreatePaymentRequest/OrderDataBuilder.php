<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Service\CreatePaymentRequest;

use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\Order;
use OnlinePayments\Sdk\Domain\OrderFactory;
use Worldline\PaymentCore\Api\Service\CreateRequest\Order\AmountDataBuilderInterface;
use Worldline\PaymentCore\Api\Service\CreateRequest\Order\CustomerDataBuilderInterface;
use Worldline\PaymentCore\Api\Service\CreateRequest\Order\ReferenceDataBuilderInterface;
use Worldline\PaymentCore\Api\Service\CreateRequest\Order\ShippingAddressDataBuilderInterface;

class OrderDataBuilder
{
    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var AmountDataBuilderInterface
     */
    private $amountDataBuilder;

    /**
     * @var CustomerDataBuilderInterface
     */
    private $customerDataBuilder;

    /**
     * @var ReferenceDataBuilderInterface
     */
    private $referenceDataBuilder;

    /**
     * @var ShippingAddressDataBuilderInterface
     */
    private $shippingAddressDataBuilder;

    public function __construct(
        OrderFactory $orderFactory,
        AmountDataBuilderInterface $amountDataBuilder,
        CustomerDataBuilderInterface $customerDataBuilder,
        ReferenceDataBuilderInterface $referenceDataBuilder,
        ShippingAddressDataBuilderInterface $shippingAddressDataBuilder
    ) {
        $this->orderFactory = $orderFactory;
        $this->amountDataBuilder = $amountDataBuilder;
        $this->customerDataBuilder = $customerDataBuilder;
        $this->referenceDataBuilder = $referenceDataBuilder;
        $this->shippingAddressDataBuilder = $shippingAddressDataBuilder;
    }

    public function build(CartInterface $quote): Order
    {
        $order = $this->orderFactory->create();

        $order->setAmountOfMoney($this->amountDataBuilder->build($quote));
        $order->setCustomer($this->customerDataBuilder->build($quote));
        $order->setReferences($this->referenceDataBuilder->build($quote));
        $order->setShipping($this->shippingAddressDataBuilder->build($quote));

        return $order;
    }
}
