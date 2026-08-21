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
 * @package     includes\blocks\class-wc-emerchantpay-blocks-direct
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Emerchantpay Blocks Direct module
 */
final class WC_Emerchantpay_Blocks_Direct extends WC_Emerchantpay_Blocks_Base {

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = WC_Emerchantpay_Constants::EMERCHANTPAY_DIRECT_BLOCKS;

	/**
	 * Only settings required for the frontend
	 *
	 * @var array
	 */
	private $required_settings = array(
		WC_Emerchantpay_Direct::SETTING_KEY_SHOW_CC_HOLDER,
		WC_Emerchantpay_Direct::SETTING_KEY_CSE_PUBLIC_KEY,
	);

	/**
	 * Initializes the payment method type.
	 *
	 * @SuppressWarnings(PHPMD.MissingImport)
	 */
	public function initialize() {
		$options = array(
			'blocks_instantiate'             => true,
			'register_renewal_subscriptions' => false,
		);
		if ( ! is_admin() ) {
			$options['draw_transaction_tree'] = false;
		}
		$this->gateway  = new WC_Emerchantpay_Direct( $options );
		$this->supports = $this->gateway->supports;
		$this->settings = $this->get_filtered_plugin_settings(
			'woocommerce_emerchantpay_direct_settings',
			$this->required_settings
		);
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
			'show_cc_holder'    => $this->settings['show_cc_holder'],
			'iframe_processing' => $this->settings['iframe_processing'],
			'cse_public_key'    => WC_Emerchantpay_Helper::deep_trim( $this->settings[ WC_Emerchantpay_Direct::SETTING_KEY_CSE_PUBLIC_KEY ] ?? '' ),
		);
	}
}
