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
 * emerchantpay Helper Class
 *
 * @class WC_emerchantpay_Transaction
 *
 * @SuppressWarnings(PHPMD)
 */
class WC_emerchantpay_Transaction {

	const TYPE_CHECKOUT = 'checkout';

	public $unique_id;
	public $parent_id;
	public $date_add;
	public $type;
	public $status;
	public $message;
	public $currency;
	public $amount;
	public $terminal;

	public function __construct( $response = null, $parent_id = false, $type = '' ) {
		if ( $response ) {
			$this->importResponse( $response );
		}

		$this->parent_id = $parent_id;
	}

	/**
	 * Import a Genesis Response Object
	 *
	 * @param stdClass|WC_emerchantpay_Transaction $trx
	 */
	public function importResponse( $trx ) {
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
	}

	/**
	 * @param string $parentType
	 *
	 * @return bool
	 */
	public function shouldChangeParentStatus( $parentType ) {
		switch ( $parentType ) {
			case static::TYPE_CHECKOUT:
				return true;
			default:
				return $this->status === \Genesis\API\Constants\Transaction\States::APPROVED;
		}
	}

	/**
	 * @return string
	 */
	public function getStatusText() {
		if ( \Genesis\API\Constants\Transaction\Types::isRefund( $this->type ) ) {
			return \Genesis\API\Constants\Transaction\States::REFUNDED;
		}

		if ( \Genesis\API\Constants\Transaction\Types::VOID === $this->type ) {
			return \Genesis\API\Constants\Transaction\States::VOIDED;
		}

		return $this->status;
	}

	/**
	 * @return bool
	 */
	public function isAuthorize() {
		return \Genesis\API\Constants\Transaction\Types::isAuthorize( $this->type );
	}
}
