<?php
declare(strict_types=1);

namespace Worldline\CreditCard\GraphQl\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Worldline\CreditCard\UI\ConfigProvider;
use Worldline\CreditCard\Model\Ui\PaymentIconsProvider;

class PaymentMethodIcons implements ResolverInterface
{
    /**
     * @var PaymentIconsProvider
     */
    private $iconProvider;

    public function __construct(PaymentIconsProvider $iconProvider)
    {
        $this->iconProvider = $iconProvider;
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
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['code'])) {
            throw new LocalizedException(__('"code" value should be specified'));
        }

        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();

        if ($value['code'] !== ConfigProvider::CODE) {
            return [];
        }

        $icons = $this->iconProvider->getIcons($storeId);

        return $this->getIconsDetails($icons);
    }

    /**
     * @param array $icons
     * @return array
     */
    private function getIconsDetails(array $icons): array
    {
        $iconsDetails = [];
        foreach ($icons as $icon) {
            $iconsDetails[] = [
                'icon_title' => $icon['title'],
                'icon_url' => $icon['url']
            ];
        }

        return $iconsDetails;
    }
}
