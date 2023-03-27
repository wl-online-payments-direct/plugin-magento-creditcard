<?php
declare(strict_types=1);

namespace Worldline\CreditCard\Plugin\Magento\Checkout\Model\PaymentInformationManagement;

use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Vault\Api\PaymentMethodListInterface;
use Magento\Store\Model\StoreManagerInterface;

class RemoveIndexFromVault
{
    /**
     * @var PaymentMethodListInterface
     */
    private $vaultPaymentMethodList;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    public function __construct(
        PaymentMethodListInterface $vaultPaymentMethodList,
        StoreManagerInterface $storeManager,
        ProductMetadataInterface $productMetadata
    ) {
        $this->vaultPaymentMethodList = $vaultPaymentMethodList;
        $this->storeManager = $storeManager;
        $this->productMetadata = $productMetadata;
    }

    /**
     * Compatibility with Magento 2.4.1 and below
     *
     * Set available vault method code without index to payment
     *
     * @param PaymentInformationManagementInterface $subject
     * @param string $cartId
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSavePaymentInformation(
        PaymentInformationManagementInterface $subject,
        string $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ): void {
        if (version_compare($this->productMetadata->getVersion(), '2.4.2', '<')) {
            $availableMethods = $this->vaultPaymentMethodList->getActiveList($this->storeManager->getStore()->getId());
            foreach ($availableMethods as $availableMethod) {
                if (strpos($paymentMethod->getMethod() ?? '', $availableMethod->getCode()) !== false) {
                    $paymentMethod->setMethod($availableMethod->getCode());
                }
            }
        }
    }
}
