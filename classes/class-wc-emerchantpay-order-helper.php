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

use Genesis\Api\Request\Financial\Alternatives\Transaction\Item as InvoiceItem;
use Genesis\Api\Request\Financial\Alternatives\Transaction\Items as InvoiceItems;
use Genesis\Api\Constants\Financial\Alternative\Transaction\ItemTypes as InvoiceItemTypes;
use Genesis\Exceptions\ErrorParameter;
use Genesis\Exceptions\InvalidArgument;

/**
 * Class wc_emerchantpay_order_helper
 *
 * @SuppressWarnings(PHPMD)
 */
class WC_Emerchantpay_Order_Helper {
	/**
	 * Format the price with a currency symbol.
	 *
	 * @param float        $price The price that should be formatted.
	 * @param int|WC_Order $order Order identifier.
	 * @return string
	 */
	public static function format_price( $price, $order ) {
		if ( ! static::is_valid_order( $order ) ) {
			$order = wc_emerchantpay_order_proxy()->get_order_by_id( $order );
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
	 * Handles Invoice custom params
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return InvoiceItems $items
	 *
	 * @throws ErrorParameter|InvalidArgument
	 */
	public static function get_invoice_custom_param_items( WC_Order $order ) {
		$items       = new InvoiceItems();
		$order_items = $order->get_items();
		$items->setCurrency( $order->get_currency() );

		foreach ( $order_items as $item ) {
			$product = self::get_item_product( $item );

			$invoice_item = new InvoiceItem();
			$invoice_item
				->setName( self::get_item_name( $item ) )
				->setItemType( $product->is_virtual() ? InvoiceItemTypes::DIGITAL : InvoiceItemTypes::PHYSICAL )
				->setQuantity( self::get_item_quantity( $item ) )
				->setUnitPrice(
					wc_get_price_excluding_tax(
						$product,
						array(
							'qty'   => self::get_item_quantity( $item ),
							'price' => '',
						)
					)
				);

			$items->addItem( $invoice_item );
		}

		$taxes = floatval( $order->get_total_tax() );
		if ( $taxes ) {
			$invoice_item = new InvoiceItem();
			$invoice_item
				->setName( WC_Emerchantpay_Method_Base::get_translated_text( 'Taxes' ) )
				->setItemType( InvoiceItemTypes::SURCHARGE )
				->setQuantity( 1 )
				->setUnitPrice( $taxes );

			$items->addItem( $invoice_item );
		}

		$discount = floatval( $order->get_discount_total() );
		if ( $discount ) {
			$invoice_item = new InvoiceItem();
			$invoice_item
				->setName( WC_Emerchantpay_Method_Base::get_translated_text( 'Discount' ) )
				->setItemType( InvoiceItemTypes::DISCOUNT )
				->setQuantity( 1 )
				->setUnitPrice( -$discount );
			$items->addItem( $invoice_item );
		}

		$total_shipping_cost = floatval( $order->get_shipping_total() );
		if ( $total_shipping_cost ) {
			$invoice_item = new InvoiceItem();
			$invoice_item
				->setName( WC_Emerchantpay_Method_Base::get_translated_text( 'Shipping Costs' ) )
				->setItemType( InvoiceItemTypes::SHIPPING_FEE )
				->setQuantity( 1 )
				->setUnitPrice( $total_shipping_cost );
			$items->addItem( $invoice_item );
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
