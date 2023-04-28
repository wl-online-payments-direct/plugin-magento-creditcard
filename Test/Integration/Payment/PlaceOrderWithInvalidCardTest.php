<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Test\Integration\Payment;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Request\HttpFactory as HttpRequestFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Worldline\CreditCard\Controller\Returns\ReturnThreeDSecureFactory;
use Worldline\CreditCard\Controller\Returns\ReturnUrlFactory;
use Worldline\CreditCard\Ui\ConfigProvider;
use Worldline\PaymentCore\Api\QuoteResourceInterface;
use Worldline\PaymentCore\Api\Test\Infrastructure\ServiceStubSwitcherInterface;

/**
 * Test case about place order with invalid card
 */
class PlaceOrderWithInvalidCardTest extends TestCase
{
    /**
     * @var ReturnUrlFactory
     */
    private $returnUrlControllerFactory;

    /**
     * @var ReturnThreeDSecureFactory
     */
    private $returnThreeDControllerFactory;

    /**
     * @var HttpRequestFactory
     */
    private $httpRequestFactory;

    /**
     * @var QuoteResourceInterface
     */
    private $quoteExtendedRepository;

    public function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->returnUrlControllerFactory = $objectManager->get(ReturnUrlFactory::class);
        $this->returnThreeDControllerFactory = $objectManager->get(ReturnThreeDSecureFactory::class);
        $this->httpRequestFactory = $objectManager->get(HttpRequestFactory::class);
        $this->quoteExtendedRepository = $objectManager->get(QuoteResourceInterface::class);
        $objectManager->get(ServiceStubSwitcherInterface::class)->setEnabled(true);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Customer/_files/customer_address.php
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture default/currency/options/allow EUR
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default/currency/options/default EUR
     * @magentoConfigFixture default/sales_email/general/async_sending 0
     * @magentoConfigFixture current_store payment/worldline_cc/active 1
     * @magentoConfigFixture current_store payment/worldline_cc/payment_action authorize
     * @magentoConfigFixture current_store payment/worldline_cc/authorization_mode final
     * @magentoConfigFixture current_store worldline_connection/webhook/key test-X-Gcs-Keyid
     * @magentoConfigFixture current_store worldline_connection/webhook/secret_key test-X-Gcs-Signature
     */
    public function testReturnToThreeDController(): void
    {
        /** @var Session $customerSession */
        $customerSession = Bootstrap::getObjectManager()->get(Session::class);
        $customerSession->loginById(1);
        $this->updateQuote();

        $params = [
            'paymentId' => '3254564315'
        ];

        $request = $this->httpRequestFactory->create();
        $returnUrlController = $this->returnThreeDControllerFactory->create(['request' => $request]);

        $returnUrlController->getRequest()->setParams($params)->setMethod(HttpRequest::METHOD_POST);
        $result = $returnUrlController->execute();

        // validate controller result
        $reflectedResult = new \ReflectionObject($result);
        $urlProperty = $reflectedResult->getProperty('url');
        $urlProperty->setAccessible(true);
        $this->assertNotFalse(strpos($urlProperty->getValue($result), 'worldline/returns/reject'));

        // validate clean quote
        $quote = $this->quoteExtendedRepository->getQuoteByWorldlinePaymentId('3254564315');
        $this->assertNull($quote->getPayment()->getAdditionalInformation('payment_id'));
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Customer/_files/customer.php
     * @magentoDataFixture Magento/Customer/_files/customer_address.php
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture default/currency/options/allow EUR
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default/currency/options/default EUR
     * @magentoConfigFixture default/sales_email/general/async_sending 0
     * @magentoConfigFixture current_store payment/worldline_cc/active 1
     * @magentoConfigFixture current_store payment/worldline_cc/payment_action authorize
     * @magentoConfigFixture current_store payment/worldline_cc/authorization_mode final
     * @magentoConfigFixture current_store worldline_connection/webhook/key test-X-Gcs-Keyid
     * @magentoConfigFixture current_store worldline_connection/webhook/secret_key test-X-Gcs-Signature
     */
    public function testReturnToController(): void
    {
        /** @var Session $customerSession */
        $customerSession = Bootstrap::getObjectManager()->get(Session::class);
        $customerSession->loginById(1);
        $this->updateQuote();

        $params = [
            'hosted_tokenization_id' => '3254564315'
        ];

        $request = $this->httpRequestFactory->create();
        $returnUrlController = $this->returnUrlControllerFactory->create(['request' => $request]);

        $returnUrlController->getRequest()->setParams($params)->setMethod(HttpRequest::METHOD_POST);
        $result = $returnUrlController->execute();

        // validate controller result
        $reflectedResult = new \ReflectionObject($result);
        $jsonProperty = $reflectedResult->getProperty('json');
        $jsonProperty->setAccessible(true);
        $this->assertNotFalse(strpos($jsonProperty->getValue($result), 'worldline\/returns\/waiting'));

        // validate clean quote
        $quote = $this->quoteExtendedRepository->getQuoteByWorldlinePaymentId('3254564315');
        $this->assertEquals('3254564315_0', $quote->getPayment()->getAdditionalInformation('payment_id'));
    }

    private function updateQuote(): void
    {
        $quote = $this->quoteExtendedRepository->getQuoteByReservedOrderId('test01');
        $quote->setCustomerId(1);
        $quote->getPayment()->setMethod(ConfigProvider::CODE);
        $quote->getShippingAddress()->setShippingMethod('flatrate_flatrate');
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->getShippingAddress()->collectShippingRates();
        $quote->setCustomerEmail('example@worldline.com');
        $quote->getPayment()->setAdditionalInformation('payment_id', '3254564315_0');
        $quote->getPayment()->setAdditionalInformation('token_id', 'test');
        $quote->getPayment()->setAdditionalInformation('customer_id', 1);
        $quote->collectTotals();
        $quote->save();
    }
}
