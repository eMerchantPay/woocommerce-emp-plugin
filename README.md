Genesis client for WooCommerce
=============================

This is a Payment Module for eMerchantPay that gives you the ability to process payments through eMerchantPay's Payment Gateway - Genesis.

Requirements
------------

* WooCommerce 2.x
* GenesisPHP 1.0

GenesisPHP Requirements
------------

* PHP version >= 5.3 (however since 5.3 is EoL, we recommend at least PHP v5.4)
* PHP with libxml
* PHP ext: cURL (optionally you can use StreamContext)
* Composer

Installation
------------

* Login into your Wordpress Admin Panel
* Navigate to Plugins -> Add New
* Install through the Marketplace/Zip File
* Activate the newly installed "WooCommerce eMerchantPay Payment Gateway Client" plugin
* Navigate to WooCommerce -> Settings -> Checkout -> eMerchantPay
* Check "Enable" and set the correct credentials and click "Save changes"

You're now ready to process payments through our gateway.
