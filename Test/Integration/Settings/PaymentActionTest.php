<?php

declare(strict_types=1);

namespace Worldline\CreditCard\Test\Integration\Settings;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Worldline\CreditCard\Gateway\Config\Config;
use Worldline\CreditCard\Service\CreatePaymentRequest\CardPaymentMethodSIDBuilder;
use Worldline\CreditCard\Ui\ConfigProvider;

/**
 * Test cases for configuration "Payment Action" and "Authorization Mode"
 */
class PaymentActionTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var CardPaymentMethodSIDBuilder
     */
    private $cardPaymentMethodSIDBuilder;

    /**
     * @var CartInterface|null
     */
    private $quote;

    public function setUp(): void
    {
        $this->resetQuote();
        $this->objectManager = Bootstrap::getObjectManager();
        $this->quoteRepository = $this->objectManager->get(CartRepositoryInterface::class);
        $this->cardPaymentMethodSIDBuilder = $this->objectManager->get(CardPaymentMethodSIDBuilder::class);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture current_store currency/options/allow EUR
     * @magentoConfigFixture current_store currency/options/base EUR
     * @magentoConfigFixture current_store currency/options/default EUR
     * @magentoConfigFixture current_store payment/worldline_cc/active 1
     * @magentoConfigFixture current_store payment/worldline_cc/payment_action authorize
     * @magentoConfigFixture current_store payment/worldline_cc/authorization_mode final
     */
    public function testAuthorizeWithFinalAuthorization(): void
    {
        $this->getQuote()->getPayment()->setAdditionalInformation('token_id', 'token_id');
        $cardPaymentMethodSpecificInput = $this->cardPaymentMethodSIDBuilder->build($this->getQuote());
        $this->assertEquals(
            Config::AUTHORIZATION_MODE_FINAL,
            $cardPaymentMethodSpecificInput->getAuthorizationMode()
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture current_store currency/options/allow EUR
     * @magentoConfigFixture current_store currency/options/base EUR
     * @magentoConfigFixture current_store currency/options/default EUR
     * @magentoConfigFixture current_store payment/worldline_cc/active 1
     * @magentoConfigFixture current_store payment/worldline_cc/payment_action authorize
     * @magentoConfigFixture current_store payment/worldline_cc/authorization_mode pre
     */
    public function testAuthorizeWithPreAuthorization(): void
    {
        $this->getQuote()->getPayment()->setAdditionalInformation('token_id', 'token_id');
        $cardPaymentMethodSpecificInput = $this->cardPaymentMethodSIDBuilder->build($this->getQuote());
        $this->assertEquals(
            Config::AUTHORIZATION_MODE_PRE,
            $cardPaymentMethodSpecificInput->getAuthorizationMode()
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture current_store currency/options/allow EUR
     * @magentoConfigFixture current_store currency/options/base EUR
     * @magentoConfigFixture current_store currency/options/default EUR
     * @magentoConfigFixture current_store payment/worldline_cc/active 1
     * @magentoConfigFixture current_store payment/worldline_cc/payment_action authorize_capture
     */
    public function testAuthorizeAndCapture(): void
    {
        $this->getQuote()->getPayment()->setAdditionalInformation('token_id', 'token_id');
        $cardPaymentMethodSpecificInput = $this->cardPaymentMethodSIDBuilder->build($this->getQuote());
        $this->assertEquals(
            Config::AUTHORIZATION_MODE_SALE,
            $cardPaymentMethodSpecificInput->getAuthorizationMode()
        );
    }

    private function getQuote(): CartInterface
    {
        if (empty($this->quote)) {
            $quotes = $this->quoteRepository->getList($this->objectManager->get(SearchCriteriaInterface::class))
                ->getItems();
            $this->quote = end($quotes);
            $this->quote->getPayment()->setMethod(ConfigProvider::CODE);
        }

        return $this->quote;
    }

    private function resetQuote(): void
    {
        $this->quote = null;
    }
}
