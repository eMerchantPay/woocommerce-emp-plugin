<?php
/*
 * Copyright (C) 2018-2023 emerchantpay Ltd.
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
 * @copyright   2018-2023 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Emerchantpay Blocks module
 */
abstract class WC_Emerchantpay_Blocks_Base extends AbstractPaymentMethodType {

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The gateway instance.
	 */
	protected $gateway;

	/**
	 * Gateway supports
	 *
	 * @var array
	 */
	protected $supports;

	/**
	 * Only settings required for the frontend part
	 *
	 * @var array
	 */
	private $required_settings = array(
		'title',
		'description',
		'iframe_processing',
	);

	/**
	 * Initializes the payment method type.
	 */
	abstract public function initialize();

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->gateway->is_available();
	}

	/**
	 * Get array with the necessary settings only
	 *
	 * @return array
	 */
	protected function get_filtered_plugin_settings( $method_settings, $additional_settings = array() ) {
		$all_settings          = get_option( $method_settings, array() );
		$all_required_settings = array_merge( $additional_settings, $this->required_settings );
		$required_settings     = array_flip( $all_required_settings );

		return array_intersect_key( $all_settings, $required_settings );
	}
}
