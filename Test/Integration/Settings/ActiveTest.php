<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Test\Integration\Settings;

use Magento\Payment\Model\MethodList;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for configuration "Payment enabled/disabled"
 */
class ActiveTest extends TestCase
{
    /**
     * @var MethodList
     */
    private $methodList;

    /**
     * @var QuoteCollectionFactory
     */
    private $quoteCollectionFactory;

    public function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->methodList = $objectManager->get(MethodList::class);
        $this->quoteCollectionFactory = $objectManager->get(QuoteCollectionFactory::class);
    }

    /**
     * Steps:
     * 1) Payment enabled=yes
     * 2) Go to checkout
     * Expected result: Payment Method is available
     *
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture default/currency/options/allow EUR
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default/currency/options/default EUR
     * @magentoConfigFixture current_store payment/worldline_cc/active 1
     * @magentoDbIsolation enabled
     */
    public function testEnabled(): void
    {
        $quote = $this->getQuote();

        $paymentMethods = $this->methodList->getAvailableMethods($quote);
        $paymentMethodCodes = array_map(static function ($method) {
            return $method->getCode();
        }, $paymentMethods);

        $this->assertContains('worldline_cc', $paymentMethodCodes);
    }

    /**
     * Steps:
     * 1) Payment enabled=no
     * 2) Go to checkout
     * Expected result: Payment Method is not available
     *
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture default/currency/options/allow EUR
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default/currency/options/default EUR
     * @magentoConfigFixture current_store payment/worldline_cc/active 0
     * @magentoDbIsolation enabled
     */
    public function testDisabled(): void
    {
        $quote = $this->getQuote();

        $paymentMethods = $this->methodList->getAvailableMethods($quote);
        $paymentMethodCodes = array_map(static function ($method) {
            return $method->getCode();
        }, $paymentMethods);

        $this->assertNotContains('worldline_cc', $paymentMethodCodes);
    }

    private function getQuote(): CartInterface
    {
        $quoteCollection = $this->quoteCollectionFactory->create();
        $quoteCollection->setOrder(CartInterface::KEY_ENTITY_ID);
        $quoteCollection->getSelect()->limit(1);
        return $quoteCollection->getLastItem();
    }
}
