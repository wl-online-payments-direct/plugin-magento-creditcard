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
use Worldline\PaymentCore\Model\Order\RejectOrderException;

class ReturnUrl extends Action implements HttpGetActionInterface
{
    private const SUCCESS_URL = 'checkout/onepage/success';
    private const WAITING_URL = 'worldline/returns/waiting';
    private const FAIL_URL = 'worldline/returns/failed';
    private const REJECT_URL = 'worldline/returns/reject';

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var ReturnRequestProcessor
     */
    private $returnRequestProcessor;

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
        $url = $this->url->getRouteUrl(self::SUCCESS_URL);

        try {
            $hostedTokenizationId = (string)$this->getRequest()->getParam('hosted_tokenization_id');
            $orderState = $this->returnRequestProcessor->processRequest(null, $hostedTokenizationId);
            if ($orderState && $orderState->getState() === ReturnRequestProcessor::WAITING_STATE) {
                $url = $this->url->getRouteUrl(self::WAITING_URL, ['incrementId' => $orderState->getIncrementId()]);
            }
        } catch (RejectOrderException $exception) {
            $url = $this->url->getRouteUrl(self::REJECT_URL);
        } catch (LocalizedException $exception) {
            $url = $this->url->getRouteUrl(self::FAIL_URL);
        }

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData(['url' => $url]);
    }
}
