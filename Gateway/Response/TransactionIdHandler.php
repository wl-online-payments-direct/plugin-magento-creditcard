<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;
use OnlinePayments\Sdk\DataObject;
use Worldline\PaymentCore\Api\SubjectReaderInterface;

class TransactionIdHandler implements HandlerInterface
{
    /**
     * @var SubjectReaderInterface
     */
    private $subjectReader;

    public function __construct(
        SubjectReaderInterface $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }

    public function handle(array $handlingSubject, array $response): void
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);

        if ($paymentDO->getPayment() instanceof Payment) {
            $transaction = $this->subjectReader->readTransaction($response);

            $orderPayment = $paymentDO->getPayment();
            $this->setTransactionId($orderPayment, $transaction);

            $orderPayment->setIsTransactionClosed($this->shouldCloseTransaction());
            $closed = $this->shouldCloseParentTransaction($orderPayment);
            $orderPayment->setShouldCloseParentTransaction($closed);
        }
    }

    protected function setTransactionId(Payment $orderPayment, DataObject $transaction): void
    {
        $orderPayment->setTransactionId($transaction->getId());
    }

    /**
     * Whether transaction should be closed
     *
     * @return bool
     */
    protected function shouldCloseTransaction(): bool
    {
        return false;
    }

    /**
     * Whether parent transaction should be closed
     *
     * @param Payment $orderPayment
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function shouldCloseParentTransaction(Payment $orderPayment): bool
    {
        return false;
    }
}
