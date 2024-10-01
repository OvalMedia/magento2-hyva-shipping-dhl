<?php

declare(strict_types=1);

namespace Hyva\HyvaShippingDhl\Magewire;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magewirephp\Magewire\Component;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\Selection\SelectionInterface;
use Netresearch\ShippingCore\Api\ShippingSettings\CheckoutManagementInterface;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Selection\QuoteSelectionFactory;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Selection\QuoteSelectionManager;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Selection\QuoteSelection;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOptionInterface;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Selection\QuoteSelectionRepository;

class ShippingOptions extends Component
{
    /**
     * @var ModuleConfig
     */
    protected ModuleConfig $moduleConfig;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var QuoteSelectionFactory
     */
    protected QuoteSelectionFactory $quoteSelectionFactory;

    /**
     * @var CheckoutManagementInterface
     */
    protected CheckoutManagementInterface $checkoutManagement;

    /**
     * @var QuoteSelectionManager
     */
    protected QuoteSelectionManager $quoteSelectionManager;

    /**
     * @var ShippingOptionInterface
     */
    protected ShippingOptionInterface $shippingOption;

    /**
     * @var CheckoutSession
     */
    protected CheckoutSession $checkoutSession;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * @var QuoteSelectionRepository
     */
    protected QuoteSelectionRepository $quoteSelectionRepository;

    /**
     * @param \Dhl\Paket\Model\Config\ModuleConfig $moduleConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Netresearch\ShippingCore\Api\ShippingSettings\CheckoutManagementInterface $checkoutManagement
     * @param \Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Selection\QuoteSelectionFactory $quoteSelectionFactory
     * @param \Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Selection\QuoteSelectionManager $quoteSelectionManager
     * @param \Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOptionInterface $shippingOption
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Selection\QuoteSelectionRepository $quoteSelectionRepository
     */
    public function __construct(
        ModuleConfig                $moduleConfig,
        StoreManagerInterface       $storeManager,
        CheckoutManagementInterface $checkoutManagement,
        QuoteSelectionFactory       $quoteSelectionFactory,
        QuoteSelectionManager       $quoteSelectionManager,
        ShippingOptionInterface     $shippingOption,
        CheckoutSession             $checkoutSession,
        ScopeConfigInterface        $scopeConfig,
        QuoteSelectionRepository    $quoteSelectionRepository
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->storeManager = $storeManager;
        $this->checkoutManagement = $checkoutManagement;
        $this->quoteSelectionFactory = $quoteSelectionFactory;
        $this->quoteSelectionManager = $quoteSelectionManager;
        $this->shippingOption = $shippingOption;
        $this->checkoutSession = $checkoutSession;
        $this->scopeConfig = $scopeConfig;
        $this->quoteSelectionRepository = $quoteSelectionRepository;
    }

    /**
     * @param string $code
     * @return array
     */
    protected function loadFromDb(string $code): array
    {
        $result = [];
        $quoteSelections = $this->getExistingQuoteSelections();

        foreach ($quoteSelections as $quoteSelection) {
            if ($quoteSelection->getShippingOptionCode() == $code) {
                $result[$quoteSelection->getInputCode()] = $quoteSelection;
            }
        }

        return $result;
    }

    /**
     * @return false|\Magento\Quote\Api\Data\CartInterface|\Magento\Quote\Model\Quote
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getQuote()
    {
        $quote = false;

        try {
            $quote = $this->checkoutSession->getQuote();
        } catch (NoSuchEntityException $e) {
            $this->dispatchErrorMessage($e->getMessage());
        }

        return $quote;
    }

    /**
     * @return false|\Magento\Quote\Model\Quote\Address
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getShippingAddress()
    {
        if (!$quote = $this->getQuote()) {
            return false;
        }

        $address = $quote->getShippingAddress();

        if (!$address->getId()) {
            $this->dispatchErrorMessage(__('Shipping address not found'));
            $address = false;
        }

        return $address;
    }

    /**
     * @return int|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getAddressId(): ?int
    {
        $id = false;

        if ($address = $this->getShippingAddress()) {
            $id = (int) $address->getId();
        }

        return $id;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getExistingQuoteSelections(): array
    {
        $addressId = $this->getAddressId();
        return $this->quoteSelectionManager->load($addressId);
    }

    /**
     * @param \Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Selection\QuoteSelection $quoteSelection
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function updateShippingOptionSelections(QuoteSelection $quoteSelection): mixed
    {
        $quoteId = (int) $this->checkoutSession->getQuote()->getId();
        $quoteSelections = $this->getExistingQuoteSelections();

        foreach ($quoteSelections as $key => $selection) {
            if (
                $selection->getShippingOptionCode() == $quoteSelection->getShippingOptionCode() &&
                $selection->getInputCode() == $quoteSelection->getInputCode()
            ) {
                unset($quoteSelections[$key]);
                break;
            }
        }

        if (!empty($quoteSelection->getInputValue())) {
            $quoteSelections[] = $quoteSelection;
        }

        $this->checkoutManagement->updateShippingOptionSelections($quoteId, $quoteSelections);
        return $value;
    }
}