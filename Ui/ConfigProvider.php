<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Worldline\CreditCard\Gateway\Config\Config;
use Worldline\CreditCard\Ui\ConfigProvider\CreateHostedTokenizationResponseProcessor;
use Worldline\PaymentCore\Api\Config\GeneralSettingsConfigInterface;
use Worldline\PaymentCore\Api\SurchargingQuoteRepositoryInterface;

/**
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var string
     */
    public const CODE = 'worldline_cc';

    /**
     * @var string
     */
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
     * @var CreateHostedTokenizationResponseProcessor
     */
    private $createHostedTokenizationResponseProcessor;

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
        CreateHostedTokenizationResponseProcessor $createHostedTokenizationResponseProcessor,
        PaymentIconsProvider $iconProvider,
        GeneralSettingsConfigInterface $generalSettings,
        SurchargingQuoteRepositoryInterface $surchargingQuoteRepository
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
        $this->createHostedTokenizationResponseProcessor = $createHostedTokenizationResponseProcessor;
        $this->iconProvider = $iconProvider;
        $this->generalSettings = $generalSettings;
        $this->surchargingQuoteRepository = $surchargingQuoteRepository;
    }

    public function getConfig(): array
    {
        $storeId = (int) $this->storeManager->getStore()->getId();
        try {
            $createHostedTokenizationResponse =
                $this->createHostedTokenizationResponseProcessor->buildAndProcess($storeId);

            $result = [
                'payment' => [
                    self::CODE => [
                        'isActive' => $this->config->isActive($storeId),
                        'url' => "https://payment.{$createHostedTokenizationResponse->getPartialRedirectUrl()}",
                        'icons' => $this->iconProvider->getIcons($storeId),
                        'ccVaultCode' => self::CC_VAULT_CODE
                    ]
                ]
            ];

            $result = $this->addSurchargingConfig($storeId, $result);

            return $result;
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
