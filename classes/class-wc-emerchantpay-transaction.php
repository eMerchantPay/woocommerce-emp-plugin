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
 * @package     classes\class-wc-emerchantpay-transaction
 */

use Genesis\Api\Constants\Transaction\States;
use Genesis\Api\Constants\Transaction\Types;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Emerchantpay Helper Class
 *
 * @class WC_Emerchantpay_Transaction
 *
 * @SuppressWarnings(PHPMD)
 */
class WC_Emerchantpay_Transaction {

	const TYPE_CHECKOUT = 'checkout';

	/**
	 * Unique id defined by gateway
	 *
	 * @var string
	 */
	public $unique_id;

	/**
	 * Transaction parent id
	 *
	 * @var string
	 */
	public $parent_id;

	/**
	 * Date added
	 *
	 * @var string
	 */
	public $date_add;

	/**
	 * Type of transaction
	 *
	 * @var string
	 */
	public $type;

	/**
	 * Status of transaction
	 *
	 * @var string
	 */
	public $status;

	/**
	 * Transaction message
	 *
	 * @var string
	 */
	public $message;

	/**
	 * Transaction currency
	 *
	 * @var string
	 */
	public $currency;

	/**
	 * Transaction amount
	 *
	 * @var int
	 */
	public $amount;

	/**
	 * Terminal token
	 *
	 * @var string
	 */
	public $terminal;

	/**
	 * 3DSecure Method URL
	 *
	 * @var string
	 */
	public $threeds_method_url;

	/**
	 * 3DSecure Method Continue URL
	 *
	 * @var string
	 */
	public $threeds_method_continue_url;

	/**
	 * URL where customer is sent to after successful payment
	 *
	 * @var string
	 */
	public $return_success_url;

	/**
	 * URL where customer is sent to after unsuccessful payment
	 *
	 * @var string
	 */
	public $return_failure_url;

	/**
	 * Recurring Type used for the Subscription payment
	 *
	 * @var string
	 */
	public $recurring_type;

	/**
	 * WC_Emerchantpay_Transaction constructor.
	 *
	 * @param null   $response Response object from Gateway.
	 * @param bool   $parent_id Transaction parent id.
	 * @param string $type Type of transation.
	 *
	 * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
	 */
	public function __construct( $response = null, $parent_id = false, $type = '' ) {
		if ( $response ) {
			$this->import_response( $response );
		}

		$this->parent_id = $parent_id;
	}

	/**
	 * Import a Genesis Response Object
	 *
	 * @param stdClass|WC_Emerchantpay_Transaction $trx Response object.
	 */
	public function import_response( $trx ) {
		if ( isset( $trx->unique_id ) ) {
			$this->unique_id = $trx->unique_id;
		}
		if ( isset( $trx->timestamp ) && $trx->timestamp instanceof DateTime ) {
			$this->date_add = $trx->timestamp->getTimestamp();
		} elseif ( isset( $trx->date_add ) ) {
			$this->date_add = $trx->date_add;
		} else {
			$this->date_add = time();
		}
		if ( isset( $trx->transaction_type ) ) {
			$this->type = $trx->transaction_type;
		} elseif ( isset( $trx->type ) ) {
			$this->type = $trx->type;
		} else {
			$this->type = static::TYPE_CHECKOUT;
		}
		if ( isset( $trx->status ) ) {
			$this->status = $trx->status;
		}
		if ( isset( $trx->message ) ) {
			$this->message = $trx->message;
		}
		if ( isset( $trx->currency ) ) {
			$this->currency = $trx->currency;
		}
		if ( isset( $trx->amount ) ) {
			$this->amount = $trx->amount;
		}
		if ( isset( $trx->terminal_token ) ) {
			$this->terminal = $trx->terminal_token;
		}
		if ( isset( $trx->payment_transaction->terminal_token ) ) {
			$this->terminal = $trx->payment_transaction->terminal_token;
		}
		if ( isset( $trx->threeds_method_url ) ) {
			$this->threeds_method_url = $trx->threeds_method_url;
		}
		if ( isset( $trx->threeds_method_continue_url ) ) {
			$this->threeds_method_continue_url = $trx->threeds_method_continue_url;
		}
		if ( isset( $trx->recurring_type ) ) {
			$this->recurring_type = $trx->recurring_type;
		}
	}

	/**
	 * Change parent transaction status
	 *
	 * @param string $parent_type Parent transaction type.
	 *
	 * @return bool
	 */
	public function should_change_parent_status( $parent_type ) {
		switch ( $parent_type ) {
			case static::TYPE_CHECKOUT:
				return true;
			default:
				return States::APPROVED === $this->status;
		}
	}

	/**
	 * Get status of order
	 *
	 * @return string
	 */
	public function get_status_text() {
		if ( Types::isRefund( $this->type ) ) {
			return States::REFUNDED;
		}

		if ( Types::VOID === $this->type ) {
			return States::VOIDED;
		}

		return $this->status;
	}

	/**
	 * Checks whether transaction is authorize
	 *
	 * @return bool
	 */
	public function is_authorize() {
		return Types::isAuthorize( $this->type ) ||
			Types::GOOGLE_PAY === $this->type ||
			Types::PAY_PAL === $this->type ||
			Types::APPLE_PAY === $this->type;
	}

	/**
	 * Gets return success URL
	 *
	 * @return string
	 */
	public function get_return_success_url() {
		return $this->return_success_url;
	}

	/**
	 * Sets return success URL
	 *
	 * @param string $return_success_url Return success URL.
	 */
	public function set_return_success_url( $return_success_url ) {
		$this->return_success_url = $return_success_url;
	}

	/**
	 * Sets return failure URL
	 *
	 * @param string $return_failure_url Return success URL.
	 */
	public function set_return_failure_url( $return_failure_url ) {
		$this->return_failure_url = $return_failure_url;
	}
}
