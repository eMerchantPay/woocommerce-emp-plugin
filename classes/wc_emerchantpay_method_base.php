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
    const META_ORDER_TRANSACTION_AMOUNT   = '_order_transaction_amount';
    const META_REFUNDED_AMOUNT            = '_refunded_amount';
    const META_CHECKOUT_RETURN_TOKEN      = '_checkout_return_token';

    /**
     * Method Setting Keys
     */
    const SETTING_KEY_ENABLED               = 'enabled';
    const SETTING_KEY_TITLE                 = 'title';
    const SETTING_KEY_DESCRIPTION           = 'description';
    const SETTING_KEY_TEST_MODE             = 'test_mode';
    const SETTING_KEY_USERNAME              = 'username';
    const SETTING_KEY_PASSWORD              = 'password';
    const SETTING_KEY_ALLOW_CAPTURES        = 'allow_captures';
    const SETTING_KEY_ALLOW_REFUNDS         = 'allow_refunds';
    const SETTING_KEY_ALLOW_SUBSCRIPTIONS   = 'allow_subscriptions';
    const SETTING_KEY_RECURRING_TOKEN       = 'recurring_token';

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

    const SETTING_VALUE_YES  = 'yes';
    const SETTING_VALUE_NO   = 'no';

    const FEATURE_PRODUCTS                           = 'products';
    const FEATURE_CAPTURES                           = 'captures';
    const FEATURE_REFUNDS                            = 'refunds';
    const FEATURE_VOIDS                              = 'voids';
    const FEATURE_SUBSCRIPTIONS                      = 'subscriptions';
    const FEATURE_SUBSCRIPTION_CANCELLATION          = 'subscription_cancellation';
    const FEATURE_SUBSCRIPTION_SUSPENSION            = 'subscription_suspension';
    const FEATURE_SUBSCRIPTION_REACTIVATION          = 'subscription_reactivation';
    const FEATURE_SUBSCRIPTION_AMOUNT_CHANGES        = 'subscription_amount_changes';
    const FEATURE_SUBSCRIPTION_DATE_CHANGES          = 'subscription_date_changes';
    const FEATURE_SUBSCRIPTION_PAYMENT_METHOD_CHANGE = 'subscription_payment_method_change';

    const WC_ACTION_SCHEDULED_SUBSCRIPTION_PAYMENT    = 'woocommerce_scheduled_subscription_payment';
    const WC_ACTION_UPDATE_OPTIONS_PAYMENT_GATEWAY    = 'woocommerce_update_options_payment_gateways';
    const WC_ACTION_ORDER_ITEM_ADD_ACTION_BUTTONS     = 'woocommerce_order_item_add_action_buttons';
    const WC_ACTION_ADMIN_ORDER_TOTALS_AFTER_TOTAL    = 'woocommerce_admin_order_totals_after_total';
    const WC_ACTION_ADMIN_ORDER_TOTALS_AFTER_REFUNDED = 'woocommerce_admin_order_totals_after_refunded';
    const WP_ACTION_ADMIN_NOTICES                     = 'admin_notices';

    const RESPONSE_SUCCESS                            = 'success';

    protected static $helpers = array(
        'WC_eMerchantPay_Helper'              => 'wc_emerchantpay_helper',
        'WC_eMerchantPay_Subscription_Helper' => 'wc_emerchantpay_subscription_helper',
        'WC_eMerchantPay_Message_Helper'      => 'wc_emerchantpay_message_helper',
    );

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
     * @return string
     */
    abstract protected function getModuleTitle();

    /**
     * Holds the Meta Key used to extract the checkout Transaction
     *   - Checkout Method -> WPF Unique Id
     *   - Direct Method   -> Transaction Unique Id
     *
     * @return string
     */
    abstract protected function getCheckoutTransactionIdMetaKey();

    /**
     * Initializes Order Payment Session.
     *
     * @param int $order_id
     * @return array
     */
    abstract protected function process_order_payment( $order_id );

    /**
     * Initializes Order Payment Session.
     *
     * @param int $order_id
     * @return array
     */
    abstract protected function process_init_subscription_payment( $order_id );

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
        foreach (static::$helpers as $helperClass => $helperFile) {
            if (!class_exists($helperClass)) {
                require_once "{$helperFile}.php";
            }
        }
    }

    /**
     * Registers all custom actions used in the payment methods
     *
     * @return void
     */
    protected function registerCustomActions()
    {
        $this->addWPSimpleActions(
            array(
                self::WC_ACTION_ORDER_ITEM_ADD_ACTION_BUTTONS,
                self::WC_ACTION_ADMIN_ORDER_TOTALS_AFTER_TOTAL,
                self::WC_ACTION_ADMIN_ORDER_TOTALS_AFTER_REFUNDED,
                self::WP_ACTION_ADMIN_NOTICES,
            ),
            array(
                'displayActionButtons',
                'displayAdminOrderAfterTotals',
                'displayAdminOrderAfterRefunded',
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
        if ( !$this->should_show_admin_notices() ) {
            return false;
        }

        $this->admin_notices_genesis_requirements();
        $this->admin_notices_api_credentials();
        $this->admin_notices_subscriptions();

        return true;
    }

    /**
     * Checks if page is settings and plug-in is enabled.
     * @return bool
     */
    protected function should_show_admin_notices()
    {
        if (!$this->getIsModuleSettingsPage()) {
            return false;
        }

        if (WC_eMerchantPay_Helper::isGetRequest()) {
            if ($this->enabled !== self::SETTING_VALUE_YES) {
                return false;
            }
        } elseif (WC_eMerchantPay_Helper::isPostRequest()) {
            if (!$this->getPostBoolSettingValue(self::SETTING_KEY_ENABLED)) {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * Checks if SSL is enabled and if Genesis requirements are met.
     */
    protected function admin_notices_genesis_requirements()
    {
        if ($this->is_ssl_required() && !WC_eMerchantPay_Helper::getStoreOverSecuredConnection()) {
            WC_eMerchantPay_Helper::printWpNotice(
                static::getTranslatedText(
                    sprintf(
                        '%s payment method requires HTTPS connection in order to process payment data!',
                        $this->getModuleTitle()
                    )
                ),
                WC_eMerchantPay_Helper::WP_NOTICE_TYPE_ERROR
            );
        }

        $genesisRequirementsVerified = WC_eMerchantPay_Helper::checkGenesisRequirementsVerified();

        if ($genesisRequirementsVerified !== true) {
            WC_eMerchantPay_Helper::printWpNotice(
                static::getTranslatedText(
                    $genesisRequirementsVerified->get_error_message()
                ),
                WC_eMerchantPay_Helper::WP_NOTICE_TYPE_ERROR
            );
        }
    }

    /**
     * Check if required plug-ins settings are set.
     */
    protected function admin_notices_api_credentials()
    {
        $areApiCredentialsDefined = true;
        if (WC_eMerchantPay_Helper::isGetRequest()) {
            foreach ($this->getRequiredApiSettingKeys() as $requiredApiSetting) {
                if (empty($this->getMethodSetting($requiredApiSetting))) {
                    $areApiCredentialsDefined = false;
                }
            }
        } elseif (WC_eMerchantPay_Helper::isPostRequest()) {
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
    }

    /**
     * Shows subscription notices, if subscriptions are enabled and WooCommerce is missing.
     * Also shows general information about subscriptions, if they are enabled.
     */
    protected function admin_notices_subscriptions()
    {
        $isSubscriptionsAllowed =
            WC_eMerchantPay_Helper::isGetRequest() && $this->isSubscriptionEnabled() ||
            WC_eMerchantPay_Helper::isPostRequest() && $this->getPostBoolSettingValue(self::SETTING_KEY_ALLOW_SUBSCRIPTIONS);
        if ($isSubscriptionsAllowed) {
            if (!WC_eMerchantPay_Subscription_Helper::isWCSubscriptionsInstalled()) {
                WC_eMerchantPay_Helper::printWpNotice(
                    static::getTranslatedText(
                        sprintf(
                            '<a href="%s">WooCommerce Subscription Plugin</a> is required for handling <strong>Subscriptions</strong>, which is disabled or not installed!',
                            WC_eMerchantPay_Subscription_Helper::WC_SUBSCRIPTIONS_PLUGIN_URL
                        )
                    ),
                    WC_eMerchantPay_Helper::WP_NOTICE_TYPE_ERROR
                );
            } else {
                WC_eMerchantPay_Helper::printWpNotice(
                    static::getTranslatedText(
                        "Subscriptions notices:<br />
                        - Only subscription products with setup sign-up fee can be processed by this method<br />
                        - Subscription orders can have only a single subscription product and no other products"
                    ),
                    WC_eMerchantPay_Helper::WP_NOTICE_TYPE_NOTICE
                );
            }
        }
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
            self::FEATURE_PRODUCTS,
            self::FEATURE_CAPTURES,
            self::FEATURE_REFUNDS,
            self::FEATURE_VOIDS
        );

        if ($this->isSubscriptionEnabled()) {
            $this->addSubscriptionSupport();
        }

        $this->icon         = plugins_url( "assets/images/{$this->id}.png", plugin_dir_path( __FILE__ ) );
        $this->has_fields   = true;

        // Public title/description
        $this->title        = $this->get_option(self::SETTING_KEY_TITLE);
        $this->description  = $this->get_option(self::SETTING_KEY_DESCRIPTION);

        // Register the method callback
        $this->addWPSimpleActions(
            'woocommerce_api_' . strtolower( get_class( $this )),
            'callback_handler'
        );

        // Save admin-panel options
        if ( defined( 'WOOCOMMERCE_VERSION' ) && version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
            $this->addWPAction(
                self::WC_ACTION_UPDATE_OPTIONS_PAYMENT_GATEWAY,
                'process_admin_options'
            );
        }
        else {
            $this->addWPSimpleActions(
                self::WC_ACTION_UPDATE_OPTIONS_PAYMENT_GATEWAY,
                'process_admin_options'
            );
        }

        $this->registerCustomActions();

        // Initialize admin options
        $this->init_form_fields();

        // Fetch module settings
        $this->init_settings();
    }

    /**
     * Enables Subscriptions for the current payment method
     *
     * @return void
     */
    protected function addSubscriptionSupport()
    {
        $this->supports = array_unique(
            array_merge(
                $this->supports,
                array(
                    self::FEATURE_SUBSCRIPTIONS,
                    self::FEATURE_SUBSCRIPTION_CANCELLATION,
                    self::FEATURE_SUBSCRIPTION_SUSPENSION,
                    self::FEATURE_SUBSCRIPTION_REACTIVATION,
                    self::FEATURE_SUBSCRIPTION_AMOUNT_CHANGES,
                    self::FEATURE_SUBSCRIPTION_DATE_CHANGES,
                    self::FEATURE_SUBSCRIPTION_PAYMENT_METHOD_CHANGE
                )
            )
        );

        if (WC_eMerchantPay_Subscription_Helper::isWCSubscriptionsInstalled()) {
            //Add handler for Recurring Sale Transactions
            $this->addWPAction(
                self::WC_ACTION_SCHEDULED_SUBSCRIPTION_PAYMENT,
                'process_scheduled_subscription_payment',
                true,
                10,
                2
            );
        }
    }

    /**
     * @param string $tag
     * @param string $instanceMethodName
     * @param bool $usePrefixedTag
     * @param int $priority
     * @param int $acceptedArgs
     * @return true
     */
    protected function addWPAction($tag, $instanceMethodName, $usePrefixedTag = true, $priority = 10, $acceptedArgs = 1)
    {
        return add_action(
            $usePrefixedTag ? "{$tag}_{$this->id}" : $tag,
            array(
                $this,
                $instanceMethodName
            ),
            $priority,
            $acceptedArgs
        );
    }

    /**
     * @param array|string $tags
     * @param array|string $instanceMethodNames
     * @return bool
     */
    protected function addWPSimpleActions($tags, $instanceMethodNames)
    {
        if (is_string($tags) && is_string($instanceMethodNames)) {
            return $this->addWPAction($tags, $instanceMethodNames, false);
        }

        if (!is_array($tags) || !is_array($instanceMethodNames) || count($tags) != count($instanceMethodNames)) {
            return false;
        }

        foreach ($tags as $tagIndex => $tag) {
            $this->addWPAction($tag, $instanceMethodNames[$tagIndex], false);
        }

        return true;
    }

    /**
     * Check if a gateway supports a given feature.
     *
     * @return bool
     */
    public function supports( $feature ) {
        $isFeatureSupported = parent::supports($feature);

        if ($feature == self::FEATURE_CAPTURES) {
            return
                $isFeatureSupported &&
                $this->getMethodBoolSetting(self::SETTING_KEY_ALLOW_CAPTURES);
        } elseif ($feature == self::FEATURE_REFUNDS) {
            return
                $isFeatureSupported &&
                $this->getMethodBoolSetting(self::SETTING_KEY_ALLOW_REFUNDS);
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
     * @return string
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
     * Initializes Payment Session.
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment( $order_id )
    {
        if (WC_eMerchantPay_Subscription_Helper::hasOrderSubscriptions( $order_id )) {
            return $this->process_init_subscription_payment( $order_id );
        }

        return $this->process_order_payment( $order_id );
    }

    /**
     * @param WC_Order $order
     * @param \stdClass $gatewayResponse
     * @param bool $displayNotice
     * @return bool
     */
    protected function process_after_init_recurring_payment( $order, $gatewayResponse, $displayNotice = true )
    {
        if (WC_eMerchantPay_Subscription_Helper::isInitRecurringOrderFinished( $order->id )) {
            return false;
        }

        if (!$gatewayResponse instanceof \stdClass) {
            return false;
        }

        $paymentTransactionResponse = WC_eMerchantPay_Helper::getReconcilePaymentTransaction($gatewayResponse);

        $paymentTxnStatus = WC_eMerchantPay_Helper::getGatewayStatusInstance($paymentTransactionResponse);

        if (!$paymentTxnStatus->isApproved()) {
            return false;
        }

        WC_eMerchantPay_Subscription_Helper::saveInitRecurringResponseToOrderSubscriptions( $order->id, $paymentTransactionResponse );

        $recurringSaleAmount = WC_eMerchantPay_Subscription_Helper::getOrderSubscriptionInitialPayment( $order );
        $recurringSaleResponse = null;

        if ($recurringSaleAmount === null) {
            /**
             * We are still in the trial period -> no need for Recurring Sale
             */
            return true;
        }

        $recurringSaleResponse = $this->process_subscription_payment( $order, $recurringSaleAmount );

        if (is_wp_error($recurringSaleResponse)) {
            $errorMessage = $recurringSaleResponse->get_error_message();

            $order->add_order_note("Recurring Order has failed: $errorMessage");
            $order->update_status(self::ORDER_STATUS_FAILED, $errorMessage);

            if ($displayNotice) {
                WC_eMerchantPay_Message_Helper::addErrorNotice($errorMessage);
            }

            return false;
        }

        $recurringSaleSuccessful = WC_eMerchantPay_Helper::isInitGatewayResponseSuccessful($recurringSaleResponse);

        $order->add_order_note(
            static::getTranslatedText(
                "Recurring Sale Transaction has been {$recurringSaleResponse->status}!"
            )
            . PHP_EOL . PHP_EOL .
            static::getTranslatedText('Id:') . ' ' . $recurringSaleResponse->unique_id
            . PHP_EOL . PHP_EOL .
            static::getTranslatedText('Total:') . ' ' . $recurringSaleResponse->amount . ' ' . $recurringSaleResponse->currency
        );

        if (!$recurringSaleSuccessful) {
            if ($displayNotice) {
                WC_eMerchantPay_Message_Helper::addErrorNotice( $recurringSaleResponse->message );
            }

            $order->update_status(self::ORDER_STATUS_FAILED, $recurringSaleResponse->technical_message);

            return false;
        }

        WC_eMerchantPay_Subscription_Helper::setInitRecurringOrderFinished( $order->id );

        return true;
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
            return WC_eMerchantPay_Helper::getWPError('No order exists with the specified reference id');
        }
        try {
            $payment_gateway->set_credentials();

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
            }

            return WC_eMerchantPay_Helper::getWPError($response->technical_message);
        } catch(\Exception $exception) {
            WC_eMerchantPay_Helper::logException($exception);

            return WC_eMerchantPay_Helper::getWPError($exception);
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

        if (!static::getCanCaptureOrder($order_id, true)) {
            wp_send_json_error(
                array(
                    'error' => static::getTranslatedText('You can do this only on a not-fully captured Authorize Transaction!')
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

            $payment_gateway->set_credentials();

            $payment_gateway->set_terminal_token($order);

            $void = new \Genesis\Genesis('Financial\Cancel');

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
            } catch (\Exception $exception) {
                $gatewayResponse = WC_eMerchantPay_Helper::getWPError($exception);
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
        } catch ( Exception $exception ) {
            WC_eMerchantPay_Helper::logException($exception);

            wp_send_json_error(
                array(
                    'error' => $exception->getMessage()
                )
            );

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

        if (static::getCanCaptureOrder($order, false)) {
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

        if (static::getCanVoidOrder($order) || static::getHasOrderValidMeta($order, self::META_TRANSACTION_VOID_ID)) {
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

        $canCaptureOrder = static::getCanCaptureOrder($order, true);
        $canVoidOrder = static::getCanVoidOrder($order);

        $this->fetchTemplate(
            'admin/order/dialogs/common.php',
            array(
                'order'             => $order,
                'payment_method'    => $this,
                'is_refund_allowed' => static::getCanRefundOrder($order)
            )
        );

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
    public function is_available()
    {
        if ( $this->enabled !== self::SETTING_VALUE_YES ) {
            return false;
        }

        foreach ($this->getRequiredApiSettingKeys() as $requiredApiSettingKey) {
            if (empty($this->getMethodSetting($requiredApiSettingKey))) {
                return false;
            }
        }

        if (!$this->checkSubscriptionRequirements()) {
            return false;
        }

        return $this->is_applicable();
    }

    /**
     * If subscriptions are enabled, default sign-up fee must be set.
     * @return bool
     */
    public function checkSubscriptionRequirements()
    {
        if (!$this->isSubscriptionEnabled()) {
            return true;
        }

        return WC_eMerchantPay_Subscription_Helper::isCartValid();
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
        return WC_eMerchantPay_Helper::checkGenesisRequirementsVerified() === true;
    }

    /**
     * Determines if the Payment Module Requires Securect HTTPS Connection
     * @return bool
     */
    protected function is_ssl_required()
    {
        return false;
    }

    /**
     * Admin Panel Field Definition
     *
     * @return void
     */
    public function init_form_fields()
    {
        // Admin title/description
        $this->method_title = $this->getModuleTitle();

        $this->form_fields = array(
            self::SETTING_KEY_ENABLED => array(
                'type'    => 'checkbox',
                'title'   => static::getTranslatedText('Enable/Disable'),
                'label'   => static::getTranslatedText('Enable Payment Method'),
                'default' => self::SETTING_VALUE_NO
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
                'default'     => static::getTranslatedText('Pay safely through eMerchantPay\'s Secure Gateway.'),
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
                'default'     => self::SETTING_VALUE_YES
            ),
            self::SETTING_KEY_ALLOW_CAPTURES => array(
                'type'        => 'checkbox',
                'title'       => static::getTranslatedText('Enable Captures'),
                'label'       => static::getTranslatedText('Enable / Disable Captures on the Order Preview Page'),
                'description' => static::getTranslatedText('Decide whether to Enable / Disable online Captures on the Order Preview Page.') .
                                 "<br /> <br />" .
                                 static::getTranslatedText('It depends on how the genesis gateway is configured'),
                'default'     => self::SETTING_VALUE_YES,
                'desc_tip'    => true,
            ),
            self::SETTING_KEY_ALLOW_REFUNDS => array(
                'type'        => 'checkbox',
                'title'       => static::getTranslatedText('Enable Refunds'),
                'label'       => static::getTranslatedText('Enable / Disable Refunds on the Order Preview Page'),
                'description' => static::getTranslatedText('Decide whether to Enable / Disable online Refunds on the Order Preview Page.') .
                                 "<br /> <br />" .
                                 static::getTranslatedText('It depends on how the genesis gateway is configured'),
                'default'     => self::SETTING_VALUE_YES,
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
     * Admin Panel Subscription Field Definition
     *
     * @return array
     */
    protected function build_subscription_form_fields()
    {
        return array(
            'subscription_settings' => array(
                'type' => 'title',
                'title' => static::getTranslatedText('Subscription Settings'),
                'description' => static::getTranslatedText(
                    'Here you can manage additional settings for the recurring payments (Subscriptions)'
                )
            ),
            self::SETTING_KEY_ALLOW_SUBSCRIPTIONS => array(
                'type' => 'checkbox',
                'title' => static::getTranslatedText('Enable/Disable'),
                'label' => static::getTranslatedText('Enable/Disable Subscription Payments'),
                'default' => self::SETTING_VALUE_NO
            ),
            self::SETTING_KEY_RECURRING_TOKEN => array(
                'type'        => 'text',
                'title'       => static::getTranslatedText('Recurring Token'),
                'description' => static::getTranslatedText(
                    'This is your Genesis Token for Recurring Transaction (Must be CVV-OFF).' .
                    'Leave it empty in order to use the token, which has been used for the processing transaction.'
                ),
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

        $this->set_credentials();

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

                        WC_eMerchantPay_Message_Helper::addSuccessNotice($notice);
                        break;
                    case 'failure':
                        $notice = static::getTranslatedText(
                            'Your payment has been declined, please check your data and try again'
                        );

                        $order->cancel_order($notice);

                        WC_eMerchantPay_Message_Helper::addSuccessNotice($notice);
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

                    if ( ! WC_eMerchantPay_Helper::isValidOrder($order) || $order->payment_method != $this->id) {
                        throw new \Exception('Invalid WooCommerce Order!');
                    }

                    $this->updateOrderStatus($order, $reconcile);

                    if (WC_eMerchantPay_Helper::isReconcileInitRecurring($reconcile)) {
                        $this->process_init_recurring_reconciliation($order, $reconcile);
                    }

                    $notification->renderResponse();
                }
            }
        } catch(\Exception $e) {
            header('HTTP/1.1 403 Forbidden');
        }
    }

    /**
     * @param \WC_Order $order
     * @param \stdClass $reconcile
     * @return bool
     */
    protected function process_init_recurring_reconciliation($order, $reconcile)
    {
        return $this->process_after_init_recurring_payment( $order, $reconcile, false);
    }

    /**
     * Returns a list with data used for preparing a request to the gateway
     *
     * @param WC_Order $order
     * @param bool $isRecurring
     * @throws \Exception
     * @return array
     */
    protected function populateGateRequestData($order, $isRecurring = false)
    {
        if ( ! WC_eMerchantPay_Helper::isValidOrder( $order )) {
            throw new \Exception('Invalid WooCommerce Order!');
        }

        return
            array(
                'transaction_id'   => static::generateTransactionId( $order->id ),
                'amount'           => $this->getAmount($order, $isRecurring),
                'currency'         => $order->get_order_currency(),
                'usage'            => WC_eMerchantPay_Helper::getPaymentTransactionUsage(false),
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
     * Returns proper amount depending, if order is for subscription or not.
     * @param \WC_Order $order
     * @param bool $isRecurring
     * @return float
     * @throws \Exception If the order is for subscription and there's no sign-up fee
     */
    protected function getAmount($order, $isRecurring)
    {
        if ($isRecurring) {
            $amount = WC_eMerchantPay_Subscription_Helper::getOrderSubscriptionSignUpFee( $order );
            if (!is_null($amount)) {
                return $amount;
            }
            throw new \Exception('Cannot process subscription orders without sign-up fee.');
        }
        return $this->get_order_total();
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
        if ( ! WC_eMerchantPay_Helper::isValidOrder( $order ) ) {
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
                     \Genesis\API\Constants\Transaction\Types::SALE_3D,
                     \Genesis\API\Constants\Transaction\Types::INIT_RECURRING_SALE,
                     \Genesis\API\Constants\Transaction\Types::INIT_RECURRING_SALE_3D,
                     \Genesis\API\Constants\Transaction\Types::CASHU,
                     \Genesis\API\Constants\Transaction\Types::PPRO,
                     \Genesis\API\Constants\Transaction\Types::INPAY,
                     \Genesis\API\Constants\Transaction\Types::P24,
                     \Genesis\API\Constants\Transaction\Types::PAYPAL_EXPRESS,
                     \Genesis\API\Constants\Transaction\Types::TRUSTLY_SALE
                );

                return
                    ($isOrderTranTypeAuthorize && !empty($capture_unique_id) && empty($void_unique_id)) ||
                    (!$isOrderTranTypeAuthorize && in_array($orderTransactionType, $refundableGatewayTransactionTypes));
                break;

            case \Genesis\API\Constants\Transaction\Types::VOID:
                $voidableTransactions = array(
                    \Genesis\API\Constants\Transaction\Types::TRUSTLY_SALE
                );

                return
                    ($isOrderTranTypeAuthorize && empty($capture_unique_id) && empty($void_unique_id)) ||
                    (in_array($orderTransactionType, $voidableTransactions) && empty($void_unique_id));
                break;

            default:
                return false;
        }
    }

    /**
     * Determines if Order has valid Meta Data for a specific key
     * @param int|WC_Order $order
     * @param string $meta_key
     * @return bool
     */
    protected static function getHasOrderValidMeta($order, $meta_key) {
        if ( ! WC_eMerchantPay_Helper::isValidOrder( $order ) ) {
            $order = WC_eMerchantPay_Helper::getOrderById( $order );
        }

        $data = WC_eMerchantPay_Helper::getOrderMetaData(
            $order->id,
            $meta_key
        );

        return !empty($data);
    }

    /**
     * Determines if the user can process a Capture Transaction
     *
     * @param int|WC_Order $order
     * @param bool $checkCapturedAmount
     * @return bool
     */
    protected static function getCanCaptureOrder($order, $checkCapturedAmount)
    {
        $canCapture = static::getCanProcessRefBackendTran(
            $order,
            \Genesis\API\Constants\Transaction\Types::CAPTURE
        );

        if (!$checkCapturedAmount || !$canCapture) {
            return $canCapture;
        }

        if ( ! WC_eMerchantpay_Helper::isValidOrder( $order ) ) {
            $order = WC_eMerchantPay_Helper::getOrderById( $order );
        }

        $totalCapturedAmount = WC_eMerchantPay_Helper::getOrderAmountMetaData(
            $order->id,
            self::META_CAPTURED_AMOUNT
        );

        $totalAmountToCapture = $order->get_total() - $totalCapturedAmount;

        return $totalAmountToCapture > 0;
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
     * @param stdClass|WP_Error $gatewayResponseObject
     * @throws Exception
     * @return void
     */
    protected function updateOrderStatus($order, $gatewayResponseObject)
    {
        if ( ! WC_eMerchantPay_Helper::isValidOrder( $order ) ) {
            throw new \Exception('Invalid WooCommerce Order!');
        }

        if (is_wp_error($gatewayResponseObject)) {
            $order->add_order_note(
                static::getTranslatedText('Payment transaction returned an error!')
            );

            $order->update_status(
                self::ORDER_STATUS_FAILED,
                $gatewayResponseObject->get_error_message()
            );

            return;
        }

        switch ($gatewayResponseObject->status) {
            case \Genesis\API\Constants\Transaction\States::APPROVED:
                $payment_transaction = WC_eMerchantPay_Helper::getReconcilePaymentTransaction($gatewayResponseObject);

                $payment_transaction_id = $payment_transaction->unique_id;

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

                WC_eMerchantPay_Helper::setOrderMetaData(
                    $order->id,
                    self::META_TRANSACTION_TYPE,
                    $payment_transaction->transaction_type
                );

                WC_eMerchantPay_Helper::setOrderMetaData(
                    $order->id,
                    self::META_ORDER_TRANSACTION_AMOUNT,
                    $payment_transaction->amount
                );

                $terminal_token =
                    isset($payment_transaction->terminal_token)
                        ? $payment_transaction->terminal_token
                        : null;

                if (!empty($terminal_token)) {
                    WC_eMerchantPay_Helper::setOrderMetaData(
                        $order->id,
                        self::META_TRANSACTION_TERMINAL_TOKEN,
                        $terminal_token
                    );

                    WC_eMerchantPay_Subscription_Helper::saveTerminalTokenToOrderSubscriptions(
                        $order->id,
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
     * @return bool|\WP_Error
     */
    public function process_refund( $order_id, $amount = null, $reason = '' )
    {
        $order = WC_eMerchantPay_Helper::getOrderById( $order_id );

        if ( !$order || !$order->get_transaction_id() ) {
            return false;
        }

        if (!static::getCanRefundOrder($order)) {
            return WC_eMerchantPay_Helper::getWPError(
                static::getTranslatedText(
                    'You cannot refund this payment, because the payment is not captured yet or ' .
                    'the gateway does not support refunds for this transaction type!'
                )
            );
        }

        try {
            $reference_transaction_id = WC_eMerchantPay_Helper::getOrderMetaData(
                $order_id,
                self::META_TRANSACTION_CAPTURE_ID
            );

            if (empty($reference_transaction_id)) {
                $reference_transaction_id = $order->get_transaction_id();
            }

            if (empty($reference_transaction_id)) {
                return WC_eMerchantPay_Helper::getWPError(
                    static::getTranslatedText(
                        'You cannot refund a payment, which has not been captured yet!'
                    )
                );
            }

            if ($order->get_status() == self::ORDER_STATUS_PENDING) {
                return WC_eMerchantPay_Helper::getWPError(
                    static::getTranslatedText(
                        'You cannot refund a payment, because the order status is not yet updated from the payment gateway!'
                    )
                );
            }

            $refundableAmount = WC_eMerchantPay_Helper::getOrderRefundableAmount( $order );

            if (empty($refundableAmount) || $amount > $refundableAmount) {
                if (empty($refundableAmount)) {
                    return WC_eMerchantPay_Helper::getWPError(
                        sprintf(
                            static::getTranslatedText(
                                'You cannot refund \'%s\', because the whole amount has already been refunded in the payment gateway!'
                            ),
                            WC_eMerchantPay_Helper::formatMoney($amount, $order)
                        )
                    );
                }

                return WC_eMerchantPay_Helper::getWPError(
                    sprintf(
                        static::getTranslatedText(
                            'You cannot refund \'%s\', because the available amount for refund in the payment gateway is \'%s\'!'
                        ),
                        WC_eMerchantPay_Helper::formatMoney($amount, $order),
                        WC_eMerchantPay_Helper::formatMoney($refundableAmount, $order)
                    )
                );
            }

            $this->set_credentials();
            $this->set_terminal_token( $order );

            $genesis = new \Genesis\Genesis('Financial\Refund');

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

            if ($response->status != \Genesis\API\Constants\Transaction\States::APPROVED) {
                return WC_eMerchantPay_Helper::getWPError($response->technical_message);
            }

            $order->update_status(
                self::ORDER_STATUS_REFUNDED,
                $response->technical_message
            );

            $order->add_order_note(
                static::getTranslatedText('Refund completed!') . PHP_EOL . PHP_EOL .
                static::getTranslatedText('Id: ') . $response->unique_id . PHP_EOL .
                static::getTranslatedText('Refunded amount:') . $response->amount . PHP_EOL
            );

            WC_eMerchantPay_Helper::addRefundedAmountToOrder( $order, $response->amount);

            // Update the order with the refund id
            WC_eMerchantPay_Helper::setOrderMetaData(
                $order_id,
                self::META_TRANSACTION_REFUND_ID,
                $response->unique_id
            );

            /**
             * Cancel Subscription when Init Recurring
             */
            if (WC_eMerchantPay_Subscription_Helper::hasOrderSubscriptions( $order_id )) {
                $this->cancelOrderSubscriptions( $order );
            }

            return true;
        } catch(\Exception $exception) {
            WC_eMerchantPay_Helper::logException($exception);

            return WC_eMerchantPay_Helper::getWPError($exception);
        }
    }

    /**
     * Cancels all Order Subscriptions
     *
     * @param WC_Order $order
     * @return void
     */
    protected function cancelOrderSubscriptions( $order )
    {
        $orderTransactionType = WC_eMerchantPay_Helper::getOrderMetaData(
            $order->id,
            self::META_TRANSACTION_TYPE
        );

        if (!WC_eMerchantPay_Helper::isInitRecurring($orderTransactionType)) {
            return;
        }

        WC_eMerchantPay_Subscription_Helper::updateOrderSubscriptionsStatus(
            $order,
            WC_eMerchantPay_Subscription_Helper::WC_SUBSCRIPTION_STATUS_CANCELED,
            sprintf(
                static::getTranslatedText(
                    'Subscription cancelled due to Refunded Order #%s'
                ),
                $order->id
            )
        );
    }

    /**
     * Handles Recurring Sale Transactions.
     *
     * @param float $amount The amount to charge.
     * @param WC_Order $renewal_order A WC_Order object created to record the renewal payment.
     * @access public
     * @return void
     */
    public function process_scheduled_subscription_payment( $amount, $renewal_order ) {
        $this->set_credentials();

        $gatewayResponse = $this->process_subscription_payment( $renewal_order, $amount );

        $this->updateOrderStatus( $renewal_order, $gatewayResponse );
    }

    /**
     * Process Recurring Sale Transactions.
     *
     * @param WC_Order $order A WC_Order object created to record the renewal payment.
     * @param float $amount The amount to charge.
     * @access public
     * @return \stdClass|\WP_Error
     */
    protected function process_subscription_payment( $order, $amount )
    {
        $referenceId = WC_eMerchantPay_Subscription_Helper::getOrderInitRecurringIdMeta( $order->id );

        \Genesis\Config::setToken(
            $this->getRecurringToken( $order )
        );

        $genesis = WC_eMerchantPay_Helper::getGatewayRequestByTxnType(
            \Genesis\API\Constants\Transaction\Types::RECURRING_SALE
        );

        $genesis
            ->request()
                ->setTransactionId(
                    static::generateTransactionId()
                )
                ->setReferenceId(
                    $referenceId
                )
                ->setUsage(
                    WC_eMerchantPay_Helper::getPaymentTransactionUsage(true)
                )
                ->setRemoteIp(
                    WC_eMerchantPay_Helper::getClientRemoteIpAddress()
                )
                ->setCurrency(
                    $order->get_order_currency()
                )
                ->setAmount(
                    $amount
                );
        try {
            $genesis->execute();

            return $genesis->response()->getResponseObject();
        } catch (Exception $recurringException) {
            return WC_eMerchantPay_Helper::getWPError($recurringException);
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

        return strtolower( substr( sha1( $unique . md5(uniqid(mt_rand(), true)) ), 0, 30) );
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
     * Set the Genesis PHP Lib Credentials, based on the customer's admin settings
     *
     * @return void
     */
    protected function set_credentials()
    {
        \Genesis\Config::setEndpoint(
            \Genesis\API\Constants\Endpoints::EMERCHANTPAY
        );

        \Genesis\Config::setUsername( $this->getMethodSetting(self::SETTING_KEY_USERNAME) );
        \Genesis\Config::setPassword( $this->getMethodSetting(self::SETTING_KEY_PASSWORD) );

        \Genesis\Config::setEnvironment(
            $this->getMethodBoolSetting(self::SETTING_KEY_TEST_MODE)
                ? \Genesis\API\Constants\Environments::STAGING
                : \Genesis\API\Constants\Environments::PRODUCTION
        );
    }

    /**
     * Determines a method bool setting value
     *
     * @param string $setting_name
     * @return bool
     */
    protected function getMethodBoolSetting($setting_name)
    {
        return
            $this->getMethodSetting($setting_name) === self::SETTING_VALUE_YES;
    }

    /**
     * Retrieves a bool Method Setting Value directly from the Post Request
     * Used for showing warning notices
     *
     * @param string $setting_name
     * @return bool
     */
    protected function getPostBoolSettingValue($setting_name)
    {
        $completePostParamName = $this->getMethodAdminSettingPostParamName($setting_name);

        return
            isset($_POST[$completePostParamName]) &&
            ($_POST[$completePostParamName] === '1');
    }

    /**
     * @param string $setting_name
     * @return string|array
     */
    protected function getMethodSetting($setting_name)
    {
        return $this->get_option($setting_name);
    }

    /**
     * @param string $setting_name
     * @return bool
     */
    protected function getMethodHasSetting($setting_name)
    {
        return !empty($this->getMethodSetting($setting_name));
    }

    /**
     * @return bool
     */
    protected function isSubscriptionEnabled()
    {
        return
            $this->getMethodBoolSetting(self::SETTING_KEY_ALLOW_SUBSCRIPTIONS);
    }

    /**
     * Determines the Recurring Token, which needs to used for the RecurringSale Transactions
     *
     * @param WC_Order $order
     * @return string
     */
    protected function getRecurringToken( $order )
    {
        return $this->getMethodSetting(self::SETTING_KEY_RECURRING_TOKEN);
    }
}

WC_eMerchantPay_Method::registerHelpers();
