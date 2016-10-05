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
 * eMerchantPay Base Method
 *
 * @class   WC_eMerchantPay_Method
 * @extends WC_Payment_Gateway
 */
abstract class WC_eMerchantPay_Method extends WC_Payment_Gateway
{
    /**
     * Order Meta Constants
     */
    const META_TRANSACTION_ID             = '_transaction_id';
    const META_TRANSACTION_TYPE           = '_transaction_type';
    const META_TRANSACTION_TERMINAL_TOKEN = '_transaction_terminal_token';
    const META_TRANSACTION_CAPTURE_ID     = '_transaction_capture_id';
    const META_TRANSACTION_REFUND_ID      = '_transaction_refund_id';
    const META_TRANSACTION_VOID_ID        = '_transaction_void_id';
    const META_CAPTURED_AMOUNT            = '_captured_amount';
    const META_CHECKOUT_RETURN_TOKEN      = '_checkout_return_token';

    /**
     * Method Setting Keys
     */
    const SETTING_KEY_ENABLED        = 'enabled';
    const SETTING_KEY_TITLE          = 'title';
    const SETTING_KEY_DESCRIPTION    = 'description';
    const SETTING_KEY_TEST_MODE      = 'test_mode';
    const SETTING_KEY_USERNAME       = 'username';
    const SETTING_KEY_PASSWORD       = 'password';
    const SETTING_KEY_ALLOW_CAPTURES = 'allow_captures';
    const SETTING_KEY_ALLOW_REFUNDS  = 'allow_refunds';

    /**
     * A List with the Available WC Order Statuses
     */
    const ORDER_STATUS_PENDING    = 'pending';
    const ORDER_STATUS_PROCESSING = 'processing';
    const ORDER_STATUS_COMPLETED  = 'completed';
    const ORDER_STATUS_REFUNDED   = 'refunded';
    const ORDER_STATUS_FAILED     = 'failed';
    const ORDER_STATUS_CANCELLED  = 'cancelled';
    const ORDER_STATUS_ON_HOLD    = 'on-hold';

    /**
     * Language domain
     */
    public static $LANG_DOMAIN = 'woocommerce-emerchantpay';

    /**
     * Payment Method Code
     *
     * @var null|string
     */
    protected static $method_code = null;

    /**
     * Holds the Meta Key used to extract the checkout Transaction
     *   - Checkout Method -> WPF Unique Id
     *   - Direct Method   -> Transaction Unique Id
     *
     * @return string
     */
    abstract protected function getCheckoutTransactionIdMetaKey();

    /**
     * Retrieves a list with the Required Api Settings
     *
     * @return array
     */
    protected function getRequiredApiSettingKeys()
    {
        return array(
            self::SETTING_KEY_USERNAME,
            self::SETTING_KEY_PASSWORD
        );
    }

    /**
     * Determines if the a post notification is a valida Gateway Notification
     *
     * @param array $postValues
     * @return bool
     */
    protected function getIsValidNotification($postValues)
    {
        return
            isset($postValues['signature']);
    }

    /**
     * Registers Helper Classes for both method classes
     *
     * @return void
     */
    public static function registerHelpers()
    {
        if (!class_exists('WC_eMerchantPay_Helper')) {
            require_once 'wc_emerchantpay_helper.php';
        }
    }

    /**
     * Registers all custom actions used in the payment methods
     *
     * @return void
     */
    protected function registerCustomActions()
    {
        add_action(
            'woocommerce_order_item_add_action_buttons',
            array(
                $this,
                'displayActionButtons'
            )
        );

        add_action(
            'woocommerce_admin_order_totals_after_total',
            array(
                $this,
                'displayAdminOrderAfterTotals'
            )
        );

        add_action(
            'woocommerce_admin_order_totals_after_refunded',
            array(
                $this,
                'displayAdminOrderAfterRefunded'
            )
        );

        add_action(
            'admin_notices',
            array(
                $this,
                'admin_notices'
            )
        );
    }

    /**
     * Determines if the user is currently reviewing the module settings page
     * Used to display Admin Notices
     *
     * @return bool
     */
    protected function getIsModuleSettingsPage()
    {
        return
            isset($_GET['page']) && ($_GET['page'] == 'wc-settings') &&
            isset($_GET['tab']) && ($_GET['tab'] == 'checkout') &&
            isset($_GET['section']) && WC_eMerchantPay_Helper::getStringEndsWith($_GET['section'], $this->id);
    }

    /**
     * Event Handler for displaying Admin Notices
     *
     * @return bool
     */
    public function admin_notices()
    {
        if (!$this->getIsModuleSettingsPage()) {
            return false;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if ($this->enabled !== 'yes') {
                return false;
            }
        } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $methodEnablePostParamName = $this->getMethodAdminSettingPostParamName(
                self::SETTING_KEY_ENABLED
            );

            if (!isset($_POST[$methodEnablePostParamName]) || ($_POST[$methodEnablePostParamName] !== '1')) {
                return false;
            }
        } else {
            return false;
        }


        $areApiCredentialsDefined = true;
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            foreach ($this->getRequiredApiSettingKeys() as $requiredApiSetting) {
                if (empty($this->settings[$requiredApiSetting])) {
                    $areApiCredentialsDefined = false;
                }
            }
        } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
            foreach ($this->getRequiredApiSettingKeys() as $requiredApiSetting) {
                $apiSettingPostParamName = $this->getMethodAdminSettingPostParamName(
                    $requiredApiSetting
                );

                if (!isset($_POST[$apiSettingPostParamName]) || empty($_POST[$apiSettingPostParamName])) {
                    $areApiCredentialsDefined = false;

                    break;
                }
            }
        }

        if (!$areApiCredentialsDefined) {
            WC_eMerchantPay_Helper::printWpNotice(
                'You need to set the API credentials in order to use this payment method!',
                WC_eMerchantPay_Helper::WP_NOTICE_TYPE_ERROR
            );
        }

        return true;
    }

    /**
     * Builds the complete input post param for a wooCommerce payment method
     *
     * @param string $settingKey
     * @return string
     */
    protected function getMethodAdminSettingPostParamName($settingKey)
    {
        return sprintf(
            'woocommerce_%s_%s',
            $this->id,
            $settingKey
        );
    }

    /**
     * Setup and initialize this module
     */
    public function __construct()
    {
        $this->id = static::$method_code;

        $this->supports = array(
            'products',
            'captures',
            'refunds',
            'voids'
        );

        $this->icon         = plugins_url( "assets/images/{$this->id}.png", plugin_dir_path( __FILE__ ) );
        $this->has_fields   = true;

        // Public title/description
        $this->title        = $this->get_option(self::SETTING_KEY_TITLE);
        $this->description  = $this->get_option(self::SETTING_KEY_DESCRIPTION);

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

        $this->registerCustomActions();

        // Initialize admin options
        $this->init_form_fields();

        // Fetch module settings
        $this->init_settings();
    }

    /**
     * Check if a gateway supports a given feature.
     *
     * @return bool
     */
    public function supports( $feature ) {
        $isFeatureSupported = parent::supports($feature);

        if ($feature == 'captures') {
            return
                $isFeatureSupported &&
                $this->settings[self::SETTING_KEY_ALLOW_CAPTURES] == 'yes';
        } elseif ($feature == 'refunds') {
            return
                $isFeatureSupported &&
                $this->settings[self::SETTING_KEY_ALLOW_REFUNDS] == 'yes';
        }

        return $isFeatureSupported;
    }

    /**
     * Wrapper of wc_get_template to relate directly to s4wc
     *
     * @param       string $template_name
     * @param       array $args
     * @return      string
     */
    protected function fetchTemplate( $template_name, $args = array() ) {
        $default_path = dirname(plugin_dir_path( __FILE__ )) . '/templates/';

        echo wc_get_template( $template_name, $args, '', $default_path );
    }

    /**
     * Retrieves a translated text by key
     *
     * @param string $text
     * @return string|void
     */
    public static function getTranslatedText($text)
    {
        return __($text, static::$LANG_DOMAIN);
    }

    /**
     * Registers all custom static actions
     * Used for processing backend transactions
     *
     * @return void
     */
    public static function registerStaticActions()
    {
        add_action(
            'wp_ajax_' . static::$method_code . '_capture',
            array(
                __CLASS__,
                'capture'
            )
        );

        add_action(
            'wp_ajax_' . static::$method_code . '_void',
            array(
                __CLASS__,
                'void'
            )
        );
    }

    /**
     * Processes a capture transaction to the gateway
     *
     * @param array $data
     * @return stdClass|WP_Error
     */
    protected static function process_capture($data)
    {
        $order_id = $data['order_id'];
        $reason = $data['reason'];
        $amount = $data['amount'];

        $order = WC_eMerchantPay_Helper::getOrderById($order_id);

        $payment_gateway = WC_eMerchantPay_Helper::getPaymentMethodInstanceByOrder($order);

        if ( !$order || !$order->get_transaction_id() ) {
            return new \WP_Error(999, "No order exists with the specified reference id");
        }
        try {
            $payment_gateway::set_credentials(
                $payment_gateway->settings
            );

            $payment_gateway->set_terminal_token( $order );

            $genesis = new \Genesis\Genesis('Financial\Capture');

            $genesis
                ->request()
                    ->setTransactionId(
                        $payment_gateway::generateTransactionId( $order_id )
                    )
                    ->setUsage(
                        $reason
                    )
                    ->setRemoteIp(
                        WC_eMerchantPay_Helper::getClientRemoteIpAddress()
                    )
                    ->setReferenceId(
                        $order->get_transaction_id()
                    )
                    ->setCurrency(
                        $order->get_order_currency()
                    )
                    ->setAmount(
                        $amount
                    );

            $genesis->execute();

            $response = $genesis->response()->getResponseObject();

            if ($response->status == \Genesis\API\Constants\Transaction\States::APPROVED) {
                // Update the order with the refund id
                WC_eMerchantPay_Helper::setOrderMetaData($order_id, self::META_TRANSACTION_CAPTURE_ID, $response->unique_id);
                $totalCapturedAmount = WC_eMerchantPay_Helper::getOrderAmountMetaData($order_id, self::META_CAPTURED_AMOUNT);
                $totalCapturedAmount += $amount;
                WC_eMerchantPay_Helper::setOrderMetaData($order_id, self::META_CAPTURED_AMOUNT, $totalCapturedAmount);

                $order->add_order_note(
                    static::getTranslatedText('Payment Captured!') . PHP_EOL . PHP_EOL .
                    static::getTranslatedText('Id: ') . $response->unique_id . PHP_EOL .
                    static::getTranslatedText('Captured amount: ') . $response->amount . PHP_EOL
                );

                return $response;
            } else {
                return new \WP_Error(999, $response->technical_message);
            }
        } catch(\Exception $e) {
            return new \WP_Error($e->getCode(), $e->getMessage());
        }
    }

    /**
     * Event Handler for executing capture transaction
     * Called in templates/admin/order/dialogs/capture.php
     *
     * @return void
     */
    public static function capture()
    {
        ob_start();

        check_ajax_referer( 'order-item', 'security' );

        if ( ! current_user_can( 'edit_shop_orders' ) ) {
            die(-1);
        }

        $order_id = absint( $_POST['order_id'] );

        if (!static::getCanCaptureOrder($order_id)) {
            wp_send_json_error(
                array(
                    'error' => static::getTranslatedText('You cannot capture non-authorize transaction!')
                )
            );
            return;
        }

        $capture_amount  = wc_format_decimal( sanitize_text_field( $_POST['capture_amount'] ) );
        $capture_reason  = sanitize_text_field( $_POST['capture_reason'] );

        $captured_amount = WC_eMerchantPay_Helper::getOrderAmountMetaData($order_id, self::META_CAPTURED_AMOUNT);

        try {
            // Validate that the refund can occur
            $order        = WC_eMerchantPay_Helper::getOrderById($order_id);
            $max_capture  = wc_format_decimal( $order->get_total() - $captured_amount );

            if ( ! $capture_amount || $max_capture < $capture_amount || 0 > $capture_amount ) {
                throw new exception( static::getTranslatedText('Invalid capture amount'));
            }

            // Create the refund object
            $gatewayResponse = static::process_capture(
                array(
                    'order_id'   => $order_id,
                    'amount'     => $capture_amount,
                    'reason'     => $capture_reason,
                )
            );

            if ( is_wp_error( $gatewayResponse ) ) {
                throw new Exception( $gatewayResponse->get_error_message() );
            }

            if ($gatewayResponse->status != \Genesis\API\Constants\Transaction\States::APPROVED) {
                throw new Exception(
                    $gatewayResponse->message
                        ?: $gatewayResponse->technical_message
                );
            }

            $captured_amount += (double) $capture_amount;

            $capture_left = $order->get_total() - $captured_amount;

            $response_data = array(
                'gateway' => $gatewayResponse,
                'form'    => array(
                    'capture' => array(
                        'total' => array(
                            'amount' => $captured_amount,
                            'formatted' => WC_eMerchantPay_Helper::formatPrice(
                                $captured_amount,
                                $order
                            )
                        ),
                        'total_available' => array(
                            'amount' => $capture_left > 0 ? $capture_left : "",
                            'formatted' => WC_eMerchantPay_Helper::formatPrice(
                                $capture_left,
                                $order
                            )
                        )
                    )
                )
            );

            wp_send_json_success( $response_data );
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'error' => $e->getMessage() ) );
        }
    }

    /**
     * Event Handler for executing void transaction
     * Called in templates/admin/order/dialogs/void.php
     *
     * @return bool
     */
    public static function void()
    {
        ob_start();

        check_ajax_referer( 'order-item', 'security' );

        if ( ! current_user_can( 'edit_shop_orders' ) ) {
            die(-1);
        }

        $order_id = absint( $_POST['order_id'] );

        if (!static::getCanVoidOrder($order_id)) {
            wp_send_json_error(
                array(
                    'error' => static::getTranslatedText('You cannot void non-authorize payment or already captured payment!')
                )
            );
            return false;
        }

        $void_reason  = sanitize_text_field( $_POST['void_reason'] );

        try {
            // Validate that the refund can occur
            $order        = WC_eMerchantPay_Helper::getOrderById($order_id);

            $payment_gateway = WC_eMerchantPay_Helper::getPaymentMethodInstanceByOrder($order);

            if ( !$order || !$order->get_transaction_id() ) {
                return false;
            }

            $payment_gateway::set_credentials(
                $payment_gateway->settings
            );

            $payment_gateway->set_terminal_token($order);

            $void = new \Genesis\Genesis('Financial\Void');

            $void
                ->request()
                    ->setTransactionId(
                        $payment_gateway::generateTransactionId( $order_id )
                    )
                    ->setUsage(
                        $void_reason
                    )
                    ->setRemoteIp(
                        WC_eMerchantPay_Helper::getClientRemoteIpAddress()
                    )
                    ->setReferenceId(
                        $order->get_transaction_id()
                    );

            try {
                $void->execute();
                // Create the refund object
                $gatewayResponse = $void->response()->getResponseObject();
            } catch (\Exception $e) {
                $gatewayResponse = new \WP_Error(
                    $e->getCode(),
                    $e->getMessage()
                );
            }

            if ( is_wp_error( $gatewayResponse ) ) {
                throw new Exception( $gatewayResponse->get_error_message() );
            }

            if ($gatewayResponse->status == \Genesis\API\Constants\Transaction\States::APPROVED) {
                // Update the order with the refund id
                WC_eMerchantPay_Helper::setOrderMetaData(
                    $order_id,
                    self::META_TRANSACTION_VOID_ID,
                    $gatewayResponse->unique_id
                );

                $order->add_order_note(
                    static::getTranslatedText('Payment Voided!') . PHP_EOL . PHP_EOL .
                    static::getTranslatedText('Id: ') . $gatewayResponse->unique_id
                );

                $order->update_status(
                    self::ORDER_STATUS_CANCELLED,
                    $gatewayResponse->technical_message
                );
            } else {
                throw new Exception(
                    $gatewayResponse->message
                        ?: $gatewayResponse->technical_message
                );
            }

            $response_data = array(
                'gateway' => $gatewayResponse,
            );

            wp_send_json_success( $response_data );
            return true;
        } catch ( Exception $e ) {
            wp_send_json_error( array( 'error' => $e->getMessage() ) );
            return false;
        }
    }

    /**
     * Admin Action Handler for displaying custom code after order totals
     *
     * @param int $order_id
     * @return void
     */
    public function displayAdminOrderAfterTotals($order_id)
    {
        $order = WC_eMerchantPay_Helper::getOrderById($order_id);

        if ($order->payment_method != $this->id) {
            return;
        }

        if (static::getCanCaptureOrder($order)) {
            $this->fetchTemplate(
                'admin/order/totals/capture.php',
                array(
                    'payment_method' => $this,
                    'order'          => $order
                )
            );
        }

        $this->fetchTemplate(
            'admin/order/totals/common.php',
            array(
                'payment_method' => $this,
                'order'          => $order
            )
        );
    }

    /**
     * Admin Action Handler for displaying custom code after order refund totals
     *
     * @param int $order_id
     * @return void
     */
    public function displayAdminOrderAfterRefunded($order_id)
    {
        $order = WC_eMerchantPay_Helper::getOrderById($order_id);

        if ($order->payment_method != $this->id) {
            return;
        }

        if (static::getCanVoidOrder($order)) {
            $this->fetchTemplate(
                'admin/order/totals/void.php',
                array(
                    'payment_method' => $this,
                    'order' => $order
                )
            );
        }
    }

    /**
     * Custom Admin Action for displaying additional order action buttons
     *
     * @param $order
     * @return void
     */
    public function displayActionButtons($order)
    {
        if ($order->payment_method != $this->id) {
            return;
        }

        $canCaptureOrder = static::getCanCaptureOrder($order);
        $canVoidOrder = static::getCanVoidOrder($order);

        if (!$canCaptureOrder && !$canVoidOrder) {
            return;
        }

        if ($canCaptureOrder) {
            $this->fetchTemplate(
                'admin/order/actions/capture.php',
                array(
                    'payment_method' => $this,
                    'order'          => $order
                )
            );
        }

        if ($canVoidOrder) {
            $this->fetchTemplate(
                'admin/order/actions/void.php',
                array(
                    'order'          => $order,
                    'payment_method' => $this
                )
            );
        }

        $this->fetchTemplate(
            'admin/order/dialogs/common.php',
            array(
                'order'          => $order,
                'payment_method' => $this
            )
        );

        if ($canCaptureOrder) {
            $this->fetchTemplate(
                'admin/order/dialogs/capture.php',
                array(
                    'order'          => $order,
                    'payment_method' => $this
                )
            );
        }

        if ($canVoidOrder) {
            $this->fetchTemplate(
                'admin/order/dialogs/void.php',
                array(
                    'order'          => $order,
                    'payment_method' => $this
                )
            );
        }
    }

    /**
     * Check if this gateway is enabled and all dependencies are fine.
     * Disable the plugin if dependencies fail.
     *
     * @access      public
     * @return      bool
     */
    public function is_available() {
        if ( $this->enabled !== 'yes' ) {
            return false;
        }

        foreach ($this->getRequiredApiSettingKeys() as $requiredApiSettingKey) {
            if (empty($this->settings[$requiredApiSettingKey])) {
                return false;
            }
        }

        return $this->is_applicable();
    }

    /**
     * Determines if the Payment Method can be used for the configured Store
     *  - Store Checkouts
     *  - SSL
     *  - etc
     *
     * Will be extended in the Direct Method
     * @return bool
     */
    protected function is_applicable()
    {
        return true;
    }

    /**
     * Admin Panel Field Definition
     *
     * @return void
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            self::SETTING_KEY_ENABLED => array(
                'type'    => 'checkbox',
                'title'   => static::getTranslatedText('Enable/Disable'),
                'label'   => static::getTranslatedText('Enable Payment Method'),
                'default' => 'no'
            ),
            self::SETTING_KEY_TITLE => array(
                'type'        => 'text',
                'title'       => static::getTranslatedText('Title:'),
                'description' => static::getTranslatedText('Title for this payment method, during customer checkout.'),
                'default'     => $this->method_title,
                'desc_tip'    => true
            ),
            self::SETTING_KEY_DESCRIPTION => array(
                'type'        => 'textarea',
                'title'       => static::getTranslatedText('Description:'),
                'description' => static::getTranslatedText('Text describing this payment method to the customer, during checkout.'),
                'default'     => 'Pay safely through eMerchantPay\'s Secure Gateway.',
                'desc_tip'    => true
            ),
            'api_credentials'   => array(
                'type'        => 'title',
                'title'       => static::getTranslatedText('API Credentials'),
                'description' =>
                    sprintf(
                        static::getTranslatedText(
                            'Enter Genesis API Credentials below, in order to access the Gateway.' .
                            'If you don\'t have credentials, %sget in touch%s with our technical support.'
                        ),
                        '<a href="mailto:tech-support@emerchantpay.com">',
                        '</a>'
                    ),
            ),
            self::SETTING_KEY_TEST_MODE => array(
                'type'        => 'checkbox',
                'title'       => static::getTranslatedText('Test Mode'),
                'label'       => static::getTranslatedText('Use test (staging) environment'),
                'description' => static::getTranslatedText(
                    'Selecting this would route all requests through our test environment.' .
                    '<br/>' .
                    'NO Funds WILL BE transferred!'
                ),
                'desc_tip'    => true,
                'default'     => 'yes'
            ),
            self::SETTING_KEY_ALLOW_CAPTURES => array(
                'type'        => 'checkbox',
                'title'       => static::getTranslatedText('Enable Captures'),
                'label'       => static::getTranslatedText('Enable / Disable Captures on the Order Preview Page'),
                'description' => static::getTranslatedText('Decide whether to Enable / Disable online Captures on the Order Preview Page.') .
                                 "<br /> <br />" .
                                 static::getTranslatedText('It depends on how the genesis gateway is configured'),
                'default'     => 'yes',
                'desc_tip'    => true,
            ),
            self::SETTING_KEY_ALLOW_REFUNDS => array(
                'type'        => 'checkbox',
                'title'       => static::getTranslatedText('Enable Refunds'),
                'label'       => static::getTranslatedText('Enable / Disable Refunds on the Order Preview Page'),
                'description' => static::getTranslatedText('Decide whether to Enable / Disable online Refunds on the Order Preview Page.') .
                                 "<br /> <br />" .
                                 static::getTranslatedText('It depends on how the genesis gateway is configured'),
                'default'     => 'yes',
                'desc_tip'    => true,
            ),
            self::SETTING_KEY_USERNAME => array(
                'type'        => 'text',
                'title'       => static::getTranslatedText('Username'),
                'description' => static::getTranslatedText('This is your Genesis username.'),
                'desc_tip'    => true
            ),
            self::SETTING_KEY_PASSWORD => array(
                'type'        => 'text',
                'title'       => static::getTranslatedText('Password'),
                'description' => static::getTranslatedText( 'This is your Genesis password.'),
                'desc_tip'    => true
            )
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

        static::set_credentials(
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
    protected function handle_return( )
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
                        $notice = static::getTranslatedText(
                            'Your payment has been completed successfully.'
                        );

                        wc_add_notice($notice, 'success');
                        break;
                    case 'failure':
                        $status = static::getTranslatedText(
                            'Payment has been declined!'
                        );

                        $notice = static::getTranslatedText(
                            'Your payment has been declined, please check your data and try again'
                        );

                        $order->cancel_order($notice);

                        wc_add_notice($notice, 'error');
                        break;
                    case 'cancel':
                        $note = static::getTranslatedText(
                            'The customer cancelled their payment session'
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
    protected function handle_notification()
    {
        if (!$this->getIsValidNotification($_POST)) {
            return;
        }

        try {
            $notification = new \Genesis\API\Notification($_POST);

            if ($notification->isAuthentic()) {
                $notification->initReconciliation();

                $reconcile = $notification->getReconciliationObject();

                if ($reconcile) {
                    $order = WC_eMerchantPay_Helper::getOrderByGatewayUniqueId(
                        $reconcile->unique_id,
                        $this->getCheckoutTransactionIdMetaKey()
                    );

                    if (!$order instanceof WC_Order || $order->payment_method != $this->id) {
                        throw new \Exception('Invalid WooCommerce Order!');
                    }

                    $this->updateOrderStatus($order, $reconcile);
                    $notification->renderResponse();
                }
            }
        } catch(\Exception $e) {
            header('HTTP/1.1 403 Forbidden');
        }
    }

    /**
     * Returns a list with data used for preparing a request to the gateway
     *
     * @param WC_Order $order
     * @return array
     */
    protected function populateGateRequestData($order)
    {
        return
            array(
                'transaction_id'   => static::generateTransactionId( $order->id ),
                'amount'           => $this->get_order_total(),
                'currency'         => $order->get_order_currency(),
                'usage'            => sprintf( '%s Payment Transaction', get_bloginfo( 'name' ) ),
                'description'      => $this->get_item_description( $order ),
                'customer_email'   => $order->billing_email,
                'customer_phone'   => $order->billing_phone,
                // URLs
                'notification_url'  => WC()->api_request_url( get_class( $this ) ),
                'return_success_url' => $this->get_return_url($order),
                'return_failure_url' => $this->append_to_url(
                    WC()->api_request_url( get_class( $this ) ),
                    array (
                        'act'  => 'failure',
                        'oid'  => $order->id,
                    )
                ),
                //Billing
                'billing' => array(
                    'first_name' => $order->billing_first_name,
                    'last_name'  => $order->billing_last_name,
                    'address1'   => $order->billing_address_1,
                    'address2'   => $order->billing_address_2,
                    'zip_code'   => $order->billing_postcode,
                    'city'       => $order->billing_city,
                    'state'      => $order->billing_state,
                    'country'    => $order->billing_country
                ),
                //Shipping
                'shipping' => array(
                    'first_name' => $order->shipping_first_name,
                    'last_name'  => $order->shipping_last_name,
                    'address1'   => $order->shipping_address_1,
                    'address2'   => $order->shipping_address_2,
                    'zip_code'   => $order->shipping_postcode,
                    'city'       => $order->shipping_city,
                    'state'      => $order->shipping_state,
                    'country'    => $order->shipping_country
                )
            );
    }

    /**
     * Determines if the user can process a specific Backend Transaction
     *   - Capture
     *   - Refund
     *   - Void
     *
     * @param int|WC_Order $order
     * @return bool
     */
    protected static function getCanProcessRefBackendTran($order, $backendTranType)
    {
        if ( ! is_object( $order ) ) {
            $order = WC_eMerchantPay_Helper::getOrderById($order);
        }

        $payedOrderStatuses = array(
            self::ORDER_STATUS_PROCESSING,
            self::ORDER_STATUS_COMPLETED
        );

        if (!in_array($order->get_status(), $payedOrderStatuses)) {
            return false;
        }

        $orderTransactionType = WC_eMerchantPay_Helper::getOrderMetaData(
            $order->id,
            self::META_TRANSACTION_TYPE
        );

        $authorizeTransactions = array(
            \Genesis\API\Constants\Transaction\Types::AUTHORIZE,
            \Genesis\API\Constants\Transaction\Types::AUTHORIZE_3D
        );

        $isOrderTranTypeAuthorize = in_array(
            $orderTransactionType,
            $authorizeTransactions
        );

        $capture_unique_id = WC_eMerchantPay_Helper::getOrderMetaData(
            $order->id,
            self::META_TRANSACTION_CAPTURE_ID
        );

        $void_unique_id = WC_eMerchantPay_Helper::getOrderMetaData(
            $order->id,
            self::META_TRANSACTION_VOID_ID
        );

        switch ($backendTranType) {
            case \Genesis\API\Constants\Transaction\Types::CAPTURE:
                return
                    $isOrderTranTypeAuthorize &&
                    (empty($void_unique_id));
                break;

            case \Genesis\API\Constants\Transaction\Types::REFUND:
                $refundableGatewayTransactionTypes = array(
                     \Genesis\API\Constants\Transaction\Types::SALE,
                     \Genesis\API\Constants\Transaction\Types::SALE_3D
                );

                return
                    ($isOrderTranTypeAuthorize && !empty($capture_unique_id) && empty($void_unique_id)) ||
                    (!$isOrderTranTypeAuthorize && in_array($orderTransactionType, $refundableGatewayTransactionTypes));
                break;

            case \Genesis\API\Constants\Transaction\Types::VOID:
                return
                    $isOrderTranTypeAuthorize &&
                    empty($capture_unique_id) &&
                    empty($void_unique_id);
                break;

            default:
                return false;
        }
    }

    /**
     * Determines if the user can process a Capture Transaction
     *
     * @param int|WC_Order $order
     * @return bool
     */
    protected static function getCanCaptureOrder($order)
    {
        return static::getCanProcessRefBackendTran(
            $order,
            \Genesis\API\Constants\Transaction\Types::CAPTURE
        );
    }

    /**
     * Determines if the user can process a Refund Transaction
     *
     * @param int|WC_Order $order
     * @return bool
     */
    protected static function getCanRefundOrder($order)
    {
        return static::getCanProcessRefBackendTran(
            $order,
            \Genesis\API\Constants\Transaction\Types::REFUND
        );
    }

    /**
     * Determines if the user can process a Void Transaction
     *
     * @param int|WC_Order $order
     * @return bool
     */
    protected static function getCanVoidOrder($order)
    {
        return static::getCanProcessRefBackendTran(
            $order,
            \Genesis\API\Constants\Transaction\Types::VOID
        );
    }

    /**
     * Updates the Order Status and creates order note
     *
     * @param WC_Order $order
     * @param stdClass $gatewayResponseObject
     * @throws Exception
     * @return void
     */
    protected function updateOrderStatus($order, $gatewayResponseObject)
    {
        if (!$order instanceof WC_Order) {
            throw new \Exception('Invalid WooCommerce Order!');
        }

        switch ($gatewayResponseObject->status) {
            case \Genesis\API\Constants\Transaction\States::APPROVED:
                $payment_transaction_id =
                    isset($gatewayResponseObject->payment_transaction)
                        ? $gatewayResponseObject->payment_transaction->unique_id
                        : $gatewayResponseObject->unique_id;

                if ($order->get_status() == self::ORDER_STATUS_PENDING) {
                    $order->add_order_note(
                        static::getTranslatedText('Payment transaction has been approved!')
                        . PHP_EOL . PHP_EOL .
                        static::getTranslatedText('Id:') . ' ' . $payment_transaction_id
                        . PHP_EOL . PHP_EOL .
                        static::getTranslatedText('Total:') . ' ' . $gatewayResponseObject->amount . ' ' . $gatewayResponseObject->currency
                    );
                }

                $order->payment_complete($payment_transaction_id);

                $transaction_type =
                    isset($gatewayResponseObject->payment_transaction)
                        ? $gatewayResponseObject->payment_transaction->transaction_type
                        : $gatewayResponseObject->transaction_type;

                WC_eMerchantPay_Helper::setOrderMetaData(
                    $order->id,
                    self::META_TRANSACTION_TYPE,
                    $transaction_type
                );

                if (isset($gatewayResponseObject->payment_transaction)) {
                    $terminal_token =
                        isset($gatewayResponseObject->payment_transaction->terminal_token)
                            ? $gatewayResponseObject->payment_transaction->terminal_token
                            : null;
                } else {
                    $terminal_token =
                        isset($gatewayResponseObject->terminal_token)
                            ? $gatewayResponseObject->terminal_token
                            : null;
                }

                if (!empty($terminal_token)) {
                    WC_eMerchantPay_Helper::setOrderMetaData(
                        $order->id,
                        self::META_TRANSACTION_TERMINAL_TOKEN,
                        $terminal_token
                    );
                }
                break;
            case \Genesis\API\Constants\Transaction\States::DECLINED:
                $order->add_order_note(
                    static::getTranslatedText('Payment transaction has been declined!')
                );

                $order->update_status(
                    self::ORDER_STATUS_FAILED,
                    $gatewayResponseObject->technical_message
                );
                break;
            case \Genesis\API\Constants\Transaction\States::ERROR:
                $order->add_order_note(
                    static::getTranslatedText('Payment transaction returned an error!')
                );

                $order->update_status(
                    self::ORDER_STATUS_FAILED,
                    $gatewayResponseObject->technical_message
                );
                break;
            case \Genesis\API\Constants\Transaction\States::REFUNDED:
                $order->add_order_note(
                    static::getTranslatedText('Payment transaction has been refunded!')
                );

                $order->update_status(
                    self::ORDER_STATUS_REFUNDED,
                    $gatewayResponseObject->technical_message
                );
                break;
        }

        // Update the order, just to be sure, sometimes transaction is not being set!
        //WC_eMerchantPay_Helper::setOrderMetaData($order->id, self::META_TRANSACTION_ID, $gatewayResponseObject->unique_id);

        // Save the terminal token, through which we processed the transaction
        //WC_eMerchantPay_Helper::setOrderMetaData($order->id, self::META_TRANSACTION_TERMINAL_TOKEN, $gatewayResponseObject->terminal_token);
    }

    /**
     * Set the Terminal token associated with an order
     *
     * @param WC_Order $order
     *
     * @return bool
     */
    protected function set_terminal_token( $order )
    {
        return false;
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
        $order = WC_eMerchantPay_Helper::getOrderById( $order_id );

        if ( !$order || !$order->get_transaction_id() ) {
            return false;
        }

        if (!static::getCanRefundOrder($order)) {
            return new \WP_Error(
                999,
                'You cannot refund this payment, because the payment is not captured yet or ' .
                'the gateway does not support refunds for this transaction type!'
            );
        }

        try {
            static::set_credentials(
                $this->settings
            );

            $this->set_terminal_token( $order );

            $genesis = new \Genesis\Genesis('Financial\Refund');

            $reference_transaction_id = WC_eMerchantPay_Helper::getOrderMetaData(
                $order_id,
                self::META_TRANSACTION_CAPTURE_ID
            );

            if (empty($reference_transaction_id)) {
                $reference_transaction_id = $order->get_transaction_id();
            }

            if (empty($reference_transaction_id)) {
                return new \WP_Error(
                    999,
                    'You cannot refund a payment, which has not been captured yet!'
                );
            }

            if ($order->get_status() == self::ORDER_STATUS_PENDING) {
                return new \WP_Error(
                    999,
                    'You cannot refund a payment, because the order status is not yet updated from the payment gateway!'
                );
            }

            $genesis
                ->request()
                    ->setTransactionId(
                        static::generateTransactionId( $order_id )
                    )
                    ->setUsage(
                        $reason
                    )
                    ->setRemoteIp(
                        WC_eMerchantPay_Helper::getClientRemoteIpAddress()
                    )
                    ->setReferenceId(
                        $reference_transaction_id
                    )
                    ->setCurrency(
                        $order->get_order_currency()
                    )
                    ->setAmount(
                        $amount
                    );

            $genesis->execute();

            $response = $genesis->response()->getResponseObject();

            if ($response->status == \Genesis\API\Constants\Transaction\States::APPROVED) {

                $order->update_status(
                    self::ORDER_STATUS_REFUNDED,
                    $response->technical_message
                );

                $order->add_order_note(
                    static::getTranslatedText('Refund completed!') . PHP_EOL . PHP_EOL .
                    static::getTranslatedText('Id: ') . $response->unique_id . PHP_EOL .
                    static::getTranslatedText('Refunded amount:') . $response->amount . PHP_EOL
                );

                // Update the order with the refund id
                WC_eMerchantPay_Helper::setOrderMetaData(
                    $order_id,
                    self::META_TRANSACTION_REFUND_ID,
                    $response->unique_id
                );
            } else {
                return new \WP_Error(999, $response->technical_message);
            }

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
    public static function generateTransactionId( $input = '' )
    {
        // Try to gather more entropy

        $unique = sprintf(
            '|%s|%s|%s|%s|',
            WC_eMerchantPay_Helper::getClientRemoteIpAddress(),
            microtime( true ),
            @$_SERVER['HTTP_USER_AGENT'],
            $input
        );

        return strtolower( md5( $unique . md5(uniqid(mt_rand(), true)) ) );
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
    protected function get_item_description( WC_Order $order )
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
    protected function append_to_url($base, $args)
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
    protected function get_one_time_token($order_id)
    {
        return WC_eMerchantPay_Helper::getOrderMetaData(
            $order_id,
            self::META_CHECKOUT_RETURN_TOKEN
        );
    }

    /**
     * Set one-time token
     *
     * @param $order_id
     */
    protected function set_one_time_token($order_id, $value)
    {
        WC_eMerchantPay_Helper::setOrderMetaData(
            $order_id,
            self::META_CHECKOUT_RETURN_TOKEN,
            $value
        );
    }

    /**
     * Set the Genesis PHP Lib Credentials, based on the customer's
     * admin settings
     *
     * @param array $settings WooCommerce settings array
     *
     * @return void
     */
    protected static function set_credentials( $settings = array() )
    {
        \Genesis\Config::setEndpoint(
            \Genesis\API\Constants\Endpoints::EMERCHANTPAY
        );

        \Genesis\Config::setUsername( $settings[self::SETTING_KEY_USERNAME] );
        \Genesis\Config::setPassword( $settings[self::SETTING_KEY_PASSWORD] );

        \Genesis\Config::setEnvironment(
            ( isset( $settings[self::SETTING_KEY_TEST_MODE] ) && $settings[self::SETTING_KEY_TEST_MODE] === 'yes' )
                ? \Genesis\API\Constants\Environments::STAGING
                : \Genesis\API\Constants\Environments::PRODUCTION
        );
    }
}

WC_eMerchantPay_Method::registerHelpers();
