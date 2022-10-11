<?php

declare(strict_types=1);

namespace Worldline\CreditCard\Service\Getter;

use OnlinePayments\Sdk\Domain\PaymentResponse;
use Worldline\PaymentCore\Model\ClientProvider;
use Worldline\PaymentCore\Model\Config\WorldlineConfig;

class Request
{
    /**
     * @var array
     */
    private $cachedRequests = [];

    /**
     * @var ClientProvider
     */
    private $clientProvider;

    /**
     * @var WorldlineConfig
     */
    private $worldlineConfig;

    public function __construct(
        ClientProvider $clientProvider,
        WorldlineConfig $worldlineConfig
    ) {
        $this->clientProvider = $clientProvider;
        $this->worldlineConfig = $worldlineConfig;
    }

    /**
     * Documentation:
     * @link: https://support.direct.ingenico.com/en/documentation/api/reference/#operation/GetPaymentApi
     *
     * @param string $paymentId
     * @param int|null $storeId
     * @return PaymentResponse
     * @throws \Exception
     */
    public function create(string $paymentId, ?int $storeId = null): PaymentResponse
    {
        if (!isset($this->cachedRequests[$paymentId])) {
            $this->cachedRequests[$paymentId] = $this->clientProvider->getClient($storeId)
                ->merchant($this->worldlineConfig->getMerchantId($storeId))
                ->payments()
                ->getPayment($paymentId);
        }

        return $this->cachedRequests[$paymentId];
    }
}
