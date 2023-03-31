<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Worldline\CreditCard\Gateway\Config\Config;
use Worldline\PaymentCore\Api\Config\GeneralSettingsConfigInterface;
use Worldline\PaymentCore\Api\SurchargingQuoteRepositoryInterface;

/**
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class ConfigProvider implements ConfigProviderInterface
{
    public const CODE = 'worldline_cc';
    public const CC_VAULT_CODE = 'worldline_cc_vault';
    public const WL_CC_CONFIG_KEY = 'worldlineCreditCardCheckoutConfig';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var PaymentIconsProvider
     */
    private $iconProvider;

    /**
     * @var GeneralSettingsConfigInterface
     */
    private $generalSettings;

    /**
     * @var SurchargingQuoteRepositoryInterface
     */
    private $surchargingQuoteRepository;

    public function __construct(
        LoggerInterface $logger,
        Config $config,
        Session $checkoutSession,
        StoreManagerInterface $storeManager,
        PaymentIconsProvider $iconProvider,
        GeneralSettingsConfigInterface $generalSettings,
        SurchargingQuoteRepositoryInterface $surchargingQuoteRepository
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
        $this->iconProvider = $iconProvider;
        $this->generalSettings = $generalSettings;
        $this->surchargingQuoteRepository = $surchargingQuoteRepository;
    }

    public function getConfig(): array
    {
        $storeId = (int) $this->storeManager->getStore()->getId();
        try {
            return $this->addSurchargingConfig($storeId, [
                'payment' => [
                    self::CODE => [
                        'isActive' => $this->config->isActive($storeId),
                        'icons' => $this->iconProvider->getIcons($storeId),
                        'ccVaultCode' => self::CC_VAULT_CODE
                    ]
                ]
            ]);
        } catch (LocalizedException $e) {
            $this->logger->critical($e);
            return [
                'payment' => [
                    self::CODE => [
                        'isActive' => $this->config->isActive($storeId),
                    ]
                ]
            ];
        }
    }

    /**
     * @param int $storeId
     * @param array $result
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function addSurchargingConfig(int $storeId, array $result): array
    {
        if ($this->generalSettings->isApplySurcharge($storeId)) {
            $quote = $this->checkoutSession->getQuote();
            $grandTotal = (float)$quote->getGrandTotal();
            $surchargingQuote = $this->surchargingQuoteRepository->getByQuoteId((int)$quote->getId());
            if ((float)$quote->getGrandTotal() > 0.00001
                && (!$surchargingQuote->getId() || (float)$surchargingQuote->getQuoteGrandTotal() !== $grandTotal)) {
                $result[self::WL_CC_CONFIG_KEY]['isSurchargeEnabled'] = true;
            }
        }

        return $result;
    }
}
