<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Setup;

use Magento\Config\Model\ResourceModel\Config as ConfigResource;
use Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;

class Uninstall implements UninstallInterface
{
    /**
     * @var ConfigResource
     */
    private $configResource;

    /**
     * @var CollectionFactory
     */
    private $configCollectionFactory;

    public function __construct(ConfigResource $configResource, CollectionFactory $configCollectionFactory)
    {
        $this->configResource = $configResource;
        $this->configCollectionFactory = $configCollectionFactory;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws \Exception
     */
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context): void
    {
        $setup->startSetup();

        $collection = $this->configCollectionFactory->create()
            ->addFieldToFilter(
                'path',
                [
                    ['like' => 'payment/worldline_cc/%'],
                    ['like' => 'payment/worldline_cc_vault/%']
                ]
            );

        foreach ($collection->getItems() as $config) {
            $this->configResource->delete($config);
        }

        $setup->endSetup();
    }
}
