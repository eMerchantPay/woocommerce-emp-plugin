<?php
/*
Plugin Name: WooCommerce eMerchantPay Payment Gateway Client
Description: Extends WooCommerce's Checkout with eMerchantPay/Genesis Gateway
Version: 1.0.0
*/

if ( !function_exists('woocommerce_emerchantpay_init') ):
	function woocommerce_emerchantpay_init()
	{
	    if ( !class_exists( 'WC_Payment_Gateway' ) ) return;

		// Load text Domain
	    load_plugin_textdomain('woocommerce_emerchantpay', false, 'languages');

		// Get Genesis class
		include dirname(__FILE__) . '/includes/WC_Gateway_Genesis_Checkout.php';

		/**
		 * Add the eMerchantPay's Genesis Gateway to WooCommerce
		 * list of installed gateways
		 *
		 * @param $methods Array of existing Payment Gateways
		 *
		 * @return array $methods Appended Payment Gateway
		 */
		if ( !function_exists('woocommerce_add_genesis_gateway') ):
		    function woocommerce_add_genesis_gateway($methods) {
			    array_push($methods, 'WC_Gateway_Genesis_Checkout');
		        return $methods;
		    }
		endif;

	    add_filter('woocommerce_payment_gateways', 'woocommerce_add_genesis_gateway' );
	}
endif;
add_action('plugins_loaded', 'woocommerce_emerchantpay_init', 0);