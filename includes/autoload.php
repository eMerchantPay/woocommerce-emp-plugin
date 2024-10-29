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

function emerchantpay_autoloader( $class_name ) {
	if ( strpos( $class_name, 'WC_Emerchantpay_' ) !== 0 ) {
		return;
	}

	$class_slug = strtolower( str_replace( '_', '-', $class_name ) );
	$file_name  = str_contains( $class_slug, '-interface' ) ? $file_name = 'interface-' . $class_slug . '.php' : 'class-' . $class_slug . '.php';

	$directories = array(
		__DIR__ . '/../classes/',
		__DIR__ . '/../classes/adapters/',
		__DIR__ . '/../classes/adapters/order/',
		__DIR__ . '/../includes/',
		__DIR__ . '/../includes/blocks/',
	);

	foreach ( $directories as $dir ) {
		$file = $dir . $file_name;
		if ( file_exists( $file ) ) {
			require_once $file;

			return;
		}
	}
}

spl_autoload_register( 'emerchantpay_autoloader' );
