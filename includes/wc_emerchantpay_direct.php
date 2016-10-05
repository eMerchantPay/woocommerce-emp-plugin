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
    /**
     * Payment Method Code
     *
     * @var null|string
     */
    protected static $method_code = 'emerchantpay_direct';

    /**
     * Additional Method Setting Keys
     */
    const SETTING_KEY_TOKEN            = 'token';
    const SETTING_KEY_TRANSACTION_TYPE = 'transaction_type';
    const SETTING_KEY_SHOW_CC_HOLDER   = 'show_cc_holder';

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
            'default_credit_card_form'
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

        add_action(
            'woocommerce_credit_card_form_start',
            array(
                $this,
                'before_cc_form'
            )
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
     * Event Handler for displaying Admin Notices
     *
     * @return bool
     */
    public function admin_notices()
    {
        if (!parent::admin_notices()) {
            return false;
        }

        if (!$this->is_applicable()) {
            WC_eMerchantPay_Helper::printWpNotice(
                static::getTranslatedText('eMerchantPay Direct payment method requires HTTPS connection in order to process payment data!'),
                WC_eMerchantPay_Helper::WP_NOTICE_TYPE_ERROR
            );
        }

        return true;
    }

    /**
     * Add additional fields just above the credit card form
     *
     * @access      public
     * @return      void
     */
    public function before_cc_form() {
        if ( $this->settings[self::SETTING_KEY_SHOW_CC_HOLDER] !== 'yes' ) {
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
        // Admin title/description
        $this->method_title         =
            static::getTranslatedText('eMerchantPay Direct');
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
                'default'     => 'yes',
                'desc_tip'    => true,
            ),
        );
    }

    /**
     * Check - transaction type is 3D-Secure
     *
     * @return boolean
     */
    private function is3DTransaction()
    {
        return in_array($this->settings[self::SETTING_KEY_TRANSACTION_TYPE], array(
            \Genesis\API\Constants\Transaction\Types::AUTHORIZE_3D,
            \Genesis\API\Constants\Transaction\Types::SALE_3D
        ));
    }

    /**
     * Returns a list with data used for preparing a request to the gateway
     *
     * @param WC_Order $order
     * @return array
     */
    protected function populateGateRequestData($order)
    {
        $data = parent::populateGateRequestData($order);

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
                'remote_ip'        => WC_eMerchantPay_Helper::getClientRemoteIpAddress(),
                'transaction_type' => $this->settings[self::SETTING_KEY_TRANSACTION_TYPE],
                'card'             => $card_info
            )
        );
    }

    /**
     * Initiate Gateway Payment Session
     *
     * @param int $order_id
     *
     * @return string HTML form
     */
    public function process_payment( $order_id )
    {
        global $woocommerce;

        $order = WC_eMerchantPay_Helper::getOrderById($order_id);

        $data = $this->populateGateRequestData($order);

        try {
            static::set_credentials(
                $this->settings
            );

            switch ($data['transaction_type']) {
                default:
                case \Genesis\API\Constants\Transaction\Types::AUTHORIZE:
                    $genesis = new \Genesis\Genesis('Financial\Cards\Authorize');
                    break;
                case \Genesis\API\Constants\Transaction\Types::AUTHORIZE_3D:
                    $genesis = new \Genesis\Genesis('Financial\Cards\Authorize3D');
                    break;
                case \Genesis\API\Constants\Transaction\Types::SALE:
                    $genesis = new \Genesis\Genesis('Financial\Cards\Sale');
                    break;
                case \Genesis\API\Constants\Transaction\Types::SALE_3D:
                    $genesis = new \Genesis\Genesis('Financial\Cards\Sale3D');
                    break;
            }
            $response = null;

            if (isset($genesis)) {
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
                        ->setCustomerPhone($data['customer_phone'])
                        //Billing
                        ->setBillingFirstName($data['billing']['first_name'])
                        ->setBillingLastName($data['billing']['first_name'])
                        ->setBillingAddress1($data['billing']['address1'])
                        ->setBillingAddress2($data['billing']['address2'])
                        ->setBillingZipCode($data['billing']['zip_code'])
                        ->setBillingCity($data['billing']['city'])
                        ->setBillingState($data['billing']['state'])
                        ->setBillingCountry($data['billing']['country'])
                        //Shipping
                        ->setShippingFirstName($data['shipping']['first_name'])
                        ->setShippingLastName($data['shipping']['last_name'])
                        ->setShippingAddress1($data['shipping']['address1'])
                        ->setShippingAddress2($data['shipping']['address2'])
                        ->setShippingZipCode($data['shipping']['zip_code'])
                        ->setShippingCity($data['shipping']['city'])
                        ->setShippingState($data['shipping']['state'])
                        ->setShippingCountry($data['shipping']['country']);

                if ($this->is3DTransaction()) {
                    $genesis
                        ->request()
                            ->setNotificationUrl($data['notification_url'])
                            ->setReturnSuccessUrl($data['return_success_url'])
                            ->setReturnFailureUrl($data['return_failure_url']);
                }

                $genesis->execute();
                $response = $genesis->response()->getResponseObject();
            }

            // Create One-time token to prevent redirect abuse
            $this->set_one_time_token($order_id, static::generateTransactionId());

            $paymentSuccessful =
                isset($response->unique_id) &&
                isset($response->status) &&
                (
                    ($response->status == \Genesis\API\Constants\Transaction\States::APPROVED) ||
                    ($response->status == \Genesis\API\Constants\Transaction\States::PENDING_ASYNC)
                );

            if ($paymentSuccessful) {
                // Save the Checkout Id
                WC_eMerchantPay_Helper::setOrderMetaData($order_id, $this->getCheckoutTransactionIdMetaKey(), $response->unique_id);
                WC_eMerchantPay_Helper::setOrderMetaData($order_id, self::META_TRANSACTION_TYPE, $response->transaction_type);

                if (isset($response->redirect_url)) {
                    return array(
                        'result'   => 'success',
                        'redirect' => $response->redirect_url
                    );
                } else {
                    $woocommerce->cart->empty_cart();
                    $this->updateOrderStatus($order, $response);
                    return array(
                        'result' => 'success',
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

                wc_add_notice($error_message, 'error');

                return false;
            }
        } catch (\Exception $e) {

            if (isset($genesis) && isset($genesis->response()->getResponseObject()->message)) {
                $error_message = $genesis->response()->getResponseObject()->message;
            } else {
                $error_message = static::getTranslatedText(
                    'We were unable to process your order!' . '<br/>' .
                    'Please double check your data and try again.'
                );
            }

            wc_add_notice($error_message, 'error');

            return false;
        }
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
        parent::set_credentials($settings);

        \Genesis\Config::setToken( $settings[self::SETTING_KEY_TOKEN] );
    }
}

WC_eMerchantPay_Direct::registerStaticActions();
