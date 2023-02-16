<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Worldline\PaymentCore\Gateway\SubjectReader;

class PaymentDataBuilder implements BuilderInterface
{
    public const PAYMENT_ID = 'payment_id';
    public const STORE_ID = 'store_id';
    public const TOKEN_ID = 'token_id';

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    public function __construct(
        SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }

    public function build(array $buildSubject): array
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        return [
            self::STORE_ID => (int)$paymentDO->getOrder()->getStoreId(),
            self::PAYMENT_ID => $paymentDO->getPayment()->getAdditionalInformation(self::PAYMENT_ID),
        ];
    }
}
