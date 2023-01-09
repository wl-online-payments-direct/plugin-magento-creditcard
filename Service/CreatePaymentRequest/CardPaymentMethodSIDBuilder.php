<?php

declare(strict_types=1);

namespace Worldline\CreditCard\Service\CreatePaymentRequest;

use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInput;
use OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInputFactory;
use Worldline\CreditCard\Gateway\Config\Config;
use Worldline\CreditCard\Gateway\Request\PaymentDataBuilder;
use Worldline\CreditCard\Ui\ConfigProvider;
use Worldline\PaymentCore\Service\CreateRequest\ThreeDSecureDataBuilder;

class CardPaymentMethodSIDBuilder
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var CardPaymentMethodSpecificInputFactory
     */
    private $cardPaymentMethodSpecificInputFactory;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var ThreeDSecureDataBuilder
     */
    private $threeDSecureDataBuilder;

    public function __construct(
        Config $config,
        CardPaymentMethodSpecificInputFactory $cardPaymentMethodSpecificInputFactory,
        ManagerInterface $eventManager,
        ThreeDSecureDataBuilder $threeDSecureDataBuilder
    ) {
        $this->config = $config;
        $this->cardPaymentMethodSpecificInputFactory = $cardPaymentMethodSpecificInputFactory;
        $this->eventManager = $eventManager;
        $this->threeDSecureDataBuilder = $threeDSecureDataBuilder;
    }

    public function build(CartInterface $quote): CardPaymentMethodSpecificInput
    {
        $storeId = (int)$quote->getStoreId();
        $cardPaymentMethodSpecificInput = $this->cardPaymentMethodSpecificInputFactory->create();

        $cardPaymentMethodSpecificInput->setReturnUrl($this->config->getReturnUrl($storeId));
        $cardPaymentMethodSpecificInput->setThreeDSecure($this->threeDSecureDataBuilder->build($quote));
        $cardPaymentMethodSpecificInput->setAuthorizationMode($this->config->getAuthorizationMode($storeId));
        $cardPaymentMethodSpecificInput->setToken(
            $quote->getPayment()->getAdditionalInformation(PaymentDataBuilder::TOKEN_ID)
        );

        $args = ['quote' => $quote, 'card_payment_method_specific_input' => $cardPaymentMethodSpecificInput];
        $this->eventManager->dispatch(ConfigProvider::CODE . '_card_payment_method_specific_input_builder', $args);

        return $cardPaymentMethodSpecificInput;
    }
}
