<?php

declare(strict_types=1);

namespace Hyva\HyvaShippingDhl\Magewire;

use Dhl\Paket\Model\Config\ModuleConfig;
use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magewirephp\Magewire\Component;
use Dhl\Paket\Model\Pipeline\CreateShipments\ShipmentRequest\RequestExtractor;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\Selection\SelectionInterface;
use Netresearch\ShippingCore\Api\ShippingSettings\CheckoutManagementInterface;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Selection\QuoteSelectionFactory;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Selection\QuoteSelectionManager;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOptionInterface;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Selection\QuoteSelectionRepository;

class PreferredLocation extends ShippingOptions
{
    /**
     * @var string
     */
    public string $preferredLocation = '';

    /**
     * @var bool
     */
    public bool $disabled = false;

    /**
     * @var string[]
     */
    protected $listeners = [
        'updated_preferred_neighbor' => 'listenPreferredNeighbor'
    ];

    /**
     * Mount the component.
     */
    public function mount() {
        /** @var $quoteSelection SelectionInterface */
        $quoteSelections = $this->loadFromDb(Codes::SERVICE_OPTION_DROPOFF_DELIVERY);

        if ($quoteSelections) {
            if (isset($quoteSelections['details'])) {
                $this->preferredLocation = $quoteSelections['details']->getInputValue();
            }
        }
    }

    /**
     * @return void
     */
    protected function dispatchEmit(): void
    {
        $this->emit('updated_preferred_location', ['preferredLocation' => $this->preferredLocation]);
    }

    /**
     * @return void
     */
    public function init(): void
    {
        $this->dispatchEmit();
    }

    /**
     * @param $value
     * @return void
     */
    public function listenPreferredNeighbor(array $value): void
    {
        $this->disabled = (!empty($value['preferredNeighborName'] || !empty($value['preferredNeighborAddress'])));
    }

    /**
     * @param $value
     * @return mixed
     */
    public function updatedPreferredLocation($value): mixed
    {
        $this->dispatchEmit();

        $quoteSelection = $this->quoteSelectionFactory->create();
        $quoteSelection->setData([
            SelectionInterface::SHIPPING_OPTION_CODE => Codes::SERVICE_OPTION_DROPOFF_DELIVERY,
            SelectionInterface::INPUT_CODE => 'details',
            SelectionInterface::INPUT_VALUE => $value
        ]);

        $this->updateShippingOptionSelections($quoteSelection);
        return $value;
    }
}
