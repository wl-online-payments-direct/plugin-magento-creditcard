<?php

declare(strict_types=1);

namespace Worldline\CreditCard\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CreditCardTypeOptions implements OptionSourceInterface
{
    public const AE_CONFIG_VALUE = 'americanexpress';
    public const CB_CONFIG_VALUE = 'cartebancaire';
    public const DC_CONFIG_VALUE = 'dinersclub';
    public const JSB_CONFIG_VALUE = 'jcb';
    public const M_CONFIG_VALUE = 'maestro';
    public const MC_CONFIG_VALUE = 'mastercard';
    public const V_CONFIG_VALUE = 'visa';

    public const PAYMENT_PRODUCTS = [
        self::V_CONFIG_VALUE => 1,
        self::AE_CONFIG_VALUE => 2,
        self::MC_CONFIG_VALUE => 3,
        self::M_CONFIG_VALUE => 117,
        self::JSB_CONFIG_VALUE => 125,
        self::CB_CONFIG_VALUE => 130,
        self::DC_CONFIG_VALUE => 132
    ];

    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::AE_CONFIG_VALUE,
                'label' => __('American Express')
            ],
            [
                'value' => self::CB_CONFIG_VALUE,
                'label' => __('Carte Bancaire')
            ],
            [
                'value' => self::DC_CONFIG_VALUE,
                'label' => __('Diners Club')
            ],
            [
                'value' => self::JSB_CONFIG_VALUE,
                'label' => __('JCB')
            ],
            [
                'value' => self::M_CONFIG_VALUE,
                'label' => __('Maestro')
            ],
            [
                'value' => self::MC_CONFIG_VALUE,
                'label' => __('Mastercard')
            ],
            [
                'value' => self::V_CONFIG_VALUE,
                'label' => __('Visa')
            ]
        ];
    }
}
