<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Service\HostedTokenization;

use Magento\Framework\Exception\LocalizedException;
use OnlinePayments\Sdk\Domain\GetHostedTokenizationResponse;
use Worldline\CreditCard\Api\Service\HostedTokenization\GetHostedTokenizationSessionServiceInterface;
use Worldline\PaymentCore\Model\ClientProvider;
use Worldline\PaymentCore\Model\Config\WorldlineConfig;

/**
 * @link https://support.direct.ingenico.com/en/documentation/api/reference/#tag/HostedTokenization/operation/GetHostedTokenizationApi
 */
class GetHostedTokenizationSessionService implements GetHostedTokenizationSessionServiceInterface
{
    /**
     * @var WorldlineConfig
     */
    private $worldlineConfig;

    /**
     * @var ClientProvider
     */
    private $clientProvider;

    public function __construct(
        WorldlineConfig $worldlineConfig,
        ClientProvider $clientProvider
    ) {
        $this->worldlineConfig = $worldlineConfig;
        $this->clientProvider = $clientProvider;
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
            throw new LocalizedException(
                __('GetHostedTokenizationApi request has failed. Please contact the provider.')
            );
        }
    }
}
