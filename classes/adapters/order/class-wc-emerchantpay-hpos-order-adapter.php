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

use WP_Query as WpQuery;

/**
 * WC legacy order in the posts tables
 *
 * @class WC_Emerchantpay_Legacy_Order_Adapter
 */
class WC_Emerchantpay_Hpos_Order_Adapter implements WC_Emerchantpay_Order_Adapter_Interface {
	/**
	 * Retrieves metadata for a specific order and key
	 *
	 * @param WC_Order $order    Order identifier.
	 * @param string   $meta_key The value of the post meta key value.
	 * @param bool     $single   Default value true.
	 *
	 * @return mixed
	 */
	public function get_order_meta_data( $order, string $meta_key, bool $single = true ) {
		return $order->get_meta( $meta_key, $single );
	}

	/**
	 * Stores order meta data for a specific key
	 *
	 * @param WC_Order $order      Order identifier.
	 * @param string   $meta_key   The value of the post meta key.
	 * @param mixed    $meta_value The value of the post meta value.
	 *
	 * @return bool|int
	 */
	public function set_order_meta_data( $order, string $meta_key, $meta_value ) {
		$meta_id = $order->update_meta_data( $meta_key, $meta_value );

		$order->save();

		return $meta_id;
	}

	/**
	 * Search into posts for the Post Id (Order Id)
	 *
	 * @param string $transaction_unique_id Gateway transaction identifier.
	 * @param string $meta_key              The value of the post meta key.
	 *
	 * @return int|null
	 */
	public function get_order_id( string $transaction_unique_id, string $meta_key ) {
		$transaction_unique_id = esc_sql( trim( $transaction_unique_id ) );

		$orders = wc_get_orders(
			array(
				'meta_query' => array( // phpcs:ignore
					array(
						'key'     => $meta_key,
						'value'   => $transaction_unique_id,
						'compare' => 'LIKE',
					),
				),
			),
		);

		if ( count( $orders ) > 0 ) {
			return $orders[0]->get_id();
		}

		return null;
	}

	/**
	 * Saved transaction list
	 *
	 * @param mixed                             $order    Order identifier.
	 * @param WC_Emerchantpay_Transactions_Tree $trx_tree Transaction tree.
	 */
	public function save_trx_tree( $order, WC_Emerchantpay_Transactions_Tree $trx_tree ) {
		$this->set_order_meta_data(
			$order,
			WC_Emerchantpay_Transactions_Tree::META_DATA_KEY_LIST,
			$trx_tree->trx_list
		);

		$this->set_order_meta_data(
			$order,
			WC_Emerchantpay_Transactions_Tree::META_DATA_KEY_HIERARCHY,
			$trx_tree->trx_hierarchy
		);
	}
}
