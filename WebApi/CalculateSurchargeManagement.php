<?php
declare(strict_types=1);

namespace Worldline\CreditCard\WebApi;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;
use OnlinePayments\Sdk\Domain\Surcharge;
use Worldline\CreditCard\Api\CalculateSurchargeManagementInterface;
use Worldline\PaymentCore\Api\AmountFormatterInterface;
use Worldline\PaymentCore\Api\Service\CalculateSurchargeRequestBuilderInterface;
use Worldline\PaymentCore\Api\SurchargingQuoteManagerInterface;
use Worldline\PaymentCore\Api\WebApi\Checkout\QuoteManagerInterface;
use Worldline\PaymentCore\Api\Service\Services\SurchargeCalculationServiceInterface;

class CalculateSurchargeManagement implements CalculateSurchargeManagementInterface
{
    /**
     * @var QuoteManagerInterface
     */
    private $quoteManager;

    /**
     * @var AmountFormatterInterface
     */
    private $amountFormatter;

    /**
     * @var SurchargingQuoteManagerInterface
     */
    private $surchargingQuoteManager;

    /**
     * @var SurchargeCalculationServiceInterface
     */
    private $surchargeCalculationService;

    /**
     * @var CalculateSurchargeRequestBuilderInterface
     */
    private $calculateSurchargeRequestBuilder;

    public function __construct(
        QuoteManagerInterface $quoteManager,
        AmountFormatterInterface $amountFormatter,
        SurchargingQuoteManagerInterface $surchargingQuoteManager,
        SurchargeCalculationServiceInterface $surchargeCalculationService,
        CalculateSurchargeRequestBuilderInterface $calculateSurchargeRequestBuilder
    ) {
        $this->quoteManager = $quoteManager;
        $this->amountFormatter = $amountFormatter;
        $this->surchargingQuoteManager = $surchargingQuoteManager;
        $this->surchargeCalculationService = $surchargeCalculationService;
        $this->calculateSurchargeRequestBuilder = $calculateSurchargeRequestBuilder;
    }

    /**
     * @param int $cartId
     * @param string $hostedTokenizationId
     * @return float
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function calculate(int $cartId, string $hostedTokenizationId): float
    {
        $quote = $this->quoteManager->getQuote($cartId);
        return $this->process($quote, $hostedTokenizationId);
    }

    /**
     * @param string $cartId
     * @param string $hostedTokenizationId
     * @param string $email
     * @return float
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function calculateForGuest(string $cartId, string $hostedTokenizationId, string $email): float
    {
        $quote = $this->quoteManager->getQuoteForGuest($cartId, $email);
        return $this->process($quote, $hostedTokenizationId);
    }

    /**
     * @param CartInterface $quote
     * @param string $hostedTokenizationId
     * @return float
     * @throws LocalizedException
     */
    private function process(CartInterface $quote, string $hostedTokenizationId): float
    {
        $totalSurcharging = 0.0;
        if ((float)$quote->getGrandTotal() < 0.00001) {
            return $totalSurcharging;
        }

        $calculateSurchargeRequest = $this->calculateSurchargeRequestBuilder->build($quote, $hostedTokenizationId);
        $surcharges = $this->surchargeCalculationService->execute($calculateSurchargeRequest);
        /** @var Surcharge $surcharge */
        foreach ($surcharges as $surcharge) {
            $surchargeAmount = $surcharge->getSurchargeAmount()->getAmount();
            $currency = $surcharge->getSurchargeAmount()->getCurrencyCode();
            $totalSurcharging += $this->amountFormatter->formatToFloat($surchargeAmount, $currency);
        }

        if ($totalSurcharging) {
            $this->surchargingQuoteManager->saveSurchargingQuote($quote, $totalSurcharging);
        }

        return $totalSurcharging;
    }
}
