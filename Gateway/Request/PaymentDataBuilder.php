<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Worldline\PaymentCore\Api\Payment\PaymentIdFormatterInterface;
use Worldline\PaymentCore\Api\SubjectReaderInterface;

class PaymentDataBuilder implements BuilderInterface
{
    public const PAYMENT_ID = 'payment_id';
    public const STORE_ID = 'store_id';

    /**
     * Refactor token
     */
    public const TOKEN_ID = 'token_id';

    /**
     * @var SubjectReaderInterface
     */
    private $subjectReader;

    /**
     * @var PaymentIdFormatterInterface
     */
    private $paymentIdFormatter;

    public function __construct(
        SubjectReaderInterface $subjectReader,
        PaymentIdFormatterInterface $paymentIdFormatter
    ) {
        $this->subjectReader = $subjectReader;
        $this->paymentIdFormatter = $paymentIdFormatter;
    }

    public function build(array $buildSubject): array
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $paymentId = $this->paymentIdFormatter->validateAndFormat(
            (string) $paymentDO->getPayment()->getAdditionalInformation(self::PAYMENT_ID),
            true
        );

        return [
            self::STORE_ID => (int)$paymentDO->getOrder()->getStoreId(),
            self::PAYMENT_ID => $paymentId,
        ];
    }
}
