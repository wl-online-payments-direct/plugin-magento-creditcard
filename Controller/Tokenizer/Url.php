<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Controller\Tokenizer;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Store\Model\StoreManagerInterface;
use Worldline\CreditCard\Ui\ConfigProvider\CreateHostedTokenizationResponseProcessor;

class Url extends Action implements HttpGetActionInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CreateHostedTokenizationResponseProcessor
     */
    private $createHostedTokenizationResponseProcessor;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        CreateHostedTokenizationResponseProcessor $createHostedTokenizationResponseProcessor
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->createHostedTokenizationResponseProcessor = $createHostedTokenizationResponseProcessor;
    }

    public function execute(): ResultInterface
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $storeId = (int) $this->storeManager->getStore()->getId();
        $createHostedTokenizationResponse = $this->createHostedTokenizationResponseProcessor->buildAndProcess($storeId);
        $result->setData(['url' => 'https://payment.' . $createHostedTokenizationResponse->getPartialRedirectUrl()]);

        return $result;
    }
}
