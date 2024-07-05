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
 * @package     includes\blocks\class-wc-emerchantpay-blocks-checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Emerchantpay Blocks Checkout module
 */
final class WC_Emerchantpay_Blocks_Checkout extends WC_Emerchantpay_Blocks_Base {

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = WC_Emerchantpay_Constants::EMERCHANTPAY_CHECKOUT_BLOCKS;

	/**
	 * Initializes the payment method type.
	 * @SuppressWarnings(PHPMD.MissingImport)
	 */
	public function initialize() {
		$options        = array(
			'draw_transaction_tree' => false,
		);
		$this->gateway  = new WC_Emerchantpay_Checkout( $options );
		$this->supports = $this->gateway->supports;
		$this->settings = $this->get_filtered_plugin_settings( 'woocommerce_emerchantpay_checkout_settings' );
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 * new WC_Payment_Gateway();
	 *
	 * @SuppressWarnings(PHPMD.MissingImport)
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$script_path       = '/assets/js/frontend/blocks.js';
		$script_asset_path = WC_Emerchantpay_Constants::plugin_abspath() . 'assets/js/frontend/blocks.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => WC_Emerchantpay_Helper::get_plugin_version(),
			);
		$script_url        = WC_Emerchantpay_Constants::plugin_url() . $script_path;

		wp_register_script(
			'wc-emerchantpay-payments-blocks-checkout',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations(
				'wc-emerchantpay-payments-blocks-checkout',
				'woocommerce-emerchantpay',
				WC_Emerchantpay_Constants::plugin_abspath() . 'languages/'
			);
		}

		return array( 'wc-emerchantpay-payments-blocks-checkout' );
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return array(
			'title'             => $this->settings['title'],
			'description'       => $this->settings['description'],
			'supports'          => array_filter( $this->gateway->supports, array( $this->gateway, 'supports' ) ),
			'iframe_processing' => $this->settings['iframe_processing'],
		);
	}
}
