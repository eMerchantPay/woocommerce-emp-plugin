<?php
/**
 * Copyright (C) 2018-2025 emerchantpay Ltd.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author      emerchantpay Ltd.
 * @copyright   2018-2025 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 * @package     classes\class-wc-emerchantpay-checkout
 */

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class WC_Emerchantpay_Functions
 *
 * Handles various functions for the WooCommerce emerchantpay plugin.
 */
class WC_Emerchantpay_Functions {

	/**
	 * @var string $file The file path.
	 */
	private $file;

	/**
	 * Constructor.
	 *
	 * @param string $file The file path.
	 */
	public function __construct( $file ) {
		$this->file = $file;
		$this->init();
	}

	/**
	 * Initialize hooks and filters.
	 *
	 * @return void
	 *
	 * // @SuppressWarnings(PHPMD.MissingImport)
	 */
	public function init() {
		$threeds_form_helper_class    = strtolower( WC_Emerchantpay_Threeds_Form_Helper::class );
		$threeds_backend_helper_class = strtolower( WC_Emerchantpay_Threeds_Backend_Helper::class );

		add_action( 'before_woocommerce_init', array( $this, 'emp_woo_add_hpos_support' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'emp_add_css_and_js_to_checkout' ) );

		add_action( 'woocommerce_review_order_before_payment', array( $this, 'emp_add_hidden_fields_before_payment' ) );

		add_action( 'woocommerce_api_' . $threeds_form_helper_class, array( new WC_Emerchantpay_Threeds_Form_Helper(), 'display_form_and_iframe' ) );
		add_action( 'woocommerce_api_' . $threeds_backend_helper_class . '-method_continue_handler', array( new WC_Emerchantpay_Threeds_Backend_Helper(), 'method_continue_handler' ) );
		add_action( 'woocommerce_api_' . $threeds_backend_helper_class . '-callback_handler', array( new WC_Emerchantpay_Threeds_Backend_Helper(), 'callback_handler' ) );
		add_action( 'woocommerce_api_' . $threeds_backend_helper_class . '-status_checker', array( new WC_Emerchantpay_Threeds_Backend_Helper(), 'status_checker' ) );
		add_action( 'woocommerce_api_' . strtolower( WC_Emerchantpay_Frame_Handler::class ), array( new WC_Emerchantpay_Frame_Handler(), 'frame_handler' ) );
		add_action( 'woocommerce_after_checkout_form', array( $this, 'emp_direct_threeds_iframe' ) );
		add_action( 'woocommerce_before_thankyou', array( $this, 'emp_woo_custom_redirect_after_purchase' ) );

		// Add arguments to JS script source
		add_filter( 'script_loader_tag', array( $this, 'handle_cse_script_arguments' ), 10, 2 );
	}

	/**
	 * Add hidden fields after order notes for FunnelKit compatibility.
	 *
	 * @return void
	 */
	public function emp_add_hidden_fields_before_payment() {
		$field_names    = WC_Emerchantpay_Direct::THREEDS_V2_BROWSER;
		$method_code    = WC_Emerchantpay_Direct::get_method_code();
		$cse_public_key = WC_Emerchantpay_Helper::get_plugin_method_options( $method_code, WC_Emerchantpay_Direct::SETTING_KEY_CSE_PUBLIC_KEY );

		foreach ( $field_names as $field_name ) {
			$field_id = $method_code . '_' . $field_name;
			echo '<input type="hidden" name="' . esc_attr( $field_id ) . '" id="' . esc_attr( $field_id ) . '" value="" />';
		}

		$cse_field_id = $method_code . '_cse_public_key';
		echo '<input type="hidden" name="' . esc_attr( $cse_field_id ) . '" id="' . esc_attr( $cse_field_id ) . '" value="' . esc_attr( WC_Emerchantpay_Helper::deep_trim( $cse_public_key ) ) . '" />';
	}


	/**
	 * Indicates WooCommerce HPOS support by the plugin
	 *
	 * @return void
	 */
	public function emp_woo_add_hpos_support() {
		if ( ! class_exists( FeaturesUtil::class ) ) {
			return;
		}

		FeaturesUtil::declare_compatibility( 'custom_order_tables', $this->file, true );
	}

	/**
	 * Injects direct method browser parameters form helper and styles into the checkout page.
	 *
	 * @return void
	 *
	 * // @SuppressWarnings(PHPMD.ShortVariable)
	 */
	public function emp_add_css_and_js_to_checkout() {
		global $wp;

		$checkout_iframe_processing = WC_Emerchantpay_Helper::get_plugin_method_options(
			WC_Emerchantpay_Checkout::get_method_code(),
			WC_Emerchantpay_Method_Base::SETTING_KEY_IFRAME_PROCESSING
		);
		$direct_iframe_processing   = WC_Emerchantpay_Helper::get_plugin_method_options(
			WC_Emerchantpay_Direct::get_method_code(),
			WC_Emerchantpay_Method_Base::SETTING_KEY_IFRAME_PROCESSING
		);

		if ( is_checkout() && empty( $wp->query_vars['order-pay'] ) && ! isset( $wp->query_vars['order-received'] ) ) {

			$this->enqueue_script( 'emp-direct-method-browser-params-helper', '/assets/javascript/direct-method-browser-params-helper.js' );

			if ( WC_Emerchantpay_Method_Base::SETTING_VALUE_YES === $direct_iframe_processing ) {
				$this->enqueue_script( 'emp-direct-method-form-helper', '/assets/javascript/direct-method-form-helper.js' );
			}

			if ( WC_Emerchantpay_Method_Base::SETTING_VALUE_YES === $checkout_iframe_processing ) {
				$this->enqueue_script( 'emp-checkout-method-form-helper', '/assets/javascript/checkout-method-form-helper.js' );
			}
			$this->enqueue_style( 'emp-iframe-checkout', '/assets/css/iframe-checkout.css' );

			// Client Side Encryption scripts
			wp_enqueue_script( 'emp-cse-direct', 'https://d3ptmkrtf16kmh.cloudfront.net/encrypto-1.0.1.js', array(), WC_Emerchantpay_Helper::get_plugin_version(), true );
			$this->enqueue_script( 'emp-cse-direct-encrypt', '/assets/javascript/direct-credit-card-data-encrypt.js' );
		}

		$this->enqueue_style( 'emp-threeds', '/assets/css/threeds.css' );
	}

	/**
	 * Add hidden iframe to the checkout page.
	 *
	 * @return void
	 */
	public function emp_direct_threeds_iframe() {
		echo '<div class="emp-threeds-modal"><iframe class="emp-threeds-iframe" allow="payment" frameBorder="0" style="border: none;"></iframe></div>';
	}

	/**
	 * WooCommerce Custom ThanYou page redirection
	 *
	 * @param $order_id
	 *
	 * @return void
	 *
	 * @SuppressWarnings(PHPMD.ExitExpression)
	 */
	public function emp_woo_custom_redirect_after_purchase( $order_id ) {
		$wc_order = wc_emerchantpay_order_proxy()->get_order_by_id( $order_id );

		if ( ! $wc_order || ! $wc_order->get_payment_method() === WC_Emerchantpay_Checkout::get_method_code() || ! $wc_order->get_payment_method() === WC_Emerchantpay_Direct::get_method_code() ) {
			return;
		}

		$custom_url = WC_Emerchantpay_Helper::get_plugin_method_options( $wc_order->get_payment_method(), WC_Emerchantpay_Method_Base::SETTING_KEY_CUSTOM_THANKYOU_URL );

		if ( ! filter_var( (string) $custom_url, FILTER_VALIDATE_URL ) ) {
			return;
		}

		$redirect_url = WC_Emerchantpay_Helper::append_url_query_arguments(
			$custom_url,
			array(
				'order_id' => $order_id,
				'key'      => $wc_order->get_order_key(),
			)
		);

		wp_safe_redirect( $redirect_url );

		// Stop script execution
		exit;
	}

	/**
	 * Adds arguments to specified scripts
	 *
	 * @param $tag
	 * @param $handle
	 *
	 * @return array|mixed|string|string[]
	 */
	public function handle_cse_script_arguments( $tag, $handle ) {
		if ( 'emp-cse-direct' === $handle ) {
			return str_replace(
				' src',
				sprintf( ' integrity="%s" crossorigin="anonymous" src', WC_Emerchantpay_Direct::ENCRYPT_LIBRARY_INTEGRITY_HASH ),
				$tag
			);
		}

		return $tag;
	}

	/**
	 * Enqueue script.
	 *
	 * @param string $handle Script handle.
	 * @param string $file   Script file.
	 *
	 * @return void
	 */
	private function enqueue_script( $handle, $file ) {
		$version = WC_Emerchantpay_Helper::get_plugin_version();

		wp_enqueue_script(
			$handle,
			plugins_url( $file, $this->file ),
			array(),
			$version,
			true
		);
	}

	/**
	 * Enqueue style.
	 *
	 * @param string $handle Style handle.
	 * @param string $file   Style file.
	 *
	 * @return void
	 */
	private function enqueue_style( $handle, $file ) {
		$version = WC_Emerchantpay_Helper::get_plugin_version();

		wp_enqueue_style(
			$handle,
			plugins_url( $file, $this->file ),
			array(),
			$version
		);
	}
}
