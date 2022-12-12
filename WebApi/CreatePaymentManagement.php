<?php

declare(strict_types=1);

namespace Worldline\CreditCard\WebApi;

use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use OnlinePayments\Sdk\Domain\MerchantAction;
use Worldline\CreditCard\Api\CreatePaymentManagementInterface;
use Worldline\CreditCard\Api\Service\Payment\CreatePaymentServiceInterface;
use Worldline\CreditCard\Gateway\Request\PaymentDataBuilder;
use Worldline\CreditCard\Service\Payment\CreatePaymentRequestBuilder;
use Worldline\PaymentCore\Model\DataAssigner\DataAssignerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreatePaymentManagement implements CreatePaymentManagementInterface
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var CreatePaymentRequestBuilder
     */
    private $createRequestBuilder;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var DataAssignerInterface[]
     */
    private $dataAssignerPool;

    /**
     * @var PaymentInformationManagementInterface
     */
    private $paymentInformationManagement;

    /**
     * @var CreatePaymentServiceInterface
     */
    private $createPaymentService;

    public function __construct(
        CartRepositoryInterface $cartRepository,
        CreatePaymentRequestBuilder $createRequestBuilder,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        RequestInterface $request,
        PaymentInformationManagementInterface $paymentInformationManagement,
        CreatePaymentServiceInterface $createPaymentService,
        array $dataAssignerPool = []
    ) {
        $this->cartRepository = $cartRepository;
        $this->createRequestBuilder = $createRequestBuilder;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->request = $request;
        $this->dataAssignerPool = $dataAssignerPool;
        $this->paymentInformationManagement = $paymentInformationManagement;
        $this->createPaymentService = $createPaymentService;
    }

    /**
     * Get redirect url
     *
     * @param int $cartId
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @throws LocalizedException
     *
     * @return string redirect url
     */
    public function createRequest(
        int $cartId,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ): string {
        $quote = $this->cartRepository->get($cartId);

        return $this->process($quote, $paymentMethod, $billingAddress);
    }

    /**
     * @param string $cartId
     * @param PaymentInterface $paymentMethod
     * @param string $email
     * @param AddressInterface|null $billingAddress
     * @throws LocalizedException
     *
     * @return string redirect url
     */
    public function createGuestRequest(
        string $cartId,
        PaymentInterface $paymentMethod,
        string $email,
        AddressInterface $billingAddress = null
    ): string {
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $quote = $this->cartRepository->get($quoteIdMask->getQuoteId());
        $quote->setCustomerEmail($email);

        // compatibility with magento 2.3.7
        $quote->setCustomerIsGuest(true);

        return $this->process($quote, $paymentMethod, $billingAddress);
    }

    private function process(
        CartInterface $quote,
        PaymentInterface $paymentMethod,
        AddressInterface $billingAddress = null
    ): string {
        $this->paymentInformationManagement->savePaymentInformation($quote->getId(), $paymentMethod, $billingAddress);
        $payment = $quote->getPayment();

        $additionalData = $paymentMethod->getAdditionalData();
        $additionalData = array_merge((array)$paymentMethod->getAdditionalInformation(), (array)$additionalData);
        $additionalData['agent'] = $this->request->getHeader('accept');
        $additionalData['user-agent'] = $this->request->getHeader('user-agent');

        foreach ($this->dataAssignerPool as $dataAssigner) {
            $dataAssigner->assign($payment, $additionalData);
        }

        $quote->reserveOrderId();

        $this->setToken($quote, $paymentMethod);

        $request = $this->createRequestBuilder->build($quote);
        $response = $this->createPaymentService->execute($request, (int)$quote->getStoreId());

        $payment->setAdditionalInformation(PaymentDataBuilder::PAYMENT_ID, $response->getPayment()->getId());

        $action = $response->getMerchantAction();
        $redirectUrl = '';

        if ($action instanceof MerchantAction) {
            $payment->setAdditionalInformation('RETURNMAC', $action->getRedirectData()->getRETURNMAC());
            $redirectUrl = $action->getRedirectData()->getRedirectURL();
        }

        $this->cartRepository->save($quote);

        return $redirectUrl;
    }

    private function setToken(CartInterface $quote, PaymentInterface $paymentMethod): void
    {
        $payment = $quote->getPayment();
        $publicToken = $paymentMethod->getAdditionalData()['public_hash'] ?? false;
        if ($publicToken) {
            $payment->setAdditionalInformation(PaymentTokenInterface::PUBLIC_HASH, $publicToken);
            $payment->setAdditionalInformation(PaymentTokenInterface::CUSTOMER_ID, $quote->getCustomerId());
        }
    }
}
