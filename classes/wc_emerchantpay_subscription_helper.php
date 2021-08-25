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
 * emerchantpay Subscription Helper Class
 *
 * Class WC_emerchantpay_Subscription_Helper
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class WC_emerchantpay_Subscription_Helper {

	const META_INIT_RECURRING_ID             = '_init_recurring_id';
	const META_RECURRING_TERMINAL_TOKEN      = '_recurring_terminal_token';
	const META_INIT_RECURRING_FINISHED       = '_init_recurring_finished';
	const META_WCS_SUBSCRIPTION_TRIAL_LENGTH = '_subscription_trial_length';

	const WC_SUBSCRIPTIONS_PLUGIN_FILTER = 'woocommerce-subscriptions/woocommerce-subscriptions.php';

	const WC_SUBSCRIPTIONS_PLUGIN_URL  = 'https://woocommerce.com/products/woocommerce-subscriptions/';
	const WC_SUBSCRIPTIONS_ORDER_CLASS = 'WC_Subscriptions_Order';

	const WC_SUBSCRIPTION_STATUS_ACTIVE   = 'active';
	const WC_SUBSCRIPTION_STATUS_ON_HOLD  = 'on-hold';
	const WC_SUBSCRIPTION_STATUS_CANCELED = 'cancelled';

	/**
	 * Is $order_id a subscription?
	 *
	 * @param  int $order_id
	 * @return boolean
	 */
	public static function hasOrderSubscriptions( $order_id ) {
		if ( ! WC_emerchantpay_Order_Helper::isValidOrderId( $order_id ) ) {
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
	public static function isWCSubscriptionsInstalled() {
		return WC_emerchantpay_Helper::isWPPluginActive( self::WC_SUBSCRIPTIONS_PLUGIN_FILTER ) &&
			class_exists( self::WC_SUBSCRIPTIONS_ORDER_CLASS );
	}

	/**
	 * Retrieves a list with Subscriptions for a specific order
	 *
	 * @param $orderId
	 * @return array
	 */
	public static function getOrderSubscriptions( $orderId ) {
		if ( ! static::isWCSubscriptionsInstalled() ) {
			return array();
		}

		if ( ! WC_emerchantpay_Order_Helper::isValidOrderId( $orderId ) ) {
			return array();
		}

		// Also store it on the subscriptions being purchased or paid for in the order
		if ( function_exists( 'wcs_order_contains_subscription' ) && wcs_order_contains_subscription( $orderId ) ) {
			return wcs_get_subscriptions_for_order( $orderId );
		} elseif ( function_exists( 'wcs_order_contains_renewal' ) && wcs_order_contains_renewal( $orderId ) ) {
			return wcs_get_subscriptions_for_renewal_order( $orderId );
		}

		return array();
	}

	/**
	 * Saves additional data from the Init Recurring Response to the Order Subscriptions
	 *
	 * @param int       $orderId
	 * @param \stdClass $response
	 */
	public static function saveInitRecurringResponseToOrderSubscriptions( $orderId, $response ) {
		if ( ! WC_emerchantpay_Order_Helper::isValidOrderId( $orderId ) ) {
			return;
		}

		$subscriptions = static::getOrderSubscriptions( $orderId );

		foreach ( $subscriptions as $subscription ) {
			update_post_meta( $subscription->get_id(), self::META_INIT_RECURRING_ID, $response->unique_id );
		}

		WC_emerchantpay_Order_Helper::setOrderMetaData( $orderId, self::META_INIT_RECURRING_ID, $response->unique_id );
	}

	/**
	 * @param int $orderId
	 * @return mixed
	 */
	public static function getOrderInitRecurringIdMeta( $orderId ) {
		return WC_emerchantpay_Order_Helper::getOrderMetaData( $orderId, self::META_INIT_RECURRING_ID );
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
			if ( self::isSubscriptionProduct( $product['data'] ) ) {
				$has_subscriptions = true;
				break;
			}
		}

		return $has_subscriptions;
	}

	/**
	 * @param \WC_Product $product
	 * @return bool
	 */
	public static function isSubscriptionProduct( \WC_Product $product ) {
		return $product instanceof \WC_Product_Subscription ||
			   $product instanceof \WC_Product_Subscription_Variation;
	}

	/**
	 * Creates a Meta for the Order to indicate the Merchant has been changed for
	 * the Initial Recurring Order
	 *    + Init Recurring Payment
	 *    + Recurring Sale (if needed - without trial period)
	 *
	 * @param int $orderId
	 */
	public static function setInitRecurringOrderFinished( $orderId ) {
		WC_emerchantpay_Order_Helper::setOrderMetaData( $orderId, self::META_INIT_RECURRING_FINISHED, true );
	}

	/**
	 * Indicates if the Merchant has already been changed for the Init Recurring
	 *    + Init Recurring Payment
	 *    + Recurring Sale (if needed - without trial period)
	 * Will be used in Notification Handler for Init Recurring 3D (to ensure not charging the Merchant twice)
	 *
	 * @param string $orderId
	 * @return bool
	 */
	public static function isInitRecurringOrderFinished( $orderId ) {
		$orderFinished = WC_emerchantpay_Order_Helper::getOrderMetaData( $orderId, self::META_INIT_RECURRING_FINISHED );

		return ! empty( $orderFinished );
	}

	/**
	 * @param \stdClass $response
	 * @return bool
	 */
	public static function isInitGatewayResponseSuccessful( $response ) {
		$successfulStatuses = array(
			\Genesis\API\Constants\Transaction\States::APPROVED,
			\Genesis\API\Constants\Transaction\States::PENDING_ASYNC,
		);

		return isset( $response->unique_id ) &&
			isset( $response->status ) &&
			in_array( $response->status, $successfulStatuses );
	}

	/**
	 * @param string $transactionType
	 * @return bool
	 */
	public static function isInitRecurring( $transactionType ) {
		$initRecurringTxnTypes = array(
			\Genesis\API\Constants\Transaction\Types::INIT_RECURRING_SALE,
			\Genesis\API\Constants\Transaction\Types::INIT_RECURRING_SALE_3D,
		);

		return in_array( $transactionType, $initRecurringTxnTypes );
	}

	/**
	 * @param stdClass|ArrayObject $reconcile
	 * @return bool
	 */
	public static function isInitRecurringReconciliation( $reconcile ) {
		$payment_object = WC_emerchantpay_Genesis_Helper::getReconcilePaymentTransaction( $reconcile );

		$payment_transaction = $payment_object;
		if ( $payment_object instanceof \ArrayObject ) {
			unset( $payment_transaction );
			$payment_transaction = $payment_object[0];
		}

		return static::isInitRecurring( $payment_transaction->transaction_type );
	}

	/**
	 * @param int    $orderId
	 * @param string $terminalToken
	 * @return bool
	 */
	public static function saveTerminalTokenToOrderSubscriptions( $orderId, $terminalToken ) {
		if ( ! self::hasOrderSubscriptions( $orderId ) ) {
			return false;
		}

		$subscriptions = static::getOrderSubscriptions( $orderId );

		foreach ( $subscriptions as $subscription ) {
			update_post_meta( $subscription->get_id(), self::META_RECURRING_TERMINAL_TOKEN, $terminalToken );
		}

		WC_emerchantpay_Order_Helper::setOrderMetaData( $orderId, self::META_RECURRING_TERMINAL_TOKEN, $terminalToken );

		return count( $subscriptions ) > 0;
	}

	/**
	 * @param int $orderId
	 * @return mixed
	 */
	public static function getTerminalTokenMetaFromSubscriptionOrder( $orderId ) {
		return WC_emerchantpay_Order_Helper::getOrderMetaData( $orderId, self::META_RECURRING_TERMINAL_TOKEN );
	}

	/**
	 * Update WC Subscription based on the Genesis Transaction Response
	 *
	 * @param WC_Order $order  WC Order Object.
	 * @param string   $status WC Subscription Status.
	 * @param string   $note   Description.
	 */
	public static function updateOrderSubscriptionsStatus( $order, $status, $note = '' ) {
		if ( ! WC_emerchantpay_Order_Helper::isValidOrder( $order ) ) {
			return;
		}

		$subscriptions = static::getOrderSubscriptions( $order->get_id() );

		foreach ( $subscriptions as $subscription ) {
			static::updateSubscriptionStatus( $subscription, $status, $note );
		}
	}

	/**
	 * @param WC_Subscription $subscription
	 * @param string          $status
	 * @param string          $note
	 */
	public static function updateSubscriptionStatus( $subscription, $status, $note = '' ) {
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
}
