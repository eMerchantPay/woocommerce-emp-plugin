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
 * @author      eMerchantPay Ltd.
 * @copyright   2015 eMerchantPay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

if (!defined( 'ABSPATH' )) {
    exit(0);
}

/**
 * eMerchantPay Checkout
 *
 * @class   WC_eMerchantPay_Checkout
 * @extends WC_Payment_Gateway
 */
class WC_eMerchantPay_Checkout extends WC_Payment_Gateway
{
    /**
     * Language domain
     */
    const LANG_DOMAIN = 'woocommerce-emerchantpay';

    /**
     * Setup and initialize this module
     */
    public function __construct()
    {
        $this->id           = 'emerchantpay';
        $this->supports     = array('products', 'refunds');
        $this->icon         = plugins_url( 'assets/images/logo.png', plugin_dir_path( __FILE__ ) );
        $this->has_fields   = false;

        // Public title/description
        $this->title        = $this->get_option('title');
        $this->description  = $this->get_option('description');

        // Admin title/description
        $this->method_title         = __( 'eMerchantPay', self::LANG_DOMAIN );
        $this->method_description   = __(
            'eMerchantPay\'s Gateway works by sending your client, to our secure (PCI-DSS certified) server.',
            self::LANG_DOMAIN
        );

        // Register the method callback
        add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'callback_handler' ) );

        // Save admin-panel options
        if ( defined( 'WOOCOMMERCE_VERSION' ) && version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
            add_action(
                'woocommerce_update_options_payment_gateways_' . $this->id,
                array( $this, 'process_admin_options' )
            );
        }
        else {
            add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ) );
        }

        // Initialize admin options
        $this->init_form_fields();

        // Fetch module settings
        $this->init_settings();
    }

    /**
     * Admin Panel Field Definition
     *
     * @return void
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled'       => array(
                'type'    => 'checkbox',
                'title'   => __( 'Enable/Disable', self::LANG_DOMAIN ),
                'label'   => __( 'Enable eMerchangPay Checkout', self::LANG_DOMAIN ),
                'default' => 'no'
            ),
            'title'         => array(
                'type'        => 'text',
                'title'       => __( 'Title:', self::LANG_DOMAIN ),
                'description' => __(
                    'Title for this payment method, during customer checkout.',
                    self::LANG_DOMAIN
                ),
                'default'     => $this->method_title,
                'desc_tip'    => true
            ),
            'description'   => array(
                'type'        => 'textarea',
                'title'       => __( 'Description:', self::LANG_DOMAIN ),
                'description' => __(
                    'Text describing this payment method to the customer, during checkout.',
                    self::LANG_DOMAIN
                ),
                'default'     => __(
                    'Pay safely through eMerchantPay\'s Secure Gateway.' .
                    self::LANG_DOMAIN
                ),
                'desc_tip'    => true
            ),
            'transaction_types' => array(
                'type'        => 'multiselect',
                'title'       => __( 'Transaction Type', self::LANG_DOMAIN ),
                'options'     => array(
                    'sale'      => __('Sale', self::LANG_DOMAIN),
                    'sale3d'    => __('Sale 3D-Secure', self::LANG_DOMAIN),
                ),
                'description' => __( 'Select transaction type for the payment transaction' ),
                'desc_tip'    => true,
            ),
            'checkout_language' => array(
                'type'      => 'select',
                'title'     => __( 'Checkout Language', self::LANG_DOMAIN ),
                'options'   => array(
                    'en' => __(\Genesis\API\Constants\i18n::EN, self::LANG_DOMAIN),
                    'es' => __(\Genesis\API\Constants\i18n::ES, self::LANG_DOMAIN),
                    'fr' => __(\Genesis\API\Constants\i18n::FR, self::LANG_DOMAIN),
                    'de' => __(\Genesis\API\Constants\i18n::DE, self::LANG_DOMAIN),
                    'it' => __(\Genesis\API\Constants\i18n::IT, self::LANG_DOMAIN),
                    'ja' => __(\Genesis\API\Constants\i18n::JA, self::LANG_DOMAIN),
                    'zh' => __(\Genesis\API\Constants\i18n::ZH, self::LANG_DOMAIN),
                    'ar' => __(\Genesis\API\Constants\i18n::AR, self::LANG_DOMAIN),
                    'pt' => __(\Genesis\API\Constants\i18n::PT, self::LANG_DOMAIN),
                    'tr' => __(\Genesis\API\Constants\i18n::TR, self::LANG_DOMAIN),
                    'ru' => __(\Genesis\API\Constants\i18n::RU, self::LANG_DOMAIN),
                    'bg' => __(\Genesis\API\Constants\i18n::BG, self::LANG_DOMAIN),
                    'hi' => __(\Genesis\API\Constants\i18n::HI, self::LANG_DOMAIN),
                ),
                'description' => __( 'Select language for the customer UI on the remote server' ),
                'desc_tip'    => true,
            ),
            'api_credentials'   => array(
                'type'        => 'title',
                'title'       => __( 'API Credentials', self::LANG_DOMAIN ),
                'description' => sprintf(__(
                        'Enter Genesis API Credentials below, in order to access the Gateway.' .
                        'If you don\'t have credentials, %sget in touch%s with our technical support.',
                        self::LANG_DOMAIN
                    ),
                    '<a href="mailto:tech-support@emerchantpay.com">',
                    '</a>'
                ),
            ),
            'test_mode'         => array(
                'type'        => 'checkbox',
                'title'       => __( 'Test Mode', self::LANG_DOMAIN ),
                'label'       => __( 'Use test (staging) environment', self::LANG_DOMAIN ),
                'description' => __(
                    'Selecting this would route all requests through our test environment.' .
                    '<br/>' .
                    'NO Funds WILL BE transferred!',
                    self::LANG_DOMAIN
                ),
                'desc_tip'    => true,
            ),
            'username'          => array(
                'type'        => 'text',
                'title'       => __( 'Username', self::LANG_DOMAIN ),
                'description' => __( 'This is your Genesis username.' ),
                'desc_tip'    => true,
            ),
            'password'          => array(
                'type'        => 'text',
                'title'       => __( 'Password', self::LANG_DOMAIN ),
                'description' => __( 'This is your Genesis password.', self::LANG_DOMAIN ),
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
            <?php echo $this->method_title; ?>
        </h3>
        <p>
            <?php echo $this->method_description; ?>
        </p>
        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table>
        <?php
    }

    /**
     * Handle URL callback
     *
     * @return void
     */
    public function callback_handler()
    {
        @ob_clean();

        $this->set_credentials(
            $this->settings
        );

        // Handle Customer returns
        $this->handle_return();

        // Handle Gateway notifications
        $this->handle_notification();

        exit(0);
    }

    /**
     * Handle customer return and update their order status
     *
     * @return void
     */
    private function handle_return( )
    {
        if ( isset($_GET['act']) && isset($_GET['oid']) ) {
            $order_id   = absint( $_GET['oid'] );
            $order      = wc_get_order( $order_id );

            if ($this->get_one_time_token($order_id) == '|CLEAR|') {
                wp_redirect(wc_get_page_permalink('cart'));
            }
            else {
                $this->set_one_time_token($order_id, '|CLEAR|');

                switch (esc_sql($_GET['act'])) {
                    case 'success':
                        $notice = __(
                            'Your payment has been completed successfully.',
                            self::LANG_DOMAIN
                        );

                        wc_add_notice($notice, 'success');
                        break;
                    case 'failure':
                        $status = __(
                            'Payment has been declined!',
                            self::LANG_DOMAIN
                        );

                        $order->update_status('on-hold', $status);

                        $notice = __(
                            'Your payment has been declined, please check your data and try again',
                            self::LANG_DOMAIN
                        );

                        wc_add_notice($notice, 'error');
                        break;
                    case 'cancel':
                        $note = __(
                            'The customer cancelled their payment session',
                            self::LANG_DOMAIN
                        );

                        $order->cancel_order($note);
                        break;
                }

                header('Location: ' . $order->get_view_order_url());
            }
        }
    }

    /**
     * Handle gateway notifications
     *
     * @return void
     */
    private function handle_notification()
    {
        if ( isset( $_POST['wpf_unique_id'] ) ) {
            try {
                $notification = new \Genesis\API\Notification($_POST);

                if ($notification->isAuthentic()) {
                    $notification->initReconciliation();

                    $reconcile = $notification->getReconciliationObject()->payment_transaction;

                    if ($reconcile) {
                        $order = $this->get_order_by_id(
                            $notification->getReconciliationObject()->unique_id
                        );

                        if (!$order instanceof WC_Order) {
                            throw new \Exception('Invalid WooCommerce Order!');
                        }

                        switch ($reconcile->status) {
                            case \Genesis\API\Constants\Transaction\States::APPROVED:
                                $order->add_order_note(
                                    __('Payment transaction has been approved!', self::LANG_DOMAIN)
                                    . PHP_EOL . PHP_EOL .
                                    __('Id:', self::LANG_DOMAIN) . ' ' . $reconcile->unique_id
                                    . PHP_EOL . PHP_EOL .
                                    __('Total:', self::LANG_DOMAIN) . ' ' . $reconcile->amount . ' ' . $reconcile->currency
                                );

                                $order->payment_complete($reconcile->unique_id);
                                break;
                            case \Genesis\API\Constants\Transaction\States::DECLINED:
                                $order->add_order_note(
                                    __('Payment transaction has been declined!', self::LANG_DOMAIN)
                                );

                                $order->update_status('failed', $reconcile->technical_message);
                                break;
                            case \Genesis\API\Constants\Transaction\States::ERROR:
                                $order->add_order_note(
                                    __('Payment transaction returned an error!', self::LANG_DOMAIN)
                                );

                                $order->update_status('failed', $reconcile->technical_message);
                                break;
                            case \Genesis\API\Constants\Transaction\States::REFUNDED:
                                $order->add_order_note(
                                    __('Payment transaction has been refunded!', self::LANG_DOMAIN)
                                );

                                $order->update_status('refunded', $reconcile->technical_message);
                                break;
                        }

                        // Update the order, just to be sure, sometimes transaction is not being set!
                        update_post_meta($order->id, '_transaction_id', $reconcile->unique_id);

                        // Save the terminal token, through which we processed the transaction
                        update_post_meta($order->id, '_transaction_terminal_token', $reconcile->terminal_token);

                        $notification->renderResponse();
                    }
                }
            } catch(\Exception $e) {
                header('HTTP/1.1 403 Forbidden');
            }
        }
    }

    /**
     * Initiate Checkout session
     *
     * @param $order_id
     *
     * @return string HTML form
     */
    public function process_payment( $order_id )
    {
        $order = new WC_Order( absint($order_id)  );

        $urls = array(
            // Notification URLs
            'notify'  => WC()->api_request_url( get_class( $this ) ),
            // Customer URLs
            'success' => $this->append_to_url(
                WC()->api_request_url( get_class( $this ) ),
                 array (
                     'act'  => 'success',
                     'oid'  => $order_id,
                 )
            ),
            'failure' => $this->append_to_url(
                WC()->api_request_url( get_class( $this ) ),
                array (
                    'act'  => 'failure',
                    'oid'  => $order_id,
                )
            ),
            'cancel' => $this->append_to_url(
                WC()->api_request_url( get_class( $this ) ),
                array (
                    'act'  => 'cancel',
                    'oid'  => $order_id,
                )
            )
        );

        try {
            $this->set_credentials(
                $this->settings
            );

            $genesis = new \Genesis\Genesis( 'WPF\Create' );

            $genesis
                ->request()
                    ->setTransactionId(
                        $this->generate_id( $order_id )
                    )
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

            if (isset($this->settings['checkout_language'])) {
                $genesis->request()->setLanguage(
                    $this->settings['checkout_language']
                );
            }

            $genesis->execute();

            $response = $genesis->response()->getResponseObject();

            // Save the Checkout Id
            update_post_meta($order->id, '_genesis_checkout_id', $response->unique_id);

            // Create One-time token to prevent redirect abuse
            $this->set_one_time_token($order_id, $this->generate_id());

            return array(
                'result'   => 'success',
                'redirect' => $response->redirect_url
            );
        } catch (\Exception $e) {
            $error_message = __(
                'We were unable to process your order!' . '<br/>' .
                'Please double check your data and try again.',
                self::LANG_DOMAIN
            );

            wc_add_notice(
                @isset($genesis->response()->getResponseObject()->message)
                     ? $genesis->response()->getResponseObject()->message
                     : $error_message,
                'error'
            );

            return false;
        }
    }

    /**
     * Process Refund transaction
     *
     * @param int    $order_id
     * @param null   $amount
     * @param string $reason
     *
     * @return bool
     */
    public function process_refund( $order_id, $amount = null, $reason = '' )
    {
        $order = new \WC_Order( $order_id );

        if ( !$order || !$order->get_transaction_id() ) {
            return false;
        }

        try {
            $this->set_credentials(
                $this->settings
            );

            $this->set_terminal_token( $order->id );

            $genesis = new \Genesis\Genesis('Financial\Refund');

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

            // Update the order with the refund id
            update_post_meta( $order->id, '_transaction_refund_id', $response->unique_id );

            $order->add_order_note(
                __( 'Refund completed!',
                    self::LANG_DOMAIN ) . PHP_EOL . PHP_EOL .
                __( 'Id: ',
                    self::LANG_DOMAIN ) . $response->unique_id . PHP_EOL .
                __( 'Refunded amount: ',
                    self::LANG_DOMAIN ) . $response->amount . PHP_EOL
            );

            return true;
        } catch(\Exception $e) {
            return new \WP_Error($e->getCode(), $e->getMessage());
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
        // Try to gather more entropy

        $unique = sprintf('|%s|%s|%s|%s|', @$_SERVER['REMOTE_ADDR'], microtime( true ), @$_SERVER['HTTP_USER_AGENT'], $input );

        return strtolower( md5( $unique . md5(uniqid(mt_rand(), true)) ) );
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
        $order_id = absint( $order_id );

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
     * Append parameters to a base URL
     *
     * @param $base
     * @param $args
     *
     * @return string
     */
    private function append_to_url($base, $args)
    {
        if(!is_array($args)) {
            return $base;
        }

        $info = parse_url($base);

        $query = array();

        if(isset($info['query'])) {
            parse_str($info['query'], $query);
        }

        if(!is_array($query)) {
            $query = array();
        }

        $params = array_merge($query, $args);

        $result = '';

        if($info['scheme']) {
            $result .= $info['scheme'] . ':';
        }

        if($info['host']) {
            $result .= '//' . $info['host'];
        }

        if($info['path']) {
            $result .= $info['path'];
        }

        if($params) {
            $result .= '?' . http_build_query($params);
        }

        return $result;
    }

    /**
     * Get a one-time token
     *
     * @param      $order_id
     *
     * @return mixed|string
     */
    private function get_one_time_token($order_id)
    {
        return get_post_meta( $order_id, '_checkout_return_token', true );
    }

    /**
     * Set one-time token
     *
     * @param $order_id
     */
    private function set_one_time_token($order_id, $value)
    {
        update_post_meta($order_id, '_checkout_return_token', $value);
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
        \Genesis\Config::setEndpoint('emerchantpay');

        \Genesis\Config::setUsername( $settings['username'] );
        \Genesis\Config::setPassword( $settings['password'] );

        \Genesis\Config::setEnvironment(
            ( isset( $settings['test_mode'] ) && $settings['test_mode'] === 'yes' ) ? 'sandbox' : 'production'
        );
    }
}
