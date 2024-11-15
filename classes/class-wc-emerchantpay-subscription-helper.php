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
 * @package     classes\class-wc-emerchantpay-subscription-helper
 */

use Genesis\Api\Constants\Transaction\States;
use Genesis\Api\Constants\Transaction\Types;
use Genesis\Api\Constants\Transaction\Parameters\Recurring\Types as RecurringTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Class WC_Emerchantpay_Subscription_Helper
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @class WC_Emerchantpay_Subscription_Helper
 */
class WC_Emerchantpay_Subscription_Helper {

	const META_INIT_RECURRING_ID               = '_init_recurring_id';
	const META_RECURRING_TERMINAL_TOKEN        = '_recurring_terminal_token';
	const META_INIT_RECURRING_FINISHED         = '_init_recurring_finished';
	const META_WCS_SUBSCRIPTION_TRIAL_LENGTH   = '_subscription_trial_length';
	const META_INIT_RECURRING_TRANSACTION_TYPE = '_transaction_type';

	const WC_SUBSCRIPTIONS_PLUGIN_FILTER = 'woocommerce-subscriptions/woocommerce-subscriptions.php';

	const WC_SUBSCRIPTIONS_PLUGIN_URL  = 'https://woocommerce.com/products/woocommerce-subscriptions/';
	const WC_SUBSCRIPTIONS_ORDER_CLASS = 'WC_Subscriptions_Order';

	const WC_SUBSCRIPTION_STATUS_ACTIVE   = 'active';
	const WC_SUBSCRIPTION_STATUS_ON_HOLD  = 'on-hold';
	const WC_SUBSCRIPTION_STATUS_CANCELED = 'cancelled';

	/**
	 * Recurring periods days
	 */
	const RECURRING_PERIOD = array(
		'day'   => 1,
		'week'  => 7,
		'month' => 27,
		'year'  => 365,
	);

	/**
	 * Is $order_id a subscription?
	 *
	 * @param  int $order_id Order identifier.
	 * @return boolean
	 */
	public static function has_order_subscriptions( $order_id ) {
		if ( ! WC_Emerchantpay_Order_Helper::is_valid_order_id( $order_id ) ) {
			return false;
		}

		return function_exists( 'wcs_order_contains_subscription' ) &&
			(
				wcs_order_contains_subscription( $order_id ) ||
				wcs_is_subscription( $order_id ) ||
				wcs_order_contains_renewal( $order_id )
			);
	}

	/**
	 * Detects if Subscriptions Extension for WC is installed
	 *
	 * @return bool
	 */
	public static function is_wc_subscriptions_installed() {
		return WC_Emerchantpay_Helper::is_wp_plugin_active( self::WC_SUBSCRIPTIONS_PLUGIN_FILTER ) &&
			class_exists( self::WC_SUBSCRIPTIONS_ORDER_CLASS );
	}

	/**
	 * Retrieves a list with Subscriptions for a specific order
	 *
	 * @param int $order_id Order identifier.
	 *
	 * @return array
	 */
	public static function get_order_subscriptions( $order_id ) {
		if ( ! static::is_wc_subscriptions_installed() ) {
			return array();
		}

		if ( ! WC_Emerchantpay_Order_Helper::is_valid_order_id( $order_id ) ) {
			return array();
		}

		// Also store it on the subscriptions being purchased or paid for in the order.
		if ( function_exists( 'wcs_order_contains_subscription' ) && wcs_order_contains_subscription( $order_id ) ) {
			return wcs_get_subscriptions_for_order( $order_id );
		} elseif ( function_exists( 'wcs_order_contains_renewal' ) && wcs_order_contains_renewal( $order_id ) ) {
			return wcs_get_subscriptions_for_renewal_order( $order_id );
		}

		return array();
	}

	/**
	 * Saves additional data from the Init Recurring Response to the Order Subscriptions
	 *
	 * @param WC_Order $order    Order identifier.
	 * @param stdClass $response Response object.
	 */
	public static function save_init_recurring_response_to_order_subscriptions( $order, $response ) {
		$subscriptions = static::get_order_subscriptions( WC_Emerchantpay_Order_Helper::get_order_prop( $order, 'id' ) );

		foreach ( $subscriptions as $subscription ) {
			wc_emerchantpay_order_proxy()->set_order_meta_data( $subscription, self::META_INIT_RECURRING_ID, $response->unique_id );
			wc_emerchantpay_order_proxy()->set_order_meta_data( $subscription, self::META_INIT_RECURRING_TRANSACTION_TYPE, $response->transaction_type );
		}

		wc_emerchantpay_order_proxy()->set_order_meta_data( $order, self::META_INIT_RECURRING_ID, $response->unique_id );
	}

	/**
	 * Get current subscription meta data
	 *
	 * @param WC_Order $order Order identifier.
	 *
	 * @return mixed
	 */
	public static function get_order_init_recurring_id_meta( $order ) {
		return wc_emerchantpay_order_proxy()->get_order_meta_data( $order, self::META_INIT_RECURRING_ID );
	}

	/**
	 * Returns init recurring transaction type
	 *
	 * @param WC_Order $order Order identifier.
	 *
	 * @return mixed
	 */
	public static function get_order_init_recurring_transaction_type( $order ) {
		return wc_emerchantpay_order_proxy()->get_order_meta_data( $order, self::META_INIT_RECURRING_TRANSACTION_TYPE );
	}

	/**
	 * Returns Subscription transaction class
	 *
	 * @param string $transaction_type Transaction type.
	 *
	 * @return string
	 */
	public static function get_order_subscription_transaction_class( $transaction_type ) {

		switch ( $transaction_type ) {
			case Types::SDD_INIT_RECURRING_SALE:
				return Types::SDD_RECURRING_SALE;
			case Types::SALE:
			case Types::SALE_3D:
				return Types::SALE;
			default:
				return Types::RECURRING_SALE;
		}
	}

	/**
	 * Get the current WC Cart
	 *
	 * @return WC_Cart|null
	 */
	public static function get_cart() {
		$cart = WC()->cart;

		if ( empty( $cart ) ) {
			return null;
		}

		return $cart;
	}

	/**
	 * Check if the current WC()->cart has subscription items
	 *
	 * @return bool
	 */
	public static function is_cart_has_subscriptions() {
		$has_subscriptions = false;
		$cart              = self::get_cart();

		if ( ! $cart ) {
			return false;
		}

		$cart_contents = $cart->cart_contents;

		if ( ! $cart_contents ) {
			return false;
		}

		foreach ( $cart_contents as $product ) {
			if ( self::is_subscription_product( $product['data'] ) ) {
				$has_subscriptions = true;
				break;
			}
		}

		return $has_subscriptions;
	}

	/**
	 * Checks for subscription product
	 *
	 * @param WC_Product $product Product object.
	 * @return bool
	 */
	public static function is_subscription_product( WC_Product $product ) {
		return $product instanceof WC_Product_Subscription ||
			$product instanceof WC_Product_Subscription_Variation;
	}

	/**
	 * Creates a Meta for the Order to indicate the Merchant has been changed for
	 * the Initial Recurring Order
	 *    + Init Recurring Payment
	 *    + Recurring Sale (if needed - without trial period)
	 *
	 * @param WC_Order $order Order identifier.
	 */
	public static function set_init_recurring_order_finished( $order ) {
		wc_emerchantpay_order_proxy()->set_order_meta_data( $order, self::META_INIT_RECURRING_FINISHED, true );
	}

	/**
	 * Indicates if the Merchant has already been changed for the Init Recurring
	 *    + Init Recurring Payment
	 *    + Recurring Sale (if needed - without trial period)
	 * Will be used in Notification Handler for Init Recurring 3D (to ensure not charging the Merchant twice)
	 *
	 * @param WC_Order $order Order identifier.
	 *
	 * @return bool
	 */
	public static function is_init_recurring_order_finished( $order ) {
		$order_finished = wc_emerchantpay_order_proxy()->get_order_meta_data( $order, self::META_INIT_RECURRING_FINISHED );

		return ! empty( $order_finished );
	}

	/**
	 *  Checks that initial gateway response is successful
	 *
	 * @param stdClass $response Response object.
	 * @return bool
	 */
	public static function is_init_gateway_response_successful( $response ) {
		$successful_statuses = array(
			States::APPROVED,
			States::PENDING_ASYNC,
		);

		return isset( $response->unique_id ) &&
			isset( $response->status ) &&
			in_array( $response->status, $successful_statuses, true );
	}

	/**
	 * Check that is initial subscription transaction
	 *
	 * @param \stdClass $payment_transaction Transaction type.
	 *
	 * @return bool
	 */
	public static function is_init_recurring( $payment_transaction ) {
		$init_recurring_txn_types = array_merge(
			WC_Emerchantpay_Constants::COMMON_RECURRING_PAYMENT_METHODS,
			WC_Emerchantpay_Constants::WPF_RECURRING_PAYMENT_METHODS,
			WC_Emerchantpay_Constants::RECURRING_METHODS_V2,
		);

		if ( isset( $payment_transaction->recurring_type ) && in_array( $payment_transaction->transaction_type, WC_Emerchantpay_Constants::RECURRING_METHODS_V2, true ) ) {
			return RecurringTypes::INITIAL === $payment_transaction->recurring_type;
		}

		return in_array( $payment_transaction->transaction_type, $init_recurring_txn_types, true );
	}

	/**
	 * Checks init recurring reconciliation
	 *
	 * @param stdClass|ArrayObject $reconcile Genesis Reconcile Object.
	 * @return bool
	 */
	public static function is_init_recurring_reconciliation( $reconcile ) {
		$payment_object = WC_Emerchantpay_Genesis_Helper::get_reconcile_payment_transaction( $reconcile );

		$payment_transaction = $payment_object;
		if ( $payment_object instanceof ArrayObject ) {
			unset( $payment_transaction );
			$payment_transaction = $payment_object[0];
		}

		return property_exists( $payment_transaction, 'transaction_type' ) && static::is_init_recurring( $payment_transaction );
	}

	/**
	 * Saves terminal token to order subscription
	 *
	 * @param WC_Order $order          Order identifier.
	 * @param string   $terminal_token Terminal token.
	 *
	 * @return bool
	 */
	public static function save_terminal_token_to_order_subscriptions( $order, $terminal_token ) {
		$order_id = WC_Emerchantpay_Order_Helper::get_order_prop( $order, 'id' );

		if ( ! self::has_order_subscriptions( $order_id ) ) {
			return false;
		}

		$subscriptions = static::get_order_subscriptions( $order_id );

		foreach ( $subscriptions as $subscription ) {
			wc_emerchantpay_order_proxy()->set_order_meta_data( $subscription, self::META_RECURRING_TERMINAL_TOKEN, $terminal_token );
		}

		wc_emerchantpay_order_proxy()->set_order_meta_data( $order, self::META_RECURRING_TERMINAL_TOKEN, $terminal_token );

		return count( $subscriptions ) > 0;
	}

	/**
	 * Returns terminal token meta from order subscription
	 *
	 * @param WC_Order $order Order identifier.
	 *
	 * @return mixed
	 */
	public static function get_terminal_token_meta_from_subscription_order( $order ) {
		return wc_emerchantpay_order_proxy()->get_order_meta_data( $order, self::META_RECURRING_TERMINAL_TOKEN );
	}

	/**
	 * Update WC Subscription based on the Genesis Transaction Response
	 *
	 * @param WC_Order $order  WC Order Object.
	 * @param string   $status WC Subscription Status.
	 * @param string   $note   Description.
	 */
	public static function update_order_subscriptions_status( $order, $status, $note = '' ) {
		if ( ! WC_Emerchantpay_Order_Helper::is_valid_order( $order ) ) {
			return;
		}

		$subscriptions = static::get_order_subscriptions( $order->get_id() );

		foreach ( $subscriptions as $subscription ) {
			static::update_subscription_status( $subscription, $status, $note );
		}
	}

	/**
	 * Updates status of subscription
	 *
	 * @param WC_Subscription $subscription Subscription object.
	 * @param string          $status Current status.
	 * @param string          $note Description.
	 */
	public static function update_subscription_status( $subscription, $status, $note = '' ) {
		$subscription->update_status( $status, $note );
	}

	/**
	 * Filter the generated form WooCommerce Subscriptions hook HTML code fragment for item price
	 *
	 * @param WC_Cart    $cart     WooCommerce Cart instance.
	 * @param WC_Product $product  WooCommerce Product instance.
	 * @param integer    $quantity Product quantity.
	 *
	 * @return string
	 */
	public static function filter_wc_subscription_price( $cart, $product, $quantity ) {
		if ( null === $cart ) {
			return '';
		}

		return html_entity_decode(
			wp_strip_all_tags( $cart->get_product_subtotal( $product, $quantity ) )
		);
	}

	/**
	 * Get expiration_date and frequency or null for recurring transactions
	 *
	 * @param int $order_id Order identifier.
	 *
	 * @return array|null[]
	 */
	public static function get_3dsv2_recurring_parameters( $order_id ) {
		$subscription = WC_Emerchantpay_subscription_helper::get_order_subscriptions( $order_id );

		if ( empty( $subscription ) ) {
			return array(
				'expiration_date' => null,
				'frequency'       => null,
			);
		}

		$subscription_obj = array_values( $subscription )[0];
		$expiration_date  = $subscription_obj->get_date( 'end' );

		if ( 0 === $expiration_date ) {
			$expiration_date = null;
		}

		$period    = $subscription_obj->get_billing_period(); // days, weeks, months, years.
		$interval  = $subscription_obj->get_billing_interval();
		$frequency = self::RECURRING_PERIOD[ $period ] * $interval;

		return array(
			'expiration_date' => $expiration_date,
			'frequency'       => $frequency,
		);
	}
}
