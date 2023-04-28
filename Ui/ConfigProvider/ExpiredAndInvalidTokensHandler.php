<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Ui\ConfigProvider;

use Exception;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\Search\FilterGroupFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;
use Psr\Log\LoggerInterface;

class ExpiredAndInvalidTokensHandler
{
    /**
     * @var UserContextInterface
     */
    private $userContext;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepository;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var FilterGroupFactory
     */
    private $filterGroupFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        UserContextInterface $userContext,
        PaymentTokenRepositoryInterface $repository,
        FilterBuilder $filterBuilder,
        FilterGroupFactory $filterGroupFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->userContext = $userContext;
        $this->paymentTokenRepository = $repository;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupFactory = $filterGroupFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    public function processExpiredAndInvalidTokens(array $tokens): void
    {
        if (!empty($tokens) && $this->userContext->getUserId()) {
            try {
                foreach ($this->getCustomerExpiredAndInvalidTokens($tokens) as $token) {
                    $this->paymentTokenRepository->delete($token);
                }
            } catch (Exception $e) {
                $this->logger->critical($e);
            }
        }
    }

    private function getCustomerExpiredAndInvalidTokens(array $tokens): array
    {
        $customerId = $this->filterBuilder
            ->setField(PaymentTokenInterface::CUSTOMER_ID)
            ->setValue($this->userContext->getUserId())
            ->create();
        /** @var FilterGroup $filterGroupCustomerId */
        $filterGroupCustomerId = $this->filterGroupFactory->create();
        $filterGroupCustomerId->setFilters([$customerId]);
        $gatewayToken = $this->filterBuilder
            ->setField(PaymentTokenInterface::GATEWAY_TOKEN)
            ->setConditionType('in')
            ->setValue($tokens)
            ->create();
        /** @var FilterGroup $filterGroupGatewayToken */
        $filterGroupGatewayToken = $this->filterGroupFactory->create();
        $filterGroupGatewayToken->setFilters([$gatewayToken]);
        $entities = $this->paymentTokenRepository->getList(
            $this->searchCriteriaBuilder->setFilterGroups([
                $filterGroupCustomerId,
                $filterGroupGatewayToken
            ])->create()
        );
        return $entities->getItems();
    }
}
