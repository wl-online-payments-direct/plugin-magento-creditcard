<?php
declare(strict_types=1);

namespace Worldline\CreditCard\WebApi\CreatePaymentManagement;

use Magento\Quote\Api\Data\PaymentInterface;
use Worldline\CreditCard\Gateway\Request\PaymentDataBuilder;
use Worldline\PaymentCore\Api\Data\QuotePaymentInterface;
use Worldline\PaymentCore\Model\DataAssigner\DataAssignerInterface;
use Worldline\CreditCard\Service\HostedTokenization\GetHostedTokenizationSessionService;

class PaymentMethodDataAssigner implements DataAssignerInterface
{
    public const PAYMENT_PRODUCT_ID = 'payment_product_id';

    /**
     * @var GetHostedTokenizationSessionService
     */
    private $getHostedTokenizationSessionService;

    public function __construct(
        GetHostedTokenizationSessionService $getHostedTokenizationSessionService
    ) {
        $this->getHostedTokenizationSessionService = $getHostedTokenizationSessionService;
    }

    public function assign(
        PaymentInterface $payment,
        QuotePaymentInterface $wlQuotePayment,
        array $additionalInformation
    ): void {
        $hostedTokenizationId = $additionalInformation['hosted_tokenization_id'] ?? '';
        if (!$hostedTokenizationId) {
            $hostedTokenizationId = (string)$payment->getAdditionalInformation('hosted_tokenization_id');
        }

        $storeId = (int)$payment->getMethodInstance()->getStore();
        $createHostedTokenizationResponse =
            $this->getHostedTokenizationSessionService->execute($hostedTokenizationId, $storeId);

        $tokenResponse = $createHostedTokenizationResponse->getToken();
        $payment->setAdditionalInformation(PaymentDataBuilder::TOKEN_ID, $tokenResponse->getId() ?: '');
        $payment->setAdditionalInformation(self::PAYMENT_PRODUCT_ID, (int)$tokenResponse->getPaymentProductId());
        $payment->setAdditionalInformation(
            'card_number',
            mb_substr($tokenResponse->getCard()->getAlias(), -4)
        );
        $payment->setAdditionalInformation('hosted_tokenization_id', $hostedTokenizationId);

        if (isset($additionalInformation['is_active_payment_token_enabler'])) {
            $payment->setAdditionalInformation(
                'is_active_payment_token_enabler',
                $additionalInformation['is_active_payment_token_enabler']
                && ($tokenResponse->getIsTemporary() === false)
            );
        }
    }
}
