<?php

declare(strict_types=1);

namespace Worldline\CreditCard\Service\CapturePayment;

use Magento\Framework\Exception\LocalizedException;
use OnlinePayments\Sdk\Domain\CapturePaymentRequest;
use OnlinePayments\Sdk\Domain\CapturePaymentRequestFactory;
use OnlinePayments\Sdk\Domain\CaptureResponse;
use Worldline\CreditCard\Api\Service\CapturePaymentInterface;
use Worldline\PaymentCore\Model\ClientProvider;
use Worldline\PaymentCore\Model\Config\WorldlineConfig;

class CapturePayment implements CapturePaymentInterface
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
     * @param string $transactionId
     * @param CapturePaymentRequest $capturePaymentRequest
     * @param int|null $storeId
     * @return CaptureResponse
     * @throws LocalizedException
     */
    public function execute(
        string $transactionId,
        CapturePaymentRequest $capturePaymentRequest,
        ?int $storeId = null
    ): CaptureResponse {
        try {
            return $this->clientProvider->getClient($storeId)
                ->merchant($this->worldlineConfig->getMerchantId($storeId))
                ->payments()
                ->capturePayment($transactionId, $capturePaymentRequest);
        } catch (\Exception $e) {
            throw new LocalizedException(__('CapturePayment request has failed'));
        }
    }
}
