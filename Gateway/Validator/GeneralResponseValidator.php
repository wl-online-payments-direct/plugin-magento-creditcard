<?php

declare(strict_types=1);

namespace Worldline\CreditCard\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Worldline\PaymentCore\Gateway\SubjectReader;

class GeneralResponseValidator extends AbstractValidator
{
    /**
     * @var SubjectReader
     */
    private $subjectReader;

    public function __construct(
        ResultInterfaceFactory $resultFactory,
        SubjectReader $subjectReader
    ) {
        parent::__construct($resultFactory);
        $this->subjectReader = $subjectReader;
    }

    public function validate(array $validationSubject): ResultInterface
    {
        $this->subjectReader->readResponseObject($validationSubject);

        return $this->createResult(true);
    }
}
