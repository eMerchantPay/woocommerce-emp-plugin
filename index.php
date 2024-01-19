<?php
/*
 * Plugin Name: WooCommerce emerchantpay Payment Gateway Client
 * Plugin URI: https://wordpress.org/plugins/emerchantpay-payment-page-for-woocommerce/
 * Description: Extend WooCommerce's Checkout options with emerchantpay's Genesis Gateway
 * Text Domain: woocommerce-emerchantpay
 * Author: emerchantpay
 * Author URI: https://www.emerchantpay.com/
 * Version: 1.14.8
 * Requires at least: 4.0
 * Tested up to: 6.4
 * WC requires at least: 3.0.0
 * WC tested up to: 8.5.0
 * WCS tested up to: 5.7.0
 * WCB tested up to: 11.7.0
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

			include __DIR__ . '/libraries/genesis/vendor/autoload.php';
			include __DIR__ . '/includes/wc_emerchantpay_checkout.php';
			include __DIR__ . '/includes/class-wc-emerchantpay-direct.php';
			include __DIR__ . '/classes/class-wc-emerchantpay-constants.php';

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
					$methods[] = 'WC_emerchantpay_Checkout';
					$methods[] = 'WC_emerchantpay_Direct';

					return $methods;
				}
			}

			add_filter( 'woocommerce_payment_gateways', 'woocommerce_add_emerchantpay_gateway' );
		}
	}

	/**
	 * Injects direct method browser parameters form helper and styles into the checkout page
	 *
	 * @return void
	 */
	function emp_add_css_and_js_to_checkout() {
		global $wp;

		$options                    = get_option( 'woocommerce_' . WC_emerchantpay_Checkout::get_method_code() . '_settings' );
		$checkout_iframe_processing = WC_emerchantpay_Helper::getArrayItemsByKey(
			$options,
			WC_emerchantpay_Method::SETTING_KEY_IFRAME_PROCESSING,
			false
		);

		$options_direct           = get_option( 'woocommerce_' . WC_Emerchantpay_Direct::get_method_code() . '_settings' );
		$direct_iframe_processing = WC_emerchantpay_Helper::getArrayItemsByKey(
			$options_direct,
			WC_emerchantpay_Method::SETTING_KEY_IFRAME_PROCESSING,
			true
		);

		if ( is_checkout() && empty( $wp->query_vars['order-pay'] ) && ! isset( $wp->query_vars['order-received'] ) ) {

			$version = WC_emerchantpay_Helper::get_plugin_version();

			wp_enqueue_script(
				'emp-direct-method-browser-params-helper',
				plugins_url( '/assets/javascript/direct-method-browser-params-helper.js', __FILE__ ),
				array(),
				$version,
				true
			);

			if ( WC_emerchantpay_Method::SETTING_VALUE_YES === $direct_iframe_processing ) {
				wp_enqueue_script(
					'emp-direct-method-form-helper',
					plugins_url( '/assets/javascript/direct-method-form-helper.js', __FILE__ ),
					array(),
					$version,
					true
				);
			}

			if ( WC_emerchantpay_Method::SETTING_VALUE_YES === $checkout_iframe_processing ) {
				wp_enqueue_script(
					'emp-checkout-method-form-helper',
					plugins_url( '/assets/javascript/checkout-method-form-helper.js', __FILE__ ),
					array(),
					$version,
					true
				);
			}
			wp_enqueue_style(
				'emp-iframe-checkout',
				plugins_url( '/assets/css/iframe-checkout.css', __FILE__ ),
				array(),
				$version
			);
		}
	}

	/**
	 * Add hidden inputs to the Credit Card form
	 *
	 * @param $fields
	 *
	 * @return array
	 */
	function emp_add_hidden_fields_to_checkout( $fields ) {
		$field_names = WC_Emerchantpay_Direct::THREEDS_V2_BROWSER;

		array_walk(
			$field_names,
			function ( $field_name ) use ( &$fields ) {
				$fields['order'][ WC_Emerchantpay_Direct::get_method_code() . '_' . $field_name ] = array(
					'type'    => 'hidden',
					'default' => null,
				);
			}
		);

		return $fields;
	}

	/**
	 * Add hidden iframe to the checkout page
	 *
	 * @return void
	 */
	function emp_direct_threeds_iframe() {
		echo '<div class="emp-threeds-modal"><iframe class="emp-threeds-iframe" frameBorder="0" style="border: none;"></iframe></div>';
	}
	add_action( 'woocommerce_after_checkout_form', 'emp_direct_threeds_iframe' );

	add_action( 'plugins_loaded', 'woocommerce_emerchantpay_init', 0 );
	add_action( 'wp_enqueue_scripts', 'emp_add_css_and_js_to_checkout' );
	add_filter( 'woocommerce_checkout_fields', 'emp_add_hidden_fields_to_checkout' );

	include dirname( __FILE__ ) . '/classes/class-wc-emerchantpay-threeds-form-helper.php';
	include dirname( __FILE__ ) . '/classes/class-wc-emerchantpay-threeds-backend-helper.php';
	include dirname( __FILE__ ) . '/classes/class-wc-emerchantpay-frame-handler.php';

	$threeds_form_helper_class = strtolower( WC_Emerchantpay_Threeds_Form_Helper::class );
	add_action( 'woocommerce_api_' . $threeds_form_helper_class, array( new WC_Emerchantpay_Threeds_Form_Helper(), 'display_form_and_iframe' ) );

	$threeds_backend_helper_class = strtolower( WC_Emerchantpay_Threeds_Backend_Helper::class );
	add_action( 'woocommerce_api_' . $threeds_backend_helper_class . '-method_continue_handler', array( new WC_Emerchantpay_Threeds_Backend_Helper(), 'method_continue_handler' ) );
	add_action( 'woocommerce_api_' . $threeds_backend_helper_class . '-callback_handler', array( new WC_Emerchantpay_Threeds_Backend_Helper(), 'callback_handler' ) );
	add_action( 'woocommerce_api_' . $threeds_backend_helper_class . '-status_checker', array( new WC_Emerchantpay_Threeds_Backend_Helper(), 'status_checker' ) );
	add_action( 'woocommerce_api_' . strtolower( WC_Emerchantpay_Frame_Handler::class ), array( new WC_Emerchantpay_Frame_Handler(), 'frame_handler' ) );


	/**
	 * Add credit card input styles to the blocks checkout page
	 *
	 * @return void
	 */
	function add_credit_card_input_styles() {
		$block_name = 'genesisgateway/emerchantpay_direct';
		$args       = array(
			'handle' => 'credit-card-input-styles',
			'src'    => plugins_url( '/assets/css/blocks/credit-card-inputs.css', __FILE__ ),
			'path'   => plugins_url( '/assets/css/blocks/credit-card-inputs.css', __FILE__ ),
			'ver'    => WC_emerchantpay_Helper::get_plugin_version(),
		);

		wp_enqueue_block_style( $block_name, $args );
	}
	add_action( 'after_setup_theme', 'add_credit_card_input_styles' );

	/**
	 * Registers WooCommerce Blocks integration
	 */
	function emerchantpay_blocks_support() {
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			require_once 'includes/blocks/class-wc-emerchantpay-blocks-base.php';
			require_once 'includes/blocks/class-wc-emerchantpay-blocks-checkout.php';
			require_once 'includes/blocks/class-wc-emerchantpay-blocks-direct.php';

			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new WC_Emerchantpay_Blocks_Checkout() );
					$payment_method_registry->register( new WC_Emerchantpay_Blocks_Direct() );
				}
			);
		}
	}

	/**
	 * Registers WooCommerce Blocks integration
	 */
	add_action( 'woocommerce_blocks_loaded', 'emerchantpay_blocks_support' );
}
