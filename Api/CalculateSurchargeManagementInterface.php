<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Api;

interface CalculateSurchargeManagementInterface
{
    /**
     * @param int $cartId
     * @param string $hostedTokenizationId
     * @return float
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function calculate(int $cartId, string $hostedTokenizationId): float;

    /**
     * @param string $cartId
     * @param string $hostedTokenizationId
     * @param string $email
     * @return float
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function calculateForGuest(string $cartId, string $hostedTokenizationId, string $email): float;
}
