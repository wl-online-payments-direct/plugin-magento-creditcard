<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use OnlinePayments\Sdk\Domain\PaymentResponse;
use Worldline\PaymentCore\Api\SubjectReaderInterface;

class PaymentDetailsHandler implements HandlerInterface
{
    /**
     * @var SubjectReaderInterface
     */
    private $subjectReader;

    public function __construct(SubjectReaderInterface $subjectReader)
    {
        $this->subjectReader = $subjectReader;
    }

    public function handle(array $handlingSubject, array $response): void
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);
        /** @var PaymentResponse $transaction */
        $transaction = $this->subjectReader->readTransaction($response);
        /** @var OrderPaymentInterface $payment */
        $payment = $paymentDO->getPayment();

        $payment->setCcTransId($transaction->getId());
        $payment->setLastTransId($transaction->getId());

        $payment->setCcStatusDescription($transaction->getStatus());
    }
}
