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

if (!class_exists('WC_eMerchantPay_Method')) {
    require_once dirname(dirname(__FILE__)) . '/classes/wc_emerchantpay_method_base.php';
}

/**
 * eMerchantPay Direct
 *
 * @class   WC_eMerchantPay_Direct
 * @extends WC_Payment_Gateway
 */
class WC_eMerchantPay_Direct extends WC_eMerchantPay_Method
{
    const FEATURE_DEFAULT_CREDIT_CARD_FORM = 'default_credit_card_form';
    const WC_ACTION_CREDIT_CARD_FORM_START = 'woocommerce_credit_card_form_start';

    /**
     * Payment Method Code
     *
     * @var null|string
     */
    protected static $method_code = 'emerchantpay_direct';

    /**
     * Additional Method Setting Keys
     */
    const SETTING_KEY_TOKEN                     = 'token';
    const SETTING_KEY_TRANSACTION_TYPE          = 'transaction_type';
    const SETTING_KEY_SHOW_CC_HOLDER            = 'show_cc_holder';
    const SETTING_KEY_INIT_RECURRING_TXN_TYPE   = 'init_recurring_txn_type';

    /**
     * @return string
     */
    protected function getModuleTitle()
    {
        return static::getTranslatedText('eMerchantPay Direct');
    }

    /**
     * Holds the Meta Key used to extract the checkout Transaction
     *   - Direct Method -> Transaction Unique Id
     *
     * @return string
     */
    protected function getCheckoutTransactionIdMetaKey()
    {
        return self::META_TRANSACTION_ID;
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
            parent::getIsValidNotification($postValues) &&
            isset($postValues['unique_id']);
    }

    /**
     * Setup and initialize this module
     */
    public function __construct()
    {
        parent::__construct();

        array_push(
            $this->supports,
            self::FEATURE_DEFAULT_CREDIT_CARD_FORM
        );
    }

    /**
     * Registers all custom actions used in the payment methods
     *
     * @return void
     */
    protected function registerCustomActions()
    {
        parent::registerCustomActions();

        $this->addWPSimpleActions(
            self::WC_ACTION_CREDIT_CARD_FORM_START,
            'before_cc_form'
        );
    }

    /**
     * Retrieves a list with the Required Api Settings
     *
     * @return array
     */
    protected function getRequiredApiSettingKeys()
    {
        $requiredApiSettingKeys = parent::getRequiredApiSettingKeys();

        array_push(
            $requiredApiSettingKeys,
            self::SETTING_KEY_TOKEN
        );

        return $requiredApiSettingKeys;
    }

    /**
     * Add additional fields just above the credit card form
     *
     * @access      public
     * @param       string $payment_method
     * @return      void
     */
    public function before_cc_form($payment_method) {
        if ($payment_method != $this->id) {
            return;
        }

        if (!$this->getMethodBoolSetting(self::SETTING_KEY_SHOW_CC_HOLDER)) {
            return;
        }

        woocommerce_form_field(
            "{$this->id}-card-holder",
            array(
                'label'             => static::getTranslatedText('Card Holder'),
                'required'          => true,
                'class'             => array( 'form-row form-row-wids' ),
                'input_class'       => array( 'wc-credit-card-form-card-holder' ),
                'custom_attributes' => array(
                    'autocomplete'  => 'off',
                    'style' => 'font-size: 1.5em; padding: 8px;'
                ),
            )
        );
    }

    /**
     * Check if this gateway is enabled and all dependencies are fine.
     * Disable the plugin if dependencies fail.
     *
     * @access      public
     * @return      bool
     */
    public function is_available() {
        return
            parent::is_available() &&
            $this->is_applicable();
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
        return
            parent::is_applicable() &&
            WC_eMerchantPay_Helper::getStoreOverSecuredConnection();
    }

    /**
     * Determines if the Payment Module Requires Securect HTTPS Connection
     * @return bool
     */
    protected function is_ssl_required()
    {
        return true;
    }

    /**
     * Output payment fields, optional additional fields and wooCommerce CC Form
     *
     * @access      public
     * @return      void
     */
    public function payment_fields()
    {
        parent::payment_fields();
    }

    /**
     * Admin Panel Field Definition
     *
     * @return void
     */
    public function init_form_fields()
    {
        // Admin description
        $this->method_description   =
            static::getTranslatedText('eMerchantPay\'s Gateway offers a secure way to pay for your order, using Credit/Debit Card.') .
            '<br />' .
            static::getTranslatedText('Direct API - allow customers to enter their CreditCard information on your website.') .
            '<br />' .
            '<br />' .
            sprintf(
                '<strong>%s</strong>',
                static::getTranslatedText('Note: You need PCI-DSS certificate in order to enable this payment method.')
            );

        parent::init_form_fields();

        $this->form_fields += array(
            self::SETTING_KEY_TOKEN => array(
                'type'        => 'text',
                'title'       => static::getTranslatedText('Token'),
                'description' => static::getTranslatedText('This is your Genesis token.'),
                'desc_tip'    => true,
            ),
            'api_transaction'   => array(
                'type'        => 'title',
                'title'       => static::getTranslatedText('API Transaction Type'),
                'description' =>
                    sprintf(
                        static::getTranslatedText(
                            'Enter Genesis API Transaction below, in order to access the Gateway.' .
                            'If you don\'t know which one to choose, %sget in touch%s with our technical support.'
                        ),
                        '<a href="mailto:tech-support@emerchantpay.com">',
                        '</a>'
                    ),
            ),
            self::SETTING_KEY_TRANSACTION_TYPE => array(
                'type'        => 'select',
                'title'       => static::getTranslatedText('Transaction Type'),
                'options'     => array(
                    \Genesis\API\Constants\Transaction\Types::AUTHORIZE =>
                        static::getTranslatedText('Authorize'),
                    \Genesis\API\Constants\Transaction\Types::AUTHORIZE_3D =>
                        static::getTranslatedText('Authorize (3D-Secure)'),
                    \Genesis\API\Constants\Transaction\Types::SALE =>
                        static::getTranslatedText('Sale'),
                    \Genesis\API\Constants\Transaction\Types::SALE_3D =>
                        static::getTranslatedText('Sale (3D-Secure)')
                ),
                'description' => static::getTranslatedText('Select transaction type for the payment transaction'),
                'desc_tip'    => true,
            ),
            'checkout_settings'   => array(
                'type'        => 'title',
                'title'       => static::getTranslatedText('Checkout Settings'),
                'description' => static::getTranslatedText(
                    'Here you can manage additional settings for the checkout page of the front site'
                )
            ),
            self::SETTING_KEY_SHOW_CC_HOLDER => array(
                'type'        => 'checkbox',
                'title'       => static::getTranslatedText('Show CC Owner Field'),
                'label'       => static::getTranslatedText('Show / Hide Credit Card Owner Field on the Checkout Page'),
                'description' => static::getTranslatedText('Decide whether to show or hide Credit Card Owner Field'),
                'default'     => static::SETTING_VALUE_YES,
                'desc_tip'    => true,
            ),
        );

        $this->form_fields += $this->build_subscription_form_fields();
    }

    /**
     * Admin Panel Subscription Field Definition
     *
     * @return array
     */
    protected function build_subscription_form_fields()
    {
        $subscription_form_fields = parent::build_subscription_form_fields();

        return array_merge(
            $subscription_form_fields,
            array(
                self::SETTING_KEY_INIT_RECURRING_TXN_TYPE => array(
                    'type'        => 'select',
                    'title'       => static::getTranslatedText('Init Recurring Transaction Type'),
                    'options'     => array(
                        \Genesis\API\Constants\Transaction\Types::INIT_RECURRING_SALE =>
                            static::getTranslatedText('Init Recurring Sale'),
                        \Genesis\API\Constants\Transaction\Types::INIT_RECURRING_SALE_3D =>
                            static::getTranslatedText('Init Recurring Sale (3D-Secure)')
                    ),
                    'description' => static::getTranslatedText('Select transaction type for the initial recurring transaction'),
                    'desc_tip'    => true,
                ),
            )
        );
    }

    /**
     * Check - transaction type is 3D-Secure
     *
     * @param bool $isRecurring
     * @return boolean
     */
    private function is3DTransaction($isRecurring = false)
    {
        if ($isRecurring) {
            $threeDRecurringTxnTypes = array(
                \Genesis\API\Constants\Transaction\Types::INIT_RECURRING_SALE_3D
            );

            return
                in_array(
                    $this->getMethodSetting(self::SETTING_KEY_INIT_RECURRING_TXN_TYPE),
                    $threeDRecurringTxnTypes
                );
        }

        $threeDTransactionTypes = array(
            \Genesis\API\Constants\Transaction\Types::AUTHORIZE_3D,
            \Genesis\API\Constants\Transaction\Types::SALE_3D
        );

        $selectedTransactionType = $this->getMethodSetting(self::SETTING_KEY_TRANSACTION_TYPE);

        return in_array($selectedTransactionType, $threeDTransactionTypes);
    }

    /**
     * Returns a list with data used for preparing a request to the gateway
     *
     * @param WC_Order $order
     * @param bool $isRecurring
     * @return array
     */
    protected function populateGateRequestData($order, $isRecurring = false)
    {
        $data = parent::populateGateRequestData($order, $isRecurring);

        $card_info = array(
            'holder'     =>
                array_key_exists("{$this->id}-card-holder", $_POST)
                    ? $_POST["{$this->id}-card-holder"]
                    : sprintf(
                        "%s %s",
                        $order->billing_first_name,
                        $order->billing_last_name
                    ),
            'number'     => str_replace(" ", "", $_POST["{$this->id}-card-number"]),
            'expiration' => $_POST["{$this->id}-card-expiry"],
            'cvv'        => $_POST["{$this->id}-card-cvc"]
        );

        list($month, $year) = explode(' / ', $card_info['expiration']);
        $card_info['expire_month'] = $month;
        $card_info['expire_year'] = substr(date('Y'), 0, 2) . substr($year, -2);

        $data['card'] = $card_info;

        return array_merge(
            $data,
            array(
                'remote_ip'        =>
                    WC_eMerchantPay_Helper::getClientRemoteIpAddress(),
                'transaction_type' =>
                    $isRecurring
                        ? $this->getMethodSetting(self::SETTING_KEY_INIT_RECURRING_TXN_TYPE)
                        : $this->getMethodSetting(self::SETTING_KEY_TRANSACTION_TYPE),
                'card'             =>
                    $card_info
            )
        );
    }

    /**
     * Initiate Gateway Payment Session
     *
     * @param int $order_id
     * @return bool|array
     */
    protected function process_order_payment( $order_id )
    {
        global $woocommerce;

        $order = WC_eMerchantPay_Helper::getOrderById($order_id);

        $data = $this->populateGateRequestData($order);

        try {
            $this->set_credentials();

            $genesis = $this->prepareInitialGenesisRequest($data);

            $genesis->execute();

            $response = $genesis->response()->getResponseObject();

            // Create One-time token to prevent redirect abuse
            $this->set_one_time_token($order_id, static::generateTransactionId());

            $paymentSuccessful = WC_eMerchantPay_Helper::isInitGatewayResponseSuccessful($response);

            if ($paymentSuccessful) {
                // Save the Checkout Id
                WC_eMerchantPay_Helper::setOrderMetaData($order_id, $this->getCheckoutTransactionIdMetaKey(), $response->unique_id);
                WC_eMerchantPay_Helper::setOrderMetaData($order_id, self::META_TRANSACTION_TYPE, $response->transaction_type);

                if (isset($response->redirect_url)) {
                    return array(
                        'result'   => static::RESPONSE_SUCCESS,
                        'redirect' => $response->redirect_url
                    );
                } else {
                    $woocommerce->cart->empty_cart();

                    $this->updateOrderStatus($order, $response);

                    return array(
                        'result' => static::RESPONSE_SUCCESS,
                        'redirect' => $data['return_success_url']
                    );
                }
            } else {
                if (isset($genesis) && isset($genesis->response()->getResponseObject()->message)) {
                    $error_message = $genesis->response()->getResponseObject()->message;
                } else {
                    $error_message = static::getTranslatedText(
                        'We were unable to process your order!' . '<br/>' .
                        'Please double check your data and try again.'
                    );
                }

                WC_eMerchantPay_Message_Helper::addErrorNotice($error_message);

                return false;
            }
        } catch (\Exception $exception) {

            if (isset($genesis) && isset($genesis->response()->getResponseObject()->message)) {
                $error_message = $genesis->response()->getResponseObject()->message;
            } else {
                $error_message = static::getTranslatedText(
                    'We were unable to process your order!' . '<br/>' .
                    'Please double check your data and try again.'
                );
            }

            WC_eMerchantPay_Message_Helper::addErrorNotice($error_message);

            WC_eMerchantPay_Helper::logException($exception);

            return false;
        }
    }

    /**
     * @param array $data
     * @return \Genesis\Genesis
     */
    protected function prepareInitialGenesisRequest($data)
    {
        $genesis = WC_eMerchantPay_Helper::getGatewayRequestByTxnType( $data['transaction_type'] );

        $genesis
            ->request()
                ->setTransactionId($data['transaction_id'])
                ->setRemoteIp($data['remote_ip'])
                ->setUsage($data['usage'])
                ->setCurrency($data['currency'])
                ->setAmount($data['amount'])
                ->setCardHolder($data['card']['holder'])
                ->setCardNumber($data['card']['number'])
                ->setExpirationYear($data['card']['expire_year'])
                ->setExpirationMonth($data['card']['expire_month'])
                ->setCvv($data['card']['cvv'])
                ->setCustomerEmail($data['customer_email'])
                ->setCustomerPhone($data['customer_phone']);

        //Billing
        $genesis
            ->request()
                ->setBillingFirstName($data['billing']['first_name'])
                ->setBillingLastName($data['billing']['last_name'])
                ->setBillingAddress1($data['billing']['address1'])
                ->setBillingAddress2($data['billing']['address2'])
                ->setBillingZipCode($data['billing']['zip_code'])
                ->setBillingCity($data['billing']['city'])
                ->setBillingState($data['billing']['state'])
                ->setBillingCountry($data['billing']['country']);

        //Shipping
        $genesis
            ->request()
                ->setShippingFirstName($data['shipping']['first_name'])
                ->setShippingLastName($data['shipping']['last_name'])
                ->setShippingAddress1($data['shipping']['address1'])
                ->setShippingAddress2($data['shipping']['address2'])
                ->setShippingZipCode($data['shipping']['zip_code'])
                ->setShippingCity($data['shipping']['city'])
                ->setShippingState($data['shipping']['state'])
                ->setShippingCountry($data['shipping']['country']);

        $isRecurring = WC_eMerchantPay_Helper::isInitRecurring(
            $data['transaction_type']
        );

        if ($this->is3DTransaction($isRecurring)) {
            $genesis
                ->request()
                    ->setNotificationUrl($data['notification_url'])
                    ->setReturnSuccessUrl($data['return_success_url'])
                    ->setReturnFailureUrl($data['return_failure_url']);
        }

        return $genesis;
    }

    /**
     * Initiate Gateway Payment Session
     *
     * @param int $order_id
     * @return bool|array
     */
    protected function process_init_subscription_payment( $order_id )
    {
        global $woocommerce;

        $order = WC_eMerchantPay_Helper::getOrderById($order_id);

        $data = $this->populateGateRequestData($order, true);

        try {
            $this->set_credentials();

            $genesis = $this->prepareInitialGenesisRequest($data);

            $genesis->execute();

            $response = $genesis->response()->getResponseObject();

            // Create One-time token to prevent redirect abuse
            $this->set_one_time_token($order_id, static::generateTransactionId());

            $paymentSuccessful = WC_eMerchantPay_Helper::isInitGatewayResponseSuccessful($response);

            if ($paymentSuccessful) {
                // Save the Checkout Id
                WC_eMerchantPay_Helper::setOrderMetaData($order_id, $this->getCheckoutTransactionIdMetaKey(), $response->unique_id);
                WC_eMerchantPay_Helper::setOrderMetaData($order_id, self::META_TRANSACTION_TYPE, $response->transaction_type);

                if (isset($response->redirect_url)) {
                    return array(
                        'result'   => static::RESPONSE_SUCCESS,
                        'redirect' => $response->redirect_url
                    );
                } else {
                    $this->updateOrderStatus($order, $response);

                    if (!$this->process_after_init_recurring_payment( $order, $response)) {
                        return false;
                    }

                    $woocommerce->cart->empty_cart();

                    return array(
                        'result'   => static::RESPONSE_SUCCESS,
                        'redirect' => $data['return_success_url']
                    );
                }
            } else {
                if (isset($genesis) && isset($genesis->response()->getResponseObject()->message)) {
                    $error_message = $genesis->response()->getResponseObject()->message;
                } else {
                    $error_message = static::getTranslatedText(
                        'We were unable to process your order!' . '<br/>' .
                        'Please double check your data and try again.'
                    );
                }

                WC_eMerchantPay_Message_Helper::addErrorNotice($error_message);

                return false;
            }
        } catch (\Exception $exception) {

            if (isset($genesis) && isset($genesis->response()->getResponseObject()->message)) {
                $error_message = $genesis->response()->getResponseObject()->message;
            } else {
                $error_message = static::getTranslatedText(
                    'We were unable to process your order!' . '<br/>' .
                    'Please double check your data and try again.'
                );
            }

            WC_eMerchantPay_Message_Helper::addErrorNotice($error_message);

            WC_eMerchantPay_Helper::logException($exception);

            return false;
        }
    }

    /**
     * Set the Genesis PHP Lib Credentials, based on the customer's admin settings
     *
     * @return void
     */
    protected function set_credentials()
    {
        parent::set_credentials();

        \Genesis\Config::setToken(
            $this->getMethodSetting(self::SETTING_KEY_TOKEN)
        );
    }

    /**
     * Determines the Recurring Token, which needs to used for the RecurringSale Transactions
     *
     * @param WC_Order $order
     * @return string
     */
    protected function getRecurringToken( $order )
    {
        $recurringToken = parent::getRecurringToken( $order );

        if (!empty($recurringToken)) {
            return $recurringToken;
        }

        return $this->getMethodSetting(self::SETTING_KEY_TOKEN);
    }
}

WC_eMerchantPay_Direct::registerStaticActions();
