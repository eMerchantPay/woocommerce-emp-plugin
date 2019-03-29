<?php
/*
 * Copyright (C) 2018 emerchantpay Ltd.
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
 * @copyright   2018 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * emerchantpay Message Helper Class
 *
 * @class   WC_emerchantpay_Message_Helper
 */
class WC_emerchantpay_Message_Helper {

	const NOTICE_TYPE_SUCCESS = 'success';
	const NOTICE_TYPE_ERROR   = 'error';

	/**
	 * @param string $message
	 * @param string $noticeType
	 * @return void
	 */
	public static function addWooCommerceNotice( $message, $noticeType ) {
		wc_add_notice( $message, $noticeType );
	}

	/**
	 * @param string $message
	 * @return void
	 */
	public static function addSuccessNotice( $message ) {
		static::addWooCommerceNotice( $message, static::NOTICE_TYPE_SUCCESS );
	}

	/**
	 * @param string $message
	 * @return void
	 */
	public static function addErrorNotice( $message ) {
		static::addWooCommerceNotice( $message, static::NOTICE_TYPE_ERROR );
	}
}
