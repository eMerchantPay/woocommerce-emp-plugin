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
 * @package     classes\adapters\interface-wc-emerchantpay-order-adapter-interface
 */

interface WC_Emerchantpay_Order_Adapter_Interface {
	/**
	 * Retrieves meta data for a specific order and key
	 *
	 * @param mixed  $order    Order identifier.
	 * @param string $meta_key The value of the post meta key value.
	 * @param bool   $single   Default value true.
	 *
	 * @return mixed
	 */
	public function get_order_meta_data( $order, string $meta_key, bool $single = true );

	/**
	 * Stores order meta data for a specific key
	 *
	 * @param mixed  $order      Order identifier.
	 * @param string $meta_key   The value of the post meta key.
	 * @param mixed  $meta_value The value of the post meta value.
	 *
	 * @return void
	 */
	public function set_order_meta_data( $order, string $meta_key, $meta_value );

	/**
	 * Search into posts for the Post Id (Order Id)
	 *
	 * @param string $transaction_unique_id Gateway transaction identifier.
	 * @param string $meta_key              The value of the post meta key.
	 *
	 * @return int|null
	 */
	public function get_order_id( string $transaction_unique_id, string $meta_key );

	/**
	 * Saved transaction list
	 *
	 * @param mixed                             $order    Order identifier.
	 * @param WC_Emerchantpay_Transactions_Tree $trx_tree Transaction tree.
	 */
	public function save_trx_tree( $order, WC_Emerchantpay_Transactions_Tree $trx_tree );
}
