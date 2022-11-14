<?php

declare(strict_types=1);

namespace Worldline\CreditCard\Gateway\Config;

use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Config\Config as PaymentGatewayConfig;
use Magento\Store\Model\ScopeInterface;
use Worldline\CreditCard\UI\ConfigProvider;

class Config extends PaymentGatewayConfig
{
    public const AUTHORIZATION_MODE = 'authorization_mode';
    public const PAYMENT_ACTION = 'payment_action';
    public const AUTHORIZATION_MODE_FINAL = 'FINAL_AUTHORIZATION';
    public const AUTHORIZATION_MODE_PRE = 'PRE_AUTHORIZATION';
    public const AUTHORIZATION_MODE_SALE = 'SALE';
    public const AUTHORIZE_CAPTURE = 'authorize_capture';
    public const CC_TYPES = 'cc_types';
    public const TEMPLATE_ID = 'template_id';
    public const SKIP_3D = 'skip_3d';
    public const PWA_ROUTE = 'pwa_route';
    public const KEY_ACTIVE = 'active';

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var State
     */
    private $appState;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var array
     */
    private $extendedConfigData;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        UrlInterface $urlBuilder,
        State $appState,
        $pathPattern = PaymentGatewayConfig::DEFAULT_PATH_PATTERN,
        ?array $extendedConfigData = []
    ) {
        parent::__construct($scopeConfig, ConfigProvider::CODE, $pathPattern);
        $this->urlBuilder = $urlBuilder;
        $this->appState = $appState;
        $this->scopeConfig = $scopeConfig;
        $this->extendedConfigData = $extendedConfigData;
    }

    public function getTemplateId(?int $storeId = null): string
    {
        return (string) $this->getValue(self::TEMPLATE_ID, $storeId);
    }

    public function getAuthorizationMode(?int $storeId = null): string
    {
        if ($this->getValue(self::PAYMENT_ACTION, $storeId) === self::AUTHORIZE_CAPTURE) {
            return self::AUTHORIZATION_MODE_SALE;
        }

        $authorizationMode = (string) $this->getValue(self::AUTHORIZATION_MODE, $storeId);
        switch ($authorizationMode) {
            case 'pre':
                return self::AUTHORIZATION_MODE_PRE;
            default:
                return self::AUTHORIZATION_MODE_FINAL;
        }
    }

    public function hasSkipAuthentication(?int $storeId = null): bool
    {
        return (bool) $this->getValue(self::SKIP_3D, $storeId);
    }

    /**
     * @param int|null $storeId
     * @return string
     * @throws LocalizedException
     */
    public function getReturnUrl(?int $storeId = null): string
    {
        $pwaRoute = (string) $this->getValue(self::PWA_ROUTE, $storeId);
        if ($pwaRoute && $this->appState->getAreaCode() === Area::AREA_GRAPHQL) {
            return $pwaRoute;
        }

        return $this->urlBuilder->getUrl('wl_creditcard/returns/returnThreeDSecure');
    }

    public function isActive(?int $storeId = null): bool
    {
        return (bool) $this->getValue(self::KEY_ACTIVE, $storeId);
    }

    public function getCcTypes(?int $storeId = null): string
    {
        return (string) $this->getValue(self::CC_TYPES, $storeId);
    }

    public function isVaultActive(?int $storeId = null): bool
    {
        return (bool) $this->scopeConfig->getValue(
            $this->extendedConfigData['worldline_cc_vault_active'],
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
