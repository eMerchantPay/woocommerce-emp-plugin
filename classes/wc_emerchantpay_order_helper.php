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

use \Genesis\API\Request\Financial\Alternatives\Klarna\Item as KlarnaItem;

/**
 * Class wc_emerchantpay_order_helper
 */
class WC_emerchantpay_Order_Helper {

	/**
	 * Retrieves meta data for a specific order and key
	 *
	 * @param int    $order_id
	 * @param string $meta_key
	 * @param bool   $single
	 * @return mixed
	 */
	public static function getOrderMetaData( $order_id, $meta_key, $single = true ) {
		return get_post_meta( $order_id, $meta_key, $single );
	}

	/**
	 * Retrieves meta data for a specific order and key
	 *
	 * @param int    $order_id
	 * @param string $meta_key
	 * @param float  $default
	 * @return float
	 */
	public static function getFloatOrderMetaData( $order_id, $meta_key, $default = 0.0 ) {
		$value = static::getOrderMetaData( $order_id, $meta_key );

		return empty( $value ) ? $default : (float) $value;
	}

	/**
	 * Retrieves meta data formatted as amount for a specific order and key
	 *
	 * @param int    $order_id
	 * @param string $meta_key
	 * @param bool   $single
	 * @return mixed
	 */
	public static function getOrderAmountMetaData( $order_id, $meta_key, $single = true ) {
		return (double) static::getOrderMetaData( $order_id, $meta_key, $single );
	}

	/**
	 * Stores order meta data for a specific key
	 *
	 * @param int    $order_id
	 * @param string $meta_key
	 * @param mixed  $meta_value
	 */
	public static function setOrderMetaData( $order_id, $meta_key, $meta_value ) {
		update_post_meta( $order_id, $meta_key, $meta_value );
	}

	/**
	 * Get payment gateway class by order data.
	 *
	 * @param int|WC_Order $order
	 * @return WC_emerchantpay_Method|bool
	 */
	public static function getPaymentMethodInstanceByOrder( $order ) {
		return wc_get_payment_gateway_by_order( $order );
	}

	/**
	 * Creates an instance of a WooCommerce Order by Id
	 *
	 * @param int $order_id
	 * @return WC_Order|null
	 */
	public static function getOrderById( $order_id ) {
		if ( ! static::isValidOrderId( $order_id ) ) {
			return null;
		}

		return wc_get_order( (int) $order_id );
	}

	/**
	 * Format the price with a currency symbol.
	 *
	 * @param float        $price
	 * @param int|WC_Order $order
	 * @return string
	 */
	public static function formatPrice( $price, $order ) {
		if ( ! static::isValidOrder( $order ) ) {
			$order = static::getOrderById( $order );
		}

		if ( $order === null ) {
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
	 * @param float|string $amount
	 * @param WC_Order     $order
	 * @return string
	 */
	public static function formatMoney( $amount, $order ) {
		$amount = (float) $amount;
		$money  = number_format( $amount, 2, '.', '' );

		if ( ! static::isValidOrder( $order ) ) {
			return $money;
		}

		return "$money {$order->get_currency()}";
	}

	/**
	 * Get WC_Order instance by UniqueId saved during checkout
	 *
	 * @param string $unique_id
	 *
	 * @return WC_Order|bool
	 */
	public static function getOrderByGatewayUniqueId( $unique_id, $meta_key ) {
		$unique_id = esc_sql( trim( $unique_id ) );

		$query = new WP_Query(
			array(
				'post_status' => 'any',
				'post_type'   => 'shop_order',
				'meta_key'    => $meta_key,
				'meta_value'  => $unique_id,
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
				WC_emerchantpay_Transactions_Tree::META_DATA_KEY_LIST
			);
		}

		if ( empty( $order_id ) && isset( $reconcile->reference_transaction_unique_id ) ) {
			$order_id = self::get_order_id(
				$reconcile->reference_transaction_unique_id,
				WC_emerchantpay_Transactions_Tree::META_DATA_KEY_LIST
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
	 * @param string $meta_key              The value of the post meta key value.
	 *
	 * @return int|null
	 */
	public static function get_order_id( $transaction_unique_id, $meta_key ) {
		$transaction_unique_id = esc_sql( trim( $transaction_unique_id ) );

		$query = new WP_Query(
			array(
				'post_status' => 'any',
				'post_type'   => 'shop_order',
				'meta_key'    => $meta_key,
				'meta_query'  => array(
					array(
						'key'     => $meta_key,
						'value'   => $transaction_unique_id,
						'compare' => 'LIKE',
					),
				),
			)
		);

		if ( $query->have_posts() ) {
			return $query->post->ID;
		}

		return null;
	}

	/**
	 * @param WC_Order $order
	 * @param array    $trx_list_new
	 */
	public static function saveTrxListToOrder( WC_Order $order, array $trx_list_new ) {
		$order_id          = static::getOrderProp( $order, 'id' );
		$trx_list_existing = static::getOrderMetaData( $order_id, WC_emerchantpay_Transactions_Tree::META_DATA_KEY_LIST );

		if ( is_array( $trx_list_existing ) ) {
			$trx_hierarchy = static::getOrderMetaData( $order_id, WC_emerchantpay_Transactions_Tree::META_DATA_KEY_HIERARCHY );
			if ( empty( $trx_hierarchy ) ) {
				$trx_hierarchy = [];
			}

			$trx_tree = new WC_emerchantpay_Transactions_Tree( $trx_list_existing, $trx_list_new, $trx_hierarchy );

			static::saveTrxTree( $order_id, $trx_tree );
		}
	}

	/**
	 * @param int                               $order_id
	 * @param WC_emerchantpay_Transactions_Tree $trx_tree
	 */
	public static function saveTrxTree( $order_id, WC_emerchantpay_Transactions_Tree $trx_tree ) {
		static::setOrderMetaData(
			$order_id,
			WC_emerchantpay_Transactions_Tree::META_DATA_KEY_LIST,
			$trx_tree->trx_list
		);

		static::setOrderMetaData(
			$order_id,
			WC_emerchantpay_Transactions_Tree::META_DATA_KEY_HIERARCHY,
			$trx_tree->trx_hierarchy
		);
	}

	/**
	 * @param int      $order_id
	 * @param stdClass $response_obj
	 */
	public static function saveInitialTrxToOrder( $order_id, $response_obj ) {
		$trx = new WC_emerchantpay_Transaction( $response_obj );

		static::setOrderMetaData(
			$order_id,
			WC_emerchantpay_Transactions_Tree::META_DATA_KEY_LIST,
			[ $trx ]
		);
	}

	/**
	 * @param int $orderId
	 * @return bool
	 */
	public static function isValidOrderId( $orderId ) {
		return (int) $orderId > 0;
	}

	/**
	 * @param WC_Order $order
	 * @return bool
	 */
	public static function isValidOrder( $order ) {
		return is_object( $order ) && ( $order instanceof WC_Order );
	}

	/**
	 * WooCommerce compatibility
	 *
	 * @param mixed $item
	 * @return string
	 */
	public static function getItemName( $item ) {
		return is_array( $item ) ? $item['name'] : $item->get_product()->get_name();
	}

	/**
	 * WooCommerce compatibility
	 *
	 * @param mixed $item
	 * @return string
	 */
	public static function getItemQuantity( $item ) {
		return is_array( $item ) ? $item['qty'] : $item->get_quantity();
	}

	/**
	 * WooCommerce compatibility
	 *
	 * @param $item
	 *
	 * @return WC_Product
	 */
	public static function getItemProduct( $item ) {
		return is_array( $item ) ? wc_get_product( $item['product_id'] ) : $item->get_product();
	}

	/**
	 * WooCommerce compatibility
	 *
	 * @param mixed  $order
	 * @param string $prop
	 * @return string
	 */
	public static function getOrderProp( $order, $prop ) {
		return is_array( $order ) ?
			$order[ $prop ] : method_exists( $order, "get_$prop" ) ?
				$order->{"get_$prop"}() :
				$order->{$prop};
	}

	/**
	 * @param WC_Order $order
	 * @return \Genesis\API\Request\Financial\Alternatives\Klarna\Items $items
	 * @throws \Genesis\Exceptions\ErrorParameter
	 */
	public static function getKlarnaCustomParamItems( WC_Order $order ) {
		$items       = new \Genesis\API\Request\Financial\Alternatives\Klarna\Items( $order->get_currency() );
		$order_items = $order->get_items();

		foreach ( $order_items as $item ) {
			$product = self::getItemProduct( $item );

			$klarnaItem = new KlarnaItem(
				self::getItemName( $item ),
				$product->is_virtual() ? KlarnaItem::ITEM_TYPE_DIGITAL : KlarnaItem::ITEM_TYPE_PHYSICAL,
				self::getItemQuantity( $item ),
				wc_get_price_excluding_tax(
					$product,
					array(
						'qty' => self::getItemQuantity( $item ),
						'price' => '',
					)
				)
			);

			$items->addItem( $klarnaItem );
		}

		$taxes = floatval( $order->get_total_tax() );
		if ( $taxes ) {
			$items->addItem(
				new KlarnaItem(
					WC_emerchantpay_Method::getTranslatedText( 'Taxes' ),
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
					WC_emerchantpay_Method::getTranslatedText( 'Discount' ),
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
					WC_emerchantpay_Method::getTranslatedText( 'Shipping Costs' ),
					KlarnaItem::ITEM_TYPE_SHIPPING_FEE,
					1,
					$total_shipping_cost
				)
			);
		}

		return $items;
	}
}
