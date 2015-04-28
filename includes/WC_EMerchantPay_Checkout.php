<?php
/*
 * Copyright (C) 2015 eMerchantPay Ltd.
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
 * @author      eMerchantPay
 * @copyright   2015 eMerchantPay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

if (!defined( 'ABSPATH' )) {
    exit(0);
}

include_once 'genesis/vendor/autoload.php';

use \Genesis\Genesis as Genesis;

/**
 * Class WC_EMerchantPay_Checkout
 *
 * eMerchantPay Checkout implementation connecting
 * WooCommerce with Genesis Payment Gateway
 */
class WC_EMerchantPay_Checkout extends WC_Payment_Gateway {
    public function __construct()
    {
        $this->id           = 'emerchantpay';
        $this->method_title = __( 'eMerchantPay', 'woocommerce_emerchantpay' );
        $this->supports     = array( 'products', 'refunds' );
        $this->has_fields   = false;
        $this->icon         = plugins_url( 'assets/images/logo.png', plugin_dir_path( __FILE__ ) );

        $this->init_form_fields();
        $this->init_settings();

        // Notifications
        $this->notify_url = WC()->api_request_url( get_class( $this ) );

        foreach ( $this->settings as $name => $value ) {
            if ( ! isset( $this->$name ) ) {
                $this->$name = $value;
            }
        }

        // WPF Redirect
        add_action( 'woocommerce_receipt_' . $this->id, array( &$this, 'generate_form' ) );

        // Notification
        add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'process_notification' ) );

        // Save admin-panel options
        if ( defined( 'WOOCOMMERCE_VERSION' ) && version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
            add_action(
                'woocommerce_update_options_payment_gateways_' . $this->id,
                array( &$this, 'process_admin_options' )
            );
        }
        else {
            add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
        }

        // Credentials Setup
        $this->set_credentials( $this->settings );
    }

    /**
     * Admin Panel Field Definition
     *
     * @return void
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled'           => array(
                'title'   => __( 'Enable/Disable', 'woocommerce_emerchantpay' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable eMerchantPay Checkout', 'woocommerce_emerchantpay' ),
                'default' => 'no'
            ),
            'title'             => array(
                'title'       => __( 'Title:', 'woocommerce_emerchantpay' ),
                'type'        => 'text',
                'description' => __(
                    'This controls the title which the user sees during checkout.',
                    'woocommerce_emerchantpay'
                ),
                'desc_tip'    => true,
                'default'     => __( 'eMerchantPay', 'woocommerce_emerchantpay' )
            ),
            'description'       => array(
                'title'       => __( 'Description:', 'woocommerce_emerchantpay' ),
                'type'        => 'textarea',
                'description' => __(
                    'This controls the description which the user sees during checkout.',
                    'woocommerce_emerchantpay'
                ),
                'desc_tip'    => true,
                'default'     => __(
                    'Pay securely by Debit or Credit card, through eMerchantPay\'s Secure Gateway.<br/>You will be redirected to your secure server',
                    'woocommerce_emerchantpay'
                )
            ),
            'test_mode'         => array(
                'title'       => __( 'Test Mode', 'woocommerce_emerchantpay' ),
                'type'        => 'checkbox',
                'label'       => __( 'Use Genesis Staging', 'woocommerce' ),
                'description' => __(
                    'Selecting this would route all request to our test environment.<br/>NO Funds are being transferred!'
                ),
                'desc_tip'    => true,
            ),
            'transaction_types' => array(
                'title'       => __( 'Transaction Type', 'woocommerce_emerchantpay' ),
                'type'        => 'multiselect',
                'options'     => array(
                    'sale'   => __( 'Sale', 'woocommerce_emerchantpay' ),
                    'sale3d' => __( 'Sale 3D', 'woocommerce_emerchantpay' ),
                ),
                'description' => __( 'Sale - Authorize & Capture at the same time' ),
                'desc_tip'    => true,
            ),
            'api_credentials'   => array(
                'title'       => __( 'API Credentials', 'woocommerce' ),
                'type'        => 'title',
                'description' => sprintf(
                    __(
                        'Enter Genesis API Credentials below, in order to access the Gateway. If you forgot/lost your credentials, please %sget in touch%s with our technical support.',
                        'woocommerce'
                    ),
                    '<a href="mailto:tech-support@e-comprocessing.com">',
                    '</a>'
                ),
            ),
            'username'          => array(
                'title'       => __( 'Gateway Username', 'woocommerce_emerchantpay' ),
                'type'        => 'text',
                'description' => __( 'This is your Genesis username.' ),
                'desc_tip'    => true,
            ),
            'password'          => array(
                'title'       => __( 'Gateway Password', 'woocommerce_emerchantpay' ),
                'type'        => 'text',
                'description' => __( 'This is your Genesis password.', 'woocommerce_emerchantpay' ),
                'desc_tip'    => true,
            ),
        );
    }

    /**
     * Render the HTML for the Admin settings
     *
     * @return void
     */
    public function admin_options()
    {
        ?>
        <h3>
            <?php _e( 'eMerchantPay', 'woocommerce_emerchantpay' ); ?>
        </h3>
        <p>
            <?php _e(
                "eMerchantPay's Gateway works by sending your client, to our secure (PCI-DSS certified) server.",
                "woocommerce_emerchantpay"
            ); ?>
        </p>
        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table>
    <?php
    }

    public function process_return( $order_id )
    {
        $type = esc_sql( $_GET['type'] );

        if ( isset( $type ) && ! empty( $type ) ) {

            $order = new WC_Order( $order_id );

            switch ( $type ) {
                case 'success':
                    $order->update_status( 'completed' );
                    break;
                case 'failure':
                    $order->update_status( 'failed' );
                    wc_add_notice( 'Invalid data, please verify your data again!', 'error' );
                    break;
                case 'cancel':
                    $order->update_status( 'cancelled' );
                    break;
            }

            header( 'Location: ' . $order->get_view_order_url() );
        }

    }

    /**
     * Generate HTML Payment form
     *
     * @param $order_id
     *
     * @return string HTML form
     */
    public function process_payment( $order_id )
    {
        $order = new WC_Order( $order_id );

        $urls = array(
            // Notification URLs
            'notify'  => WC()->api_request_url( get_class( $this ) ),
            // Customer URLs
            'success' => $order->get_checkout_order_received_url(),
            'failure' => $order->get_cancel_order_url(),
            'cancel'  => $order->get_cancel_order_url(),
        );

        $genesis = new Genesis( 'WPF\Create' );

        $genesis
            ->request()
                ->setTransactionId( $this->generate_id( $order_id ) )
                ->setCurrency( $order->get_order_currency() )
                ->setAmount( $this->get_order_total() )
                ->setUsage(
                    sprintf( '%s Payment Transaction', get_bloginfo( 'name' ) )
                )
                ->setDescription( $this->get_item_description( $order ) )
                ->setCustomerEmail( $order->billing_email )
                ->setCustomerPhone( $order->billing_phone )
                ->setNotificationUrl( $urls['notify'] )
                ->setReturnSuccessUrl( $urls['success'] )
                ->setReturnFailureUrl( $urls['failure'] )
                ->setReturnCancelUrl( $urls['cancel'] )
                ->setBillingFirstName( $order->billing_first_name )
                ->setBillingLastName( $order->billing_last_name )
                ->setBillingAddress1( $order->billing_address_1 )
                ->setBillingAddress2( $order->billing_address_2 )
                ->setBillingZipCode( $order->billing_postcode )
                ->setBillingCity( $order->billing_city )
                ->setBillingState( $order->billing_state )
                ->setBillingCountry( $order->billing_country )
                ->setShippingFirstName( $order->shipping_first_name )
                ->setShippingLastName( $order->shipping_last_name )
                ->setShippingAddress1( $order->shipping_address_1 )
                ->setShippingAddress2( $order->shipping_address_2 )
                ->setShippingZipCode( $order->shipping_postcode )
                ->setShippingCity( $order->shipping_city )
                ->setShippingState( $order->shipping_state )
                ->setShippingCountry( $order->shipping_country );

        foreach ( $this->settings['transaction_types'] as $transaction_type ) {
            $genesis->request()->addTransactionType( $transaction_type );
        }

        $genesis->execute();

        if ( ! $genesis->response()->isSuccessful() ) {
            wc_add_notice(
                __(
                    'We were unable to process your order, please make sure all the data is correct or try again later.',
                    'woocommerce_emerchantpay'
                ),
                'error'
            );
        }
        else {
            update_post_meta(
                $order->id,
                '_genesis_checkout_id',
                $genesis->response()->getResponseObject()->unique_id
            );
        }

        $data = array();

        if ( isset( $genesis->response()->getResponseObject()->redirect_url ) ) {
            $data = array(
                'result'   => 'success',
                'redirect' => $genesis->response()->getResponseObject()->redirect_url
            );
        }

        return $data;
    }

    public function process_refund( $order_id, $amount = null, $reason = '' )
    {
        $order = new WC_Order( $order_id );

        if ( ! $order || ! $order->get_transaction_id() ) {
            return false;
        }

        $this->set_terminal_token( $order->id );

        $genesis = new Genesis( 'Financial\Refund' );

        $genesis
            ->request()
                ->setTransactionId( $this->generate_id( $order_id ) )
                ->setUsage( $reason )
                ->setRemoteIp( $_SERVER['REMOTE_ADDR'] )
                ->setReferenceId( $order->get_transaction_id() )
                ->setCurrency( $order->get_order_currency() )
                ->setAmount( $amount );

        $genesis->execute();

        $response = $genesis->response()->getResponseObject();

        if ( $genesis->response()->isSuccessful() ) {
            // Update the order with the refund id
            update_post_meta( $order->id, '_transaction_refund_id', $response->unique_id );

            $order->add_order_note(
                __( 'Refunded completed!', 'woocommerce_emerchantpay' ) . PHP_EOL . PHP_EOL .
                __( 'Refund ID: ', 'woocommerce_emerchantpay' ) . $response->unique_id . PHP_EOL .
                __( 'Refund amount: ', 'woocommerce_emerchantpay' ) . $genesis->response()->getFormattedAmount(
                ) . PHP_EOL
            );

            return true;
        }

        return false;
    }

    /**
     * Check Gateway Notification and alter order status
     *
     * @return void
     */
    public function process_notification()
    {
        @ob_clean();

        global $woocommerce;

        if ( isset( $_POST['wpf_unique_id'] ) && isset( $_POST['notification_type'] ) ) {
            $notification = new \Genesis\API\Notification();

            $notification->parseNotification( $_POST );

            if ( $notification->isAuthentic() ) {
                $genesis = new Genesis( 'WPF\Reconcile' );
                $genesis->request()->setUniqueId( $notification->getParsedNotification()->wpf_unique_id );
                $genesis->execute();

                $reconcile = $genesis->response()->getResponseObject()->payment_transaction;

                if ( $reconcile ) {
                    $order = $this->get_order_by_id( $genesis->response()->getResponseObject()->unique_id );

                    if ( ! $order instanceof WC_Order ) {
                        exit( 0 );
                    }

                    switch ( $reconcile->status ) {
                        case 'approved':
                            $amount = \Genesis\Utils\Currency::exponentToAmount(
                                $reconcile->amount,
                                $reconcile->currency
                            );

                            $order->add_order_note(
                                __( 'Payment through Genesis completed!', 'woocommerce_emerchantpay' ) . PHP_EOL .
                                __(
                                    'Payment ID:',
                                    'woocommerce_emerchantpay'
                                ) . PHP_EOL . $reconcile->unique_id . PHP_EOL .
                                __( 'Total:', 'woocommerce_emerchantpay' ) . ' ' . $amount
                            );

                            $order->payment_complete( $reconcile->unique_id );

                            // Update the order, just to be sure, sometimes transaction is not being set!
                            update_post_meta( $order->id, '_transaction_id', $reconcile->unique_id );

                            // Save the terminal token, through which we processed the transaction
                            update_post_meta( $order->id, '_transaction_terminal_token', $reconcile->terminal_token );

                            $woocommerce->cart->empty_cart();
                            break;
                        case 'declined':
                            $order->update_status( 'failure', $reconcile->technical_message );
                            break;
                        case 'error':
                            $order->update_status( 'error', $reconcile->technical_message );
                            break;
                        case 'refunded':
                            $order->update_status( 'refund', $reconcile->technical_message );
                    }

                    // Woo are OB everything up to this point.
                    // In order to respond, we have to exit!
                    $notification->renderResponse( true );
                }
            }
        }
    }

    /**
     * Generate transaction id, unique to this instance
     *
     * @param string $input
     *
     * @return array|string
     */
    private function generate_id( $input = '' )
    {
        // Why are we doing this?
        // We need to be sure that we have a unique string we can use as transaction id.
        // In order to do this, we use a few $_SERVER parameters to make some unique id.

        $unique = sprintf( '%s|%s|%s|%s', $_SERVER['SERVER_NAME'], microtime( true ), $_SERVER['REMOTE_ADDR'], $input );

        return strtolower( md5( $unique ) );
    }

    /**
     * Get WC_Order instance by UniqueId saved during checkout
     *
     * @param string $unique_id
     *
     * @return WC_Order|bool
     */
    private function get_order_by_id( $unique_id = '' )
    {
        $unique_id = esc_sql( trim( $unique_id ) );

        $query = new WP_Query(
            array(
                'post_status' => 'any',
                'post_type'   => 'shop_order',
                'meta_key'    => '_genesis_checkout_id',
                'meta_value'  => $unique_id
            )
        );

        if ( isset( $query->post->ID ) ) {
            return new WC_Order( $query->post->ID );
        }

        return false;
    }

    /**
     * Set the Terminal token associated with an order
     *
     * @param $order_id
     *
     * @return bool
     */
    private function set_terminal_token( $order_id )
    {
        $order_id = esc_sql( trim( $order_id ) );

        $token = get_post_meta( $order_id, '_transaction_terminal_token', true );

        if ( ! empty( $token ) ) {
            \Genesis\Config::setToken( $token );

            return true;
        }

        return false;
    }

    /**
     * Get the Order items in the following format:
     *
     * "%name% x%quantity%"
     *
     * @param WC_Order $order
     *
     * @return string
     */
    private function get_item_description( WC_Order $order )
    {
        $items = array();

        foreach ( $order->get_items() as $item ) {
            $items[] = sprintf( '%s x%d', $item['name'], reset( $item['item_meta']['_qty'] ) );
        }

        return implode( PHP_EOL, $items );
    }

    /**
     * Set the Genesis PHP Lib Credentials, based on the customer's
     * admin settings
     *
     * @param array $settings WooCommerce settings array
     *
     * @return void
     */
    private function set_credentials( $settings = array() )
    {
        \Genesis\Config::setUsername( $settings['username'] );
        \Genesis\Config::setPassword( $settings['password'] );

        \Genesis\Config::setEnvironment(
            ( isset( $settings['test_mode'] ) && $settings['test_mode'] ) ? 'sandbox' : 'production'
        );
    }
}