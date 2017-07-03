<?php
/*
 * Plugin Name: WooCommerce eMerchantPay Payment Gateway Client
 * Description: Extend WooCommerce's Checkout options with eMerchantPay's Genesis Gateway
 * Text Domain: woocommerce-emerchantpay
 * Author: eMerchantPay
 * Version: 1.5.2
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/* there is no need to load the plugin if woocommerce is not active */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    if (!function_exists('woocommerce_emerchantpay_init')) {
        function woocommerce_emerchantpay_init()
        {
            if (!class_exists('WC_Payment_Gateway')) {
                return;
            }

            $translation = load_plugin_textdomain(
                'woocommerce-emerchantpay', false, basename(__DIR__) . DIRECTORY_SEPARATOR . 'languages');

            if (!$translation) {
                error_log('Unable to load language file for locale: ' . get_locale());
            }

            include dirname(__FILE__) . '/libraries/genesis/vendor/autoload.php';

            include dirname(__FILE__) . '/includes/wc_emerchantpay_checkout.php';

            include dirname(__FILE__) . '/includes/wc_emerchantpay_direct.php';

            /**
             * Add the eMerchantPay Gateway to WooCommerce's
             * list of installed gateways
             *
             * @param $methods Array of existing Payment Gateways
             *
             * @return array $methods Appended Payment Gateway
             */
            if (!function_exists('woocommerce_add_emerchantpay_gateway')) {
                function woocommerce_add_emerchantpay_gateway($methods)
                {
                    array_push($methods, 'WC_eMerchantPay_Checkout');
                    array_push($methods, 'WC_eMerchantPay_Direct');

                    return $methods;
                }
            }

            add_filter('woocommerce_payment_gateways', 'woocommerce_add_emerchantpay_gateway');
        }
    }

    add_action('plugins_loaded', 'woocommerce_emerchantpay_init', 0);

}
