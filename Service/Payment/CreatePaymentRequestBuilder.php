<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Service\Payment;

use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\CreatePaymentRequest;
use OnlinePayments\Sdk\Domain\CreatePaymentRequestFactory;
use Worldline\CreditCard\Service\CreatePaymentRequest\CardPaymentMethodSIDBuilder;
use Worldline\PaymentCore\Api\Config\GeneralSettingsConfigInterface;
use Worldline\PaymentCore\Api\Service\CreateRequest\Order\SurchargeDataBuilderInterface;
use Worldline\PaymentCore\Service\CreateRequest\Order\GeneralDataBuilder;

class CreatePaymentRequestBuilder
{
    /**
     * @var GeneralSettingsConfigInterface
     */
    private $generalSettings;

    /**
     * @var SurchargeDataBuilderInterface
     */
    private $surchargeDataBuilder;

    /**
     * @var CreatePaymentRequestFactory
     */
    private $createPaymentRequestFactory;

    /**
     * @var GeneralDataBuilder
     */
    private $generalOrderDataBuilder;

    /**
     * @var CardPaymentMethodSIDBuilder
     */
    private $cardPaymentMethodSIDBuilder;

    public function __construct(
        GeneralSettingsConfigInterface $generalSettings,
        SurchargeDataBuilderInterface $surchargeDataBuilder,
        CreatePaymentRequestFactory $createPaymentRequestFactory,
        GeneralDataBuilder $generalOrderDataBuilder,
        CardPaymentMethodSIDBuilder $cardPaymentMethodSIDBuilder
    ) {
        $this->generalSettings = $generalSettings;
        $this->surchargeDataBuilder = $surchargeDataBuilder;
        $this->createPaymentRequestFactory = $createPaymentRequestFactory;
        $this->generalOrderDataBuilder = $generalOrderDataBuilder;
        $this->cardPaymentMethodSIDBuilder = $cardPaymentMethodSIDBuilder;
    }

    public function build(CartInterface $quote): CreatePaymentRequest
    {
        $storeId = (int)$quote->getStoreId();
        $createPaymentRequest = $this->createPaymentRequestFactory->create();
        $order = $this->generalOrderDataBuilder->build($quote);
        if ($this->generalSettings->isApplySurcharge($storeId) && (float)$quote->getGrandTotal() > 0.00001) {
            $order->setSurchargeSpecificInput($this->surchargeDataBuilder->build());
        }

        $createPaymentRequest->setOrder($order);
        $createPaymentRequest->setCardPaymentMethodSpecificInput($this->cardPaymentMethodSIDBuilder->build($quote));

        return $createPaymentRequest;
    }
}
