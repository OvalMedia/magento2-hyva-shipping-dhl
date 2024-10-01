
# magento2-hyva-shipping-dhl
Hyvä Compatibility module for Hyva_ShippingDhl
 
## Installation

### Via packagist.com

Hyvä Compatibility modules that are tagged as stable can be installed using composer via packagist.com:

1. Install via composer
    ```
    composer require hyva-themes/magento2-hyva-shipping-dhl
    ```
2. Enable module
    ```
    bin/magento setup:upgrade
    ```


### Via gitlab

For development of or to contribute to this module, it needs to be installed using composer via gitlab.  
This installation method is not suited for deployments, because gitlab requires SSH key authorization.

1. Install via composer
    If this is the first time a compatibility module is installed via gitlab, the compat-module-fallback git repository has to be added as a
    composer repository. This step is only required once.
    ```
    composer config repositories.hyva-themes/magento2-compat-module-fallback git git@gitlab.hyva.io:hyva-themes/magento2-compat-module-fallback.git
    ```

    When the compat-module-fallback repo is configured, the compatibility module itself can be installed with composer:
    ```
    composer config repositories.hyva-themes/magento2-hyva-shipping-dhl git git@gitlab.hyva.io:hyva-checkout/checkout-integrations/magento2-hyva-shipping-dhl.git
    composer require hyva-themes/magento2-hyva-shipping-dhl:dev-main
    ```
2. Enable module
    ```
    bin/magento setup:upgrade
    ```
   
