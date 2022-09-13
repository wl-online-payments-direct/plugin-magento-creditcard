<?php

declare(strict_types=1);

namespace Worldline\CreditCard\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Worldline\CreditCard\Gateway\Config\Config;

class SettlementDataBuilder implements BuilderInterface
{
    public const AUTHORIZATION_MODE = 'authorization_mode';

    /**
     * @param array $buildSubject
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function build(array $buildSubject): array
    {
        return [
            self::AUTHORIZATION_MODE => Config::AUTHORIZATION_MODE_SALE,
        ];
    }
}
