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

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Class WC_Emerchantpay_Blocks_Functions
 *
 * Handles the integration of WooCommerce Blocks for the emerchantpay plugin.
 */
class WC_Emerchantpay_Blocks_Functions {

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
	 */
	public function init() {
		add_action( 'woocommerce_blocks_loaded', array( $this, 'emerchantpay_blocks_support' ) );
	}

	/**
	 * Add credit card input styles to the blocks checkout page.
	 *
	 * @return void
	 */
	public function add_credit_card_input_styles() {
		$block_name = 'genesisgateway/emerchantpay_direct';
		$args       = array(
			'handle' => 'emp-credit-card-input-styles',
			'src'    => plugins_url( '/assets/css/blocks/credit-card-inputs.css', $this->file ),
			'path'   => plugins_url( '/assets/css/blocks/credit-card-inputs.css', $this->file ),
			'ver'    => WC_Emerchantpay_Helper::get_plugin_version(),
		);

		wp_enqueue_block_style( $block_name, $args );
	}

	/**
	 * Registers WooCommerce Blocks integration.
	 *
	 * @return void
	 *
	 * // @SuppressWarnings(PHPMD.MissingImport)
	 */
	public function emerchantpay_blocks_support() {
		if ( class_exists( AbstractPaymentMethodType::class ) ) {
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new WC_Emerchantpay_Blocks_Checkout() );
					$payment_method_registry->register( new WC_Emerchantpay_Blocks_Direct() );
				}
			);
			add_action( 'wp_enqueue_scripts', array( $this, 'add_credit_card_input_styles' ) );
		}
	}
}
