<?php
declare(strict_types=1);

namespace  Worldline\CreditCard\Ui\ConfigProvider;

use OnlinePayments\Sdk\Domain\CreateHostedTokenizationResponse;
use Worldline\CreditCard\Service\HostedTokenization\CreateHostedTokenizationSessionService;
use Worldline\CreditCard\Service\HostedTokenization\CreateRequestBodyBuilder;

class CreateHostedTokenizationResponseProcessor
{
    /**
     * @var ExpiredAndInvalidTokensHandler
     */
    private $expiredAndInvalidTokensHandler;

    /**
     * @var CreateRequestBodyBuilder
     */
    private $createRequestBodyBuilder;

    /**
     * @var CreateHostedTokenizationSessionService
     */
    private $createRequest;

    public function __construct(
        ExpiredAndInvalidTokensHandler $expiredAndInvalidTokensHandler,
        CreateRequestBodyBuilder $createRequestBodyBuilder,
        CreateHostedTokenizationSessionService $createRequest
    ) {
        $this->expiredAndInvalidTokensHandler = $expiredAndInvalidTokensHandler;
        $this->createRequestBodyBuilder = $createRequestBodyBuilder;
        $this->createRequest = $createRequest;
    }

    /**
     * @param int|null $storeId
     * @return CreateHostedTokenizationResponse
     * @throws \Exception
     */
    public function buildAndProcess(?int $storeId = null): CreateHostedTokenizationResponse
    {
        $createHostedTokenizationRequest = $this->createRequestBodyBuilder->build($storeId);
        $createHostedTokenizationResponse = $this->createRequest->execute($createHostedTokenizationRequest, $storeId);

        $this->expiredAndInvalidTokensHandler->processExpiredAndInvalidTokens(array_merge(
            $createHostedTokenizationResponse->getInvalidTokens(),
            $createHostedTokenizationResponse->getExpiredCardTokens()
        ));

        return $createHostedTokenizationResponse;
    }
}
