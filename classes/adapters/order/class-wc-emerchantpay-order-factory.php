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
* @package     classes\adapters\class-wc-emerchantpay-legacy-order-adapter
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 ); // Exit if accessed directly.
}

use WC_Emerchantpay_Hpos_Order_Adapter as HposOrder;
use WC_Emerchantpay_Legacy_Order_Adapter as LegacyOrder;
use WC_Emerchantpay_Order_Helper as OrderHelper;

/**
 * Order Storage Adapter Factory
 */
class WC_Emerchantpay_Order_Factory {
	/**
	 * Get whether HPOS Order is enabled
	 *
	 * @return bool
	 */
	public static function is_hpos_enabled() {
		$utils_class = '\Automattic\WooCommerce\Utilities\OrderUtil';
		// Compatibility support before WC 8
		if ( class_exists( $utils_class ) && method_exists( $utils_class, 'custom_orders_table_usage_is_enabled' ) ) {
			return $utils_class::custom_orders_table_usage_is_enabled();
		}

		return false;
	}

	/**
	 * Initialize adapter based on the WooCommerce Order storage
	 *
	 * @return WC_Emerchantpay_Order_Adapter_Interface
	 */
	public static function init_order_adapter() {
		if ( static::is_hpos_enabled() ) {
			// HPOS usage is enabled.
			return new HposOrder();
		}

		// Traditional CPT-based orders are in use.
		return new LegacyOrder();
	}

	/**
	 * Provide the Order identifier based on the way how the EMP adapters are working
	 *  - hpos -> WC_Order
	 *  - legacy -> integer (order_id)
	 *
	 * @param WC_Order $order
	 *
	 * @return WC_Order|string
	 */
	public static function get_order_accessor( $order ) {
		return static::is_hpos_enabled() ? $order : OrderHelper::get_order_prop( $order, 'id' );
	}
}
