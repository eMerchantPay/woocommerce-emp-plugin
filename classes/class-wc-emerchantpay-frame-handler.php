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

/**
 * emerchantpay 3DS v2 Return url via iframe handler
 *
 * @class WC_Emerchantpay_Frame_Handler
 */
class WC_Emerchantpay_Frame_Handler {
	/**
	 * Parse QUERY_STRING and load it into the parent window
	 *
	 * @return void
	 */
	public static function frame_handler() {
		if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
			$query_params = urldecode( wp_unslash( $_SERVER['QUERY_STRING'] ) );
			echo "<script>parent.location.href = '" . esc_url_raw( $query_params ) . "';</script>";

			exit;
		}

		wp_die( 'Missing data!' );
	}
}
