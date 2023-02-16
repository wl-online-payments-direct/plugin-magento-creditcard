<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Api\Service\HostedTokenization;

use Magento\Framework\Exception\LocalizedException;
use OnlinePayments\Sdk\Domain\GetHostedTokenizationResponse;

interface GetHostedTokenizationSessionServiceInterface
{
    /**
     * Retrieve hosted tokenization session
     *
     * @param string $hostedTokenizationId
     * @param int|null $storeId
     * @return GetHostedTokenizationResponse
     * @throws LocalizedException
     */
    public function execute(string $hostedTokenizationId, ?int $storeId = null): GetHostedTokenizationResponse;
}
