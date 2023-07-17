<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Block\Checkout;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Page\Config;
use Worldline\PaymentCore\Api\Config\WorldlineConfigInterface;

class Head extends Template
{
    private const PROD_URL
        = "https://payment.direct.worldline-solutions.com/hostedtokenization/js/client/tokenizer.min.js";
    private const TEST_URL
        = "https://payment.preprod.direct.worldline-solutions.com/hostedtokenization/js/client/tokenizer.min.js";

    /**
     * @var WorldlineConfigInterface
     */
    private $worldlineConfig;

    public function __construct(
        Template\Context $context,
        Config $pageConfig,
        WorldlineConfigInterface $worldlineConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->pageConfig = $pageConfig;
        $this->worldlineConfig = $worldlineConfig;
        $this->manageScripts();
    }

    public function manageScripts(): void
    {
        $hostedTokenUrl = $this->worldlineConfig->isProductionMode() ? self::PROD_URL : self::TEST_URL;
        $this->pageConfig->addRemotePageAsset($hostedTokenUrl, 'js', ['src_type' => 'url']);
    }
}
