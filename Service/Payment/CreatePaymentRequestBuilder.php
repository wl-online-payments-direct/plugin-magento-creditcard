<?php

declare(strict_types=1);

namespace Worldline\CreditCard\Service\Payment;

use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\CreatePaymentRequest;
use OnlinePayments\Sdk\Domain\CreatePaymentRequestFactory;
use Worldline\CreditCard\Service\CreatePaymentRequest\CardPaymentMethodSIDBuilder;
use Worldline\CreditCard\Service\CreatePaymentRequest\OrderDataBuilder;

class CreatePaymentRequestBuilder
{
    /**
     * @var CreatePaymentRequestFactory
     */
    private $createPaymentRequestFactory;

    /**
     * @var OrderDataBuilder
     */
    private $orderDataBuilder;

    /**
     * @var CardPaymentMethodSIDBuilder
     */
    private $cardPaymentMethodSIDBuilder;

    public function __construct(
        CreatePaymentRequestFactory $createPaymentRequestFactory,
        OrderDataBuilder $orderDataBuilder,
        CardPaymentMethodSIDBuilder $cardPaymentMethodSIDBuilder
    ) {
        $this->createPaymentRequestFactory = $createPaymentRequestFactory;
        $this->orderDataBuilder = $orderDataBuilder;
        $this->cardPaymentMethodSIDBuilder = $cardPaymentMethodSIDBuilder;
    }

    public function build(CartInterface $quote): CreatePaymentRequest
    {
        $createPaymentRequest = $this->createPaymentRequestFactory->create();
        $createPaymentRequest->setOrder($this->orderDataBuilder->build($quote));
        $createPaymentRequest->setCardPaymentMethodSpecificInput($this->cardPaymentMethodSIDBuilder->build($quote));

        return $createPaymentRequest;
    }
}
