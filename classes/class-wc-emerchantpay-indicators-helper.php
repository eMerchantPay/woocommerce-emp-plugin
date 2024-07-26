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
 * @package     classes\class-wc-emerchantpay-indicators-helper
 */

use Genesis\Api\Constants\Transaction\Parameters\Threeds\V2\CardHolderAccount\ShippingAddressUsageIndicators;
use Genesis\Api\Constants\Transaction\Parameters\Threeds\V2\CardHolderAccount\RegistrationIndicators;
use Genesis\Api\Constants\Transaction\Parameters\Threeds\V2\CardHolderAccount\UpdateIndicators;
use Genesis\Api\Constants\Transaction\Parameters\Threeds\V2\CardHolderAccount\PasswordChangeIndicators;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Emerchantpay Indicators Helper Class
 *
 * @class   WC_Emerchantpay_Indicator_Helper
 */
class WC_Emerchantpay_Indicators_Helper {

	/**
	 * Customer instance
	 *
	 * @var WC_Customer $customer Holds customer's object.
	 */
	private $customer;

	/**
	 * Date format
	 *
	 * @var string WC date format.
	 */
	private $date_format;

	/**
	 * Indicators helper constructor
	 *
	 * @param WC_Customer $customer Customer argument.
	 * @param string      $date_format Date format argument.
	 */
	public function __construct( $customer, $date_format ) {
		$this->customer    = $customer;
		$this->date_format = $date_format;
	}

	/**
	 * Fetch the CardHolder Account Update Indicator
	 *
	 * @return string
	 */
	public function fetch_account_update_indicator() {
		// WooCommerce doesn't have Address history.
		return $this->get_indicator_value_by_class(
			UpdateIndicators::class,
			$this->get_customer_modified_date()
		);
	}

	/**
	 * Fetch the Password change indicator based on the Customer modified date
	 *
	 * @return string
	 */
	public function fetch_password_change_indicator() {
		$last_update_date = $this->get_customer_modified_date();

		if ( $last_update_date === $this->get_customer_created_date() ) {
			return PasswordChangeIndicators::NO_CHANGE;
		}

		return $this->get_indicator_value_by_class(
			PasswordChangeIndicators::class,
			$last_update_date
		);
	}

	/**
	 * Fetch the Shipping Address Usage Indicator based on the date of Shipping Address's first usage
	 *
	 * @param string $address_first_used First usage of shipping address.
	 *
	 * @return string
	 */
	public function fetch_shipping_address_usage_indicator( $address_first_used ) {
		return $this->get_indicator_value_by_class(
			ShippingAddressUsageIndicators::class,
			$address_first_used
		);
	}

	/**
	 * Fetch the registration indicator
	 *
	 * @param string $order_date Date when order created.
	 *
	 * @return string
	 */
	public function fetch_registration_indicator( $order_date ) {
		return $this->get_indicator_value_by_class(
			RegistrationIndicators::class,
			$order_date
		);
	}

	/**
	 * Get the modified date of the customer
	 *
	 * @return null|string
	 */
	public function get_customer_modified_date() {
		return $this->customer->get_date_modified() ?
			$this->customer->get_date_modified()->date( $this->date_format ) : null;
	}

	/**
	 * Get the created date of the customer
	 *
	 * @return null|string
	 */
	public function get_customer_created_date() {
		return $this->customer->get_date_created() ?
			$this->customer->get_date_created()->date( $this->date_format ) : null;
	}

	/**
	 * Build dynamically the indicator class
	 *
	 * @param string $class_indicator Class of indicator.
	 * @param string $date  Last update date.
	 *
	 * @return string
	 */
	private function get_indicator_value_by_class( $class_indicator, $date ) {
		switch ( WC_Emerchantpay_Helper::get_transaction_indicator( $date ) ) {
			case WC_Emerchantpay_Helper::LESS_THAN_30_DAYS_INDICATOR:
				return $class_indicator::LESS_THAN_30DAYS;
			case WC_Emerchantpay_Helper::MORE_30_LESS_60_DAYS_INDICATOR:
				return $class_indicator::FROM_30_TO_60_DAYS;
			case WC_Emerchantpay_Helper::MORE_THAN_60_DAYS_INDICATOR:
				return $class_indicator::MORE_THAN_60DAYS;
			default:
				if ( PasswordChangeIndicators::class === $class_indicator ) {
					return $class_indicator::DURING_TRANSACTION;
				}
				return $class_indicator::CURRENT_TRANSACTION;
		}
	}
}
