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
 * @class WC_emerchantpay_Transactions_Tree
 *
 * @SuppressWarnings(PHPMD)
 */
class WC_emerchantpay_Transactions_Tree {

	const META_DATA_KEY_HIERARCHY = 'emp_trx_hierarchy';
	const META_DATA_KEY_LIST      = 'emp_trx_list';

	/**
	 * @var array $trx_hierarchy child_unique_id => parent_unique_id
	 */
	public $trx_hierarchy;
	public $trx_list;

	/**
	 * @param WC_Order $order
	 *
	 * @return WC_emerchantpay_Transactions_Tree
	 */
	public static function createFromOrder( WC_Order $order ) {
		return new WC_emerchantpay_Transactions_Tree(
			array_map(
				function ( $v ) {
					return (object) $v;
				},
				static::getTransactionsListFromOrder( $order )
			)
		);
	}

	/**
	 * WC_emerchantpay_Transactions_Tree constructor.
	 *
	 * @param array $trx_list_existing
	 * @param array $trx_list_new
	 * @param array $trx_hierarchy
	 */
	public function __construct( array $trx_list_existing, array $trx_list_new = array(), array $trx_hierarchy = array() ) {
		$this->setTrxHierarchy( $trx_list_existing, $trx_list_new, $trx_hierarchy );
		$this->setTrxData( $trx_list_existing, $trx_list_new );
		$this->parseTrxs();
		$this->updateParentTrxStatus();
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return array
	 */
	public static function getTransactionsListFromOrder( WC_Order $order ) {
		return static::get_transaction_tree(
			WC_emerchantpay_Order_Helper::getOrderMetaData(
				WC_emerchantpay_Order_Helper::getOrderProp( $order, 'id' ),
				static::META_DATA_KEY_LIST
			)
		);
	}

	/**
	 * @param WC_Order $order
	 * @param string   $unique_id
	 * @param array    $trx_tree
	 *
	 * @return array|null
	 */
	public static function getTrxFromOrder( WC_Order $order, $unique_id, $trx_tree = null ) {
		if ( $trx_tree === null ) {
			$trx_tree = static::getTransactionsListFromOrder( $order );
		}
		foreach ( $trx_tree as $trx ) {
			if ( $trx['unique_id'] === $unique_id ) {
				return $trx;
			}
		}

		return null;
	}

	/**
	 * @param array $trx_list_existing
	 * @param array $trx_list_new
	 * @param array $trx_hierarchy
	 */
	protected function setTrxHierarchy( $trx_list_existing, $trx_list_new, $trx_hierarchy ) {
		$this->trx_hierarchy = $trx_hierarchy;

		// Add reference_id to checkout trx so hierarchy will work out and
		// change checkout status the same way like refunds and voids
		if ( count( $trx_list_existing ) === 1 && array_key_exists( 0, $trx_list_existing ) &&
			$trx_list_existing[0]->type === WC_emerchantpay_Transaction::TYPE_CHECKOUT ) {
			foreach ( $trx_list_new as $trx_new ) {
				if ( $trx_new->unique_id !== $trx_list_existing[0]->unique_id ) {
					$this->trx_hierarchy[ $trx_new->unique_id ] = $trx_list_existing[0]->unique_id;
				}
			}
		}
	}

	/**
	 * @param array $trx_list_existing
	 * @param array $trx_list_new
	 * @return array
	 */
	protected function setTrxData( array $trx_list_existing, array $trx_list_new ) {
		$this->trx_list = array_reduce(
			array_merge( $trx_list_existing, $trx_list_new ),
			function ( $trx_list_hash, $trx ) {
				// Set relations child => parent
				if ( ! empty( $trx->parent_id ) ) {
					$this->trx_hierarchy[ $trx->unique_id ] = $trx->parent_id;
				} elseif ( isset( $trx->reference_id ) ) {
					$this->trx_hierarchy[ $trx->unique_id ] = $trx->reference_id;
				}

				$trx_list_hash[ $trx->unique_id ] = $trx;

				return $trx_list_hash;
			},
			[]
		);
	}

	/**
	 * Change genesis response objects to internal WC_emerchantpay_Transaction
	 */
	protected function parseTrxs() {
		foreach ( $this->trx_list as &$raw_trx ) {
			if ( $raw_trx instanceof stdClass ) {
				$raw_trx = new WC_emerchantpay_Transaction(
					$raw_trx,
					$this->findParentId( $raw_trx )
				);
			}
		}
	}

	/**
	 * @param stdClass|WC_emerchantpay_Transaction $trx
	 *
	 * @return bool|string
	 */
	protected function findParentId( $trx ) {
		if ( ! isset( $this->trx_hierarchy[ $trx->unique_id ] ) ) {
			return false;
		}

		return $this->trx_hierarchy[ $trx->unique_id ];
	}

	/**
	 * Updates status of parent transactions
	 */
	protected function updateParentTrxStatus() {
		foreach ( $this->trx_list as $trx ) {
			if ( ! empty( $trx->parent_id ) && isset( $this->trx_list[ $trx->parent_id ] ) ) {
				if ( $trx->shouldChangeParentStatus( $this->trx_list[ $trx->parent_id ]->type ) ) {
					$this->trx_list[ $trx->parent_id ]->status = $trx->getStatusText();
				}
			}
		}
	}

	/**
	 * Returns an array with tree-structure where
	 * every branch is a transaction related to
	 * the order
	 *
	 * @param $transactions array WC_emerchantpay_Transaction
	 * @param $selected_transaction_types array
	 *
	 * @return array
	 */
	public static function get_transaction_tree( $transactions, $selected_transaction_types = array() ) {
		if ( empty( $transactions ) || ! is_array( $transactions ) ) {
			return [];
		}

		$trx_arr = [];
		foreach ( $transactions as $trx ) {
			$trx_arr[] = (array) $trx;
		}

		// Sort the transactions list in the following order:
		//
		// 1. Sort by timestamp (date), i.e. most-recent transactions on top
		// 2. Sort by relations, i.e. every parent has the child nodes immediately after
		// Ascending Date/Timestamp sorting
		uasort(
			$trx_arr,
			function ( $a, $b ) {
				// sort by timestamp (date) first
				if ( @$a['date_add'] == @$b['date_add'] ) {
					return 0;
				}

				return ( @$a['date_add'] > @$b['date_add'] ) ? 1 : -1;
			}
		);

		// Process individual fields
		foreach ( $trx_arr as &$transaction ) {
			if ( is_numeric( $transaction['date_add'] ) ) {
				$transaction['date_add'] = date( "H:i:s \n m/d/Y", $transaction['date_add'] );
			}

			$transaction['can_capture'] = static::canCapture( $transaction, $selected_transaction_types );

			if ( $transaction['can_capture'] ) {
				$totalAuthorizedAmount           = self::getChildrenAuthorizedAmount( $trx_arr, $transaction );
				$totalCapturedAmount             = self::getChildrenCapturedAmount( $trx_arr, $transaction );
				$transaction['available_amount'] = $totalAuthorizedAmount - $totalCapturedAmount;

				if ( $transaction['available_amount'] == 0 ) {
					$transaction['can_capture'] = false;
				}
			}

			$transaction['can_refund'] = static::canRefund( $transaction, $selected_transaction_types );

			if ( $transaction['can_refund'] ) {
				$totalCapturedAmount             = $transaction['amount'];
				$totalRefundedAmount             = self::getChildrenRefundAmount( $trx_arr, $transaction );
				$transaction['available_amount'] = $totalCapturedAmount - $totalRefundedAmount;

				if ( $transaction['available_amount'] == 0 ) {
					$transaction['can_refund'] = false;
				}
			}

			$transaction['can_void'] = static::canVoid( $transactions, $transaction );

			$transaction['amount'] = self::formatTransactionValue( $transaction['amount'] );

			if ( ! isset( $transaction['available_amount'] ) ) {
				$transaction['available_amount'] = $transaction['amount'];
			}

			$transaction['available_amount'] = self::formatTransactionValue( $transaction['available_amount'] );
		}

		// Create the parent/child relations from a flat array
		$array_asc = array();

		foreach ( $trx_arr as $key => $val ) {
			// create an array with ids as keys and children
			// with the assumption that parents are created earlier.
			// store the original key
			$array_asc[ $val['unique_id'] ] = array_merge( $val, array( 'org_key' => $key ) );

			if ( isset( $val['parent_id'] ) && (bool) $val['parent_id'] ) {
				$array_asc[ $val['parent_id'] ]['children'][] = $val['unique_id'];
			}
		}

		// Order the parent/child entries
		$trx_arr = array();

		foreach ( $array_asc as $val ) {
			self::treeTransactionSort( $trx_arr, $val, $array_asc );
		}

		return $trx_arr;
	}

	/**
	 * @param array $trx_arr
	 * @param array $transaction
	 *
	 * @return float
	 */
	private static function getChildrenAuthorizedAmount( array $trx_arr, array $transaction ) {
		return self::getTransactionsSumAmount(
			$trx_arr,
			$transaction['parent_id'],
			array(
				\Genesis\API\Constants\Transaction\Types::AUTHORIZE,
				\Genesis\API\Constants\Transaction\Types::AUTHORIZE_3D,
				\Genesis\API\Constants\Transaction\Types::KLARNA_AUTHORIZE,
				\Genesis\API\Constants\Transaction\Types::GOOGLE_PAY,
			),
			\Genesis\API\Constants\Transaction\States::APPROVED
		);
	}

	/**
	 * @param array $trx_arr
	 * @param array $transaction
	 *
	 * @return float
	 */
	private static function getChildrenCapturedAmount( array $trx_arr, array $transaction ) {
		$transactions_to_look_for = array(
			array(
				'type'   => \Genesis\API\Constants\Transaction\Types::CAPTURE,
				'status' => \Genesis\API\Constants\Transaction\States::APPROVED,
			),
			array(
				'type'   => \Genesis\API\Constants\Transaction\Types::CAPTURE,
				'status' => \Genesis\API\Constants\Transaction\States::REFUNDED,
			),
			array(
				'type'   => \Genesis\API\Constants\Transaction\Types::KLARNA_CAPTURE,
				'status' => \Genesis\API\Constants\Transaction\States::APPROVED,
			),
			array(
				'type'   => \Genesis\API\Constants\Transaction\Types::KLARNA_CAPTURE,
				'status' => \Genesis\API\Constants\Transaction\States::REFUNDED,
			),
		);

		$sum = 0.0;
		foreach ( $transactions_to_look_for as $trx ) {
			$sum += self::getTransactionsSumAmount(
				$trx_arr,
				$transaction['unique_id'],
				$trx['type'],
				$trx['status']
			);
		}

		return $sum;
	}

	/**
	 * @param array $trx_arr
	 * @param array $transaction
	 *
	 * @return float
	 */
	private static function getChildrenRefundAmount( array $trx_arr, array $transaction ) {
		return self::getTransactionsSumAmount(
			$trx_arr,
			$transaction['unique_id'],
			array(
				\Genesis\API\Constants\Transaction\Types::REFUND,
				\Genesis\API\Constants\Transaction\Types::KLARNA_REFUND,
				\Genesis\API\Constants\Transaction\Types::BITPAY_REFUND,
			),
			\Genesis\API\Constants\Transaction\States::APPROVED
		);
	}

	/**
	 * Check if the specific transaction types by custom attribute exists
	 *
	 * @param string $transaction_type
	 */
	private static function is_transaction_has_custom_attr( $transaction_type ) {
		$transaction_types = array(
			\Genesis\API\Constants\Transaction\Types::GOOGLE_PAY
		);

		return in_array( $transaction_type, $transaction_types, true );
	}

	/**
	 * Check specific transaction based on the selected custom attribute
	 *
	 * @param string $action
	 * @param string $transaction_type
	 * @param array  $selected_types
	 *
	 * @return boolean
	 */
	private static function check_transaction_by_selected_attribute( $action, $transaction_type, $selected_types ) {
		switch ( $transaction_type ) {
			case \Genesis\API\Constants\Transaction\Types::GOOGLE_PAY:
				if ( WC_emerchantpay_Method::METHOD_ACTION_CAPTURE === $action ) {
					return in_array(
						WC_emerchantpay_Method::GOOGLE_PAY_TRANSACTION_PREFIX . WC_emerchantpay_Method::GOOGLE_PAY_PAYMENT_TYPE_AUTHORIZE,
						$selected_types,
						true
					);
				}

				if ( WC_emerchantpay_Method::METHOD_ACTION_REFUND === $action ) {
					return in_array(
						WC_emerchantpay_Method::GOOGLE_PAY_TRANSACTION_PREFIX . WC_emerchantpay_Method::GOOGLE_PAY_PAYMENT_TYPE_SALE,
						$selected_types,
						true
					);
				}
			default:
				return false;
		}
	}

	/**
	 * @param array $transaction
	 * @param array $selected_transaction_types
	 *
	 * @return bool
	 */
	public static function canCapture( $transaction, $selected_transaction_types = array() ) {
		if ( empty( $transaction['status'] ) ) {
			return false;
		}

		$state = new \Genesis\API\Constants\Transaction\States( $transaction['status'] );

		if ( ! $state->isApproved() ) {
			return false;
		}

		if ( self::is_transaction_has_custom_attr( $transaction['type'] ) && count( $selected_transaction_types ) > 0 ) {
			return self::check_transaction_by_selected_attribute(
				WC_emerchantpay_Method::METHOD_ACTION_CAPTURE,
				$transaction['type'],
				$selected_transaction_types
			);
		}

		return \Genesis\API\Constants\Transaction\Types::canCapture( $transaction['type'] );
	}

	/**
	 * @param array $transaction
	 * @param array $selected_transaction_types
	 *
	 * @return bool
	 */
	public static function canRefund( $transaction, $selected_transaction_types = array() ) {
		if ( empty( $transaction['status'] ) ) {
			return false;
		}

		$state = new \Genesis\API\Constants\Transaction\States( $transaction['status'] );

		if ( ! ( $state->isApproved() || $state->isRefunded() ) ) {
			return false;
		}

		if ( self::is_transaction_has_custom_attr( $transaction['type'] ) && count( $selected_transaction_types ) > 0 ) {
			return self::check_transaction_by_selected_attribute(
				WC_emerchantpay_Method::METHOD_ACTION_REFUND,
				$transaction['type'],
				$selected_transaction_types
			);
		}

		return \Genesis\API\Constants\Transaction\Types::canRefund( $transaction['type'] );
	}

	/**
	 * @param array $transactions
	 * @param array $transaction
	 *
	 * @return bool
	 */
	public static function canVoid( $transactions, $transaction ) {
		if ( $transaction['status'] !== \Genesis\API\Constants\Transaction\States::APPROVED ||
			! \Genesis\API\Constants\Transaction\Types::canVoid( $transaction['type'] ) ) {
			return false;
		}

		foreach ( $transactions as $trx ) {
			if ( ! is_array( $trx ) ) {
				$trx = (array) $trx;
			}

			if ( self::isVoidAlreadyProcessed( $transaction, $trx ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param array $parent
	 * @param array $child
	 *
	 * @return bool
	 */
	private static function isVoidAlreadyProcessed( array $parent, array $child ) {
		return $child['parent_id'] === $parent['unique_id'] &&
			   ( $child['type'] === \Genesis\API\Constants\Transaction\Types::VOID ||
				$child['type'] === \Genesis\API\Constants\Transaction\Types::CAPTURE ||
				$child['type'] === \Genesis\API\Constants\Transaction\Types::KLARNA_CAPTURE );
	}

	/**
	 * @return WC_emerchantpay_Transaction
	 */
	public function getAuthorizeTrx() {
		foreach ( $this->trx_list as $trx ) {
			if ( $trx->isAuthorize() ) {
				return $trx;
			}
		}
	}

	/**
	 * @return WC_emerchantpay_Transaction
	 */
	public function getSettlementTrx() {
		foreach ( $this->trx_list as $trx ) {
			if ( \Genesis\API\Constants\Transaction\Types::canRefund( $trx->type ) ) {
				return $trx;
			}
		}
	}

	/**
	 * Recursive function used in the process of sorting
	 * the Transactions list
	 *
	 * @param $array_out array
	 * @param $val array
	 * @param $array_asc array
	 */
	public static function treeTransactionSort( &$array_out, $val, $array_asc ) {
		if ( isset( $val['org_key'] ) ) {
			$array_out[ $val['org_key'] ] = $val;

			if ( isset( $val['children'] ) && sizeof( $val['children'] ) ) {
				foreach ( $val['children'] as $id ) {
					self::treeTransactionSort( $array_out, $array_asc[ $id ], $array_asc );
				}
			}
			unset( $array_out[ $val['org_key'] ]['children'], $array_out[ $val['org_key'] ]['org_key'] );
		}
	}

	/**
	 * Get the sum of the amount for a list of transaction types and status
	 *
	 * @param array        $transactions
	 * @param string       $parent_id
	 * @param array|string $types
	 * @param string       $status
	 *
	 * @return float
	 */
	private static function getTransactionsSumAmount( $transactions, $parent_id, $types, $status ) {
		$totalAmount = 0.0;

		/** @var $transaction */
		foreach ( $transactions as $transaction ) {
			if ( ! empty( $parent_id ) && $parent_id !== $transaction['parent_id'] ) {
				continue;
			}
			if ( is_array( $types ) ? ! in_array( $transaction['type'], $types ) : $types !== $transaction['type'] ) {
				continue;
			}
			if ( $transaction['status'] !== $status ) {
				continue;
			}

			$totalAmount += $transaction['amount'];
		}

		return $totalAmount;
	}

	/**
	 * Get a formatted transaction value for the Admin Transactions Panel
	 *
	 * @param float $amount
	 *
	 * @return string
	 */
	private static function formatTransactionValue( $amount ) {
		/*
		 DecimalSeparator   -> .
		   Thousand Separator -> empty

		   Otherwise an exception could be thrown from genesis
		*/
		return number_format( $amount, 2, '.', '' );
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return float
	 */
	public static function getTotalCapturedAmount( WC_Order $order ) {
		$trx_list = static::getTransactionsListFromOrder( $order );

		return static::getTotalAmount(
			$trx_list,
			\Genesis\API\Constants\Transaction\Types::CAPTURE
		) + static::getTotalAmount(
			$trx_list,
			\Genesis\API\Constants\Transaction\Types::CAPTURE,
			\Genesis\API\Constants\Transaction\States::REFUNDED
		);
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return float
	 */
	public static function getTotalRefundedAmount( WC_Order $order ) {
		return static::getTotalAmount(
			static::getTransactionsListFromOrder( $order ),
			\Genesis\API\Constants\Transaction\Types::REFUND
		);
	}

	/**
	 * @param array        $trx_tree
	 * @param array|string $types
	 * @param string       $status
	 *
	 * @return float
	 */
	public static function getTotalAmount( array $trx_tree, $types, $status = \Genesis\API\Constants\Transaction\States::APPROVED ) {
		$totalAmount = 0.0;

		foreach ( $trx_tree as $trx ) {
			$totalAmount += self::getTransactionsSumAmount(
				$trx_tree,
				$trx['unique_id'],
				$types,
				$status
			);
		}

		return $totalAmount;
	}

	/**
	 * Get the sum of amount of all transaction by specific criteria
	 *
	 * Unique Id parameter is used for excluding an transaction from the sum.
	 *      This is used mainly for Refund transactions when we can have refund from Genesis and from the Store.
	 *      If there is Refund in the Store and there is already the received notification unique_id we
	 *      have to exclude that specific transaction.
	 *
	 * @param $unique_id
	 * @param array $trx_tree
	 * @param $types
	 * @param string $status
	 *
	 * @return float
	 */
	public static function get_total_amount_without_unique_id( $unique_id, array $trx_tree, $types, $status = \Genesis\API\Constants\Transaction\States::APPROVED ) {
		$total_amount = 0.0;

		/** @var $transaction */
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
