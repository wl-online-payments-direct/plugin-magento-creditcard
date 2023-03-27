<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Test\Integration\Settings;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Worldline\CreditCard\Gateway\Config\Config;
use Worldline\CreditCard\Service\CreatePaymentRequest\CardPaymentMethodSIDBuilder;
use Worldline\CreditCard\Ui\ConfigProvider;
use Worldline\PaymentCore\Api\QuoteResourceInterface;
use Worldline\PaymentCore\Api\Test\Infrastructure\ServiceStubSwitcherInterface;
use Worldline\PaymentCore\Api\Test\Infrastructure\WebhookStubSenderInterface;
use Worldline\PaymentCore\Test\Infrastructure\StubData\Webhook\Authorization;

/**
 * Test cases for configuration "Payment Action" and "Authorization Mode"
 */
class PaymentAuthorizeAndCaptureActionTest extends TestCase
{
    /**
     * @var CardPaymentMethodSIDBuilder
     */
    private $cardPaymentMethodSIDBuilder;

    /**
     * @var CartInterface
     */
    private $quote;

    /**
     * @var  WebhookStubSenderInterface
     */
    private $webhookStubSender;

    /**
     * @var OrderInterfaceFactory
     */
    private $orderFactory;

    /**
     * @var QuoteResourceInterface
     */
    private $quoteExtendedRepository;

    public function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->cardPaymentMethodSIDBuilder = $objectManager->get(CardPaymentMethodSIDBuilder::class);
        $this->webhookStubSender = $objectManager->get(WebhookStubSenderInterface::class);
        $this->orderFactory = $objectManager->get(OrderInterfaceFactory::class);
        $this->quoteExtendedRepository = $objectManager->get(QuoteResourceInterface::class);
        $objectManager->get(ServiceStubSwitcherInterface::class)->setEnabled(true);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture current_store currency/options/allow EUR
     * @magentoConfigFixture current_store currency/options/base EUR
     * @magentoConfigFixture current_store currency/options/default EUR
     * @magentoConfigFixture current_store payment/worldline_cc/active 1
     * @magentoConfigFixture current_store payment/worldline_cc/payment_action authorize_capture
     * @magentoConfigFixture current_store worldline_connection/webhook/key test-X-Gcs-Keyid
     * @magentoConfigFixture current_store worldline_connection/webhook/secret_key test-X-Gcs-Signature
     */
    public function testAuthorizeAndCapture(): void
    {
        $quote = $this->getQuote();
        $cardPaymentMethodSpecificInput = $this->cardPaymentMethodSIDBuilder->build($this->getQuote());
        $this->assertEquals(
            Config::AUTHORIZATION_MODE_SALE,
            $cardPaymentMethodSpecificInput->getAuthorizationMode()
        );

        // send the webhook and place the order
        $result = $this->webhookStubSender->sendWebhook(Authorization::getData($quote->getReservedOrderId()));

        // validate controller result
        $reflectedResult = new \ReflectionObject($result);
        $jsonProperty = $reflectedResult->getProperty('json');
        $jsonProperty->setAccessible(true);
        $this->assertEquals('{"messages":[],"error":false}', $jsonProperty->getValue($result));

        // validate created order
        $order = $this->orderFactory->create()->loadByIncrementId($quote->getReservedOrderId());
        $this->assertTrue((bool) $order->getId());
        $this->assertEquals('processing', $order->getStatus());
        $this->assertEquals('worldline_cc', $order->getPayment()->getMethod());
        $this->assertCount(1, $order->getInvoiceCollection()->getItems());
    }

    private function getQuote(): CartInterface
    {
        if (empty($this->quote)) {
            $this->quote = $this->quoteExtendedRepository->getQuoteByReservedOrderId('test01');
            $this->quote->getPayment()->setMethod(ConfigProvider::CODE);
            $this->quote->getShippingAddress()->setShippingMethod('flatrate_flatrate');
            $this->quote->getShippingAddress()->setCollectShippingRates(true);
            $this->quote->getShippingAddress()->collectShippingRates();
            $this->quote->setCustomerEmail('example@worldline.com');
            $this->quote->getPayment()->setAdditionalInformation('payment_id', '3254564310_0');
            $this->quote->getPayment()->setAdditionalInformation('token_id', 'test');
            $this->quote->save();
        }

        return $this->quote;
    }
}
