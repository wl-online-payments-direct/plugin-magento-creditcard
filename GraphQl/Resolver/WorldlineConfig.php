<?php

declare(strict_types=1);

namespace Worldline\CreditCard\GraphQl\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Worldline\CreditCard\Ui\ConfigProvider;

/**
 * Resolver to pull URL for iFrame
 */
class WorldlineConfig implements ResolverInterface
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        ConfigProvider $configProvider
    ) {
        $this->configProvider = $configProvider;
    }

    /**
     * @param Field $field
     * @param $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): array {
        $url = '';
        $icons = [];
        $config = $this->configProvider->getConfig();

        if (!empty($config['payment'])) {
            $url = $config['payment'][ConfigProvider::CODE]['url'];
            $icons = array_keys($config['payment'][ConfigProvider::CODE]['icons']);
        }

        return [
            'url' => $url,
            'icons' => $icons
        ];
    }
}
