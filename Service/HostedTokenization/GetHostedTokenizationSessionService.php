<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Service\HostedTokenization;

use Magento\Framework\Exception\LocalizedException;
use OnlinePayments\Sdk\Domain\GetHostedTokenizationResponse;
use Psr\Log\LoggerInterface;
use Worldline\PaymentCore\Api\ClientProviderInterface;
use Worldline\CreditCard\Api\Service\HostedTokenization\GetHostedTokenizationSessionServiceInterface;
use Worldline\PaymentCore\Api\Config\WorldlineConfigInterface;

/**
 * @link https://support.direct.ingenico.com/en/documentation/api/reference/#tag/HostedTokenization/operation/GetHostedTokenizationApi
 */
class GetHostedTokenizationSessionService implements GetHostedTokenizationSessionServiceInterface
{
    /**
     * @var WorldlineConfigInterface
     */
    private $worldlineConfig;

    /**
     * @var ClientProviderInterface
     */
    private $clientProvider;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        WorldlineConfigInterface $worldlineConfig,
        ClientProviderInterface $clientProvider,
        LoggerInterface $logger
    ) {
        $this->worldlineConfig = $worldlineConfig;
        $this->clientProvider = $clientProvider;
        $this->logger = $logger;
    }

    /**
     * Retrieve hosted tokenization session
     *
     * @param string $hostedTokenizationId
     * @param int|null $storeId
     * @return GetHostedTokenizationResponse
     * @throws LocalizedException
     */
    public function execute(string $hostedTokenizationId, ?int $storeId = null): GetHostedTokenizationResponse
    {
        try {
            return $this->clientProvider->getClient($storeId)
                ->merchant($this->worldlineConfig->getMerchantId($storeId))
                ->hostedTokenization()
                ->getHostedTokenization($hostedTokenizationId);
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
            throw new LocalizedException(
                __('GetHostedTokenizationApi request has failed. Please contact the provider.')
            );
        }
    }
}
