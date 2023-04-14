<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Test\Integration\Settings;

use Magento\Payment\Model\MethodInterface;
use Magento\Payment\Model\MethodList;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for configuration "title"
 */
class TitleTest extends TestCase
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
     * 2) Set title to Credit Card (Worldline Online Payments)+++++
     * 3) Go to checkout
     * Expected result: Payment Method is available with title "Credit Card (Worldline Online Payments)+++++"
     *
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture default/currency/options/allow EUR
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default/currency/options/default EUR
     * @magentoConfigFixture current_store payment/worldline_cc/active 1
     * @magentoConfigFixture current_store payment/worldline_cc/title Credit Card (Worldline Online Payments)+++++
     * @magentoDbIsolation enabled
     */
    public function testFirstInOrder(): void
    {
        $quote = $this->getQuote();
        $paymentMethods = $this->methodList->getAvailableMethods($quote);
        $ccPaymentMethod = $this->getCardPaymentMethod($paymentMethods);

        $this->assertTrue($ccPaymentMethod instanceof MethodInterface);

        $this->assertEquals(
            'Credit Card (Worldline Online Payments)+++++',
            $ccPaymentMethod->getConfigData('title')
        );
    }

    private function getCardPaymentMethod(array $paymentMethods): ?MethodInterface
    {
        $result = null;

        foreach ($paymentMethods as $paymentMethod) {
            if ($paymentMethod->getCode() === 'worldline_cc') {
                $result = $paymentMethod;
                break;
            }
        }

        return $result;
    }

    private function getQuote(): CartInterface
    {
        $quoteCollection = $this->quoteCollectionFactory->create();
        $quoteCollection->setOrder(CartInterface::KEY_ENTITY_ID);
        $quoteCollection->getSelect()->limit(1);
        return $quoteCollection->getLastItem();
    }
}
