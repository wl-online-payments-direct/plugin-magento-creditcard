<?php

declare(strict_types=1);

namespace Worldline\CreditCard\Service\HostedTokenizationSession;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Locale\Resolver as LocalResolver;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\PaymentTokenManagement;
use OnlinePayments\Sdk\Domain\CreateHostedTokenizationRequest;
use OnlinePayments\Sdk\Domain\CreateHostedTokenizationRequestFactory;
use Worldline\CreditCard\Gateway\Config\Config;
use Worldline\CreditCard\UI\ConfigProvider;

class CreateRequestBodyBuilder
{
    public const CREATE_HOSTED_TOKENIZATION_REQUEST = 'create_hosted_tokenization_request';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var LocalResolver
     */
    private $localResolver;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @var CreateHostedTokenizationRequestFactory
     */
    private $createHostedTokenizationRequestFactory;

    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var PaymentTokenManagement
     */
    private $paymentTokenManagement;

    public function __construct(
        Config $config,
        LocalResolver $localResolver,
        ManagerInterface $eventManager,
        CreateHostedTokenizationRequestFactory $createHostedTokenizationRequestFactory,
        UserContextInterface $userContext,
        PaymentTokenManagement $paymentTokenManagement
    ) {
        $this->config = $config;
        $this->localResolver = $localResolver;
        $this->eventManager = $eventManager;
        $this->createHostedTokenizationRequestFactory = $createHostedTokenizationRequestFactory;
        $this->userContext = $userContext;
        $this->paymentTokenManagement = $paymentTokenManagement;
    }

    public function build(?int $storeId = null)
    {
        /** @var CreateHostedTokenizationRequest $createHostedTokenizationRequest */
        $createHostedTokenizationRequest = $this->createHostedTokenizationRequestFactory->create();

        $createHostedTokenizationRequest->setAskConsumerConsent(
            $this->config->isVaultActive($storeId)
            && $this->userContext->getUserId()
        );
        $createHostedTokenizationRequest->setVariant($this->config->getTemplateId($storeId));
        $createHostedTokenizationRequest->setLocale($this->localResolver->getLocale());
        $this->setCurrentCustomerTokens($createHostedTokenizationRequest);

        $args = [self::CREATE_HOSTED_TOKENIZATION_REQUEST => $createHostedTokenizationRequest];
        $this->eventManager->dispatch(ConfigProvider::CODE . '_create_hosted_tokenization_request_builder', $args);

        return $createHostedTokenizationRequest;
    }

    private function setCurrentCustomerTokens(CreateHostedTokenizationRequest $createHostedTokenizationRequest): void
    {
        if (!$this->userContext->getUserId()) {
            return;
        }

        $tokens = $this->paymentTokenManagement->getListByCustomerId($this->userContext->getUserId());
        /** @var PaymentTokenInterface $token */
        foreach ($tokens as $token) {
            if ($token->getPaymentMethodCode() === ConfigProvider::CODE) {
                $customerTokens[] = $token->getGatewayToken();
            }
        }

        if (!isset($customerTokens)) {
            return;
        }

        if (count($customerTokens) > 1) {
            $createHostedTokenizationRequest->setTokens(implode(',', $customerTokens));
        } else {
            $createHostedTokenizationRequest->setTokens(current($customerTokens));
        }
    }
}
