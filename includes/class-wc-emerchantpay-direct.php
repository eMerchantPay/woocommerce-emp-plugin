<?php
/*
 * Copyright (C) 2018 emerchantpay Ltd.
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
 * @author      emerchantpay Ltd.
 * @copyright   2018 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

use Genesis\API\Constants\Transaction\Parameters\Threeds\V2\Control\ChallengeWindowSizes;
use Genesis\API\Constants\Transaction\Parameters\Threeds\V2\Control\DeviceTypes as DeviceTypes;
use Genesis\Genesis;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

if ( ! class_exists( 'WC_emerchantpay_Method' ) ) {
	require_once dirname( dirname( __FILE__ ) ) . '/classes/wc_emerchantpay_method_base.php';
}

/**
 * emerchantpay Direct
 *
 * @class   WC_Emerchantpay_Direct
 * @extends WC_Payment_Gateway
 *
 * @SuppressWarnings(PHPMD)
 */
class WC_Emerchantpay_Direct extends WC_emerchantpay_Method {

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
	const SETTING_KEY_TOKEN                   = 'token';
	const SETTING_KEY_TRANSACTION_TYPE        = 'transaction_type';
	const SETTING_KEY_SHOW_CC_HOLDER          = 'show_cc_holder';
	const SETTING_KEY_INIT_RECURRING_TXN_TYPE = 'init_recurring_txn_type';

	const THREEDS_V2_JAVA_ENABLED                 = 'java_enabled';
	const THREEDS_V2_COLOR_DEPTH                  = 'color_depth';
	const THREEDS_V2_BROWSER_LANGUAGE             = 'browser_language';
	const THREEDS_V2_SCREEN_HEIGHT                = 'screen_height';
	const THREEDS_V2_SCREEN_WIDTH                 = 'screen_width';
	const THREEDS_V2_USER_AGENT                   = 'user_agent';
	const THREEDS_V2_BROWSER_TIMEZONE_ZONE_OFFSET = 'browser_timezone_zone_offset';

	/**
	 * @var string[] Browser parameters field names
	 */
	const THREEDS_V2_BROWSER = array(
		self::THREEDS_V2_JAVA_ENABLED,
		self::THREEDS_V2_COLOR_DEPTH,
		self::THREEDS_V2_BROWSER_LANGUAGE,
		self::THREEDS_V2_SCREEN_HEIGHT,
		self::THREEDS_V2_SCREEN_WIDTH,
		self::THREEDS_V2_USER_AGENT,
		self::THREEDS_V2_BROWSER_TIMEZONE_ZONE_OFFSET,
	);

	/**
	 * @return string
	 */
	protected function getModuleTitle() {
		return static::getTranslatedText( 'emerchantpay Direct' );
	}

	/**
	 * Holds the Meta Key used to extract the checkout Transaction
	 *   - Direct Method -> Transaction Unique Id
	 *
	 * @return string
	 */
	protected function getCheckoutTransactionIdMetaKey() {
		return self::META_TRANSACTION_ID;
	}

	/**
	 * Determines if the a post notification is a valida Gateway Notification
	 *
	 * @param array $post_values
	 * @return bool
	 */
	protected function getIsValidNotification( $post_values ) {
		return parent::getIsValidNotification( $post_values ) &&
			isset( $post_values['unique_id'] );
	}

	/**
	 * Setup and initialize this module
	 */
	public function __construct() {
		parent::__construct();

		$this->supports[] = self::FEATURE_DEFAULT_CREDIT_CARD_FORM;
	}

	/**
	 * Registers all custom actions used in the payment methods
	 *
	 * @return void
	 */
	protected function registerCustomActions() {
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
	protected function getRequiredApiSettingKeys() {
		$required_api_setting_keys = parent::getRequiredApiSettingKeys();

		$required_api_setting_keys[] = self::SETTING_KEY_TOKEN;

		return $required_api_setting_keys;
	}

	/**
	 * Add additional fields just above the credit card form
	 *
	 * @access      public
	 * @param       string $payment_method
	 * @return      void
	 */
	public function before_cc_form( $payment_method ) {
		if ( $payment_method !== $this->id ) {
			return;
		}

		if ( ! $this->getMethodBoolSetting( self::SETTING_KEY_SHOW_CC_HOLDER ) ) {
			return;
		}

		woocommerce_form_field(
			"{$this->id}-card-holder",
			array(
				'label'             => static::getTranslatedText( 'Card Holder' ),
				'required'          => true,
				'class'             => array( 'form-row form-row-wide' ),
				'input_class'       => array( 'wc-credit-card-form-card-holder' ),
				'custom_attributes' => array(
					'autocomplete' => 'off',
					'style'        => 'font-size: 1.5em; padding: 8px;',
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
		return parent::is_available() &&
			$this->is_applicable();
	}

	/**
	 * Determines if the Payment Method can be used for the configured Store
	 *  - Store Checkouts
	 *  - SSL
	 *  - etc
	 *
	 * Will be extended in the Direct Method
	 *
	 * @return bool
	 */
	protected function is_applicable() {
		return parent::is_applicable() &&
			WC_emerchantpay_Helper::isStoreOverSecuredConnection();
	}

	/**
	 * Determines if the Payment Module Requires Securect HTTPS Connection
	 *
	 * @return bool
	 */
	protected function is_ssl_required() {
		return true;
	}

	/**
	 * Admin Panel Field Definition
	 *
	 * @return void
	 */
	public function init_form_fields() {
		// Admin description
		$this->method_description =
			static::getTranslatedText( 'emerchantpay\'s Gateway offers a secure way to pay for your order, using Credit/Debit Card.' ) .
			'<br />' .
			static::getTranslatedText( 'Direct API - allow customers to enter their CreditCard information on your website.' ) .
			'<br />' .
			'<br />' .
			sprintf(
				'<strong>%s</strong>',
				static::getTranslatedText( 'Note: You need PCI-DSS certificate in order to enable this payment method.' )
			);

		parent::init_form_fields();

		$this->form_fields += array(
			self::SETTING_KEY_TOKEN            => array(
				'type'        => 'text',
				'title'       => static::getTranslatedText( 'Token' ),
				'description' => static::getTranslatedText( 'This is your Genesis token.' ),
				'desc_tip'    => true,
			),
			'api_transaction'                  => array(
				'type'        => 'title',
				'title'       => static::getTranslatedText( 'API Transaction Type' ),
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
				'title'       => static::getTranslatedText( 'Transaction Type' ),
				'options'     => array(
					\Genesis\API\Constants\Transaction\Types::AUTHORIZE =>
						static::getTranslatedText( 'Authorize' ),
					\Genesis\API\Constants\Transaction\Types::AUTHORIZE_3D =>
						static::getTranslatedText( 'Authorize (3D-Secure)' ),
					\Genesis\API\Constants\Transaction\Types::SALE =>
						static::getTranslatedText( 'Sale' ),
					\Genesis\API\Constants\Transaction\Types::SALE_3D =>
						static::getTranslatedText( 'Sale (3D-Secure)' ),
				),
				'description' => static::getTranslatedText( 'Select transaction type for the payment transaction' ),
				'desc_tip'    => true,
			),
			'checkout_settings'                => array(
				'type'        => 'title',
				'title'       => static::getTranslatedText( 'Checkout Settings' ),
				'description' => static::getTranslatedText(
					'Here you can manage additional settings for the checkout page of the front site'
				),
			),
			self::SETTING_KEY_SHOW_CC_HOLDER   => array(
				'type'        => 'checkbox',
				'title'       => static::getTranslatedText( 'Show CC Owner Field' ),
				'label'       => static::getTranslatedText( 'Show / Hide Credit Card Owner Field on the Checkout Page' ),
				'description' => static::getTranslatedText( 'Decide whether to show or hide Credit Card Owner Field' ),
				'default'     => static::SETTING_VALUE_YES,
				'desc_tip'    => true,
			),
		);

		$this->form_fields += $this->build_3dsv2_attributes_form_fields();

		$this->form_fields += $this->build_sca_exemption_options_form_fields();

		$this->form_fields += $this->build_subscription_form_fields();

		$this->form_fields += $this->build_redirect_form_fields();

		$this->form_fields += $this->build_business_attributes_form_fields();
	}

	/**
	 * Admin Panel Subscription Field Definition
	 *
	 * @return array
	 */
	protected function build_subscription_form_fields() {
		$subscription_form_fields = parent::build_subscription_form_fields();

		return array_merge(
			$subscription_form_fields,
			array(
				self::SETTING_KEY_INIT_RECURRING_TXN_TYPE => array(
					'type'        => 'select',
					'title'       => static::getTranslatedText( 'Init Recurring Transaction Type' ),
					'options'     => array(
						\Genesis\API\Constants\Transaction\Types::INIT_RECURRING_SALE =>
							static::getTranslatedText( 'Init Recurring Sale' ),
						\Genesis\API\Constants\Transaction\Types::INIT_RECURRING_SALE_3D =>
							static::getTranslatedText( 'Init Recurring Sale (3D-Secure)' ),
					),
					'description' => static::getTranslatedText( 'Select transaction type for the initial recurring transaction' ),
					'desc_tip'    => true,
				),
			)
		);
	}

	/**
	 * Check - transaction type is 3D-Secure
	 *
	 * @param bool $is_recurring
	 * @return boolean
	 */
	private function is_3d_transaction( $is_recurring = false ) {
		if ( $is_recurring ) {
			$three_d_recurring_txn_types = array(
				\Genesis\API\Constants\Transaction\Types::INIT_RECURRING_SALE_3D,
			);

			return in_array(
				$this->getMethodSetting( self::SETTING_KEY_INIT_RECURRING_TXN_TYPE ),
				$three_d_recurring_txn_types,
				true
			);
		}

		$three_d_transaction_types = array(
			\Genesis\API\Constants\Transaction\Types::AUTHORIZE_3D,
			\Genesis\API\Constants\Transaction\Types::SALE_3D,
		);

		$selected_transaction_types = $this->getMethodSetting( self::SETTING_KEY_TRANSACTION_TYPE );

		return in_array( $selected_transaction_types, $three_d_transaction_types, true );
	}

	/**
	 * Returns a list with data used for preparing a request to the gateway
	 *
	 * @param WC_Order $order
	 * @param bool $is_recurring
	 *
	 * @return array
	 *
	 * @throws Exception
	 */
	protected function populateGateRequestData( $order, $is_recurring = false ) {
		$data = parent::populateGateRequestData( $order, $is_recurring );

		$card_info            = $this->populate_cc_data( $order );
		$data['browser_data'] = $this->populate_browser_parameters();

		list($month, $year)        = explode( ' / ', $card_info['expiration'] );
		$card_info['expire_month'] = $month;
		$card_info['expire_year']  = substr( gmdate( 'Y' ), 0, 2 ) . substr( $year, -2 );

		$data['card'] = $card_info;

		return array_merge(
			$data,
			array(
				'remote_ip'        =>
					WC_emerchantpay_Helper::get_client_remote_ip_address(),
				'transaction_type' =>
					$is_recurring
						? $this->getMethodSetting( self::SETTING_KEY_INIT_RECURRING_TXN_TYPE )
						: $this->getMethodSetting( self::SETTING_KEY_TRANSACTION_TYPE ),
				'card'             =>
					$card_info,
			)
		);
	}

	/**
	 * Initiate Gateway Payment Session
	 *
	 * @param int $order_id
	 *
	 * @return bool|array
	 *
	 * @throws Exception
	 */
	protected function process_order_payment( $order_id ) {
		global $woocommerce;

		$order = WC_emerchantpay_Order_Helper::getOrderById( $order_id );

		$data = $this->populateGateRequestData( $order );

		try {
			$this->set_credentials();

			$genesis = $this->prepare_initial_genesis_request( $data );
			$genesis = $this->add_business_data_to_gateway_request( $genesis, $order );
			if ( $this->is_3dsv2_enabled() ) {
				$this->add_3dsv2_parameters( $genesis, $order, $data, false );
			}
			$this->add_sca_exemption_parameters( $genesis );

			$genesis->execute();

			$response = $genesis->response()->getResponseObject();

			// Save whole trx
			WC_emerchantpay_Order_Helper::save_initial_trx_to_order( $order_id, $response, $data );

			// Create One-time token to prevent redirect abuse
			$this->set_one_time_token( $order_id, static::generateTransactionId() );

			$payment_successful = WC_emerchantpay_Subscription_Helper::isInitGatewayResponseSuccessful( $response );

			if ( ! $payment_successful ) {
				$error_message = ( isset( $genesis ) && isset( $genesis->response()->getResponseObject()->message ) ) ?
					$genesis->response()->getResponseObject()->message :
					static::getTranslatedText(
						'We were unable to process your order!<br/>' .
						'Please double check your data and try again.'
					);

				WC_emerchantpay_Message_Helper::addErrorNotice( $error_message );

				return false;
			}

			// Save the Checkout Id
			WC_emerchantpay_Order_Helper::setOrderMetaData( $order_id, $this->getCheckoutTransactionIdMetaKey(), $response->unique_id );
			WC_emerchantpay_Order_Helper::setOrderMetaData( $order_id, self::META_TRANSACTION_TYPE, $response->transaction_type );

			switch ( true ) {
				case ( isset( $response->threeds_method_url ) ):
					$unique_id_hash = hash( 'sha256', $response->unique_id );
					$url_params     = http_build_query(
						array(
							'order_id' => $order_id,
							'checksum' => $unique_id_hash,
						)
					);

					return array(
						'result'   => static::RESPONSE_SUCCESS,
						'redirect' => WC()->api_request_url( WC_Emerchantpay_Threeds_Form_Helper::class ) . "?{$url_params}",
					);
				case ( isset( $response->redirect_url ) ):
					return array(
						'result'   => static::RESPONSE_SUCCESS,
						'redirect' => $response->redirect_url,
					);
				default:
					$woocommerce->cart->empty_cart();

					$this->updateOrderStatus( $order, $response );

					return array(
						'result'   => static::RESPONSE_SUCCESS,
						'redirect' => $data['return_success_url'],
					);
			}
		} catch ( \Exception $exception ) {

			if ( isset( $genesis ) && isset( $genesis->response()->getResponseObject()->message ) ) {
				$error_message = $genesis->response()->getResponseObject()->message;
			} else {
				$error_message = static::getTranslatedText(
					'We were unable to process your order!<br/>' .
					'Please double check your data and try again.'
				);
			}

			WC_emerchantpay_Message_Helper::addErrorNotice( $error_message );

			WC_emerchantpay_Helper::logException( $exception );

			return false;
		} // End try().
	}

	/**
	 * Add initial data to the Request
	 *
	 * @param array $data
	 *
	 * @return Genesis
	 *
	 * @throws \Genesis\Exceptions\InvalidMethod
	 */
	protected function prepare_initial_genesis_request( $data ) {
		$genesis = WC_emerchantpay_Genesis_Helper::getGatewayRequestByTxnType( $data['transaction_type'] );

		$genesis
			->request()
				->setTransactionId( $data['transaction_id'] )
				->setRemoteIp( $data['remote_ip'] )
				->setUsage( $data['usage'] )
				->setCurrency( $data['currency'] )
				->setAmount( $data['amount'] )
				->setCardHolder( $data['card']['holder'] )
				->setCardNumber( $data['card']['number'] )
				->setExpirationYear( $data['card']['expire_year'] )
				->setExpirationMonth( $data['card']['expire_month'] )
				->setCvv( $data['card']['cvv'] )
				->setCustomerEmail( $data['customer_email'] )
				->setCustomerPhone( $data['customer_phone'] );

		// Billing
		$genesis
			->request()
				->setBillingFirstName( $data['billing']['first_name'] )
				->setBillingLastName( $data['billing']['last_name'] )
				->setBillingAddress1( $data['billing']['address1'] )
				->setBillingAddress2( $data['billing']['address2'] )
				->setBillingZipCode( $data['billing']['zip_code'] )
				->setBillingCity( $data['billing']['city'] )
				->setBillingState( $data['billing']['state'] )
				->setBillingCountry( $data['billing']['country'] );

		// Shipping
		$genesis
			->request()
				->setShippingFirstName( $data['shipping']['first_name'] )
				->setShippingLastName( $data['shipping']['last_name'] )
				->setShippingAddress1( $data['shipping']['address1'] )
				->setShippingAddress2( $data['shipping']['address2'] )
				->setShippingZipCode( $data['shipping']['zip_code'] )
				->setShippingCity( $data['shipping']['city'] )
				->setShippingState( $data['shipping']['state'] )
				->setShippingCountry( $data['shipping']['country'] );

		$is_recurring = WC_emerchantpay_Subscription_Helper::isInitRecurring(
			$data['transaction_type']
		);

		if ( $this->is_3d_transaction( $is_recurring ) ) {
			$genesis
				->request()
					->setNotificationUrl( $data['notification_url'] )
					->setReturnSuccessUrl( $data['return_success_url'] )
					->setReturnFailureUrl( $data['return_failure_url'] );
		}

		return $genesis;
	}

	/**
	 * Initiate Gateway Payment Session
	 *
	 * @param int $order_id
	 *
	 * @return bool|array
	 *
	 * @throws Exception
	 */
	protected function process_init_subscription_payment( $order_id ) {
		global $woocommerce;

		$order = WC_emerchantpay_Order_Helper::getOrderById( $order_id );

		$data = $this->populateGateRequestData( $order, true );

		try {
			$this->set_credentials();

			$genesis = $this->prepare_initial_genesis_request( $data );
			if ( $this->is_3dsv2_enabled() ) {
				$this->add_3dsv2_parameters( $genesis, $order, $data, true );
			}
			$this->add_sca_exemption_parameters( $genesis );

			$genesis->execute();

			$response = $genesis->response()->getResponseObject();
			WC_emerchantpay_Order_Helper::save_initial_trx_to_order( $order_id, $response, $data );

			// Create One-time token to prevent redirect abuse
			$this->set_one_time_token( $order_id, static::generateTransactionId() );

			$payment_successful = WC_emerchantpay_Subscription_Helper::isInitGatewayResponseSuccessful( $response );

			if ( ! $payment_successful ) {
				$error_message = ( isset( $genesis ) && isset( $genesis->response()->getResponseObject()->message ) ) ?
					$genesis->response()->getResponseObject()->message :
					static::getTranslatedText(
						'We were unable to process your order!<br/>' .
						'Please double check your data and try again.'
					);
				WC_emerchantpay_Message_Helper::addErrorNotice( $error_message );

				return false;
			}

			// Save the Checkout Id
			WC_emerchantpay_Order_Helper::setOrderMetaData( $order_id, $this->getCheckoutTransactionIdMetaKey(), $response->unique_id );
			WC_emerchantpay_Order_Helper::setOrderMetaData( $order_id, self::META_TRANSACTION_TYPE, $response->transaction_type );
			switch ( true ) {
				case isset( $response->threeds_method_continue_url ):
					$unique_id_hash = hash( 'sha256', $response->unique_id );
					$url_params     = http_build_query(
						array(
							'order_id' => $order_id,
							'checksum' => $unique_id_hash,
						)
					);

					return array(
						'result'   => static::RESPONSE_SUCCESS,
						'redirect' => WC()->api_request_url( WC_Emerchantpay_Threeds_Form_Helper::class ) . "?{$url_params}",
					);
				case ( isset( $response->redirect_url ) ):
					return array(
						'result'   => static::RESPONSE_SUCCESS,
						'redirect' => $response->redirect_url,
					);
				default:
					$this->updateOrderStatus( $order, $response );

					if ( ! $this->process_after_init_recurring_payment( $order, $response ) ) {
						return false;
					}

					$woocommerce->cart->empty_cart();

					return array(
						'result'   => static::RESPONSE_SUCCESS,
						'redirect' => $data['return_success_url'],
					);
			}
		} catch ( \Exception $exception ) {
			if ( isset( $genesis ) && isset( $genesis->response()->getResponseObject()->message ) ) {
				$error_message = $genesis->response()->getResponseObject()->message;
			} else {
				$error_message = static::getTranslatedText(
					'We were unable to process your order!<br/>' .
					'Please double check your data and try again.'
				);
			}

			WC_emerchantpay_Message_Helper::addErrorNotice( $error_message );

			WC_emerchantpay_Helper::logException( $exception );

			return false;
		} // End try().
	}

	/**
	 * Set the Genesis PHP Lib Credentials, based on the customer's admin settings
	 *
	 * @return void
	 */
	public function set_credentials() {
		parent::set_credentials();

		\Genesis\Config::setToken(
			$this->getMethodSetting( self::SETTING_KEY_TOKEN )
		);
	}

	/**
	 * Determines the Recurring Token, which needs to used for the RecurringSale Transactions
	 *
	 * @param WC_Order $order
	 *
	 * @return string
	 */
	protected function getRecurringToken( $order ) {
		$recurring_token = parent::getRecurringToken( $order );

		if ( ! empty( $recurring_token ) ) {
			return $recurring_token;
		}

		return $this->getMethodSetting( self::SETTING_KEY_TOKEN );
	}

	/**
	 * Check the input and populate Credit Card data
	 *
	 * @param object $order
	 *
	 * @return array
	 */
	private function populate_cc_data( $order ) {
		$holder = sprintf(
			'%s %s',
			$order->get_billing_first_name(),
			$order->get_billing_last_name()
		);

		if ( isset( $_POST[ "{$this->id}-card-holder" ] ) ) {
			$holder = sanitize_text_field( wp_unslash( $_POST[ "{$this->id}-card-holder" ] ) );
		}

		$number = isset( $_POST[ "{$this->id}-card-number" ] )
			? str_replace( ' ', '', sanitize_text_field( wp_unslash( $_POST[ "{$this->id}-card-number" ] ) ) )
			: null;

		$expiration = isset( $_POST[ "{$this->id}-card-expiry" ] )
			? sanitize_text_field( wp_unslash( $_POST[ "{$this->id}-card-expiry" ] ) )
			: null;

		$cvc = isset( $_POST[ "{$this->id}-card-cvc" ] )
			? sanitize_text_field( wp_unslash( $_POST[ "{$this->id}-card-cvc" ] ) )
			: null;

		return array(
			'holder'     => $holder,
			'number'     => $number,
			'expiration' => $expiration,
			'cvv'        => $cvc,
		);
	}

	/**
	 * Adds 3DSv2 parameters to the Request
	 *
	 * @param Genesis  $genesis
	 * @param WC_Order $order
	 * @param array    $data
	 * @param bool     $is_recurring
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	private function add_3dsv2_parameters( $genesis, $order, $data, $is_recurring ) {
		$this->add_3dsv2_parameters_to_gateway_request( $genesis, $order, $is_recurring );
		$this->add_3dsv2_browser_parameters_to_gateway_request( $genesis, $data );
		$genesis->request()->setThreedsV2MethodCallbackUrl(
			WC()->api_request_url( WC_Emerchantpay_Threeds_Backend_Helper::class . '-callback_handler' ) . '?order_id=' . $order->get_id()
		);
	}

	/**
	 * Adds browser data to the Genesis Request
	 *
	 * @param $genesis
	 * @param $data
	 *
	 * @return void
	 */
	private function add_3dsv2_browser_parameters_to_gateway_request( $genesis, $data ) {
		$request = $genesis->request();

		$http_accept = isset( $_SERVER['HTTP_ACCEPT'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT'] ) )
			: null;

		$request
			->setThreedsV2ControlDeviceType( DeviceTypes::BROWSER )
			->setThreedsV2ControlChallengeWindowSize( ChallengeWindowSizes::FULLSCREEN )
			->setThreedsV2BrowserAcceptHeader( $http_accept )
			->setThreedsV2BrowserJavaEnabled( $data['browser_data'][ self::THREEDS_V2_JAVA_ENABLED ] )
			->setThreedsV2BrowserLanguage( $data['browser_data'][ self::THREEDS_V2_BROWSER_LANGUAGE ] )
			->setThreedsV2BrowserColorDepth( $data['browser_data'][ self::THREEDS_V2_COLOR_DEPTH ] )
			->setThreedsV2BrowserScreenHeight( $data['browser_data'][ self::THREEDS_V2_SCREEN_HEIGHT ] )
			->setThreedsV2BrowserScreenWidth( $data['browser_data'][ self::THREEDS_V2_SCREEN_WIDTH ] )
			->setThreedsV2BrowserTimeZoneOffset( $data['browser_data'][ self::THREEDS_V2_BROWSER_TIMEZONE_ZONE_OFFSET ] )
			->setThreedsV2BrowserUserAgent( $data['browser_data'][ self::THREEDS_V2_USER_AGENT ] );
	}

	/**
	 * Parse and populate received browser parameters to array
	 *
	 * @return array
	 */
	private function populate_browser_parameters() {
		$field_names = self::THREEDS_V2_BROWSER;
		$data        = array();

		array_walk(
			$field_names,
			function ( $field_name ) use ( &$data ) {
				$data[ $field_name ] = isset( $_POST[ "{$this->id}_{$field_name}" ] )
					? sanitize_text_field( wp_unslash( $_POST[ "{$this->id}_{$field_name}" ] ) )
					: null;
			}
		);

		return $data;
	}
}

WC_emerchantpay_Direct::registerStaticActions();
