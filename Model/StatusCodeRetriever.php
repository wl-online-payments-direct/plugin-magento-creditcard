<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Model;

use Magento\Quote\Model\Quote\Payment;
use Worldline\CreditCard\Gateway\Request\PaymentDataBuilder;
use Worldline\CreditCard\Service\Getter\Request as GetterRequest;
use Worldline\PaymentCore\Api\TransactionWLResponseManagerInterface;
use Worldline\PaymentCore\Model\PaymentStatusCode\StatusCodeRetrieverInterface;
use Worldline\PaymentCore\Model\Transaction\TransactionStatusInterface;
use Worldline\PaymentCore\Api\PaymentManagerInterface;

class StatusCodeRetriever implements StatusCodeRetrieverInterface
{
    /**
     * @var GetterRequest
     */
    private $getterRequest;

    /**
     * @var TransactionWLResponseManagerInterface
     */
    private $transactionWLResponseManager;

    /**
     * @var PaymentManagerInterface
     */
    private $paymentManager;

    public function __construct(
        GetterRequest $getterRequest,
        TransactionWLResponseManagerInterface $transactionWLResponseManager,
        PaymentManagerInterface $paymentManager
    ) {
        $this->getterRequest = $getterRequest;
        $this->transactionWLResponseManager = $transactionWLResponseManager;
        $this->paymentManager = $paymentManager;
    }

    public function getStatusCode(Payment $payment): ?int
    {
        $paymentId = (string)$payment->getAdditionalInformation(PaymentDataBuilder::PAYMENT_ID);
        if (!$paymentId) {
            return null;
        }

        $storeId = (int)$payment->getMethodInstance()->getStore();
        $paymentResponse = $this->getterRequest->create($paymentId, $storeId);
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
