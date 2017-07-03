<?php
/*
 * Copyright (C) 2017 eMerchantPay Ltd.
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
 * @author      eMerchantPay Ltd.
 * @copyright   2017 eMerchantPay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

if (!defined( 'ABSPATH' )) {
    exit(0);
}

/**
 * eMerchantPay Subscription Helper Class
 *
 * Class WC_eMerchantPay_Subscription_Helper
 */
class WC_eMerchantPay_Subscription_Helper
{
    const META_INIT_RECURRING_ID        = '_init_recurring_id';
    const META_RECURRING_TERMINAL_TOKEN = '_recurring_terminal_token';
    const META_INIT_RECURRING_FINISHED  = '_init_recurring_finished';

    const WC_SUBSCRIPTIONS_PLUGIN_FILTER = 'woocommerce-subscriptions/woocommerce-subscriptions.php';

    const WC_SUBSCRIPTIONS_PLUGIN_URL    = 'https://woocommerce.com/products/woocommerce-subscriptions/';
    const WC_SUBSCRIPTIONS_ORDER_CLASS   = 'WC_Subscriptions_Order';

    const WC_SUBSCRIPTION_STATUS_ACTIVE   = 'active';
    const WC_SUBSCRIPTION_STATUS_ON_HOLD  = 'on-hold';
    const WC_SUBSCRIPTION_STATUS_CANCELED = 'cancelled';

    /**
     * Is $order_id a subscription?
     * @param  int  $order_id
     * @return boolean
     */
    public static function hasOrderSubscriptions( $order_id )
    {
        if ( ! WC_eMerchantPay_Helper::isValidOrderId( $order_id )) {
            return false;
        }

        return
            function_exists( 'wcs_order_contains_subscription' ) &&
            (
                wcs_order_contains_subscription( $order_id ) ||
                wcs_is_subscription( $order_id ) ||
                wcs_order_contains_renewal( $order_id )
            );
    }

    /**
     * Detects if Subscriptions Extension for WC is installed
     * @return bool
     */
    public static function isWCSubscriptionsInstalled()
    {
        return
            WC_eMerchantPay_Helper::isWPPluginActive( self::WC_SUBSCRIPTIONS_PLUGIN_FILTER ) &&
            class_exists( self::WC_SUBSCRIPTIONS_ORDER_CLASS );
    }

    /**
     * Retrieves a list with Subscriptions for a specific order
     *
     * @param $orderId
     * @return array
     */
    public static function getOrderSubscriptions( $orderId )
    {
        if ( ! static::isWCSubscriptionsInstalled() ) {
            return array();
        }

        if ( ! WC_eMerchantPay_Helper::isValidOrderId( $orderId )) {
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
     * @param int $orderId
     * @param \stdClass $response
     */
    public static function saveInitRecurringResponseToOrderSubscriptions( $orderId, $response)
    {
        if ( ! WC_eMerchantPay_Helper::isValidOrderId( $orderId ) ) {
            return;
        }

        $subscriptions = static::getOrderSubscriptions( $orderId );

        foreach ( $subscriptions as $subscription ) {
            update_post_meta( $subscription->id, self::META_INIT_RECURRING_ID, $response->unique_id );
        }

        WC_eMerchantPay_Helper::setOrderMetaData( $orderId, self::META_INIT_RECURRING_ID, $response->unique_id );
    }

    /**
     * @param int $orderId
     * @return mixed
     */
    public static function getOrderInitRecurringIdMeta( $orderId )
    {
        return WC_eMerchantPay_Helper::getOrderMetaData( $orderId, self::META_INIT_RECURRING_ID);
    }

    /**
     * @return bool
     */
    public static function isCartValid()
    {
        $cart = WC()->cart->cart_contents;
        $hasProducts = false;
        $hasSubscriptions = false;

        foreach ( $cart AS $product ) {
            if ( !self::isSubscriptionProduct($product['data']) ) {
                if ( $hasSubscriptions ) {
                    return false;
                }

                $hasProducts = true;
                continue;
            }

            if ( $hasProducts ) {
                return false;
            }

            $hasSubscriptions = true;
            /** @var \WC_Product_Subscription|\WC_Product_Subscription_Variation $product['data'] */
            $fee = floatval( $product['data']->get_sign_up_fee() );
            if ($fee === 0.0) {
                return false;
            }
        }

        if ( $hasSubscriptions ) {
            return !$hasProducts;
        }

        return $hasProducts;
    }

    /**
     * @param \WC_Product $product
     * @return bool
     */
    public static function isSubscriptionProduct(\WC_Product $product)
    {
        return $product instanceof \WC_Product_Subscription ||
               $product instanceof \WC_Product_Subscription_Variation;
    }

    /**
     * Retrieves the Sign Up Fee for the Init Recurring Transactions
     *
     * @param WC_Order $order
     * @return float|null
     */
    public static function getOrderSubscriptionSignUpFee( $order )
    {
        if ( !static::isWCSubscriptionsInstalled() ) {
            return null;
        }

        if ( !WC_eMerchantPay_Helper::isValidOrder( $order ) ) {
            return null;
        }

        $signUpFee = WC_Subscriptions_Order::get_sign_up_fee( $order->id );

        /**
         * It is supposed to be only one item
         * P.S WC-Subscription-Plugin does not allow to add different subscriptions for the same cart
         */
        $recurringItems = $order->get_items();

        /**
         * This is needed, because
         *    WC_Subscriptions_Order::get_sign_up_fee
         * returns the Sign-Up fee for the Subscription (Qty = 1)
         * We need to calculate the real sign-up fee
         * @var string $key
         * @var \WC_Product_Subscription $recurringItem
         */
        foreach ( $recurringItems as $key => $recurringItem ) {
            $quantity = (int) $recurringItem->get_quantity();

            $signUpFee = (float) ($quantity * $signUpFee);
        }

        return $signUpFee > 0 ? $signUpFee : null;
    }

    /**
     * Retrieve the amount got the First Subscription.
     * Used after performing the Init Recurring Transaction
     *
     * @param WC_Order $order
     * @return null|float
     */
    public static function getOrderSubscriptionInitialPayment( $order )
    {
        if ( ! WC_eMerchantPay_Helper::isValidOrder( $order )) {
            return null;
        }

        $signUpFee = static::getOrderSubscriptionSignUpFee( $order );

        if ($signUpFee === null) {
            return null;
        }

        $amountToPay = $order->get_total() - $signUpFee;

        return $amountToPay > 0 ? $amountToPay : null;
    }

    /**
     * Creates a Meta for the Order to indicate the Merchant has been changed for
     * the Initial Recurring Order
     *    + Init Recurring Payment
     *    + Recurring Sale (if needed - without trial period)
     * @param int $orderId
     */
    public static function setInitRecurringOrderFinished( $orderId )
    {
        WC_eMerchantPay_Helper::setOrderMetaData( $orderId, self::META_INIT_RECURRING_FINISHED, true);
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
    public static function isInitRecurringOrderFinished( $orderId )
    {
        $orderFinished = WC_eMerchantPay_Helper::getOrderMetaData( $orderId, self::META_INIT_RECURRING_FINISHED);

        return !empty($orderFinished);
    }

    /**
     * @param int $orderId
     * @param string $terminalToken
     * @return bool
     */
    public static function saveTerminalTokenToOrderSubscriptions( $orderId, $terminalToken )
    {
        if (!WC_eMerchantPay_Subscription_Helper::hasOrderSubscriptions( $orderId )) {
            return false;
        }

        $subscriptions = static::getOrderSubscriptions( $orderId );

        foreach ( $subscriptions as $subscription ) {
            update_post_meta( $subscription->id, self::META_RECURRING_TERMINAL_TOKEN, $terminalToken );
        }

        WC_eMerchantPay_Helper::setOrderMetaData( $orderId, self::META_RECURRING_TERMINAL_TOKEN, $terminalToken);

        return count($subscriptions) > 0;
    }

    /**
     * @param int $orderId
     * @return mixed
     */
    public static function getTerminalTokenMetaFromSubscriptionOrder( $orderId )
    {
        return WC_eMerchantPay_Helper::getOrderMetaData($orderId, self::META_RECURRING_TERMINAL_TOKEN);
    }

    /**
     * @param WC_Order $order
     * @param string $status
     * @param string $note
     */
    public static function updateOrderSubscriptionsStatus($order, $status, $note = '')
    {
        if ( ! WC_eMerchantPay_Helper::isValidOrder( $order )) {
            return;
        }

        $subscriptions = static::getOrderSubscriptions( $order->id );

        foreach ($subscriptions as $subscription) {
            static::updateSubscriptionStatus($subscription, $status, $note);
        }
    }

    /**
     * @param WC_Subscription $subscription
     * @param string $status
     * @param string $note
     */
    public static function updateSubscriptionStatus($subscription, $status, $note = '')
    {
        $subscription->update_status( $status, $note );
    }
}
