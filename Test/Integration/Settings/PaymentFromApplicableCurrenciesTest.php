<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Test\Integration\Settings;

use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Payment\Model\MethodList;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Worldline\CreditCard\Ui\ConfigProvider;

/**
 * Test cases for configuration "Payment from Applicable Currencies"
 */
class PaymentFromApplicableCurrenciesTest extends TestCase
{
    /**
     * @var MethodList
     */
    private $methodList;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var ReinitableConfigInterface
     */
    private $reinitableConfig;

    /**
     * @var QuoteCollectionFactory
     */
    private $quoteCollectionFactory;

    public function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->methodList = $objectManager->get(MethodList::class);
        $this->configWriter = $objectManager->get(WriterInterface::class);
        $this->reinitableConfig = $objectManager->get(ReinitableConfigInterface::class);
        $this->quoteCollectionFactory = $objectManager->get(QuoteCollectionFactory::class);
    }

    /**
     * Test the selected specific currencies setting
     *
     * Steps:
     * 1) Payment from Applicable Currencies=Specific Currencies
     * 2) In multiselect choose EUR
     * 3) Go to checkout with EUR currency
     * Expected result: Payment Method is available
     * 4) Change your currency on USD
     * Expected result: Payment Method is NOT available
     *
     * @dataProvider testPaymentFromApplicableCurrenciesDataProvider
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoDbIsolation disabled
     */
    public function testPaymentFromApplicableCurrencies(string $specificCurrency, int $expectedDelta): void
    {
        $this->initConfiguration();

        $quote = $this->getQuote(); // the quote has default currency value - EUR
        $quote->getPayment()->setMethod(ConfigProvider::CODE);

        // count numbers of available payment methods
        $numberOfPaymentMethodsBeforeChangingCurrency = count($this->methodList->getAvailableMethods($quote));

        // set new specific currency in magento configuration
        $this->configWriter->save('currency/options/base', $specificCurrency);
        $this->configWriter->save('currency/options/default', $specificCurrency);
        $this->reinitableConfig->reinit();

        // count numbers of available payment methods after the currency has been changed
        $numberOfPaymentMethodsAfterChangingCurrency = $this->methodList->getAvailableMethods($quote);

        $this->assertCount(
            $numberOfPaymentMethodsBeforeChangingCurrency + $expectedDelta,
            $numberOfPaymentMethodsAfterChangingCurrency
        );
    }

    private function initConfiguration(): void
    {
        $this->configWriter->save('payment/worldline_cc/active', 1);
        $this->configWriter->save('payment/worldline_cc/allow_specific_currency', 1);
        $this->configWriter->save('payment/worldline_cc/currency', 'EUR');
        $this->configWriter->save('currency/options/base', 'USD');
        $this->configWriter->save('currency/options/default', 'USD');
        $this->reinitableConfig->reinit();
    }

    private function getQuote(): CartInterface
    {
        $quoteCollection = $this->quoteCollectionFactory->create();
        $quoteCollection->setOrder(CartInterface::KEY_ENTITY_ID);
        $quoteCollection->getSelect()->limit(1);
        return $quoteCollection->getLastItem();
    }

    public function testPaymentFromApplicableCurrenciesDataProvider(): array
    {
        return [
            [
                'EUR',
                1
            ],
            [
                'USD',
                0
            ]
        ];
    }
}
