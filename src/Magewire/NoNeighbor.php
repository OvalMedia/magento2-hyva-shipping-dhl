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

class NoNeighbor extends ShippingOptions
{
    /**
     * @var bool
     */
    public bool $noNeighbor = false;

    /**
     * @var bool
     */
    public bool $disabled = false;

    /**
     * @var
     */
    public $fee;

    /**
     * @var string[]
     */
    protected $listeners = [
        'updated_preferred_neighbor' => 'listenPreferredNeighbor'
    ];

    /**
     * @return void
     */
    public function mount(): void
    {
        /** @var $quoteSelection SelectionInterface */
        $quoteSelections = $this->loadFromDb(Codes::SERVICE_OPTION_NO_NEIGHBOR_DELIVERY);

        if ($quoteSelections) {
            if (isset($quoteSelections['enabled'])) {
                $this->noNeighbor = (bool) $quoteSelections['enabled']->getInputValue();
            }
        }

        $this->fee = $this->scopeConfig->getValue(ModuleConfig::CONFIG_PATH_NO_NEIGHBOR_DELIVERY_CHARGE);
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
     * @return void
     */
    protected function dispatchEmit(): void
    {
        $this->emit('updated_no_neighbor', [
            'noNeighbor' => $this->noNeighbor
        ]);
    }

    /**
     * @param $value
     * @return mixed
     */
    public function updatedNoNeighbor($value): mixed
    {
        $this->dispatchEmit();

        $quoteSelection = $this->quoteSelectionFactory->create();
        $quoteSelection->setData([
            SelectionInterface::SHIPPING_OPTION_CODE => Codes::SERVICE_OPTION_NO_NEIGHBOR_DELIVERY,
            SelectionInterface::INPUT_CODE => 'enabled',
            SelectionInterface::INPUT_VALUE => $value
        ]);

        $this->updateShippingOptionSelections($quoteSelection);
        return $value;
    }
}
