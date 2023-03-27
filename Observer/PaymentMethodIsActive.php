<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Worldline\CreditCard\Gateway\Config\Config;
use Worldline\CreditCard\Ui\ConfigProvider;
use Worldline\PaymentCore\Api\AvailableMethodCheckerInterface;

class PaymentMethodIsActive implements ObserverInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var AvailableMethodCheckerInterface
     */
    private $availableMethodChecker;

    public function __construct(
        Config $config,
        AvailableMethodCheckerInterface $availableMethodChecker
    ) {
        $this->config = $config;
        $this->availableMethodChecker = $availableMethodChecker;
    }

    public function execute(Observer $observer): void
    {
        /** @var \Magento\Payment\Model\Method\Adapter $methodInstance */
        $methodInstance = $observer->getMethodInstance();
        $quote = $observer->getQuote();
        if ($methodInstance === null
            || $quote === null
            || !$observer->getResult()->getIsAvailable()
            || $methodInstance->getCode() !== ConfigProvider::CODE
            || !$this->config->isActive()
        ) {
            return;
        }

        $observer->getResult()->setIsAvailable(
            $this->availableMethodChecker->checkIsAvailable($this->config, $quote)
        );
    }
}
