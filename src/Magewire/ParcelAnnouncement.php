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

class ParcelAnnouncement extends ShippingOptions
{
    /**
     * @var bool
     */
    public $parcelAnnouncement = false;

    /**
     * Mount the component.
     */
    public function mount() {
        /** @var $quoteSelection SelectionInterface */
        $quoteSelections = $this->loadFromDb(Codes::SERVICE_OPTION_PARCEL_ANNOUNCEMENT);

        if ($quoteSelections) {
            if (isset($quoteSelections['enabled'])) {
                $this->parcelAnnouncement = $quoteSelections['enabled']->getInputValue();
            }
        }
    }

    /**
     * @param $value
     * @return mixed
     */
    public function updatedParcelAnnouncement($value): mixed
    {
        $quoteSelection = $this->quoteSelectionFactory->create();
        $quoteSelection->setData([
            SelectionInterface::SHIPPING_OPTION_CODE => Codes::SERVICE_OPTION_PARCEL_ANNOUNCEMENT,
            SelectionInterface::INPUT_CODE => 'enabled',
            SelectionInterface::INPUT_VALUE => $value
        ]);

        $this->updateShippingOptionSelections($quoteSelection);
        return $value;
    }
}
