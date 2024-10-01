<?php

declare(strict_types=1);

namespace Hyva\HyvaShippingDhl\Magewire;

use Magento\Checkout\Model\Session as SessionCheckout;
use Magewirephp\Magewire\Component;

/**
 * Validates the availability of delivery options based on the country.
 */
class DeliveryOptions extends Component
{
    /**
     * @var bool Indicates if the shipping address is valid.
     */
    public bool $isShippingAddressValid = false;

    /**
     * @var array Stores the shipping address.
     */
    private array $shippingAddress = [];

    /**
     * Event listeners for Magewire.
     *
     * @var array
     */
    protected $listeners = [
        'shipping_address_saved' => 'shippingAddressSaved',
        'shipping_address_activated' => 'shippingAddressActivated',
        'guest_shipping_address_saved' => 'shippingAddressSaved',
        'updatedCountDown' => 'updatedCountDown'
    ];

    /**
     * @var SessionCheckout
     */
    private SessionCheckout $sessionCheckout;

    /**
     * DeliveryOptionsValidator constructor.
     *
     * @param SessionCheckout $sessionCheckout
     */
    public function __construct(SessionCheckout $sessionCheckout)
    {
        $this->sessionCheckout = $sessionCheckout;
    }

    public function boot()
    {
        $this->checkCountryValidity();
    }

    /**
     * Checks and sets the validity of the shipping address based on country.
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function checkCountryValidity(): bool
    {
        $quote = $this->sessionCheckout->getQuote();
        $shippingAddress = $quote?->getShippingAddress();
        $validCountryCodes = ['DE'];
        $countryId = $shippingAddress?->getCountryId();
        $this->isShippingAddressValid = in_array($countryId, $validCountryCodes);
        return $this->isShippingAddressValid;
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function shippingAddressSaved(): void
    {
        $this->checkCountryValidity();
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function shippingAddressActivated(): void
    {
        $this->checkCountryValidity();
    }
    
    /**
     * Returns the validity status of the shipping address.
     *
     * @return bool
     */
    public function isShippingAddressValid(): bool
    {
        return $this->isShippingAddressValid;
    }
}
