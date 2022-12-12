<?php

declare(strict_types=1);

namespace Worldline\CreditCard\Api\Service\HostedTokenization;

use Magento\Framework\Exception\LocalizedException;
use OnlinePayments\Sdk\Domain\CreateHostedTokenizationRequest;
use OnlinePayments\Sdk\Domain\CreateHostedTokenizationResponse;

interface CreateHostedTokenizationSessionServiceInterface
{
    /**
     * Create hosted tokenization session
     *
     * @param CreateHostedTokenizationRequest $createHostedTokenizationRequest
     * @param int|null $storeId
     * @return CreateHostedTokenizationResponse
     * @throws LocalizedException
     */
    public function execute(
        CreateHostedTokenizationRequest $createHostedTokenizationRequest,
        ?int $storeId = null
    ): CreateHostedTokenizationResponse;
}
