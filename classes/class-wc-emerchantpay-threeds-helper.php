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
 * @package     classes\class-wc-emerchantpay-threeds-helper
 */

use Genesis\Api\Constants\Transaction\Parameters\Threeds\V2\MerchantRisk\ShippingIndicators;
use Genesis\Api\Constants\Transaction\Parameters\Threeds\V2\MerchantRisk\ReorderItemIndicators;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Emerchantpay Threeds Helper Class
 *
 * Class WC_Emerchantpay_Threeds_helper
 *
 * @suppressWarnings(PHPMD.LongVariable)
 */
class WC_Emerchantpay_Threeds_Helper {

	/**
	 * Date format
	 *
	 * @var string Date format type.
	 */
	private $date_format;

	/**
	 * List of paid statuses
	 *
	 * @var array
	 */
	private $paid_statuses = array(
		WC_Emerchantpay_Method_Base::ORDER_STATUS_PROCESSING,
		WC_Emerchantpay_Method_Base::ORDER_STATUS_COMPLETED,
	);

	/**
	 * Order instance
	 *
	 * @var WC_Order
	 */
	private $order;

	/**
	 * Customer instance
	 *
	 * @var WC_Customer
	 */
	private $customer;

	/**
	 * All Customer Orders ordered in Ascending order
	 *
	 * @var array
	 */
	private $customer_orders = array();

	/**
	 * Store if the order contain at least one physical product
	 *
	 * @var bool
	 */
	private $has_physical_product;

	/**
	 * WC_Emerchantpay_Threeds_Helper constructor.
	 *
	 * @param WC_Order    $order Order object.
	 * @param WC_Customer $customer Customer object.
	 * @param string      $date_format Date format.
	 */
	public function __construct( $order, $customer, $date_format ) {
		$this->order       = $order;
		$this->customer    = $customer;
		$this->date_format = $date_format;

		$this->has_physical_product = ! $this->are_all_items_digital();

		$this->init_customer_orders_history();
	}

	/**
	 * Order Has Physical Product accessor
	 *
	 * @return bool
	 */
	public function has_physical_product() {
		return $this->has_physical_product;
	}

	/**
	 * Shipping indicator statuses
	 * same_as_billing, stored_address, verified_address, pick_up, digital_goods, travel, event_tickets, other
	 *
	 * @return string
	 */
	public function fetch_shipping_indicator() {
		if ( ! $this->has_physical_product() ) {
			return ShippingIndicators::DIGITAL_GOODS;
		}

		if ( $this->has_same_addresses() ) {
			return ShippingIndicators::SAME_AS_BILLING;
		}

		if ( $this->order->has_shipping_address() &&
			$this->order->has_billing_address() &&
			! $this->is_guest_customer()
		) {
			return ShippingIndicators::STORED_ADDRESS;
		}

		return ShippingIndicators::OTHER;
	}

	/**
	 * Check if item from the Order is already being ordered
	 *
	 * @return string
	 */
	public function fetch_reorder_items_indicator() {
		if ( $this->is_guest_customer() ) {
			return ReorderItemIndicators::FIRST_TIME;
		}

		$ordered_items = array_values(
			array_map(
				array( WC_Emerchantpay_Order_Helper::class, 'get_item_id' ),
				$this->order->get_items()
			)
		);

		$previous_ordered_items = array_filter(
			$this->customer_orders,
			function ( $value ) use ( $ordered_items ) {
				return count( array_intersect( $ordered_items, $value['product_ids'] ) ) > 0;
			}
		);

		return empty( $previous_ordered_items ) ? ReorderItemIndicators::FIRST_TIME : ReorderItemIndicators::REORDERED;
	}

	/**
	 * Count the Orders count for the last 24 hours, no matter of the status
	 *
	 * @SuppressWarnings(PHPMD.MissingImport)
	 * @return int
	 */
	public function get_transactions_last_24_hours() {
		$comparison_date = ( new WC_DateTime() )->sub( new DateInterval( 'PT24H' ) );

		return count(
			array_filter(
				$this->customer_orders,
				function ( $order_data ) use ( $comparison_date ) {
					return WC_DateTime::CreateFromFormat( $this->date_format, $order_data['date_created'] ) >=
						$comparison_date;
				}
			)
		);
	}

	/**
	 * Get the Orders count for the previous year
	 *
	 * @return int
	 */
	public function get_transactions_previous_year() {
		$previous_year = gmdate( 'Y', strtotime( '-1 Year' ) );
		$start_object  = WC_DateTime::createFromFormat( $this->date_format . 'H:i:s', "$previous_year-01-01 00:00:00" );
		$end_object    = WC_DateTime::createFromFormat( $this->date_format . 'H:i:s', "$previous_year-12-31 23:59:59" );

		return count(
			array_filter(
				$this->customer_orders,
				function ( $order_data ) use ( $start_object, $end_object ) {
					$order_date = WC_DateTime::CreateFromFormat( $this->date_format, $order_data['date_created'] );

					return $start_object <= $order_date && $order_date <= $end_object;
				}
			)
		);
	}

	/**
	 * Returns paid transaction for 6 months
	 *
	 * @SuppressWarnings(PHPMD.MissingImport)
	 * @return int
	 */
	public function get_paid_transactions_for_6_months() {
		$comparison_date = ( new WC_DateTime() )->sub( new DateInterval( 'P6M' ) );

		return count(
			array_filter(
				$this->customer_orders,
				function ( $order_data ) use ( $comparison_date ) {
					return in_array( $order_data['order_status'], $this->paid_statuses, true ) &&
						WC_DateTime::CreateFromFormat( $this->date_format, $order_data['date_created'] ) >=
						$comparison_date;
				}
			)
		);
	}

	/**
	 * Check if the Customer Order has assigned customer_id
	 *
	 * @return bool
	 */
	public function is_guest_customer() {
		return empty( $this->order->get_customer_id() );
	}

	/**
	 * Get first Order Date that uses the current $order Shipping Address
	 *
	 * @return mixed
	 */
	public function get_shipping_address_date_first_used() {
		$address_index = array_search(
			$this->order->get_formatted_shipping_address(),
			array_column( $this->customer_orders, 'shipping' ),
			true
		);

		return false !== $address_index ? $this->customer_orders[ $address_index ]['date_created'] : null;
	}

	/**
	 * Get the First Order Date created via emerchantpay plugin
	 *
	 * @return null|string
	 */
	public function get_first_order_date() {
		return $this->customer_orders ? $this->customer_orders[0]['date_created'] : null;
	}

	/**
	 * Compare if Billing and Shipping addresses are equal for the current Order
	 *
	 * @return bool
	 */
	protected function has_same_addresses() {
		return $this->order->get_formatted_billing_address() === $this->order->get_formatted_shipping_address();
	}

	/**
	 * Check if order contain at least one physical product
	 *
	 * @return bool
	 */
	private function are_all_items_digital() {
		return count(
			array_filter(
				$this->order->get_items(),
				function ( $item ) {
					$product = WC_Emerchantpay_Order_Helper::get_item_product( $item );
					return $product->is_virtual();
				}
			)
		) === count( $this->order->get_items() );
	}

	/**
	 * Load an array from the WP_Orders[]
	 *
	 * @return void
	 */
	private function init_customer_orders_history() {
		$customer_orders = wc_get_orders(
			array(
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'customer_id'    => $this->customer->get_id(),
				'payment_method' => WC_Emerchantpay_Checkout::get_method_code(),
				'limit'          => -1,
				'paginate'       => false,
			)
		);

		// Pop the current order with pending status.
		array_pop( $customer_orders );

		$this->customer_orders = array_map(
			function ( $customer_order ) {
				return array(
					'post_id'      => $customer_order->get_id(),
					'billing'      => $customer_order->get_formatted_billing_address(),
					'shipping'     => $customer_order->get_formatted_shipping_address(),
					'order_status' => $customer_order->get_status(),
					'date_created' => $customer_order->get_date_created()->date( $this->date_format ),
					'date_updated' => $customer_order->get_date_modified()->date( $this->date_format ),
					'product_ids'  => array_values(
						array_map(
							array( WC_Emerchantpay_Order_Helper::class, 'get_item_id' ),
							$customer_order->get_items()
						)
					),
				);
			},
			count( $customer_orders ) > 0 ? $customer_orders : array()
		);
	}
}
