<?php
/**
 * Plugin Name: WooCommerce emerchantpay Payment Gateway Client
 * Plugin URI: https://wordpress.org/plugins/emerchantpay-payment-page-for-woocommerce/
 * Description: Extend WooCommerce's Checkout options with emerchantpay's Genesis Gateway
 * Text Domain: woocommerce-emerchantpay
 * Author: emerchantpay
 * Author URI: https://www.emerchantpay.com/
 * Version: 1.17.10
 * Requires at least: 4.0
 * Tested up to: 6.8.1
 * WC requires at least: 3.0.0
 * WC tested up to: 9.9.4
 * WCS tested up to: 7.6.0
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

/**
 * Load the emerchantpay plugin text domain
 */
function woocommerce_emerchantpay_load_textdomain() {
	$translation = load_plugin_textdomain(
		'woocommerce-emerchantpay',
		false,
		basename( __DIR__ ) . DIRECTORY_SEPARATOR . 'languages'
	);

	if ( ! $translation && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( 'Unable to load language file for locale: ' . get_locale() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}
}


/**
 * Filter for handling emerchantpay orders in My Account
 *
 * @param  array    $actions List of available actions
 * @param  WC_Order $order   The order object
 *
 * @return array Modified actions
 */
function woocommerce_emerchantpay_my_orders_actions( $actions, $order ) {
	$is_pending = $order && $order->has_status( 'pending' );
	$is_emp     = ( $order instanceof WC_Order ) && in_array(
		$order->get_payment_method(),
		array(
			WC_Emerchantpay_Checkout::get_method_code(),
			WC_Emerchantpay_Direct::get_method_code(),
		),
		true
	);

	if ( $is_emp && $is_pending ) {
		unset( $actions['pay'] );
		unset( $actions['cancel'] );
	}

	return $actions;
}

/**
 * Add emerchantpay payment gateways
 *
 * @param array $methods Existing payment gateways
 * @return array
 */
function woocommerce_add_emerchantpay_gateway( $methods ) {
	$methods[] = 'WC_Emerchantpay_Checkout';
	$methods[] = 'WC_Emerchantpay_Direct';

	return $methods;
}

/* there is no need to load the plugin if woocommerce is not active */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {

	if ( ! function_exists( 'woocommerce_emerchantpay_init' ) ) {
		/**
		 * Init woocommerce emerchantpay plugin.
		 */
		/**
		 * Init woocommerce emerchantpay plugin.
		 */
		function woocommerce_emerchantpay_init() {
			if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
				return;
			}

			woocommerce_emerchantpay_load_textdomain();

			add_filter( 'woocommerce_payment_gateways', 'woocommerce_add_emerchantpay_gateway' );
			add_filter( 'woocommerce_my_account_my_orders_actions', 'woocommerce_emerchantpay_my_orders_actions', 10, 2 );
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
