<?php
declare(strict_types=1);

namespace Worldline\CreditCard\WebApi\CreatePaymentManagement;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\PaymentInterface;
use OnlinePayments\Sdk\Domain\MerchantAction;
use Worldline\CreditCard\Gateway\Request\PaymentDataBuilder;
use Worldline\CreditCard\Service\Payment\CreatePaymentRequestBuilder;
use Worldline\PaymentCore\Api\Payment\PaymentIdFormatterInterface;
use Worldline\PaymentCore\Api\Service\Payment\CreatePaymentServiceInterface;
use Worldline\PaymentCore\Model\DataAssigner\DataAssignerInterface;

class CreatePaymentDataAssigner implements DataAssignerInterface
{
    /**
     * @var CreatePaymentRequestBuilder
     */
    private $createRequestBuilder;

    /**
     * @var CreatePaymentServiceInterface
     */
    private $createPaymentService;

    /**
     * @var PaymentIdFormatterInterface
     */
    private $paymentIdFormatter;

    public function __construct(
        CreatePaymentRequestBuilder $createRequestBuilder,
        CreatePaymentServiceInterface $createPaymentService,
        PaymentIdFormatterInterface $paymentIdFormatter
    ) {
        $this->createRequestBuilder = $createRequestBuilder;
        $this->createPaymentService = $createPaymentService;
        $this->paymentIdFormatter = $paymentIdFormatter;
    }

    /**
     * Assign payment id and identify redirect url
     *
     * @param PaymentInterface $payment
     * @param array $additionalInformation
     * @return void
     * @throws LocalizedException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function assign(PaymentInterface $payment, array $additionalInformation): void
    {
        $quote = $payment->getQuote();
        $request = $this->createRequestBuilder->build($quote);
        $response = $this->createPaymentService->execute($request, (int)$quote->getStoreId());

        $wlPaymentId = $this->paymentIdFormatter->validateAndFormat((string) $response->getPayment()->getId());
        $payment->setAdditionalInformation(PaymentDataBuilder::PAYMENT_ID, $wlPaymentId);

        $action = $response->getMerchantAction();

        if ($action instanceof MerchantAction) {
            $payment->setAdditionalInformation('RETURNMAC', $action->getRedirectData()->getRETURNMAC());
            $payment->setWlRedirectUrl($action->getRedirectData()->getRedirectURL());
        }
    }
}
