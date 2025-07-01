<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Service\CreatePaymentRequest;

use Magento\Framework\Event\ManagerInterface;
use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInput;
use OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInputFactory;
use OnlinePayments\Sdk\Domain\PaymentProduct130SpecificInput;
use OnlinePayments\Sdk\Domain\PaymentProduct130SpecificThreeDSecure;
use Worldline\CreditCard\Gateway\Config\Config;
use Worldline\CreditCard\Gateway\Request\PaymentDataBuilder;
use Worldline\CreditCard\Ui\ConfigProvider;
use Worldline\PaymentCore\Api\Config\GeneralSettingsConfigInterface;
use Worldline\PaymentCore\Api\Service\CreateRequest\ThreeDSecureDataBuilderInterface;
use Worldline\PaymentCore\Model\ThreeDSecure\ParamsHandler;

class CardPaymentMethodSIDBuilder
{
    public const RETURN_URL = 'wl_creditcard/returns/returnThreeDSecure';
    public const SINGLE_AMOUNT_USE_CASE = 'single-amount';
    const MAX_SUPPORTED_NUMBER_OF_ITEMS = 99;

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
     * @var ThreeDSecureDataBuilderInterface
     */
    private $threeDSecureDataBuilder;

    /**
     * @var GeneralSettingsConfigInterface
     */
    private $generalSettings;

    public function __construct(
        Config $config,
        CardPaymentMethodSpecificInputFactory $cardPaymentMethodSpecificInputFactory,
        ManagerInterface $eventManager,
        ThreeDSecureDataBuilderInterface $threeDSecureDataBuilder,
        GeneralSettingsConfigInterface $generalSettings
    ) {
        $this->config = $config;
        $this->cardPaymentMethodSpecificInputFactory = $cardPaymentMethodSpecificInputFactory;
        $this->eventManager = $eventManager;
        $this->threeDSecureDataBuilder = $threeDSecureDataBuilder;
        $this->generalSettings = $generalSettings;
    }

    public function build(CartInterface $quote): CardPaymentMethodSpecificInput
    {
        $storeId = (int)$quote->getStoreId();
        $cardPaymentMethodSpecificInput = $this->cardPaymentMethodSpecificInputFactory->create();

        $cardPaymentMethodSpecificInput->setReturnUrl($this->generalSettings->getReturnUrl(self::RETURN_URL, $storeId));
        $cardPaymentMethodSpecificInput->setThreeDSecure($this->threeDSecureDataBuilder->build($quote, true));
        $cardPaymentMethodSpecificInput->setAuthorizationMode($this->config->getAuthorizationMode($storeId));
        $cardPaymentMethodSpecificInput->setPaymentProduct130SpecificInput(
            $this->buildPaymentProduct130SpecificInput($quote)
        );
        $cardPaymentMethodSpecificInput->setToken(
            $quote->getPayment()->getAdditionalInformation(PaymentDataBuilder::TOKEN_ID)
        );

        $args = ['quote' => $quote, 'card_payment_method_specific_input' => $cardPaymentMethodSpecificInput];
        $this->eventManager->dispatch(ConfigProvider::CODE . '_card_payment_method_specific_input_builder', $args);

        return $cardPaymentMethodSpecificInput;
    }

    private function buildPaymentProduct130SpecificInput(CartInterface $quote): ?PaymentProduct130SpecificInput
    {
        $storeId = (int)$quote->getStoreId();

        if (true === $this->generalSettings->isThreeDEnabled($storeId)) {
            $paymentProduct130SpecificInput = new PaymentProduct130SpecificInput();
            $paymentProduct130ThreeDSecure = new PaymentProduct130SpecificThreeDSecure();

            $paymentProduct130ThreeDSecure->setUsecase(self::SINGLE_AMOUNT_USE_CASE);
            $numberOfItems = $quote->getItemsQty() <= self::MAX_SUPPORTED_NUMBER_OF_ITEMS
                ? $quote->getItemsQty()
                : self::MAX_SUPPORTED_NUMBER_OF_ITEMS;
            $paymentProduct130ThreeDSecure->setNumberOfItems($numberOfItems);

            if (!$this->generalSettings->isAuthExemptionEnabled($storeId)) {
                $paymentProduct130ThreeDSecure->setAcquirerExemption(false);
            } elseif ($this->generalSettings->isAuthExemptionEnabled($storeId)) {
                $threeDSExemptionType = $this->generalSettings->getAuthExemptionType($storeId);
                $threeDSExemptedAmount = $threeDSExemptionType === ParamsHandler::LOW_VALUE_EXEMPTION_TYPE ?
                    $this->generalSettings->getAuthLowValueAmount($storeId) :
                    $this->generalSettings->getAuthTransactionRiskAnalysisAmount($storeId);

                (float)$threeDSExemptedAmount >= (float)$quote->getGrandTotal() ?
                    $paymentProduct130ThreeDSecure->setAcquirerExemption(true) :
                    $paymentProduct130ThreeDSecure->setAcquirerExemption(false);
            }
            $paymentProduct130SpecificInput->setThreeDSecure($paymentProduct130ThreeDSecure);

            return $paymentProduct130SpecificInput;
        }

        return null;
    }
}
