<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Controller\Returns;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Worldline\CreditCard\Model\ReturnRequestProcessor;
use Worldline\PaymentCore\Model\OrderState;

class ReturnUrl extends Action implements HttpGetActionInterface
{
    private const SUCCESS_URL = 'checkout/onepage/success';
    private const WAITING_URL = 'worldline/returns/waiting';
    private const FAIL_URL = 'worldline/returns/failed';

    /**
     * @var ReturnRequestProcessor
     */
    private $returnRequestProcessor;

    /**
     * @var UrlInterface
     */
    private $url;

    public function __construct(
        Context $context,
        ReturnRequestProcessor $returnRequestProcessor
    ) {
        parent::__construct($context);
        $this->url = $context->getUrl();
        $this->returnRequestProcessor = $returnRequestProcessor;
    }

    public function execute(): ResultInterface
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        try {
            $hostedTokenizationId = (string)$this->getRequest()->getParam('hosted_tokenization_id');

            /** @var OrderState $orderState */
            $orderState = $this->returnRequestProcessor->processRequest($hostedTokenizationId);
            if ($orderState->getState() === ReturnRequestProcessor::WAITING_STATE) {
                $url = $this->url->getRouteUrl(self::WAITING_URL, ['incrementId' => $orderState->getIncrementId()]);
                $result->setData(['url' => $url]);
            } else {
                $result->setData(['url' => $this->url->getRouteUrl(self::SUCCESS_URL)]);
            }
        } catch (LocalizedException $exception) {
            $result->setData(['url' => $this->url->getRouteUrl(self::FAIL_URL)]);
        }

        return $result;
    }
}
