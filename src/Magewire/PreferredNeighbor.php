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

class PreferredNeighbor extends ShippingOptions
{
    /**
     * @var string
     */
    public string $preferredNeighborName = '';

    /**
     * @var string
     */
    public string $preferredNeighborAddress = '';

    /**
     * @var bool
     */
    public bool $disabled = false;

    /**
     * @var string[]
     */
    protected $listeners = [
        'updated_preferred_location' => 'listenPreferredLocation',
        'updated_no_neighbor' => 'listenNoNeighbor',
    ];

    /**
     * @return void
     */
    public function mount() {
        /** @var $quoteSelection SelectionInterface */
        $quoteSelections = $this->loadFromDb(Codes::SERVICE_OPTION_NEIGHBOR_DELIVERY);

        if ($quoteSelections) {
            if (isset($quoteSelections['name'])) {
                $this->preferredNeighborName = $quoteSelections['name']->getInputValue();
            }

            if (isset($quoteSelections['address'])) {
                $this->preferredNeighborAddress = $quoteSelections['address']->getInputValue();
            }
        }
    }

    /**
     * @return void
     */
    protected function dispatchEmit(): void
    {
        $this->emit('updated_preferred_neighbor', [
            'preferredNeighborName' => $this->preferredNeighborName,
            'preferredNeighborAddress' => $this->preferredNeighborAddress
        ]);
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
    public function listenPreferredLocation(array $value): void
    {
        $this->disabled = !empty($value['preferredLocation']);
    }

    /**
     * @param array $value
     * @return void
     */
    public function listenNoNeighbor(array $value): void
    {
        $this->disabled = $value['noNeighbor'];
    }

    /**
     * @param string $value
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updatedPreferredNeighborName(string $value): mixed
    {
        $this->dispatchEmit();

        $quoteSelection = $this->quoteSelectionFactory->create();
        $quoteSelection->setData([
            SelectionInterface::SHIPPING_OPTION_CODE => Codes::SERVICE_OPTION_NEIGHBOR_DELIVERY,
            SelectionInterface::INPUT_CODE => 'name',
            SelectionInterface::INPUT_VALUE => $value
        ]);

        $this->updateShippingOptionSelections($quoteSelection);
        return $value;
    }

    /**
     * @param string $value
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updatedPreferredNeighborAddress(string $value): mixed
    {
        $this->dispatchEmit();

        $quoteSelection = $this->quoteSelectionFactory->create();
        $quoteSelection->setData([
            SelectionInterface::SHIPPING_OPTION_CODE => Codes::SERVICE_OPTION_NEIGHBOR_DELIVERY,
            SelectionInterface::INPUT_CODE => 'address',
            SelectionInterface::INPUT_VALUE => $value
        ]);

        $this->updateShippingOptionSelections($quoteSelection);
        return $value;
    }
}
