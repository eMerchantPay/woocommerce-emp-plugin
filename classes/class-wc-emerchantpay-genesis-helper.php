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
 * @package     classes\classes-wc-emerchantpay-genesis-helper
 */

use Genesis\Api\Constants\Transaction\States;
use Genesis\Api\Constants\Transaction\Types;
use Genesis\Exceptions\DeprecatedMethod;
use Genesis\Exceptions\InvalidArgument;
use Genesis\Exceptions\InvalidMethod;
use Genesis\Genesis;
use Genesis\Utils\Requirements;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Class class-wc-emerchantpay-genesis-helper
 */
class WC_Emerchantpay_Genesis_Helper {

	/**
	 * Builds full Request Class Name by Transaction Type
	 *
	 * @param string $transaction_type Selected transaction type.
	 * @return string
	 */
	public static function get_transaction_type_request_class_name( $transaction_type ) {
		return Types::getFinancialRequestClassForTrxType( $transaction_type );
	}

	/**
	 * Constructs a Gateway Request Instance depending on the selected Txn Type
	 *
	 * @param string $transaction_type Selected transaction type.
	 *
	 * @return Genesis
	 *
	 * @throws DeprecatedMethod Throws deprecated methods.
	 * @throws InvalidArgument Throws invalid argument of current method.
	 * @throws InvalidMethod  Throws invalid method.
	 */
	public static function get_gateway_request_by_txn_type( $transaction_type ) {
		$api_request_class_name = static::get_transaction_type_request_class_name( $transaction_type );

		return new Genesis( $api_request_class_name );
	}

	/**
	 *  Retrieves data for current payment transactions.
	 *
	 * @param stdClass $reconcile Genesis Reconcile Object.
	 *
	 * @return stdClass
	 */
	public static function get_reconcile_payment_transaction( $reconcile ) {
		return isset( $reconcile->payment_transaction )
				? $reconcile->payment_transaction
				: $reconcile;
	}

	/**
	 * Returns current gateway status
	 *
	 * @param stdClass $response Response object.
	 * @return States
	 */
	public static function get_gateway_status_instance( $response ) {
		return new States( $response->status );
	}

	/**
	 * Returns the usage of the transaction towards whether it is a subscription or not
	 *
	 * @param bool $is_recurring Determines that is subscription or not.
	 *
	 * @return string
	 */
	public static function get_payment_transaction_usage( $is_recurring ) {
		return sprintf(
			$is_recurring ? '%s Recurring Transaction' : '%s Payment Transaction',
			get_bloginfo( 'name' )
		);
	}

	/**
	 * Makes a check if all the requirements of Genesis Lib are verified
	 *
	 * @return true|WP_Error (True -> verified; WP_Error -> Exception Message)
	 */
	public static function check_genesis_requirements_verified() {
		try {
			Requirements::verify();

			return true;
		} catch ( Exception $exception ) {
			return WC_Emerchantpay_Helper::get_wp_error( $exception );
		}
	}

	/**
	 * Retrieves the consumer's user id
	 *
	 * @return int
	 */
	public static function get_current_user_id() {
		return get_current_user_id();
	}

	/**
	 * Retrieves the consumer's user id hash
	 *
	 * @param int $length Length of customer's user id hash.
	 * @return string
	 */
	public static function get_current_user_id_hash( $length = 20 ) {
		$user_id = self::get_current_user_id();

		$user_hash = $user_id > 0 ? sha1( $user_id ) : WC_Emerchantpay_Method_Base::generate_transaction_id();

		return substr( $user_hash, 0, $length );
	}

	/**
	 * Get the total Refunded sum from the Genesis WPF Reconcile Response object
	 *
	 * @param stdClass $gateway_response Getaway response object.
	 *
	 * @return float
	 */
	public static function get_total_refund_from_wpf_reconcile( $gateway_response ) {
		$sum                  = 0.0;
		$payment_transactions = self::get_reconcile_payment_transaction( $gateway_response );

		foreach ( $payment_transactions as $payment_transaction ) {
			if ( States::APPROVED === $payment_transaction->status &&
				Types::isRefund( $payment_transaction->transaction_type )
			) {
				$sum += $payment_transaction->amount;
			}
		}

		return $sum;
	}

	/**
	 * Checks that payment transaction has payment
	 *
	 * @param stdClass $reconcile Getaway response object.
	 *
	 * @return bool
	 */
	public static function has_payment( $reconcile ) {
		return isset( $reconcile->payment_transaction );
	}

	/**
	 * Handles phone number
	 *
	 * @param string $phone_number Phone number variable.
	 *
	 * @return string
	 */
	public static function handle_phone_number( $phone_number ) {

		if ( strpos( $phone_number, '+' ) === 0 || empty( $phone_number ) ) {
			return $phone_number;
		}

		return "+{$phone_number}";
	}

	/**
	 * Fetch the Gateway Response message and technical_message upon declined payment
	 *
	 * @param stdClass $response_obj Genesis Gateway Response Object
	 *
	 * @return string
	 */
	public static function fetch_gateway_response_message( $response_obj ) {
		$message           = $response_obj->message ?? '';
		$technical_message = $response_obj->technical_message ?? '';

		return trim( "$message $technical_message" );
	}
}
