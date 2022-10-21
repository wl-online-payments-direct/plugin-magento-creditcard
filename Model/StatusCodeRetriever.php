<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Model;

use Magento\Quote\Model\Quote\Payment;
use Worldline\CreditCard\Gateway\Request\PaymentDataBuilder;
use Worldline\CreditCard\Service\Getter\Request as GetterRequest;
use Worldline\PaymentCore\Api\TransactionWLResponseManagerInterface;
use Worldline\PaymentCore\Model\PaymentStatusCode\StatusCodeRetrieverInterface;
use Worldline\PaymentCore\Model\Transaction\TransactionStatusInterface;

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

    public function __construct(
        GetterRequest $getterRequest,
        TransactionWLResponseManagerInterface $transactionWLResponseManager
    ) {
        $this->getterRequest = $getterRequest;
        $this->transactionWLResponseManager = $transactionWLResponseManager;
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
            $this->transactionWLResponseManager->saveTransaction($paymentResponse);
        }

        return $statusCode;
    }
}
