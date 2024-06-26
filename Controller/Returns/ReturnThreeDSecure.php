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
use Worldline\PaymentCore\Model\Order\RejectOrderException;

class ReturnThreeDSecure extends Action implements HttpGetActionInterface
{
    private const SUCCESS_URL = 'checkout/onepage/success';
    private const WAITING_URL = 'worldline/returns/waiting';
    private const FAIL_URL = 'worldline/returns/failed';
    private const REJECT_URL = 'worldline/returns/reject';

    /**
     * @var ReturnRequestProcessor
     */
    private $returnRequestProcessor;

    public function __construct(
        Context $context,
        ReturnRequestProcessor $returnRequestProcessor
    ) {
        parent::__construct($context);
        $this->returnRequestProcessor = $returnRequestProcessor;
    }

    public function execute(): ResultInterface
    {
        try {
            $paymentId = (string)$this->getRequest()->getParam('paymentId');
            $orderState = $this->returnRequestProcessor->processRequest($paymentId);
            if ($orderState && $orderState->getState() === ReturnRequestProcessor::WAITING_STATE) {
                return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)
                    ->setPath(self::WAITING_URL, ['incrementId' => $orderState->getIncrementId()]);
            }

            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath(self::SUCCESS_URL);
        } catch (RejectOrderException $exception) {
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath(self::REJECT_URL);
        } catch (LocalizedException $exception) {
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath(self::FAIL_URL);
        }
    }
}
