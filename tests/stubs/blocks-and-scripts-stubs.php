<?php
/**
 * Script registry and WooCommerce Blocks stubs for unit tests.
 *
 * @package WC_Emerchantpay\Tests
 */

namespace Automattic\WooCommerce\Blocks\Payments\Integrations {
	abstract class AbstractPaymentMethodType {
		public $settings = array();
	}
}

namespace {
	if ( ! function_exists( 'wc_emerchantpay_test_reset_scripts' ) ) {
		function wc_emerchantpay_test_reset_scripts() {
			global $wp_test_enqueued_scripts, $wp_test_registered_scripts, $wp_test_script_translations;

			$wp_test_enqueued_scripts       = array();
			$wp_test_registered_scripts     = array();
			$wp_test_script_translations    = array();
		}
	}

	wc_emerchantpay_test_reset_scripts();

	if ( ! function_exists( 'wp_enqueue_script' ) ) {
		function wp_enqueue_script( $handle, $src = '', $deps = array(), $version = false, $in_footer = false ) {
			global $wp_test_enqueued_scripts;

			$wp_test_enqueued_scripts[ $handle ] = array(
				'src'       => $src,
				'deps'      => $deps,
				'version'   => $version,
				'in_footer' => $in_footer,
			);
		}
	}

	if ( ! function_exists( 'wp_register_script' ) ) {
		function wp_register_script( $handle, $src = '', $deps = array(), $version = false, $in_footer = false ) {
			global $wp_test_registered_scripts;

			$wp_test_registered_scripts[ $handle ] = array(
				'src'       => $src,
				'deps'      => $deps,
				'version'   => $version,
				'in_footer' => $in_footer,
			);
		}
	}

	if ( ! function_exists( 'wp_script_is' ) ) {
		function wp_script_is( $handle, $status = 'enqueued' ) {
			global $wp_test_registered_scripts;

			return 'registered' === $status && isset( $wp_test_registered_scripts[ $handle ] );
		}
	}

	if ( ! function_exists( 'wp_set_script_translations' ) ) {
		function wp_set_script_translations( $handle, $domain, $path = null ) {
			global $wp_test_script_translations;

			$wp_test_script_translations[ $handle ] = array(
				'domain' => $domain,
				'path'   => $path,
			);
		}
	}

	if ( ! function_exists( 'wp_enqueue_style' ) ) {
		function wp_enqueue_style( $handle, $src = '', $deps = array(), $version = false ) {}
	}

	if ( ! function_exists( 'is_checkout' ) ) {
		function is_checkout() {
			return true;
		}
	}

	if ( ! function_exists( 'get_option' ) ) {
		function get_option( $option, $default = false ) {
			global $wp_test_options;

			return isset( $wp_test_options[ $option ] ) ? $wp_test_options[ $option ] : $default;
		}
	}

	if ( ! function_exists( 'trailingslashit' ) ) {
		function trailingslashit( $string ) {
			return rtrim( $string, '/\\' ) . '/';
		}
	}

	if ( ! function_exists( 'untrailingslashit' ) ) {
		function untrailingslashit( $string ) {
			return rtrim( $string, '/\\' );
		}
	}

	if ( ! function_exists( 'get_plugin_data' ) ) {
		function get_plugin_data( $plugin_file, $markup = true, $translate = true ) {
			return array( 'Version' => 'test-version' );
		}
	}

	if ( ! class_exists( 'WC_Emerchantpay_Helper' ) ) {
		class WC_Emerchantpay_Helper {
			public static function get_plugin_version() {
				return 'test-version';
			}

			public static function get_plugin_method_options( $method, $key = '' ) {
				return '';
			}
		}
	}

	if ( ! class_exists( 'WC_Emerchantpay_Constants' ) ) {
		class WC_Emerchantpay_Constants {
			const EMERCHANTPAY_CHECKOUT_BLOCKS = 'emerchantpay-checkout-blocks';
			const EMERCHANTPAY_DIRECT_BLOCKS   = 'emerchantpay-direct-blocks';

			public static function plugin_url() {
				return 'https://example.com/wp-content/plugins/woocommerce-emerchantpay';
			}

			public static function plugin_abspath() {
				return dirname( __DIR__, 2 ) . '/';
			}
		}
	}


	if ( ! class_exists( 'WC_Emerchantpay_Checkout' ) ) {
		class WC_Emerchantpay_Checkout {
			public static function get_method_code() {
				return 'emerchantpay_checkout';
			}
		}
	}

	if ( ! class_exists( 'WC_Emerchantpay_Direct' ) ) {
		class WC_Emerchantpay_Direct {
			const SETTING_KEY_CSE_PUBLIC_KEY = 'cse_public_key';

			public static function get_method_code() {
				return 'emerchantpay_direct';
			}
		}
	}
}
