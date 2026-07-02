<?php
/**
 * Plugin Name: WooCommerce emerchantpay Payment Gateway Client
 * Plugin URI: https://wordpress.org/plugins/emerchantpay-payment-page-for-woocommerce/
 * Description: Extend WooCommerce's Checkout options with emerchantpay's Genesis Gateway
 * Text Domain: woocommerce-emerchantpay
 * Author: emerchantpay
 * Author URI: https://www.emerchantpay.com/
 * Version: 1.17.1
 * Requires at least: 4.0
 * Tested up to: 6.7
 * WC requires at least: 3.0.0
 * WC tested up to: 9.4.1
 * WCS tested up to: 6.8.0
 * WCB tested up to: 11.7.0
 * License: GPL-2.0
 * License URI: http://opensource.org/licenses/gpl-2.0.php
 *
 * @package index.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once 'libraries/vendor/autoload.php';

/* there is no need to load the plugin if woocommerce is not active */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {

	if ( ! function_exists( 'woocommerce_emerchantpay_init' ) ) {
		/**
		 * Init woocommerce emerchantpay plugin.
		 */
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
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Unable to load language file for locale: ' . get_locale() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}
			}

			/**
			 * Add the emerchantpay Gateway to WooCommerce's
			 * list of installed gateways
			 *
			 * @param $methods Array of existing Payment Gateways
			 *
			 * @return array $methods Appended Payment Gateway
			 */
			if ( ! function_exists( 'woocommerce_add_emerchantpay_gateway' ) ) {
				/**
				 * Add emerchantpay payment gateways
				 *
				 * @param array $methods An existing Payment Gateways.
				 *
				 * @return array
				 */
				function woocommerce_add_emerchantpay_gateway( $methods ) {
					$methods[] = 'WC_Emerchantpay_Checkout';
					$methods[] = 'WC_Emerchantpay_Direct';

					return $methods;
				}
			}

			add_filter( 'woocommerce_payment_gateways', 'woocommerce_add_emerchantpay_gateway' );
		}
	}
	add_action( 'plugins_loaded', 'woocommerce_emerchantpay_init', 0 );

	new WC_Emerchantpay_Functions( __FILE__ );
	new WC_Emerchantpay_Blocks_Functions( __FILE__ );


	if ( ! function_exists( 'wc_emerchantpay_post_adapter' ) ) {
		/**
		 * @return WC_Emerchantpay_Posts_Adapter|null
		 */
		function wc_emerchantpay_post_adapter() {
			return WC_Emerchantpay_Posts_Adapter::get_instance();
		}
	}

	if ( ! function_exists( 'wc_emerchantpay_order_proxy' ) ) {
		/**
		 * @return WC_Emerchantpay_Order_Proxy
		 */
		function wc_emerchantpay_order_proxy() {
			return WC_Emerchantpay_Order_Proxy::get_instance();
		}
	}
}
