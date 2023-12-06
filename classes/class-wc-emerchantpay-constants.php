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

/**
 * @class WC_Emerchantpay_Constants
 */
class WC_Emerchantpay_Constants {

	const EMERCHANTPAY_CHECKOUT_BLOCKS = 'emerchantpay-checkout-blocks';

	/**
	 * Plugin url
	 *
	 * @return string
	 */
	public static function plugin_url() {
		return untrailingslashit( dirname( plugins_url( '/', __FILE__ ) ) );
	}

	/**
	 * Plugin absolute path
	 *
	 * @return string
	 */
	public static function plugin_abspath() {
		return trailingslashit( dirname( plugin_dir_path( __FILE__ ) ) );
	}
}
