<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Test\Integration\Settings;

use Magento\Framework\App\Area;
use Magento\Quote\Api\Data\CartInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\App\State;
use PHPUnit\Framework\TestCase;
use Worldline\CreditCard\Service\CreatePaymentRequest\CardPaymentMethodSIDBuilder;
use Worldline\CreditCard\Ui\ConfigProvider;
use Worldline\PaymentCore\Api\QuoteResourceInterface;
use Worldline\PaymentCore\Api\Test\Infrastructure\ServiceStubSwitcherInterface;

/**
 * Test case for configuration "Custom Return URL"
 */
class ReturnUrlForPwaTest extends TestCase
{
    /**
     * @var State
     */
    private $state;

    /**
     * @var CardPaymentMethodSIDBuilder
     */
    private $cardPaymentMethodSIDBuilder;

    /**
     * @var QuoteResourceInterface
     */
    private $quoteExtendedRepository;

    public function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->state = $objectManager->get(State::class);
        $this->cardPaymentMethodSIDBuilder = $objectManager->get(CardPaymentMethodSIDBuilder::class);
        $this->quoteExtendedRepository = $objectManager->get(QuoteResourceInterface::class);
        $objectManager->get(ServiceStubSwitcherInterface::class)->setEnabled(true);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture default/currency/options/allow EUR
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default/currency/options/default EUR
     * @magentoConfigFixture current_store payment/worldline_cc/active 1
     * @magentoConfigFixture current_store payment/worldline_cc/payment_action authorize_capture
     * @magentoConfigFixture current_store worldline_connection/webhook/key test-X-Gcs-Keyid
     * @magentoConfigFixture current_store worldline_connection/webhook/secret_key test-X-Gcs-Signature
     * @magentoConfigFixture current_store worldline_payment/general_settings/pwa_route https://pwa.com/checkout/success
     */
    public function testCustomUrl(): void
    {
        $this->state->setAreaCode(Area::AREA_GRAPHQL);

        $quote = $this->getQuote();
        $cardPaymentMethodSpecificInput = $this->cardPaymentMethodSIDBuilder->build($quote);

        $this->assertEquals(
            'https://pwa.com/checkout/success',
            $cardPaymentMethodSpecificInput->getReturnUrl()
        );
    }

    private function getQuote(): CartInterface
    {
        $quote = $this->quoteExtendedRepository->getQuoteByReservedOrderId('test01');
        $quote->setReservedOrderId('test02');
        $quote->getPayment()->setMethod(ConfigProvider::CODE);
        $quote->getShippingAddress()->setShippingMethod('flatrate_flatrate');
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->getShippingAddress()->collectShippingRates();
        $quote->setCustomerEmail('example@worldline.com');
        $quote->getPayment()->setAdditionalInformation('payment_id', '3254564310_0');
        $quote->getPayment()->setAdditionalInformation('token_id', 'test');
        $quote->collectTotals();
        $quote->save();

        return $quote;
    }
}
