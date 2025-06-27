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
 * @package     classes\class-wc-emerchantpay-message-helper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Emerchantpay Message Helper Class
 *
 * @class WC_Emerchantpay_Message_Helper
 */
class WC_Emerchantpay_Message_Helper {

	const NOTICE_TYPE_SUCCESS = 'success';
	const NOTICE_TYPE_ERROR   = 'error';

	/**
	 * Add WooCommerce Notice
	 *
	 * @param string $message Text message.
	 * @param string $notice_type Type of notice.
	 *
	 * @return void
	 */
	public static function add_woocommerce_notice( $message, $notice_type ) {
		wc_add_notice( $message, $notice_type );
	}

	/**
	 * Add success notice
	 *
	 * @param string $message Text message.
	 * @return void
	 */
	public static function add_success_notice( $message ) {
		static::add_woocommerce_notice( $message, static::NOTICE_TYPE_SUCCESS );
	}

	/**
	 * Add error notice
	 *
	 * @param string $message Text message.
	 * @return void
	 */
	public static function add_error_notice( $message ) {
		static::add_woocommerce_notice( $message, static::NOTICE_TYPE_ERROR );
	}
}
