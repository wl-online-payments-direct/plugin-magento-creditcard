<?php

declare(strict_types=1);

namespace Worldline\CreditCard\Service\Creator\Request;

use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInput;
use OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInputFactory;
use OnlinePayments\Sdk\Domain\RedirectionDataFactory;
use OnlinePayments\Sdk\Domain\ThreeDSecure;
use OnlinePayments\Sdk\Domain\ThreeDSecureFactory;
use Worldline\CreditCard\Gateway\Config\Config;
use Worldline\CreditCard\Gateway\Request\PaymentDataBuilder;
use Worldline\CreditCard\UI\ConfigProvider;

class CardPaymentMethodSpecificInputDataBuilder
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
     * @var ThreeDSecureFactory
     */
    private $threeDSecureFactory;

    /**
     * @var RedirectionDataFactory
     */
    private $redirectionDataFactory;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var string|null
     */
    private $returnUrl;

    public function __construct(
        Config $config,
        CardPaymentMethodSpecificInputFactory $cardPaymentMethodSpecificInputFactory,
        ThreeDSecureFactory $threeDSecureFactory,
        RedirectionDataFactory $redirectionDataFactory,
        ManagerInterface $eventManager
    ) {
        $this->config = $config;
        $this->cardPaymentMethodSpecificInputFactory = $cardPaymentMethodSpecificInputFactory;
        $this->threeDSecureFactory = $threeDSecureFactory;
        $this->redirectionDataFactory = $redirectionDataFactory;
        $this->eventManager = $eventManager;
    }

    public function build(CartInterface $quote): CardPaymentMethodSpecificInput
    {
        $storeId = (int)$quote->getStoreId();
        $cardPaymentMethodSpecificInput = $this->cardPaymentMethodSpecificInputFactory->create();

        $cardPaymentMethodSpecificInput->setReturnUrl($this->getReturnUrl($storeId));
        $cardPaymentMethodSpecificInput->setThreeDSecure($this->getThreeDSecure($storeId));
        $cardPaymentMethodSpecificInput->setAuthorizationMode($this->config->getAuthorizationMode($storeId));
        $cardPaymentMethodSpecificInput->setToken(
            $quote->getPayment()->getAdditionalInformation(PaymentDataBuilder::TOKEN_ID)
        );

        $args = ['quote' => $quote, 'card_payment_method_specific_input' => $cardPaymentMethodSpecificInput];
        $this->eventManager->dispatch(ConfigProvider::CODE . '_card_payment_method_specific_input_builder', $args);

        return $cardPaymentMethodSpecificInput;
    }

    private function getReturnUrl(int $storeId): string
    {
        if (null === $this->returnUrl) {
            $this->returnUrl = $this->config->getReturnUrl($storeId);
        }

        return $this->returnUrl;
    }

    private function getThreeDSecure(int $storeId): ThreeDSecure
    {
        $threeDSecure = $this->threeDSecureFactory->create();
        $threeDSecure->setSkipAuthentication($this->config->hasSkipAuthentication($storeId));
        $redirectionData = $this->redirectionDataFactory->create();
        $redirectionData->setReturnUrl($this->getReturnUrl($storeId));
        $threeDSecure->setRedirectionData($redirectionData);

        return $threeDSecure;
    }
}
