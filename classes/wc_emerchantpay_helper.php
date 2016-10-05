<?php
/*
 * Copyright (C) 2016 eMerchantPay Ltd.
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
 * @copyright   2016 eMerchantPay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

if (!defined( 'ABSPATH' )) {
    exit(0);
}

/**
 * eMerchantPay Helper Class
 *
 * @class   WC_eMerchantPay_Helper

 */
class WC_eMerchantPay_Helper
{
    const WP_NOTICE_TYPE_ERROR = 'error';

    /**
     * Setup and initialize this module
     */
    public function __construct()
    {
    }

    /**
     * Retrieves meta data for a specific order and key
     *
     * @param int $order_id
     * @param string $meta_key
     * @param bool $single
     * @return mixed
     */
    public static function getOrderMetaData($order_id, $meta_key, $single = true)
    {
        return get_post_meta($order_id, $meta_key, $single);
    }

    /**
     * Retrieves meta data formatted as amount for a specific order and key
     *
     * @param int $order_id
     * @param string $meta_key
     * @param bool $single
     * @return mixed
     */
    public static function getOrderAmountMetaData($order_id, $meta_key, $single = true)
    {
        return (double) static::getOrderMetaData($order_id, $meta_key, $single);
    }

    /**
     * Stores order meta data for a specific key
     *
     * @param int $order_id
     * @param string $meta_key
     * @param mixed $meta_value
     */
    public static function setOrderMetaData($order_id, $meta_key, $meta_value)
    {
        update_post_meta($order_id, $meta_key, $meta_value);
    }

    /**
     * Get payment gateway class by order data.
     *
     * @param int|WC_Order $order
     * @return WC_eMerchantPay_Method|bool
     */
    public static function getPaymentMethodInstanceByOrder($order)
    {
        return wc_get_payment_gateway_by_order($order);
    }

    /**
     * Creates an instance of a WooCommerce Order by Id
     *
     * @param int $order_id
     * @return WC_Order
     */
    public static function getOrderById($order_id)
    {
        $order_id = absint($order_id);
        return wc_get_order($order_id);
    }

    /**
     * Format the price with a currency symbol.
     *
     * @param float $price
     * @param int|WC_Order $order
     * @return string
     */
    public static function formatPrice($price, $order)
    {
        if ( ! is_object( $order ) ) {
            $order = static::getOrderById($order);
        }

        return wc_price(
            $price,
            array(
                'currency' => $order->get_order_currency()
            )
        );
    }

    /**
     * Determines if the SSL of the WebSite is enabled of not
     *
     * @return bool
     */
    public static function getStoreOverSecuredConnection()
    {
        return ( is_ssl() || get_option('woocommerce_force_ssl_checkout') == 'yes' );
    }

    /**
     * Retrieves the Client IP Address of the Customer
     * Used in the Direct (Hosted) Payment Method
     *
     * @return string
     */
    public static function getClientRemoteIpAddress()
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Get WC_Order instance by UniqueId saved during checkout
     *
     * @param string $unique_id
     *
     * @return WC_Order|bool
     */
    public static function getOrderByGatewayUniqueId( $unique_id, $meta_key )
    {
        $unique_id = esc_sql( trim( $unique_id ) );

        $query = new WP_Query(
            array(
                'post_status' => 'any',
                'post_type'   => 'shop_order',
                'meta_key'    => $meta_key,
                'meta_value'  => $unique_id
            )
        );

        if ( isset( $query->post->ID ) ) {
            return new WC_Order( $query->post->ID );
        }

        return false;
    }

    /**
     * Prints WordPress Notice HTML
     *
     * @param string $text
     * @param string $noticeType
     */
    public static function printWpNotice($text, $noticeType)
    {
        ?>
            <div class="<?php echo $noticeType;?>">
                <p><?php echo $text;?></p>
            </div>
        <?php
    }

    /**
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function getStringEndsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    /**
     * Compares the WooCommerce Version with the given one
     *
     * @param string $version
     * @param string $operator
     * @return bool|mixed
     */
    public static function getIsWooCommerceVersion($version, $operator)
    {
        if (defined( 'WOOCOMMERCE_VERSION' )) {
            return version_compare(WOOCOMMERCE_VERSION, $version, $operator);
        }

        return false;
    }
}
