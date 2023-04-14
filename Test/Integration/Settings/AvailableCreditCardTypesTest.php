<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Test\Integration\Settings;

use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Worldline\CreditCard\Ui\ConfigProvider;
use Worldline\CreditCard\Ui\PaymentIconsProvider;
use Worldline\PaymentCore\Api\QuoteResourceInterface;
use Worldline\PaymentCore\Api\Test\Infrastructure\ServiceStubSwitcherInterface;

/**
 * Test cases for configuration "Available Credit Card Types"
 */
class AvailableCreditCardTypesTest extends TestCase
{
    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var PaymentIconsProvider
     */
    private $iconsProvider;

    /**
     * @var ReinitableConfigInterface
     */
    private $reinitableConfig;

    /**
     * @var QuoteResourceInterface
     */
    private $quoteExtendedRepository;

    public function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->configWriter = $objectManager->get(WriterInterface::class);
        $this->iconsProvider = $objectManager->get(PaymentIconsProvider::class);
        $this->reinitableConfig = $objectManager->get(ReinitableConfigInterface::class);
        $this->quoteExtendedRepository = $objectManager->get(QuoteResourceInterface::class);
        $objectManager->get(ServiceStubSwitcherInterface::class)->setEnabled(true);
    }

    /**
     * Test the selected available Credit Card types setting
     *
     * Steps:
     * 1) Available Credit Card Types = 7 payments (ALL)
     * 2) Go to checkout
     * Expected result: 7 icons are available
     * 3) Change available Credit Card types setting on 3 payments
     * Expected result: 3 icons are available
     *
     * @dataProvider testAvailableCreditCardTypesDataProvider
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture current_store currency/options/allow EUR
     * @magentoConfigFixture current_store currency/options/base EUR
     * @magentoConfigFixture current_store currency/options/default EUR
     * @magentoConfigFixture current_store payment/worldline_cc/active 1
     * @magentoConfigFixture current_store payment/worldline_cc/payment_action authorize_capture
     * @magentoConfigFixture current_store payment/worldline_cc/allowspecific 0
     * @magentoConfigFixture current_store payment/worldline_cc/allow_specific_currency 0
     * @magentoConfigFixture current_store payment/worldline_cc/cc_types americanexpress,cartebancaire,dinersclub,jcb
     * @magentoAppIsolation enabled
     */
    public function testAvailableCreditCardTypes(string $paymentList, int $expectedCount): void
    {
        $quote = $this->getQuote();

        // set new available types in credit card configuration
        $this->configWriter->save('payment/worldline_cc/cc_types', $paymentList);
        $this->reinitableConfig->reinit();

        // count numbers of available payment icons after the available types has been changed
        $paymentIcons = $this->iconsProvider->getIcons((int)$quote->getStoreId());

        $this->assertCount($expectedCount, $paymentIcons);
        $paymentIconsString = implode(",", array_keys($paymentIcons));
        $this->assertEquals($paymentList, $paymentIconsString);
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

    public function testAvailableCreditCardTypesDataProvider(): array
    {
        return [
            [
                'maestro,mastercard,visa',
                3
            ],
            [
                'americanexpress,cartebancaire,dinersclub,jcb,maestro',
                5
            ],
            [
                'americanexpress,cartebancaire,dinersclub,jcb,maestro,mastercard,visa',
                7
            ]
        ];
    }
}
