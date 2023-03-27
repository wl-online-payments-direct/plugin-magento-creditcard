<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Controller\Returns;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Worldline\CreditCard\Model\ReturnRequestProcessor;
use Worldline\PaymentCore\Api\Payment\PaymentIdFormatterInterface;
use Worldline\PaymentCore\Model\OrderState;

class ReturnThreeDSecure extends Action implements HttpGetActionInterface
{
    private const SUCCESS_URL = 'checkout/onepage/success';
    private const WAITING_URL = 'worldline/returns/waiting';
    private const FAIL_URL = 'worldline/returns/failed';

    /**
     * @var ReturnRequestProcessor
     */
    private $returnRequestProcessor;

    /**
     * @var PaymentIdFormatterInterface
     */
    private $paymentIdFormatter;

    public function __construct(
        Context $context,
        ReturnRequestProcessor $returnRequestProcessor,
        PaymentIdFormatterInterface $paymentIdFormatter
    ) {
        parent::__construct($context);
        $this->returnRequestProcessor = $returnRequestProcessor;
        $this->paymentIdFormatter = $paymentIdFormatter;
    }

    public function execute(): ResultInterface
    {
        try {
            $paymentId = (string)$this->getRequest()->getParam('paymentId');
            $paymentId = $this->paymentIdFormatter->validateAndFormat($paymentId);

            /** @var OrderState $orderState */
            $orderState = $this->returnRequestProcessor->processRequest($paymentId);
            if ($orderState->getState() === ReturnRequestProcessor::WAITING_STATE) {
                return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
                    ->setPath(self::WAITING_URL, ['incrementId' => $orderState->getIncrementId()]);
            }

            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath(self::SUCCESS_URL);
        } catch (LocalizedException $exception) {
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath(self::FAIL_URL);
        }
    }
}
