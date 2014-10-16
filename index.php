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

	    /**
	     * Localisation
	     */
	    load_plugin_textdomain('woocommerce_emerchantpay', false, './languages');

	    /*
	    if($_GET['msg']!=''){
	        add_action('the_content', 'showMessageEmp');
	    }

	    function showMessageEmp($content){
	            return '<div class="box '.htmlentities($_GET['type']).'-box">'.htmlentities(urldecode($_GET['msg'])).'</div>'.$content;
	    }
	    */

		include dirname(__FILE__) . '/includes/class.wc_genesis.php';

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
			    array_push($methods, 'WC_Genesis');
		        return $methods;
		    }
		endif;

	    add_filter('woocommerce_payment_gateways', 'woocommerce_add_genesis_gateway' );
	}
endif;
add_action('plugins_loaded', 'woocommerce_emerchantpay_init', 0);