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
 * @package     classes\class-wc-emerchantpay-transactions-tree
 */

use Genesis\API\Constants\Transaction\States;
use Genesis\API\Constants\Transaction\Types;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Emerchantpay Helper Class
 *
 * @class WC_Emerchantpay_Transactions_Tree
 *
 * @SuppressWarnings(PHPMD)
 */
class WC_Emerchantpay_Transactions_Tree {

	const META_DATA_KEY_HIERARCHY = 'emp_trx_hierarchy';
	const META_DATA_KEY_LIST      = 'emp_trx_list';

	/**
	 * Transaction hierarchy
	 *
	 * @var array $trx_hierarchy child_unique_id => parent_unique_id
	 */
	public $trx_hierarchy;

	/**
	 * Transaction list
	 *
	 * @var array $trx_list
	 */
	public $trx_list;

	/**
	 * Create transaction tree from Order
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return WC_Emerchantpay_Transactions_Tree
	 */
	public static function create_from_order( WC_Order $order ) {
		return new WC_Emerchantpay_Transactions_Tree(
			array_map(
				function ( $v ) {
					return (object) $v;
				},
				static::get_transactions_list_from_order( $order )
			)
		);
	}

	/**
	 * WC_Emerchantpay_Transactions_Tree constructor.
	 *
	 * @param array $trx_list_existing Existing transactions list.
	 * @param array $trx_list_new New transactions list.
	 * @param array $trx_hierarchy Transactions hierarchy.
	 */
	public function __construct( array $trx_list_existing, array $trx_list_new = array(), array $trx_hierarchy = array() ) {
		$this->set_trx_hierarchy( $trx_list_existing, $trx_list_new, $trx_hierarchy );
		$this->set_trx_data( $trx_list_existing, $trx_list_new );
		$this->parse_trxs();
		$this->update_parent_trx_status();
	}

	/**
	 * Returns transaction list from order
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return array
	 */
	public static function get_transactions_list_from_order( WC_Order $order ) {
		return static::get_transaction_tree(
			WC_Emerchantpay_Order_Helper::get_order_meta_data(
				WC_Emerchantpay_Order_Helper::get_order_prop( $order, 'id' ),
				static::META_DATA_KEY_LIST
			)
		);
	}

	/**
	 * Get transaction from order
	 *
	 * @param WC_Order $order Order object.
	 * @param string   $unique_id Unique ID.
	 * @param array    $trx_tree Transaction tree. Default null.
	 *
	 * @return array|null
	 */
	public static function get_trx_from_order( WC_Order $order, $unique_id, $trx_tree = null ) {
		if ( null === $trx_tree ) {
			$trx_tree = static::get_transactions_list_from_order( $order );
		}
		foreach ( $trx_tree as $trx ) {
			if ( $trx['unique_id'] === $unique_id ) {
				return $trx;
			}
		}

		return null;
	}

	/**
	 * Sets transaction hierarchy
	 *
	 * @param array $trx_list_existing Existing transactions list.
	 * @param array $trx_list_new New transactions list.
	 * @param array $trx_hierarchy Transactions hierarchy.
	 */
	protected function set_trx_hierarchy( $trx_list_existing, $trx_list_new, $trx_hierarchy ) {
		$this->trx_hierarchy = $trx_hierarchy;

		// Add reference_id to checkout trx so hierarchy will work out and
		// change checkout status the same way like refunds and voids.
		if ( count( $trx_list_existing ) === 1 && array_key_exists( 0, $trx_list_existing ) &&
			WC_Emerchantpay_Transaction::TYPE_CHECKOUT === $trx_list_existing[0]->type ) {
			foreach ( $trx_list_new as $trx_new ) {
				if ( $trx_new->unique_id !== $trx_list_existing[0]->unique_id ) {
					$this->trx_hierarchy[ $trx_new->unique_id ] = $trx_list_existing[0]->unique_id;
				}
			}
		}
	}

	/**
	 * Sets transaction data
	 *
	 * @param array $trx_list_existing Existing transactions list.
	 * @param array $trx_list_new New transactions list.
	 *
	 * @return void
	 */
	protected function set_trx_data( array $trx_list_existing, array $trx_list_new ) {
		$this->trx_list = array_reduce(
			array_merge( $trx_list_existing, $trx_list_new ),
			function ( $trx_list_hash, $trx ) {
				// Set relations child => parent.
				if ( ! empty( $trx->parent_id ) ) {
					$this->trx_hierarchy[ $trx->unique_id ] = $trx->parent_id;
				} elseif ( isset( $trx->reference_id ) ) {
					$this->trx_hierarchy[ $trx->unique_id ] = $trx->reference_id;
				}

				$trx_list_hash[ $trx->unique_id ] = $trx;

				return $trx_list_hash;
			},
			array()
		);
	}

	/**
	 * Change genesis response objects to internal WC_Emerchantpay_Transaction
	 */
	protected function parse_trxs() {
		foreach ( $this->trx_list as &$raw_trx ) {
			if ( $raw_trx instanceof stdClass ) {
				$raw_trx = new WC_Emerchantpay_Transaction(
					$raw_trx,
					$this->find_parent_id( $raw_trx )
				);
			}
		}
	}

	/**
	 * Returns parent id from transactions hierarchy
	 *
	 * @param stdClass|WC_Emerchantpay_Transaction $trx Transaction object.
	 *
	 * @return bool|string
	 */
	protected function find_parent_id( $trx ) {
		if ( ! isset( $this->trx_hierarchy[ $trx->unique_id ] ) ) {
			return false;
		}

		return $this->trx_hierarchy[ $trx->unique_id ];
	}

	/**
	 * Updates status of parent transactions
	 */
	protected function update_parent_trx_status() {
		foreach ( $this->trx_list as $trx ) {
			if ( ! empty( $trx->parent_id ) && isset( $this->trx_list[ $trx->parent_id ] ) ) {
				if ( $trx->should_change_parent_status( $this->trx_list[ $trx->parent_id ]->type ) ) {
					$this->trx_list[ $trx->parent_id ]->status = $trx->get_status_text();
				}
			}
		}
	}

	/**
	 * Returns an array with tree-structure where
	 * every branch is a transaction related to
	 * the order
	 *
	 * @param array $transactions Transactions array.
	 * @param array $selected_transaction_types Selected transaction types.
	 *
	 * @return array
	 */
	public static function get_transaction_tree( $transactions, $selected_transaction_types = array() ) {
		if ( empty( $transactions ) || ! is_array( $transactions ) ) {
			return array();
		}

		$trx_arr = array();
		foreach ( $transactions as $trx ) {
			$trx_arr[] = (array) $trx;
		}

		// Sort the transactions list in the following order:
		//
		// 1. Sort by timestamp (date), i.e. most-recent transactions on top
		// 2. Sort by relations, i.e. every parent has the child nodes immediately after
		// Ascending Date/Timestamp sorting.
		uasort(
			$trx_arr,
			function ( $a, $b ) {
				return ( $a['date_added'] ?? null ) <=> ( $b['date_added'] ?? null );
			}
		);

		// Process individual fields.
		foreach ( $trx_arr as &$transaction ) {
			if ( is_numeric( $transaction['date_add'] ) ) {
				$transaction['date_add'] = gmdate( "H:i:s \n m/d/Y", $transaction['date_add'] );
			}

			$transaction['can_capture'] = static::can_capture( $transaction, $selected_transaction_types );

			if ( $transaction['can_capture'] ) {
				$total_authorized_amount         = self::get_children_authorized_amount( $trx_arr, $transaction );
				$total_captured_amount           = self::get_children_captured_amount( $trx_arr, $transaction );
				$transaction['available_amount'] = $total_authorized_amount - $total_captured_amount;

				if ( 0 === $transaction['available_amount'] ) {
					$transaction['can_capture'] = false;
				}
			}

			$transaction['can_refund'] = static::can_refund( $transaction, $selected_transaction_types );

			if ( $transaction['can_refund'] ) {
				$total_captured_amount           = $transaction['amount'];
				$total_refunded_amount           = self::get_children_refund_amount( $trx_arr, $transaction );
				$transaction['available_amount'] = $total_captured_amount - $total_refunded_amount;

				if ( 0 === $transaction['available_amount'] ) {
					$transaction['can_refund'] = false;
				}
			}

			$transaction['can_void'] = static::can_void( $transactions, $transaction );

			$transaction['amount'] = self::format_transaction_value( $transaction['amount'] );

			if ( ! isset( $transaction['available_amount'] ) ) {
				$transaction['available_amount'] = $transaction['amount'];
			}

			$transaction['available_amount'] = self::format_transaction_value( $transaction['available_amount'] );
		}

		// Create the parent/child relations from a flat array.
		$array_asc = array();

		foreach ( $trx_arr as $key => $val ) {
			// create an array with ids as keys and children
			// with the assumption that parents are created earlier.
			// store the original key.
			$array_asc[ $val['unique_id'] ] = array_merge( $val, array( 'org_key' => $key ) );

			if ( isset( $val['parent_id'] ) && (bool) $val['parent_id'] ) {
				$array_asc[ $val['parent_id'] ]['children'][] = $val['unique_id'];
			}
		}

		// Order the parent/child entries.
		$trx_arr = array();

		foreach ( $array_asc as $val ) {
			self::tree_transaction_sort( $trx_arr, $val, $array_asc );
		}

		return $trx_arr;
	}

	/**
	 * Gets authorized children amount
	 *
	 * @param array $trx_arr List of transactions.
	 * @param array $transaction Transaction array.
	 *
	 * @return float
	 */
	private static function get_children_authorized_amount( array $trx_arr, array $transaction ) {
		return self::get_transactions_sum_amount(
			$trx_arr,
			$transaction['parent_id'],
			array(
				Types::AUTHORIZE,
				Types::AUTHORIZE_3D,
				Types::KLARNA_AUTHORIZE,
				Types::GOOGLE_PAY,
				Types::PAY_PAL,
				Types::APPLE_PAY,
			),
			States::APPROVED
		);
	}

	/**
	 * Returns sum of children captured amount
	 *
	 * @param array $trx_arr Transactions array.
	 * @param array $transaction Transaction data array.
	 *
	 * @return float
	 */
	private static function get_children_captured_amount( array $trx_arr, array $transaction ) {
		$transactions_to_look_for = array(
			array(
				'type'   => Types::CAPTURE,
				'status' => States::APPROVED,
			),
			array(
				'type'   => Types::CAPTURE,
				'status' => States::REFUNDED,
			),
			array(
				'type'   => Types::KLARNA_CAPTURE,
				'status' => States::APPROVED,
			),
			array(
				'type'   => Types::KLARNA_CAPTURE,
				'status' => States::REFUNDED,
			),
		);

		$sum = 0.0;
		foreach ( $transactions_to_look_for as $trx ) {
			$sum += self::get_transactions_sum_amount(
				$trx_arr,
				$transaction['unique_id'],
				$trx['type'],
				$trx['status']
			);
		}

		return $sum;
	}

	/**
	 * Returns children refund amount
	 *
	 * @param array $trx_arr Transactions array.
	 * @param array $transaction Transaction data array.
	 *
	 * @return float
	 */
	private static function get_children_refund_amount( array $trx_arr, array $transaction ) {
		return self::get_transactions_sum_amount(
			$trx_arr,
			$transaction['unique_id'],
			array(
				Types::REFUND,
				Types::KLARNA_REFUND,
				Types::BITPAY_REFUND,
			),
			States::APPROVED
		);
	}

	/**
	 * Check if the specific transaction types by custom attribute exists
	 *
	 * @param string $transaction_type Transaction type.
	 *
	 * @return bool
	 */
	private static function is_transaction_has_custom_attr( $transaction_type ) {
		$transaction_types = array(
			Types::GOOGLE_PAY,
			Types::PAY_PAL,
			Types::APPLE_PAY,
		);

		return in_array( $transaction_type, $transaction_types, true );
	}

	/**
	 * Check specific transaction based on the selected custom attribute
	 *
	 * @param string $action Capture or refund action.
	 * @param string $transaction_type Transaction type.
	 * @param array  $selected_types Array of selected types.
	 *
	 * @return boolean
	 */
	private static function check_transaction_by_selected_attribute( $action, $transaction_type, $selected_types ) {
		switch ( $transaction_type ) {
			case Types::GOOGLE_PAY:
				if ( WC_Emerchantpay_Method_Base::METHOD_ACTION_CAPTURE === $action ) {
					return in_array(
						WC_Emerchantpay_Method_Base::GOOGLE_PAY_TRANSACTION_PREFIX . WC_Emerchantpay_Method_Base::GOOGLE_PAY_PAYMENT_TYPE_AUTHORIZE,
						$selected_types,
						true
					);
				}

				if ( WC_Emerchantpay_Method_Base::METHOD_ACTION_REFUND === $action ) {
					return in_array(
						WC_Emerchantpay_Method_Base::GOOGLE_PAY_TRANSACTION_PREFIX . WC_Emerchantpay_Method_Base::GOOGLE_PAY_PAYMENT_TYPE_SALE,
						$selected_types,
						true
					);
				}
				break;
			case Types::PAY_PAL:
				if ( WC_Emerchantpay_Method_Base::METHOD_ACTION_CAPTURE === $action ) {
					return in_array(
						WC_Emerchantpay_Method_Base::PAYPAL_TRANSACTION_PREFIX .
						WC_Emerchantpay_Method_Base::PAYPAL_PAYMENT_TYPE_AUTHORIZE,
						$selected_types,
						true
					);
				}

				if ( WC_Emerchantpay_Method_Base::METHOD_ACTION_REFUND === $action ) {
					$refundable_types = array(
						WC_Emerchantpay_Method_Base::PAYPAL_TRANSACTION_PREFIX .
						WC_Emerchantpay_Method_Base::PAYPAL_PAYMENT_TYPE_SALE,
						WC_Emerchantpay_Method_Base::PAYPAL_TRANSACTION_PREFIX .
						WC_Emerchantpay_Method_Base::PAYPAL_PAYMENT_TYPE_EXPRESS,
					);

					return ( count( array_intersect( $refundable_types, $selected_types ) ) > 0 );
				}
				break;
			case Types::APPLE_PAY:
				if ( WC_Emerchantpay_Method_Base::METHOD_ACTION_CAPTURE === $action ) {
					return in_array(
						WC_Emerchantpay_Method_Base::APPLE_PAY_TRANSACTION_PREFIX .
						WC_Emerchantpay_Method_Base::APPLE_PAY_PAYMENT_TYPE_AUTHORIZE,
						$selected_types,
						true
					);
				}

				if ( WC_Emerchantpay_Method_Base::METHOD_ACTION_REFUND === $action ) {
					return in_array(
						WC_Emerchantpay_Method_Base::APPLE_PAY_TRANSACTION_PREFIX .
						WC_Emerchantpay_Method_Base::APPLE_PAY_PAYMENT_TYPE_SALE,
						$selected_types,
						true
					);
				}
				break;
			default:
				return false;
		}
	}

	/**
	 * Estimates whether it can do Capture transaction
	 *
	 * @param array $transaction Transaction data array.
	 * @param array $selected_transaction_types The selected transaction type.
	 *
	 * @return bool
	 */
	public static function can_capture( $transaction, $selected_transaction_types = array() ) {
		if ( empty( $transaction['status'] ) ) {
			return false;
		}

		$state = new States( $transaction['status'] );

		if ( ! $state->isApproved() ) {
			return false;
		}

		if ( self::is_transaction_has_custom_attr( $transaction['type'] ) && count( $selected_transaction_types ) > 0 ) {
			return self::check_transaction_by_selected_attribute(
				WC_Emerchantpay_Method_Base::METHOD_ACTION_CAPTURE,
				$transaction['type'],
				$selected_transaction_types
			);
		}

		return Types::canCapture( $transaction['type'] );
	}

	/**
	 * Estimates whether it can do Refund transaction
	 *
	 * @param array $transaction Transaction data array.
	 * @param array $selected_transaction_types Selected transaction types.
	 *
	 * @return bool
	 */
	public static function can_refund( $transaction, $selected_transaction_types = array() ) {
		if ( empty( $transaction['status'] ) ) {
			return false;
		}

		$state = new States( $transaction['status'] );

		if ( ! ( $state->isApproved() || $state->isRefunded() ) ) {
			return false;
		}

		if ( self::is_transaction_has_custom_attr( $transaction['type'] ) && count( $selected_transaction_types ) > 0 ) {
			return self::check_transaction_by_selected_attribute(
				WC_Emerchantpay_Method_Base::METHOD_ACTION_REFUND,
				$transaction['type'],
				$selected_transaction_types
			);
		}

		return Types::canRefund( $transaction['type'] );
	}

	/**
	 * Estimates whether it can do Void transaction
	 *
	 * @param array $transactions Transactions array.
	 * @param array $transaction Transaction data array.
	 *
	 * @return bool
	 */
	public static function can_void( $transactions, $transaction ) {
		if ( States::APPROVED !== $transaction['status'] ||
			! Types::canVoid( $transaction['type'] ) ) {
			return false;
		}

		foreach ( $transactions as $trx ) {
			if ( ! is_array( $trx ) ) {
				$trx = (array) $trx;
			}

			if ( self::is_void_already_processed( $transaction, $trx ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Estimates whether void transaction is already processed
	 *
	 * @param array $parent_trx Parent transaction array.
	 * @param array $child Child transaction array.
	 *
	 * @return bool
	 */
	private static function is_void_already_processed( array $parent_trx, array $child ) {
		return $child['parent_id'] === $parent_trx['unique_id'] &&
			( Types::VOID === $child['type'] ||
				Types::CAPTURE === $child['type'] ||
				Types::KLARNA_CAPTURE === $child['type'] );
	}

	/**
	 * Returns authorize transaction from transaction list
	 *
	 * @return WC_Emerchantpay_Transaction
	 */
	public function get_authorize_trx() {
		foreach ( $this->trx_list as $trx ) {
			if ( $trx->is_authorize() ) {
				return $trx;
			}
		}
	}

	/**
	 * Returns transaction type that can refund from transaction list
	 *
	 * @return WC_Emerchantpay_Transaction
	 */
	public function get_settlement_trx() {
		foreach ( $this->trx_list as $trx ) {
			if ( Types::canRefund( $trx->type ) ) {
				return $trx;
			}
		}
	}

	/**
	 * Recursive function used in the process of sorting
	 * the Transactions list
	 *
	 * @param array $array_out Transactions list.
	 * @param array $val Array values.
	 * @param array $array_asc Array with parent/child relations.
	 */
	public static function tree_transaction_sort( &$array_out, $val, $array_asc ) {
		if ( isset( $val['org_key'] ) ) {
			$array_out[ $val['org_key'] ] = $val;

			if ( isset( $val['children'] ) && count( $val['children'] ) ) {
				foreach ( $val['children'] as $id ) {
					self::tree_transaction_sort( $array_out, $array_asc[ $id ], $array_asc );
				}
			}
			unset( $array_out[ $val['org_key'] ]['children'], $array_out[ $val['org_key'] ]['org_key'] );
		}
	}

	/**
	 * Get the sum of the amount for a list of transaction types and status
	 *
	 * @param array        $transactions Transactions list.
	 * @param string       $parent_id Transaction parent id.
	 * @param array|string $types Transaction type.
	 * @param string       $status Transaction status.
	 *
	 * @return float
	 */
	private static function get_transactions_sum_amount( $transactions, $parent_id, $types, $status ) {
		$total_amount = 0.0;

		/**
		 * Transaction list
		 *
		 * @var $transaction Transaction data.
		 */
		foreach ( $transactions as $transaction ) {
			if ( ! empty( $parent_id ) && $parent_id !== $transaction['parent_id'] ) {
				continue;
			}

			if ( is_array( $types ) ? ! in_array( $transaction['type'], $types, true ) : $types !== $transaction['type'] ) {
				continue;
			}

			if ( $transaction['status'] !== $status ) {
				continue;
			}

			$total_amount += $transaction['amount'];
		}

		return $total_amount;
	}

	/**
	 * Get a formatted transaction value for the Admin Transactions Panel
	 *
	 * @param float $amount Transaction amount.
	 *
	 * @return string
	 */
	private static function format_transaction_value( $amount ) {
		/**
		DecimalSeparator   -> .
		Thousand Separator -> empty
		Otherwise an exception could be thrown from genesis.
		 */
		return number_format( $amount, 2, '.', '' );
	}

	/**
	 * Return sum of total captured amount.
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return float
	 */
	public static function get_total_captured_amount( WC_Order $order ) {
		$trx_list = static::get_transactions_list_from_order( $order );

		return static::get_total_amount(
			$trx_list,
			Types::CAPTURE
		) + static::get_total_amount(
			$trx_list,
			Types::CAPTURE,
			States::REFUNDED
		);
	}

	/**
	 * Returns sum of total refunded amount
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return float
	 */
	public static function get_total_refunded_amount( WC_Order $order ) {
		return static::get_total_amount(
			static::get_transactions_list_from_order( $order ),
			Types::REFUND
		);
	}

	/**
	 * Returns total amount
	 *
	 * @param array        $trx_tree Transaction tree.
	 * @param array|string $types Transaction type.
	 * @param string       $status Transaction status.
	 *
	 * @return float
	 */
	public static function get_total_amount( array $trx_tree, $types, $status = States::APPROVED ) {
		$total_amount = 0.0;

		foreach ( $trx_tree as $trx ) {
			$total_amount += self::get_transactions_sum_amount(
				$trx_tree,
				$trx['unique_id'],
				$types,
				$status
			);
		}

		return $total_amount;
	}

	/**
	 * Get the sum of amount of all transaction by specific criteria
	 *
	 * Unique Id parameter is used for excluding an transaction from the sum.
	 *      This is used mainly for Refund transactions when we can have refund from Genesis and from the Store.
	 *      If there is Refund in the Store and there is already the received notification unique_id we
	 *      have to exclude that specific transaction.
	 *
	 * @param string $unique_id Unique ID.
	 * @param array  $trx_tree Transaction tree.
	 * @param string $types Transaction type.
	 * @param string $status Transaction status.
	 *
	 * @return float
	 */
	public static function get_total_amount_without_unique_id( $unique_id, array $trx_tree, $types, $status = States::APPROVED ) {
		$total_amount = 0.0;

		/**
		 * Transactions tree
		 *
		 * @var array $transaction Single transaction data.
		 */
		foreach ( $trx_tree as $transaction ) {
			if ( ! empty( $parent_id ) && $parent_id !== $transaction['parent_id'] ) {
				continue;
			}
			if ( is_array( $types ) ? ! in_array( $transaction['type'], $types, true ) : $types !== $transaction['type'] ) {
				continue;
			}
			if ( $transaction['status'] !== $status ) {
				continue;
			}
			if ( $unique_id === $transaction['unique_id'] ) {
				continue;
			}

			$total_amount += $transaction['amount'];
		}

		return $total_amount;
	}
}
