<?php

declare(strict_types=1);

namespace Hyva\HyvaShippingDhl\Magewire;

use Magento\Checkout\Model\Session as SessionCheckout;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magewirephp\Magewire\Component;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ResponseFactory;
use Netresearch\ShippingCore\Model\Config\MapBoxConfig;
use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes;
use Hyva\Checkout\Model\Magewire\Component\EvaluationInterface;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultFactory;
use Hyva\Checkout\Model\Magewire\Component\EvaluationResultInterface;

/**
 * Manages the display and logic of the Pickup and Delivery buttons.
 */
class ParcelPackstation extends Component implements EvaluationInterface
{
    /**
     * @var Controls the display of the location finder.
     */
    public $modalOpened = false;

    /**
     * @var MapBoxConfig Holds the Mapbox configuration.
     */
    private $mapBoxConfig;

    /**
     * @var string Controls the display of the location finder.
     */
    public $apiUrl;

    /**
     * @var Controls the display of the location button.
     */
    public $parcelPackstationCheck = false;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ScopeConfigInterface Manages configuration scope.
     */
    private $scopeConfig;

    /**
     * @var array Stores the API data for packstations.
     */
    public array $apiData = [];

    /**
     * @var array DeliveryLocation the shipping address.
     */
    public array $deliveryLocation = [];

    /**
     * @var array mapboxApi the API data for packstations.
     */
    public $mapboxApi;

    /**
     * @var array Stores the shipping address.
     */
    public array $shippingAddress = [];

    /**
     * @var array Listeners for various shipping address events.
     */
    protected $listeners = [
        'shipping_address_saved' => 'isCheckAndSetShippingAddressValid',
        'guest_shipping_address_saved' => 'isCheckAndSetShippingAddressValid',
        'parcel_packstation_saved' => 'getPackstation'
    ];

    /**
     * @var SessionCheckout Session checkout instance.
     */
    private $sessionCheckout;

    /**
     * @var ResponseFactory Factory to create response objects.
     */
    private $responseFactory;

    /**
     * @var ClientFactory Factory to create client objects.
     */
    private $clientFactory;
    
    /**
     * Returns the complete API endpoint.
     *
     * @return string The API endpoint.
     */
    private function getApiEndpoint(): string
    {
        try {
            $storeBase = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB, true);
            $storeId = $this->storeManager->getStore()->getCode();
            // Correctly concatenate the store ID with the API path
            $this->apiUrl = $storeBase . 'rest/' . $storeId . '/V1/nrshipping/delivery-locations/dhlpaket/search';
        } catch (NoSuchEntityException $exception) {
            $this->dispatchMessage($messageType, $message);
        }

        return $this->apiUrl;
    }

    /**
     * Constructor for ParcelPackstation.
     *
     * @param SessionCheckout $sessionCheckout
     * @param ClientFactory $clientFactory
     * @param ResponseFactory $responseFactory
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        SessionCheckout $sessionCheckout,
        ClientFactory $clientFactory,
        ResponseFactory $responseFactory,
        MapBoxConfig $mapBoxConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->sessionCheckout = $sessionCheckout;
        $this->clientFactory = $clientFactory;
        $this->responseFactory = $responseFactory;
        $this->mapBoxConfig = $mapBoxConfig;
        $this->storeManager = $storeManager;
        $this->setMapboxApiToken();
    }

    /**
     * Checks and sets the shipping address.
     *
     * @return bool True if shipping address exists and is set, false otherwise.
     */
    public function checkAndSetShippingAddress(): bool
    {
        // Check if shippingAddress is already set
        if (!empty($this->shippingAddress['street']) &&
            !empty($this->shippingAddress['city']) &&
            !empty($this->shippingAddress['postal_code']) &&
            !empty($this->shippingAddress['country_code'])) {
            return true; // Address data is already present
        }

        $quote = $this->sessionCheckout->getQuote();
        $shippingAddress = $quote->getShippingAddress();

        if ($shippingAddress) {
            $streetLines = $shippingAddress->getStreet();
            $city = $shippingAddress->getCity();
            $postcode = $shippingAddress->getPostcode();
            $countryId = $shippingAddress->getCountryId();

            $street = is_array($streetLines) ? implode(' ', $streetLines) : $streetLines;

            if (empty($street) || empty($city) || empty($postcode) || empty($countryId)) {
                $this->shippingAddress = [];
                return false;
            } else {
                $this->shippingAddress = [
                    'street' => $street,
                    'city' => $city,
                    'postal_code' => $postcode,
                    'country_code' => $countryId,
                ];
                return true;
            }
        } else {
            return false;
        }
    }
    
    /**
     * Returns the validity status of the shipping address.
     *
     * @return bool Returns true if the shipping address is valid, false otherwise.
     */
    public function isCheckAndSetShippingAddressValid(): bool
    {
        // Aktualisieren Sie zuerst die Versandadresse
        $this->checkAndSetShippingAddress();

        // Überprüfen Sie dann die Gültigkeit der Adresse
        return $this->checkAndSetShippingAddress();
    }
    
    /**
     * Gets the shipping address.
     *
     * @return array Shipping address.
     */
    public function getShippingAddress(): array
    {
        return $this->shippingAddress;
    }

    /**
     * Opens the modal and activates the checkbox.
     */
    public function openModalAndActivateCheckbox()
    {
        $this->parcelPackstationCheck = true;
        $this->getApiRequest();
        $this->modalOpened = true;        
    }

    /**
     * Checks if the model is open.
     *
     * @return bool True if modal is open, false otherwise.
     */
    public function isModelOpen(): bool
    {
        return $this->modalOpened === true;
    }

    /**
     * Closes the modal.
     */
    public function closeModal(): void
    {
        $this->modalOpened = false;
        $this->shippingAddress = [];
    }

    /**
     * Updates the API data.
     *
     * @param array $data New API data.
     */
    public function updatedApiData($data)
    {
        $this->apiData = $data;
    }

    /**
     * Fetches data from the API.
     */
    public function getApiRequest(): void
    {
        if ($this->checkAndSetShippingAddress()) {
            $requestData = ['searchAddress' => $this->shippingAddress];
            $fullApiEndpoint = $this->getApiEndpoint(); // Get the full API endpoint
            $response = $this->makeApiRequest($fullApiEndpoint, $requestData); // Correct order of arguments

            $status = $response->getStatusCode();
            $responseBody = $response->getBody();
            $responseContent = json_decode($responseBody->getContents(), true);

            $this->apiData = is_array($responseContent) ? $responseContent : [];
        }
    }

    /**
     * Sets the Mapbox API token.
     */
    public function setMapboxApiToken(): void
    {
        $this->mapboxApi = $this->mapBoxConfig->getApiToken();
    }

    /**
     * Makes an API request with provided parameters.
     *
     * @param array $params Parameters for the request.
     *
     * @return Response The response from the API.
     */
    private function makeApiRequest(string $uriEndpoint, array $params = []): Response
    {
        $fullApiEndpoint = $this->getApiEndpoint(); // Full API endpoint

        $client = $this->clientFactory->create(); // No 'base_uri' needed

        $body = json_encode($params);

        try {
            $response = $client->request('POST', $fullApiEndpoint, [ // Use the full endpoint
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'body' => $body
            ]);
        } catch (GuzzleException $exception) {
            $response = $this->responseFactory->create([
                'status' => $exception->getCode(),
                'reason' => $exception->getMessage()
            ]);
        }

        return $response;
    }

    /**
     * Retrieves packstation information based on given data.
     *
     * @param array $data Data to identify the packstation.
     */
    public function getPackstation(array $data): void
    {
        // Check if 'shop_number' is present
        if (isset($data['value'])) {
            $shopNumber = $data['value'];

            // Search the packstations array for the matching entry
            foreach ($this->apiData as $shop) {
                if (isset($shop['shop_number']) && $shop['shop_number'] == $shopNumber) {
                    // Once the corresponding entry is found, update $deliveryLocation
                    $this->deliveryLocation = [
                        'company' => $shop['address']['company'] ?? '',
                        'countryCode' => $shop['address']['country_code'] ?? '',
                        'displayName' => $shop['display_name'] ?? '',
                        'customerPostnumber' => '',
                        'enabled' => true,
                        'id' => $shop['shop_id'] ?? '',
                        'number' => $shop['shop_number'] ?? '',
                        'postalCode' => $shop['address']['postal_code'] ?? '',                        
                        'street' => $shop['address']['street'] ?? '',                        
                        'city' => $shop['address']['city'] ?? '',                        
                        'type' => $shop['shop_type'] ?? ''                   
                    ];

                    // Close the modal
                    $this->modalOpened = false;

                    // Break the loop as the matching entry has been found
                    break;
                }
            }
        }
    }
    
    /**
     * Clears the packstation data.
     */
    public function clearPackstation(): void
    {
        $this->parcelPackstationCheck = false;
        $this->deliveryLocation = [];
    }

    /**
     * Dispatches a browser event to indicate the start of loading.
     */
    public function updateApiRequest()
    {
        $this->dispatchBrowserEvent('loadingStart');
        $this->getApiRequest();
        $this->dispatchBrowserEvent('loadingEnd');
    }
    
    
    /**
     * Emits a "postnumberChanged" event with the provided value.
     *
     * @param mixed $value The value to emit.
     */
    public function postnumberChanged($value)
    {
        $this->emit('postnumberChanged', $value);
    }
    
    public function updatedParcelPackstationCheck($value): bool
    {
        if (is_bool($value)) {
            $this->parcelPackstationCheck = $value;

            if (!$value) {
                $this->clearPackstation();
            }
        }

        return $value;
    }
    
    /**
     * Evaluates the completion of the delivery location.
     *
     * @param EvaluationResultFactory $factory The factory for creating evaluation results.
     *
     * @return EvaluationResultInterface The evaluation result.
     */
    public function evaluateCompletion(EvaluationResultFactory $factory): EvaluationResultInterface
    {
        if ($this->parcelPackstationCheck) {
            
            if (empty($this->deliveryLocation['id'])) {
                return $factory->createErrorMessage((string) __('Please select a DHL parcel station, a parcel shop or a post office.'));
            }
            
            if (isset($this->deliveryLocation['type']) && $this->deliveryLocation['type'] == 'locker') {
                if (empty($this->deliveryLocation['customerPostnumber']) && !$this->isDHLPostNumberValid())  {
                    return $factory->createErrorMessage((string) __('Please enter a DHL Post Number.'));
                }    
            }
            
            // For other delivery types, check if DHL Post Number is at least 6 characters long if not empty
            if (isset($this->deliveryLocation['customerPostnumber'])) {
                if (!$this->isDHLPostNumberValid()) {
                    return $factory->createErrorMessage((string) __('DHL Post Number must be at least 6 characters long.'));
                }
            }
            
            return $factory->createSuccess();
            
        } else {
            return $factory->createSuccess();
        }
    }

    /**
     * Check if the "DHL Post Number" input field is valid.
     *
     * @return bool True if the DHL Post Number is valid, false otherwise.
     */
    private function isDHLPostNumberValid(): bool
    {
        // Check if it meets the defined pattern and is not empty if required.
        $postNumber = $this->deliveryLocation['customerPostnumber'] ?? '';
        $pattern = '/.{6,}/'; // Define the pattern

        return preg_match($pattern, $postNumber) === 1;
    }

    /**
     * Renders the Livewire component's view.
     *
     * @return View The rendered view.
     */
    public function render()
    {
        return view('hyva::livewire.parcel-packstation');
    }

}
