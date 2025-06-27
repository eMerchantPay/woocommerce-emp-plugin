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
 * @package     classes\adapters\class-wc-emerchantpay-legacy-order-adapter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 ); // Exit if accessed directly.
}

use Exception as SystemException;
use WC_Emerchantpay_Order_Adapter_Interface as OrderAdapterInterface;
use WC_Emerchantpay_Order_Helper as OrderHelper;
use WC_Emerchantpay_Transaction as Transaction;
use WC_Emerchantpay_Transactions_Tree as TransactionTree;
use WC_Emerchantpay_Order_Factory as OrderFactory;

/**
 * Emerchantpay Order Storage Proxy
 */
class WC_Emerchantpay_Order_Proxy {

	/**
	 * Class instance
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * @var OrderAdapterInterface
	 */
	private $order_adapter;

	/**
	 * Singleton class instance
	 *
	 * @return self
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Retrieves metadata for a specific order and key
	 *
	 * @param mixed  $order    Order identifier.
	 * @param string $meta_key The value of the post meta key value.
	 * @param bool   $single   Default value true.
	 * @return mixed
	 */
	public function get_order_meta_data( $order, string $meta_key, bool $single = true ) {
		return $this->order_adapter->get_order_meta_data( $this->get_order_accessor( $order ), $meta_key, $single );
	}

	/**
	 * Stores order meta data for a specific key
	 *
	 * @param mixed  $order      Order identifier.
	 * @param string $meta_key   The value of the post meta key.
	 * @param mixed  $meta_value The value of the post meta value.
	 *
	 * @return int|bool
	 */
	public function set_order_meta_data( $order, string $meta_key, $meta_value ) {
		return $this->order_adapter->set_order_meta_data( $this->get_order_accessor( $order ), $meta_key, $meta_value );
	}


	/**
	 * Creates an instance of a WooCommerce Order by Id
	 *
	 * @param int $order_id Order identifier.
	 * @return WC_Order|null
	 */
	public function get_order_by_id( int $order_id ) {
		if ( ! OrderHelper::is_valid_order_id( $order_id ) ) {
			return null;
		}

		return wc_get_order( (int) $order_id );
	}

	/**
	 * Saved order transaction list
	 *
	 * @param WC_Order $order The order object.
	 * @param array    $trx_list_new The new transaction list.
	 */
	public function save_trx_list_to_order( WC_Order $order, array $trx_list_new ) {
		$trx_list_existing = $this->get_order_meta_data( $order, TransactionTree::META_DATA_KEY_LIST );
		$trx_hierarchy     = $this->get_order_meta_data( $order, TransactionTree::META_DATA_KEY_HIERARCHY );

		if ( empty( $trx_hierarchy ) ) {
			$trx_hierarchy = array();
		}

		$trx_tree = new TransactionTree( array(), $trx_list_new, $trx_hierarchy );
		if ( is_array( $trx_list_existing ) ) {
			$trx_tree = new TransactionTree( $trx_list_existing, $trx_list_new, $trx_hierarchy );
		}

		$this->order_adapter->save_trx_tree( $this->get_order_accessor( $order ), $trx_tree );
	}

	/**
	 * Save Response object, along with 3DSv2 URLs
	 *
	 * @param mixed    $order        Order identifier.
	 * @param stdClass $response_obj Response object from Gateway.
	 * @param array    $data         Request data.
	 *
	 * @return void
	 */
	public function save_initial_trx_to_order( $order, $response_obj, $data = array() ) {
		$trx = new Transaction( $response_obj );

		if ( isset( $data['return_success_url'] ) && isset( $data['return_failure_url'] ) ) {
			$trx->set_return_success_url( $data['return_success_url'] );
			$trx->set_return_failure_url( $data['return_failure_url'] );
		}

		$this->set_order_meta_data( $order, TransactionTree::META_DATA_KEY_LIST, array( $trx ) );
	}

	/**
	 * Get payment gateway class by order data.
	 *
	 * @param int|WC_Order $order Order identifier.
	 *
	 * @return WC_Emerchantpay_Method_Base|bool
	 */
	public function get_payment_method_instance_by_order( $order ) {
		return wc_get_payment_gateway_by_order( $order );
	}

	/**
	 * Try to load the Order from the Reconcile Object notification
	 *
	 * @param \stdClass $reconcile         Genesis Reconcile Object.
	 * @param string    $checkout_meta_key The method used for handling the notification.
	 *
	 * @return WC_Order
	 * @throws SystemException Throw general exception.
	 */
	public function load_order_from_reconcile_object( stdClass $reconcile, string $checkout_meta_key ) {
		$order_id = $this->order_adapter->get_order_id(
			$reconcile->unique_id,
			$checkout_meta_key
		);

		if ( empty( $order_id ) ) {
			$order_id = $this->order_adapter->get_order_id(
				$reconcile->unique_id,
				WC_Emerchantpay_Transactions_Tree::META_DATA_KEY_LIST
			);
		}

		if ( empty( $order_id ) && isset( $reconcile->reference_transaction_unique_id ) ) {
			$order_id = $this->order_adapter->get_order_id(
				$reconcile->reference_transaction_unique_id,
				TransactionTree::META_DATA_KEY_LIST
			);
		}

		if ( ! OrderHelper::is_valid_order_id( $order_id ) ) {
			throw new SystemException( 'Invalid transaction unique_id' );
		}

		return $this->get_order_by_id( $order_id );
	}

	/**
	 * Class constructor
	 *
	 * @return void
	 */
	private function __construct() {
		$this->order_adapter = $this->init_order_adapter();
	}

	/**
	 * Clone not allowed
	 */
	private function __clone() { }

	/**
	 * @return OrderAdapterInterface
	 */
	private function init_order_adapter() {
		return OrderFactory::init_order_adapter();
	}

	/**
	 * Based on the Order DB storage use the proper WC Order accessor
	 *
	 * @param WC_Order $order
	 *
	 * @return WC_Order|string
	 */
	private function get_order_accessor( $order ) {
		return OrderFactory::get_order_accessor( $order );
	}
}
