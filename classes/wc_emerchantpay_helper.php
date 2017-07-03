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
 * eMerchantPay Helper Class
 *
 * @class   WC_eMerchantPay_Helper

 */
class WC_eMerchantPay_Helper
{
    const WP_NOTICE_TYPE_ERROR  = 'error';
    const WP_NOTICE_TYPE_NOTICE = 'notice';

    /**
     * Setup and initialize this module
     */
    public function __construct()
    {
    }

    /**
     * @return bool
     */
    public static function isGetRequest()
    {
        return $_SERVER['REQUEST_METHOD'] == 'GET';
    }

    /**
     * @return bool
     */
    public static function isPostRequest()
    {
        return $_SERVER['REQUEST_METHOD'] == 'POST';
    }

    /**
     * Detects if a WordPress plugin is active
     *
     * @param string $plugin_filter
     * @return bool
     */
    public static function isWPPluginActive($plugin_filter)
    {
        if ( ! function_exists( 'is_plugin_active' )) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return is_plugin_active( $plugin_filter );
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
     * Retrieves meta data for a specific order and key
     *
     * @param int $order_id
     * @param string $meta_key
     * @param float $default
     * @return float
     */
    public static function getFloatOrderMetaData($order_id, $meta_key, $default = 0.0)
    {
        $value = static::getOrderMetaData($order_id, $meta_key);

        return empty($value) ? $default : (float) $value;
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
     * @return WC_Order|null
     */
    public static function getOrderById($order_id)
    {
        if ( ! static::isValidOrderId( $order_id ) ) {
            return null;
        }

        return wc_get_order( (int) $order_id );
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
        if ( ! static::isValidOrder( $order ) ) {
            $order = static::getOrderById($order);
        }

        if ( $order === null ) {
            return (string) $price;
        }

        return wc_price(
            $price,
            array(
                'currency' => $order->get_order_currency()
            )
        );
    }


    /**
     * Returns a formatted money with currency (non HTML)
     * @param float|string $amount
     * @param WC_Order $order
     * @return string
     */
    public static function formatMoney($amount, $order)
    {
        $amount = (float) $amount;
        $money = number_format($amount, 2, '.', '');

        if ( ! static::isValidOrder( $order ) ) {
            return $money;
        }

        return "$money {$order->get_order_currency()}";
    }

    /**
     * Determines if the SSL of the WebSite is enabled of not
     *
     * @return bool
     */
    public static function getStoreOverSecuredConnection()
    {
        return ( is_ssl() && get_option('woocommerce_force_ssl_checkout') == 'yes' );
    }

    /**
     * Retrieves the WP Site Url
     *
     * @return null|string
     */
    protected function getWPSiteUrl()
    {
        if ( ! function_exists( 'get_site_url' ) ) {
            return null;
        }

        $siteUrl = get_site_url();

        return $siteUrl ?: null;
    }

    /**
     * Retrieves the Host name from the WP Site
     * @return null|string
     */
    protected function getWPSiteHostName()
    {
        if ( ! function_exists( 'parse_url' )) {
            return null;
        }

        $siteUrl = static::getWPSiteUrl();

        if ($siteUrl === null) {
            return null;
        }

        $urlParams = parse_url($siteUrl);

        if (is_array($urlParams) && array_key_exists('host', $urlParams)) {
            return $urlParams['host'];
        }

        return null;
    }

    /**
     * Retrieves the Host IP from the WP Site
     * @return null|string
     */
    protected function getWPSiteHostIPAddress()
    {
        if ( ! function_exists( 'gethostbyname' )) {
            return null;
        }

        $siteHostName = static::getWPSiteHostName();

        if ($siteHostName === null) {
            return null;
        }

        return gethostbyname($siteHostName);
    }

    /**
     * Retrieves the Client IP Address of the Customer
     * Used in the Direct (Hosted) Payment Method
     *
     * @return string
     */
    public static function getClientRemoteIpAddress()
    {
        $remoteAddress = $_SERVER['REMOTE_ADDR'];

        if (empty($remoteAddress)) {
            $remoteAddress = static::getWPSiteHostIPAddress();
        }

        return $remoteAddress ?: '127.0.0.1';
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
        if (defined('WOOCOMMERCE_VERSION')) {
            return version_compare(WOOCOMMERCE_VERSION, $version, $operator);
        }

        return false;
    }

    /**
     * Builds full Request Class Name by Transaction Type
     * @param string $transactionType
     * @return string
     */
    public static function getTransactionTypeRequestClassName($transactionType)
    {
        $requestClassName = \Genesis\Utils\Common::snakeCaseToCamelCase(
            str_replace('3d', '3D', $transactionType)
        );
        $recurringInnerNamespace =
            strpos($transactionType, 'recurring') !== false
                ? "Recurring\\"
                : '';
        return "Financial\\Cards\\{$recurringInnerNamespace}{$requestClassName}";
    }

    /**
     * Constructs a Gateway Request Instance depending on the selected Txn Type
     * @param string $transactionType
     * @return \Genesis\Genesis
     */
    public static function getGatewayRequestByTxnType($transactionType)
    {
        $apiRequestClassName = WC_eMerchantPay_Helper::getTransactionTypeRequestClassName(
            $transactionType
        );

        return new \Genesis\Genesis($apiRequestClassName);
    }

    /**
     * @param \stdClass $response
     * @return bool
     */
    public static function isInitGatewayResponseSuccessful($response)
    {
        $successfulStatuses = array(
            \Genesis\API\Constants\Transaction\States::APPROVED,
            \Genesis\API\Constants\Transaction\States::PENDING_ASYNC
        );

        return
            isset($response->unique_id) &&
            isset($response->status) &&
            in_array($response->status, $successfulStatuses);
    }

    /**
     * @param \Exception|string $exception
     * @return \WP_Error
     */
    public static function getWPError($exception)
    {
        if ($exception instanceof \Exception) {
            return new \WP_Error(
                $exception->getCode() ?: 999,
                $exception->getMessage()
            );
        }

        return new \WP_Error(999, $exception);
    }

    /**
     * @param string $transactionType
     * @return bool
     */
    public static function isInitRecurring($transactionType)
    {
        $initRecurringTxnTypes = array(
            \Genesis\API\Constants\Transaction\Types::INIT_RECURRING_SALE,
            \Genesis\API\Constants\Transaction\Types::INIT_RECURRING_SALE_3D
        );

        return in_array($transactionType, $initRecurringTxnTypes);
    }

    /**
     * @param \stdClass $reconcile
     * @return \stdClass
     */
    public static function getReconcilePaymentTransaction($reconcile)
    {
        return
            isset($reconcile->payment_transaction)
                ? $reconcile->payment_transaction
                : $reconcile;
    }
    /**
     * @param stdClass $reconcile
     * @return bool
     */
    public static function isReconcileInitRecurring($reconcile)
    {
        $transactionType = static::getReconcilePaymentTransaction($reconcile)->transaction_type;

        return static::isInitRecurring($transactionType);
    }

    /**
     * @param \stdClass $response
     * @return \Genesis\API\Constants\Transaction\States
     */
    public static function getGatewayStatusInstance($response)
    {
        return new \Genesis\API\Constants\Transaction\States($response->status);
    }

    /**
     * @param WC_Order $order
     * @return null|float
     */
    public static function getOrderRefundableAmount( $order )
    {
        $orderTransactionAmount = WC_eMerchantPay_Helper::getFloatOrderMetaData(
            $order->id,
            WC_eMerchantPay_Method::META_ORDER_TRANSACTION_AMOUNT
        );

        $refundedAmount = WC_eMerchantPay_Helper::getFloatOrderMetaData(
            $order->id,
            WC_eMerchantPay_Method::META_REFUNDED_AMOUNT
        );

        $refundableAmount = $orderTransactionAmount - $refundedAmount;

        return $refundableAmount > 0 ? $refundableAmount : null;
    }

    /**
     * @param WC_Order $order
     * @param float|string $amount
     */
    public static function addRefundedAmountToOrder( $order, $amount)
    {
        $refundedAmount = WC_eMerchantPay_Helper::getFloatOrderMetaData(
            $order->id,
            WC_eMerchantPay_Method::META_REFUNDED_AMOUNT
        );

        $refundedAmount = $refundedAmount + (float) $amount;

        WC_eMerchantPay_Helper::setOrderMetaData(
            $order->id,
            WC_eMerchantPay_Method::META_REFUNDED_AMOUNT,
            $refundedAmount
        );
    }

    /**
     * Writes a message / Exception to the error log
     * @param \Exception|string $exception
     */
    public static function logException($exception)
    {
        error_log(
            $exception instanceof \Exception
                ? $exception->getMessage()
                : $exception
        );
    }

    /**
     * @param int $orderId
     * @return bool
     */
    public static function isValidOrderId( $orderId )
    {
        return (int) $orderId > 0;
    }

    /**
     * @param WC_Order $order
     * @return bool
     */
    public static function isValidOrder( $order )
    {
        return is_object( $order ) && ($order instanceof WC_Order);
    }

    /**
     * @param bool $isRecurring
     * @return string
     */
    public static function getPaymentTransactionUsage($isRecurring)
    {
        return sprintf(
            $isRecurring ? '%s Recurring Transaction' : '%s Payment Transaction',
            get_bloginfo( 'name' )
        );
    }

    /**
     * Makes a check if all the requirements of Genesis Lib are verified
     *
     * @return true|WP_Error (True -> verified; WP_Error -> Exception Message)
     */
    public static function checkGenesisRequirementsVerified()
    {
        try {
            \Genesis\Utils\Requirements::verify();

            return true;
        } catch (\Exception $exception) {
            return static::getWPError($exception);
        }
    }

    /**
     * @param array $arr
     * @param string $key
     * @return array|mixed
     */
    public static function getArrayItemsByKey($arr, $key, $default = array())
    {
        if (!is_array($arr)) {
            return $default;
        }

        if (!array_key_exists($key, $arr)) {
            return $default;
        }

        return $arr[$key];
    }

    /**
     * Retrieves the consumer's user id
     *
     * @return int
     */
    public static function getCurrentUserId()
    {
        return get_current_user_id();
    }

    /**
     * @param int $length
     * @return string
     */
    public static function getCurrentUserIdHash($length = 20)
    {
        $userId = self::getCurrentUserId();

        $userHash = $userId > 0 ? sha1($userId) : WC_eMerchantPay_Method::generateTransactionId();

        return substr($userHash, 0, $length);
    }
}
