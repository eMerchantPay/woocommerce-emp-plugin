<?php
/*
 * Plugin Name: WooCommerce emerchantpay Payment Gateway Client
 * Plugin URI: https://wordpress.org/plugins/emerchantpay-payment-page-for-woocommerce/
 * Description: Extend WooCommerce's Checkout options with emerchantpay's Genesis Gateway
 * Text Domain: woocommerce-emerchantpay
 * Author: emerchantpay
 * Author URI: https://www.emerchantpay.com/
 * Version: 1.11.7
 * WC requires at least: 3.0.0
 * WC tested up to: 4.3.1
 * License: GPL-2.0
 * License URI: http://opensource.org/licenses/gpl-2.0.php
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/* there is no need to load the plugin if woocommerce is not active */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	if ( ! function_exists( 'woocommerce_emerchantpay_init' ) ) {
		function woocommerce_emerchantpay_init() {
			if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
				return;
			}

			$translation = load_plugin_textdomain(
				'woocommerce-emerchantpay',
				false,
				basename( __DIR__ ) . DIRECTORY_SEPARATOR . 'languages'
			);

			if ( ! $translation ) {
				error_log( 'Unable to load language file for locale: ' . get_locale() );
			}

			include dirname( __FILE__ ) . '/libraries/genesis/vendor/autoload.php';

			include dirname( __FILE__ ) . '/includes/wc_emerchantpay_checkout.php';

			include dirname( __FILE__ ) . '/includes/wc_emerchantpay_direct.php';

			/**
			 * Add the emerchantpay Gateway to WooCommerce's
			 * list of installed gateways
			 *
			 * @param $methods Array of existing Payment Gateways
			 *
			 * @return array $methods Appended Payment Gateway
			 */
			if ( ! function_exists( 'woocommerce_add_emerchantpay_gateway' ) ) {
				function woocommerce_add_emerchantpay_gateway( $methods ) {
					array_push( $methods, 'WC_emerchantpay_Checkout' );
					array_push( $methods, 'WC_emerchantpay_Direct' );

					return $methods;
				}
			}

			add_filter( 'woocommerce_payment_gateways', 'woocommerce_add_emerchantpay_gateway' );
		}
	}

	add_action( 'plugins_loaded', 'woocommerce_emerchantpay_init', 0 );

}
