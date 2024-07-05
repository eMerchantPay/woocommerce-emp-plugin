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
 * @package     classes\class-wc-emerchantpay-order-helper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

use Genesis\API\Request\Financial\Alternatives\Klarna\Item as KlarnaItem;

/**
 * Class wc_emerchantpay_order_helper
 *
 * @SuppressWarnings(PHPMD)
 */
class WC_Emerchantpay_Order_Helper {

	/**
	 * Retrieves meta data for a specific order and key
	 *
	 * @param int    $order_id Order identifier.
	 * @param string $meta_key The value of the post meta key value.
	 * @param bool   $single   Default value true.
	 * @return mixed
	 */
	public static function get_order_meta_data( $order_id, $meta_key, $single = true ) {
		return get_post_meta( $order_id, $meta_key, $single );
	}

	/**
	 * Retrieves meta data for a specific order and key
	 *
	 * @param int    $order_id Order identifier.
	 * @param string $meta_key The value of the post meta key.
	 * @param float  $default_value Default value 0.0.
	 *
	 * @return float
	 */
	public static function get_float_order_meta_data( $order_id, $meta_key, $default_value = 0.0 ) {
		$value = static::get_order_meta_data( $order_id, $meta_key );

		return empty( $value ) ? $default_value : (float) $value;
	}

	/**
	 * Retrieves meta data formatted as amount for a specific order and key
	 *
	 * @param int    $order_id Order identifier.
	 * @param string $meta_key The value of the post meta key.
	 * @param bool   $single Default value true.
	 * @return mixed
	 */
	public static function get_order_amount_meta_data( $order_id, $meta_key, $single = true ) {
		return (float) static::get_order_meta_data( $order_id, $meta_key, $single );
	}

	/**
	 * Stores order meta data for a specific key
	 *
	 * @param int    $order_id Order identifier.
	 * @param string $meta_key The value of the post meta key.
	 * @param mixed  $meta_value The value of the post meta value.
	 */
	public static function set_order_meta_data( $order_id, $meta_key, $meta_value ) {
		update_post_meta( $order_id, $meta_key, $meta_value );
	}

	/**
	 * Get payment gateway class by order data.
	 *
	 * @param int|WC_Order $order Order identifier.
	 *
	 * @return WC_Emerchantpay_Method_Base|bool
	 */
	public static function get_payment_method_instance_by_order( $order ) {
		return wc_get_payment_gateway_by_order( $order );
	}

	/**
	 * Creates an instance of a WooCommerce Order by Id
	 *
	 * @param int $order_id Order identifier.
	 * @return WC_Order|null
	 */
	public static function get_order_by_id( $order_id ) {
		if ( ! static::is_valid_order_id( $order_id ) ) {
			return null;
		}

		return wc_get_order( (int) $order_id );
	}

	/**
	 * Format the price with a currency symbol.
	 *
	 * @param float        $price The price that should be formatted.
	 * @param int|WC_Order $order Order identifier.
	 * @return string
	 */
	public static function format_price( $price, $order ) {
		if ( ! static::is_valid_order( $order ) ) {
			$order = static::get_order_by_id( $order );
		}

		if ( null === $order ) {
			return (string) $price;
		}

		return wc_price(
			$price,
			array(
				'currency' => $order->get_currency(),
			)
		);
	}

	/**
	 * Returns a formatted money with currency (non HTML)
	 *
	 * @param float|string $amount Formatted amount.
	 * @param WC_Order     $order Order data.
	 * @return string
	 */
	public static function format_money( $amount, $order ) {
		$amount = (float) $amount;
		$money  = number_format( $amount, 2, '.', '' );

		if ( ! static::is_valid_order( $order ) ) {
			return $money;
		}

		return "$money {$order->get_currency()}";
	}

	/**
	 * Get WC_Order instance by UniqueId saved during checkout
	 *
	 * @param string $unique_id Unique identifier provided by Gateway.
	 * @param string $meta_key The value of the post meta key.
	 *
	 * @return WC_Order|bool
	 */
	public static function get_order_by_gateway_unique_id( $unique_id, $meta_key ) {
		$unique_id = esc_sql( trim( $unique_id ) );

		$query = new WP_Query(
			array(
				'post_status' => 'any',
				'post_type'   => 'shop_order',
				// TODO Research to improve query.
				// phpcs:disable
				'meta_key'    => $meta_key,
				// TODO Research to improve query.
				'meta_value'  => $unique_id,
				//phpcs:enable
			)
		);

		if ( isset( $query->post->ID ) ) {
			return new WC_Order( $query->post->ID );
		}

		return false;
	}

	/**
	 * Try to load the Order from the Reconcile Object notification
	 *
	 * @param \stdClass $reconcile         Genesis Reconcile Object.
	 * @param string    $checkout_meta_key The method used for handling the notification.
	 *
	 * @return WC_Order
	 * @throws \Exception Throw general exception.
	 */
	public static function load_order_from_reconcile_object( $reconcile, $checkout_meta_key ) {
		$order_id = self::get_order_id(
			$reconcile->unique_id,
			$checkout_meta_key
		);

		if ( empty( $order_id ) ) {
			$order_id = self::get_order_id(
				$reconcile->unique_id,
				WC_Emerchantpay_Transactions_Tree::META_DATA_KEY_LIST
			);
		}

		if ( empty( $order_id ) && isset( $reconcile->reference_transaction_unique_id ) ) {
			$order_id = self::get_order_id(
				$reconcile->reference_transaction_unique_id,
				WC_Emerchantpay_Transactions_Tree::META_DATA_KEY_LIST
			);
		}

		if ( empty( $order_id ) ) {
			throw new \Exception( 'Invalid transaction unique_id' );
		}

		return new WC_Order( $order_id );
	}

	/**
	 * Search into posts for the Post Id (Order Id)
	 *
	 * @param string $transaction_unique_id Unique Id of the transaction.
	 * @param string $meta_key              The value of the post meta key.
	 *
	 * @return int|null
	 */
	public static function get_order_id( $transaction_unique_id, $meta_key ) {
		$transaction_unique_id = esc_sql( trim( $transaction_unique_id ) );

		$query = new WP_Query(
			array(
				'post_status' => 'any',
				'post_type'   => 'shop_order',
				// TODO Research to improve query.
				// phpcs:disable
				'meta_key'    => $meta_key,
				// TODO Research to improve query.
				'meta_query'  => array(
					array(
						'key'     => $meta_key,
						'value'   => $transaction_unique_id,
						'compare' => 'LIKE',
					),
				),
				// phpcs:enable
			)
		);

		if ( $query->have_posts() ) {
			return $query->post->ID;
		}

		return null;
	}

	/**
	 * Saved order transaction list
	 *
	 * @param WC_Order $order The order object.
	 * @param array    $trx_list_new The new transaction list.
	 */
	public static function save_trx_list_to_order( WC_Order $order, array $trx_list_new ) {
		$order_id          = static::get_order_prop( $order, 'id' );
		$trx_list_existing = static::get_order_meta_data( $order_id, WC_Emerchantpay_Transactions_Tree::META_DATA_KEY_LIST );
		$trx_hierarchy     = static::get_order_meta_data( $order_id, WC_Emerchantpay_Transactions_Tree::META_DATA_KEY_HIERARCHY );

		if ( empty( $trx_hierarchy ) ) {
			$trx_hierarchy = array();
		}

		$trx_tree = new WC_Emerchantpay_Transactions_Tree( array(), $trx_list_new, $trx_hierarchy );
		if ( is_array( $trx_list_existing ) ) {
			$trx_tree = new WC_Emerchantpay_Transactions_Tree( $trx_list_existing, $trx_list_new, $trx_hierarchy );
		}

		static::save_trx_tree( $order_id, $trx_tree );
	}

	/**
	 * Saved transaction list
	 *
	 * @param int                               $order_id Order identifier.
	 * @param WC_Emerchantpay_Transactions_Tree $trx_tree Transaction tree.
	 */
	public static function save_trx_tree( $order_id, WC_Emerchantpay_Transactions_Tree $trx_tree ) {
		static::set_order_meta_data(
			$order_id,
			WC_Emerchantpay_Transactions_Tree::META_DATA_KEY_LIST,
			$trx_tree->trx_list
		);

		static::set_order_meta_data(
			$order_id,
			WC_Emerchantpay_Transactions_Tree::META_DATA_KEY_HIERARCHY,
			$trx_tree->trx_hierarchy
		);
	}

	/**
	 * Save Response object, along with 3DSv2 URLs
	 *
	 * @param int      $order_id Order identifier.
	 * @param stdClass $response_obj Response object from Gateway.
	 * @param array    $data Request data.
	 *
	 * @return void
	 */
	public static function save_initial_trx_to_order( $order_id, $response_obj, $data = array() ) {
		$trx = new WC_Emerchantpay_Transaction( $response_obj );

		if ( isset( $data['return_success_url'] ) && isset( $data['return_failure_url'] ) ) {
			$trx->set_return_success_url( $data['return_success_url'] );
			$trx->set_return_failure_url( $data['return_failure_url'] );
		}

		static::set_order_meta_data(
			$order_id,
			WC_Emerchantpay_Transactions_Tree::META_DATA_KEY_LIST,
			array( $trx )
		);
	}

	/**
	 * Checks that order id is valid.
	 *
	 * @param int $order_id Order identifier.
	 *
	 * @return bool
	 */
	public static function is_valid_order_id( $order_id ) {
		return (int) $order_id > 0;
	}

	/**
	 * Checks that order is an object and of type WC_Order
	 *
	 * @param WC_Order $order Order object.
	 * @return bool
	 */
	public static function is_valid_order( $order ) {
		return is_object( $order ) && ( $order instanceof WC_Order );
	}

	/**
	 * WooCommerce compatibility
	 *
	 * @param mixed $item Product object.
	 * @return string
	 */
	public static function get_item_name( $item ) {
		return is_array( $item ) ? $item['name'] : $item->get_product()->get_name();
	}

	/**
	 * WooCommerce compatibility
	 *
	 * @param mixed $item Product object.
	 * @return string
	 */
	public static function get_item_quantity( $item ) {
		return is_array( $item ) ? $item['qty'] : $item->get_quantity();
	}

	/**
	 * WooCommerce compatibility
	 *
	 * @param mixed $item Product object.
	 *
	 * @return WC_Product
	 */
	public static function get_item_product( $item ) {
		return is_array( $item ) ? wc_get_product( $item['product_id'] ) : $item->get_product();
	}

	/**
	 * WooCommerce compatibility
	 *
	 * @param mixed  $order Order data.
	 * @param string $prop Specific property.
	 * @return string
	 */
	public static function get_order_prop( $order, $prop ) {
		return is_array( $order ) ?
			$order[ $prop ] : ( method_exists( $order, "get_$prop" ) ?
				$order->{"get_$prop"}() :
				$order->{$prop} );
	}

	/**
	 * Handles Klarna custom params
	 *
	 * @param WC_Order $order Order object.
	 * @return \Genesis\API\Request\Financial\Alternatives\Klarna\Items $items
	 * @throws \Genesis\Exceptions\ErrorParameter Throws error parameters.
	 */
	public static function get_klarna_custom_param_items( WC_Order $order ) {
		$items       = new \Genesis\API\Request\Financial\Alternatives\Klarna\Items( $order->get_currency() );
		$order_items = $order->get_items();

		foreach ( $order_items as $item ) {
			$product = self::get_item_product( $item );

			$klarna_item = new KlarnaItem(
				self::get_item_name( $item ),
				$product->is_virtual() ? KlarnaItem::ITEM_TYPE_DIGITAL : KlarnaItem::ITEM_TYPE_PHYSICAL,
				self::get_item_quantity( $item ),
				wc_get_price_excluding_tax(
					$product,
					array(
						'qty'   => self::get_item_quantity( $item ),
						'price' => '',
					)
				)
			);

			$items->addItem( $klarna_item );
		}

		$taxes = floatval( $order->get_total_tax() );
		if ( $taxes ) {
			$items->addItem(
				new KlarnaItem(
					WC_Emerchantpay_Method_Base::get_translated_text( 'Taxes' ),
					KlarnaItem::ITEM_TYPE_SURCHARGE,
					1,
					$taxes
				)
			);
		}

		$discount = floatval( $order->get_discount_total() );
		if ( $discount ) {
			$items->addItem(
				new KlarnaItem(
					WC_Emerchantpay_Method_Base::get_translated_text( 'Discount' ),
					KlarnaItem::ITEM_TYPE_DISCOUNT,
					1,
					-$discount
				)
			);
		}

		$total_shipping_cost = floatval( $order->get_shipping_total() );
		if ( $total_shipping_cost ) {
			$items->addItem(
				new KlarnaItem(
					WC_Emerchantpay_Method_Base::get_translated_text( 'Shipping Costs' ),
					KlarnaItem::ITEM_TYPE_SHIPPING_FEE,
					1,
					$total_shipping_cost
				)
			);
		}

		return $items;
	}

	/**
	 * Return WC_Order_Item Id
	 *
	 * @param WC_Order_Item $item Product object.
	 * @return integer
	 */
	public static function get_item_id( $item ) {
		return is_object( $item ) ? $item->get_product_id() : 0;
	}
}
