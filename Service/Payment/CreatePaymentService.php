<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Service\Payment;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use OnlinePayments\Sdk\Domain\CreatePaymentRequest;
use OnlinePayments\Sdk\Domain\CreatePaymentResponse;
use Worldline\CreditCard\Api\Service\Payment\CreatePaymentServiceInterface;
use Worldline\PaymentCore\Model\ClientProvider;
use Worldline\PaymentCore\Model\Config\WorldlineConfig;

/**
 * @link https://support.direct.ingenico.com/en/documentation/api/reference/#operation/CreatePaymentApi
 */
class CreatePaymentService implements CreatePaymentServiceInterface
{
    /**
     * @var WorldlineConfig
     */
    private $worldlineConfig;

    /**
     * @var ClientProvider
     */
    private $modelClient;

    public function __construct(
        WorldlineConfig $worldlineConfig,
        ClientProvider $modelClient
    ) {
        $this->worldlineConfig = $worldlineConfig;
        $this->modelClient = $modelClient;
    }

    /**
     * Create payment
     *
     * @param CreatePaymentRequest $request
     * @param int|null $storeId
     * @return CreatePaymentResponse
     * @throws LocalizedException
     */
    public function execute(CreatePaymentRequest $request, ?int $storeId = null): CreatePaymentResponse
    {
        try {
            return $this->modelClient->getClient($storeId)
                ->merchant($this->worldlineConfig->getMerchantId($storeId))
                ->payments()
                ->createPayment($request);
        } catch (Exception $e) {
            throw new LocalizedException(__('CreatePaymentApi request has failed. Please contact the provider.'));
        }
    }
}
