<?php

declare(strict_types=1);

namespace Worldline\CreditCard\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Worldline\CreditCard\Gateway\Config\Config;
use Worldline\PaymentCore\Gateway\SubjectReader;

class SettlementDataBuilder implements BuilderInterface
{
    public const AUTHORIZATION_MODE = 'authorization_mode';

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    public function __construct(SubjectReader $subjectReader)
    {
        $this->subjectReader = $subjectReader;
    }

    public function build(array $buildSubject): array
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();

        return [
            self::AUTHORIZATION_MODE => Config::AUTHORIZATION_MODE_SALE,
            PaymentDataBuilder::STORE_ID => (int)$payment->getMethodInstance()->getStore(),
        ];
    }
}
