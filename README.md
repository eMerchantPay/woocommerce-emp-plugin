eMerchantPay Gateway Module for WooCommerce
===========================================

This is a Payment Module for WooCommerce that gives you the ability to process payments through eMerchantPay's Payment Gateway - Genesis.

Requirements
------------

* WooCommerce 2.x (Tested up to 2.6.4)
* [GenesisPHP v1.4.3](https://github.com/GenesisGateway/genesis_php) - (Integrated in Module)
* PCI-certified server in order to use ```eMerchantPay Direct```

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
* Navigate to ```WooCommerce -> Settings -> Checkout``` 
* Select your preferred payment method ```eMerchantPay Checkout``` or ```eMerchantPay Direct```
* Check ```Enable```, set the correct credentials and click "Save changes"

Enable WooCommerce Secure Checkout
------------
This steps should be followed if you with to use the ```eMerchantPay Direct``` Method
* Ensure you have installed and configured a SSL Certificate on your PCI-DSS Certified Server
* Login into your WordPress Admin Panel with Administrator privileges
* Navigate to ```WooCommerce``` - > ```Settings``` -> ```Checkout```
* In Section ```Checkout Process``` check ```Force secure checkout```

_Note_: If you have trouble with your credentials or terminal configuration, get in touch with our [support] team

You're now ready to process payments through our gateway.

[support]: mailto:tech-support@emerchantpay.net
