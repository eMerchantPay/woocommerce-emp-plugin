eMerchantPay Gateway Module for WooCommerce
===========================================

This is a Payment Module for WooCommerce that gives you the ability to process payments through eMerchantPay's Payment Gateway - Genesis.

Requirements
------------

* WooCommerce 2.x
* GenesisPHP 1.2

GenesisPHP Requirements
------------

* PHP version 5.3.2 or newer
* PHP Extensions:
    * [BCMath](https://php.net/bcmath)
    * [CURL](https://php.net/curl) (required, only if you use the curl network interface)
    * [Filter](https://php.net/filter)
    * [Hash](https://php.net/hash)
    * [XMLReader](https://php.net/xmlreader)
    * [XMLWriter](https://php.net/xmlwriter)

Installation
------------

* Login into your Wordpress Admin Panel with Administrator privileges
* Navigate to ```Plugins -> Add New```
* Install through the Marketplace/ Select the downloaded ```.zip``` File
* Activate the newly installed ```WooCommerce eMerchantPay Payment Gateway Client``` plugin
* Navigate to ```WooCommerce -> Settings -> Checkout -> E-ComProcessing```
* Check ```Enable```, set the correct credentials and click "Save changes"

You're now ready to process payments through our gateway.
