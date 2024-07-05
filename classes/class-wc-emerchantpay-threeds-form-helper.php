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
 * @copyright   2018-2022 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 * @package     classes\class-wc-emerchantpay-threeds-form-helper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

if ( ! class_exists( 'WC_Emerchantpay_Threeds_Base' ) ) {
	require_once __DIR__ . '/class-wc-emerchantpay-threeds-base.php';
}

/**
 * Emerchantpay 3DS v2 Form Helper Class
 *
 * @class WC_Emerchantpay_Threeds_Form_Helper
 */
class WC_Emerchantpay_Threeds_Form_Helper extends WC_Emerchantpay_Threeds_Base {
	/**
	 * Load template with hidden iframe and scripts
	 *
	 * @suppressWarnings(PHPMD.ExitExpression)
	 * @return void
	 */
	public function display_form_and_iframe() {
		$args = $this->get_data_from_url();
		if ( ! $args || ! $args['unique_id'] || ! $args['signature'] ) {
			wp_die( 'Missing data!' );
		}

		$template_name = 'threeds/form-helper.php';
		$default_path  = dirname( plugin_dir_path( __FILE__ ) ) . '/templates/';

		wc_get_template( $template_name, $args, '', $default_path );

		// TODO: Fix exit expression
		exit;
	}
}
