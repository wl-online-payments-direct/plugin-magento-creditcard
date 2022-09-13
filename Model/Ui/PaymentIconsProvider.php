<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Model\Ui;

use Magento\Framework\App\Area;
use Magento\Framework\View\Asset\Source as AssetSource;
use Worldline\CreditCard\Gateway\Config\Config;
use Worldline\CreditCard\Model\Config\Source\CreditCardTypeOptions;
use Worldline\PaymentCore\Model\Ui\PaymentIconsProvider as GeneralIconsProvider;

class PaymentIconsProvider
{
    /**
     * @var AssetSource
     */
    private $assetSource;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var CreditCardTypeOptions
     */
    private $options;

    /**
     * @var GeneralIconsProvider
     */
    private $generalIconsProvider;

    public function __construct(
        AssetSource $assetSource,
        Config $config,
        CreditCardTypeOptions $options,
        GeneralIconsProvider $generalIconsProvider
    ) {
        $this->assetSource = $assetSource;
        $this->config = $config;
        $this->options = $options;
        $this->generalIconsProvider = $generalIconsProvider;
    }

    public function getIcons(?int $storeId = null): array
    {
        $cCTypes = explode(',', $this->config->getCcTypes($storeId));
        if (empty($cCTypes)) {
            return [];
        }

        $icons = [];
        $labels = $this->getLabels();
        foreach ($cCTypes as $cCType) {
            $asset = $this->generalIconsProvider->createAsset(
                'Worldline_PaymentCore::images/cc/pay_' . $cCType . '.svg',
                [Area::PARAM_AREA => Area::AREA_FRONTEND]
            );
            $placeholder = $this->assetSource->findSource($asset);
            if ($placeholder) {
                list($width, $height) = $this->generalIconsProvider->getDimensions($asset);
                $icons[$cCType] = [
                    'url' => $asset->getUrl(),
                    'width' => $width,
                    'height' => $height,
                    'title' => $labels[$cCType]
                ];
            }
        }

        return $icons;
    }

    public function getLabels(): array
    {
        $labels = [];
        foreach ($this->options->toOptionArray() as $option) {
            $labels[$option['value']] = $option['label'];
        }

        return $labels;
    }
}
