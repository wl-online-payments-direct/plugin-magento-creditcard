<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote\Payment;
use Worldline\CreditCard\Gateway\Request\PaymentDataBuilder;
use Worldline\PaymentCore\Api\PaymentManagerInterface;
use Worldline\PaymentCore\Api\Service\Payment\GetPaymentServiceInterface;
use Worldline\PaymentCore\Api\TransactionWLResponseManagerInterface;
use Worldline\PaymentCore\Model\PaymentStatusCode\StatusCodeRetrieverInterface;
use Worldline\PaymentCore\Model\Transaction\TransactionStatusInterface;

class StatusCodeRetriever implements StatusCodeRetrieverInterface
{
    /**
     * @var TransactionWLResponseManagerInterface
     */
    private $transactionWLResponseManager;

    /**
     * @var PaymentManagerInterface
     */
    private $paymentManager;

    /**
     * @var GetPaymentServiceInterface
     */
    private $getPaymentService;

    public function __construct(
        TransactionWLResponseManagerInterface $transactionWLResponseManager,
        PaymentManagerInterface $paymentManager,
        GetPaymentServiceInterface $getPaymentService
    ) {
        $this->transactionWLResponseManager = $transactionWLResponseManager;
        $this->paymentManager = $paymentManager;
        $this->getPaymentService = $getPaymentService;
    }

    /**
     * Extract status code and save payment and transaction data
     *
     * @param Payment $payment
     * @return int|null
     * @throws LocalizedException
     */
    public function getStatusCode(Payment $payment): ?int
    {
        $paymentId = (string)$payment->getAdditionalInformation(PaymentDataBuilder::PAYMENT_ID);
        if (!$paymentId) {
            return null;
        }

        $storeId = (int)$payment->getMethodInstance()->getStore();
        $paymentResponse = $this->getPaymentService->execute($paymentId, $storeId);
        $statusCode = (int)$paymentResponse->getStatusOutput()->getStatusCode();
        if (in_array(
            $statusCode,
            [TransactionStatusInterface::PENDING_CAPTURE_CODE, TransactionStatusInterface::CAPTURED_CODE]
        )) {
            $this->paymentManager->savePayment($paymentResponse);
            $this->transactionWLResponseManager->saveTransaction($paymentResponse);
        }

        return $statusCode;
    }
}
