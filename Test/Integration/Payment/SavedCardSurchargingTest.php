<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Test\Integration\Payment;

use Magento\Customer\Model\Session;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Worldline\CreditCard\Service\Payment\CreatePaymentRequestBuilder;
use Worldline\CreditCard\Ui\ConfigProvider;
use Worldline\CreditCard\WebApi\CalculateSurchargeManagement;
use Worldline\PaymentCore\Api\QuoteResourceInterface;
use Worldline\PaymentCore\Api\Test\Infrastructure\ServiceStubSwitcherInterface;
use Worldline\PaymentCore\Api\Test\Infrastructure\WebhookStubSenderInterface;
use Worldline\PaymentCore\Infrastructure\ActiveVault\FakePaymentToken;
use Worldline\PaymentCore\Infrastructure\StubData\Webhook\Authorization;
use Worldline\PaymentCore\Service\CreateRequest\Order\SurchargeDataBuilder;

/**
 * Test case about place order with surcharging and saved card
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SavedCardSurchargingTest extends TestCase
{
    /**
     * @var CreatePaymentRequestBuilder
     */
    private $createRequestBuilder;

    /**
     * @var QuoteResourceInterface
     */
    private $quoteExtendedRepository;

    /**
     * @var CalculateSurchargeManagement
     */
    private $calculateSurchargeManagement;

    /**
     * @var  WebhookStubSenderInterface
     */
    private $webhookStubSender;

    /**
     * @var OrderInterfaceFactory
     */
    private $orderFactory;

    public function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->createRequestBuilder = $objectManager->get(CreatePaymentRequestBuilder::class);
        $this->quoteExtendedRepository = $objectManager->get(QuoteResourceInterface::class);
        $this->calculateSurchargeManagement = $objectManager->get(CalculateSurchargeManagement::class);
        $this->webhookStubSender = $objectManager->get(WebhookStubSenderInterface::class);
        $this->orderFactory = $objectManager->get(OrderInterfaceFactory::class);
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
     * @magentoConfigFixture current_store payment/worldline_cc_vault/active 1
     * @magentoConfigFixture current_store payment/worldline_cc/payment_action authorize
     * @magentoConfigFixture current_store payment/worldline_cc/authorization_mode final
     * @magentoConfigFixture current_store worldline_payment/general_settings/apply_surcharge 1
     * @magentoConfigFixture current_store worldline_connection/webhook/key test-X-Gcs-Keyid
     * @magentoConfigFixture current_store worldline_connection/webhook/secret_key test-X-Gcs-Signature
     */
    public function testSurchargingWithSavedCard(): void
    {
        /** @var Session $customerSession */
        $customerSession = Bootstrap::getObjectManager()->get(Session::class);
        $customerSession->loginById(1);

        Bootstrap::getObjectManager()->get(FakePaymentToken::class)->createVaultToken(ConfigProvider::CODE);

        $quote = $this->getQuote();
        $grandTotalBeforeCalculateSurcharging = $quote->getGrandTotal();

        $createPaymentRequest = $this->createRequestBuilder->build($quote);
        $surchargeSpecificInput = $createPaymentRequest->getOrder()->getSurchargeSpecificInput();

        // validate surcharge settings
        $this->assertNotNull($surchargeSpecificInput);
        $this->assertEquals(SurchargeDataBuilder::SURCHARGE_MODE, $surchargeSpecificInput->getMode());

        // validate surcharge calculation
        $totalSurcharging = $this->calculateSurchargeManagement->calculate((int)$quote->getId(), '3254564310');
        $this->assertEquals(10.0, $totalSurcharging);

        // validate quote total calculation
        $quote->setTotalsCollectedFlag(false);
        $quote->collectTotals();
        $quote->save();
        $this->assertEquals($grandTotalBeforeCalculateSurcharging + 10.0, $quote->getGrandTotal());

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
        $this->assertEquals(ConfigProvider::CODE, $order->getPayment()->getMethod());
        $this->assertEquals($grandTotalBeforeCalculateSurcharging + 10.0, $order->getGrandTotal());
    }

    private function getQuote(): CartInterface
    {
        $quote = $this->quoteExtendedRepository->getQuoteByReservedOrderId('test01');
        $quote->getPayment()->setMethod(ConfigProvider::CC_VAULT_CODE);
        $quote->getShippingAddress()->setShippingMethod('flatrate_flatrate');
        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->getShippingAddress()->collectShippingRates();
        $quote->setCustomerEmail('example@worldline.com');
        $quote->getPayment()->setAdditionalInformation('payment_id', '3254564310_0');
        $quote->getPayment()->setAdditionalInformation('token_id', 'test');
        $quote->getPayment()->setAdditionalInformation('public_hash', 'fakePublicHash');
        $quote->getPayment()->setAdditionalInformation('customer_id', 1);
        $quote->collectTotals();
        $quote->save();

        return $quote;
    }
}
