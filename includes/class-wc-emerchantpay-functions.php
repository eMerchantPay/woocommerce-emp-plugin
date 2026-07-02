<?php
/**
 * Copyright (C) 2018-2024 emerchantpay Ltd.
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
 * @copyright   2018-2024 emerchantpay Ltd.
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
		add_filter( 'woocommerce_checkout_fields', array( $this, 'emp_add_hidden_fields_to_checkout' ) );
		add_action( 'woocommerce_api_' . $threeds_form_helper_class, array( new WC_Emerchantpay_Threeds_Form_Helper(), 'display_form_and_iframe' ) );
		add_action( 'woocommerce_api_' . $threeds_backend_helper_class . '-method_continue_handler', array( new WC_Emerchantpay_Threeds_Backend_Helper(), 'method_continue_handler' ) );
		add_action( 'woocommerce_api_' . $threeds_backend_helper_class . '-callback_handler', array( new WC_Emerchantpay_Threeds_Backend_Helper(), 'callback_handler' ) );
		add_action( 'woocommerce_api_' . $threeds_backend_helper_class . '-status_checker', array( new WC_Emerchantpay_Threeds_Backend_Helper(), 'status_checker' ) );
		add_action( 'woocommerce_api_' . strtolower( WC_Emerchantpay_Frame_Handler::class ), array( new WC_Emerchantpay_Frame_Handler(), 'frame_handler' ) );
		add_action( 'woocommerce_after_checkout_form', array( $this, 'emp_direct_threeds_iframe' ) );
		add_action( 'woocommerce_before_thankyou', array( $this, 'emp_woo_custom_redirect_after_purchase' ) );
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

			$this->enqueue_script(
				'emp-direct-method-browser-params-helper',
				'/assets/javascript/direct-method-browser-params-helper.js'
			);

			if ( WC_Emerchantpay_Method_Base::SETTING_VALUE_YES === $direct_iframe_processing ) {
				$this->enqueue_script(
					'emp-direct-method-form-helper',
					'/assets/javascript/direct-method-form-helper.js'
				);
			}

			if ( WC_Emerchantpay_Method_Base::SETTING_VALUE_YES === $checkout_iframe_processing ) {
				$this->enqueue_script(
					'emp-checkout-method-form-helper',
					'/assets/javascript/checkout-method-form-helper.js'
				);
			}
			$this->enqueue_style(
				'emp-iframe-checkout',
				'/assets/css/iframe-checkout.css'
			);
		}

		$this->enqueue_style(
			'emp-threeds',
			'/assets/css/threeds.css'
		);
	}

	/**
	 * Add hidden inputs to the Credit Card form.
	 *
	 * @param array $fields Hidden fields to checkout.
	 *
	 * @return array Modified fields with hidden inputs.
	 */
	public function emp_add_hidden_fields_to_checkout( $fields ) {
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
	 * Add hidden iframe to the checkout page.
	 *
	 * @return void
	 */
	public function emp_direct_threeds_iframe() {
		echo '<div class="emp-threeds-modal"><iframe class="emp-threeds-iframe" frameBorder="0" style="border: none;"></iframe></div>';
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
