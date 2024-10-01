<?php

declare(strict_types=1);

namespace Hyva\HyvaShippingDhl\Magewire;

use Magewirephp\Magewire\Component;
use Dhl\Paket\Model\Pipeline\CreateShipments\ShipmentRequest\RequestExtractor;
use Netresearch\ShippingCore\Api\ShippingSettings\CheckoutManagementInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\Selection\SelectionInterface;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Selection\QuoteSelectionFactory;
use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes;
use Magento\Checkout\Model\Session as CheckoutSession;

class PreferredDay extends ShippingOptions
{
    /**
     * @var string
     */
    public string $preferredDay = '';

    public $fee;

    /**
     * Mount the component.
     */
    public function mount() {
        /** @var $quoteSelection SelectionInterface */
        $quoteSelections = $this->loadFromDb(Codes::SERVICE_OPTION_PREFERRED_DAY);

        if ($quoteSelections) {
            if (isset($quoteSelections['enabled'])) {
                $this->preferredDay = $quoteSelections['enabled']->getInputValue();
            }
        }

        $this->fee = $this->moduleConfig->getPreferredDayAdditionalCharge($this->storeManager->getStore()->getId());
    }

    /**
     * @param $value
     * @return mixed
     */
    public function updatedPreferredDay($value): mixed
    {
        $quoteSelection = $this->quoteSelectionFactory->create();
        $quoteSelection->setData([
            SelectionInterface::SHIPPING_OPTION_CODE => Codes::SERVICE_OPTION_PREFERRED_DAY,
            SelectionInterface::INPUT_CODE => 'date',
            SelectionInterface::INPUT_VALUE => $value
        ]);

        $this->updateShippingOptionSelections($quoteSelection);
        return $value;
    }
}
