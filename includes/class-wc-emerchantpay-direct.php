<?php
/**
 * Copyright (C) 2018-2024 emerchantpay Ltd.
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
 * @copyright   2018-2024 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 * @package     classes\class-wc-emerchantpay-transaction
 */

use Genesis\Api\Constants\Transaction\Parameters\Recurring\Types as RecurringTypes;
use Genesis\Api\Constants\Transaction\Parameters\Threeds\V2\Control\ChallengeWindowSizes;
use Genesis\Api\Constants\Transaction\Parameters\Threeds\V2\Control\DeviceTypes;
use Genesis\Api\Constants\Transaction\Types;
use Genesis\Config;
use Genesis\Genesis;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

if ( ! class_exists( 'WC_Emerchantpay_Method_Base' ) ) {
	require_once dirname( __DIR__ ) . '/classes/wc_emerchantpay_method_base.php';
}

/**
 * Emerchantpay Direct
 *
 * @class   WC_Emerchantpay_Direct
 * @extends WC_Payment_Gateway
 *
 * @SuppressWarnings(PHPMD)
 */
class WC_Emerchantpay_Direct extends WC_Emerchantpay_Method_Base {

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
	const SETTING_KEY_CSE_PUBLIC_KEY          = 'cse_public_key';

	const THREEDS_V2_JAVA_ENABLED                 = 'java_enabled';
	const THREEDS_V2_COLOR_DEPTH                  = 'color_depth';
	const THREEDS_V2_BROWSER_LANGUAGE             = 'browser_language';
	const THREEDS_V2_SCREEN_HEIGHT                = 'screen_height';
	const THREEDS_V2_SCREEN_WIDTH                 = 'screen_width';
	const THREEDS_V2_USER_AGENT                   = 'user_agent';
	const THREEDS_V2_BROWSER_TIMEZONE_ZONE_OFFSET = 'browser_timezone_zone_offset';
	const ENCRYPT_LIBRARY_INTEGRITY_HASH          = 'sha512-fezEmqlQWwvKq3A2s897RSda3JsDen/xmPTsBmnx6TWk++rnofg2omiNLHhCbQvQ8DtEvfAvXQTsvE95DlELAw==';

	/**
	 * Constant contains browser parameters field names
	 *
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
	 * Returns module name
	 *
	 * @return string
	 */
	protected function get_module_title() {
		return static::get_translated_text( 'emerchantpay Direct' );
	}

	/**
	 * Holds the Meta Key used to extract the checkout Transaction
	 *   - Direct Method -> Transaction Unique Id
	 *
	 * @return string
	 */
	protected function get_checkout_transaction_id_meta_key() {
		return WC_Emerchantpay_Order_Factory::is_hpos_enabled() ? self::META_HPOS_DIRECT_TRANSACTION_ID : self::META_TRANSACTION_ID;
	}

	/**
	 * Determines if the post notification is a valid Gateway Notification
	 *
	 * @param array $post_values Post notifications values.
	 * @return bool
	 */
	protected function get_is_valid_notification( $post_values ) {
		return parent::get_is_valid_notification( $post_values ) &&
			isset( $post_values['unique_id'] );
	}

	/**
	 * Setup and initialize this module
	 *
	 * @param array $options Options array.
	 */
	public function __construct( $options = array() ) {
		parent::__construct( $options );

		$this->supports[] = self::FEATURE_DEFAULT_CREDIT_CARD_FORM;
		$this->register_custom_actions();
	}

	/**
	 * Add additional fields just above the credit card form
	 *
	 * @access      public
	 * @param       string $payment_method Contains the transaction payment method.
	 * @return      void
	 */
	public function before_cc_form( $payment_method ) {
		if ( $payment_method !== $this->id ) {
			return;
		}

		if ( ! $this->get_method_bool_setting( self::SETTING_KEY_SHOW_CC_HOLDER ) ) {
			return;
		}
		woocommerce_form_field(
			"{$this->id}-card-holder",
			array(
				'label'             => static::get_translated_text( 'Card Holder' ),
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
			WC_Emerchantpay_Helper::is_store_over_secured_connection();
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
		// Admin description.
		$this->method_description =
			static::get_translated_text( 'emerchantpay\'s Gateway offers a secure way to pay for your order, using Credit/Debit Card.' ) .
			'<br />' .
			static::get_translated_text( 'Direct API - allow customers to enter their CreditCard information on your website.' ) .
			'<br />' .
			'<br />' .
			sprintf(
				'<strong>%s</strong>',
				static::get_translated_text( 'Note: You need PCI-DSS certificate in order to enable this payment method.' )
			);

		parent::init_form_fields();

		$this->form_fields += array(
			self::SETTING_KEY_TOKEN             => array(
				'type'        => 'text',
				'title'       => static::get_translated_text( 'Token' ),
				'description' => static::get_translated_text(
					'This is your Gateway token. ' .
					'If you do not have a token, contact tech-support@emerchantpay.com ' .
					'to enable Smart Router for your account.'
				),
				'desc_tip'    => true,
			),
			self::SETTING_KEY_IFRAME_PROCESSING => array(
				'type'        => 'checkbox',
				'title'       => static::get_translated_text( 'Enable/Disable' ),
				'label'       => static::get_translated_text( 'Enable payment processing into an iframe' ),
				'default'     => self::SETTING_VALUE_YES,
				'description' => static::get_translated_text(
					'Enable 3D Secure Method processing into an iFrame. By disabling the iFrame, the ' .
					'3DSv2 processing will be executed by redirecting from the checkout page.'
				),
			),
			'api_transaction'                   => array(
				'type'        => 'title',
				'title'       => static::get_translated_text( 'API Transaction Type' ),
				'description' =>
					sprintf(
						static::get_translated_text(
							'Enter Genesis API Transaction below, in order to access the Gateway.' .
							'If you don\'t know which one to choose, %sget in touch%s with our technical support.'
						),
						'<a href="mailto:tech-support@emerchantpay.com">',
						'</a>'
					),
			),
			self::SETTING_KEY_TRANSACTION_TYPE  => array(
				'type'        => 'select',
				'title'       => static::get_translated_text( 'Transaction Type' ),
				'options'     => array(
					Types::AUTHORIZE    =>
						static::get_translated_text( 'Authorize' ),
					Types::AUTHORIZE_3D =>
						static::get_translated_text( 'Authorize (3D-Secure)' ),
					Types::SALE         =>
						static::get_translated_text( 'Sale' ),
					Types::SALE_3D      =>
						static::get_translated_text( 'Sale (3D-Secure)' ),
				),
				'description' => static::get_translated_text( 'Select transaction type for the payment transaction' ),
				'desc_tip'    => true,
			),
			'checkout_settings'                 => array(
				'type'        => 'title',
				'title'       => static::get_translated_text( 'Checkout Settings' ),
				'description' => static::get_translated_text(
					'Here you can manage additional settings for the checkout page of the front site'
				),
			),
			self::SETTING_KEY_SHOW_CC_HOLDER    => array(
				'type'        => 'checkbox',
				'title'       => static::get_translated_text( 'Show CC Owner Field' ),
				'label'       => static::get_translated_text( 'Show / Hide Credit Card Owner Field on the Checkout Page' ),
				'description' => static::get_translated_text( 'Decide whether to show or hide Credit Card Owner Field' ),
				'default'     => static::SETTING_VALUE_YES,
				'desc_tip'    => true,
			),
			self::SETTING_KEY_CSE_PUBLIC_KEY    => array(
				'type'        => 'textarea',
				'title'       => static::get_translated_text( 'Client-Side Encryption Public Key' ),
				'label'       => static::get_translated_text( 'Turn CSE by filling up your public key.' ),
				'description' => static::get_translated_text( 'Client Side Encryption public key is located in your Console Admin. For more info ask tech-support@emerchantpay.com.' ),
				'default'     => '',
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
					'title'       => static::get_translated_text( 'Init Recurring Transaction Type' ),
					'options'     => $this->get_recurring_transaction_types(),
					'description' => static::get_translated_text( 'Select transaction type for the initial recurring transaction' ),
					'desc_tip'    => true,
				),
			)
		);
	}

	/**
	 * Check - transaction type is 3D-Secure
	 *
	 * @param bool $is_recurring Defines that request should be recurring or not. Default false.
	 * @return boolean
	 */
	private function is_3d_transaction( $is_recurring = false ) {
		if ( $is_recurring ) {
			$three_d_recurring_txn_types = array(
				Types::INIT_RECURRING_SALE_3D,
				Types::SALE_3D,
			);

			return in_array(
				$this->get_method_setting( self::SETTING_KEY_INIT_RECURRING_TXN_TYPE ),
				$three_d_recurring_txn_types,
				true
			);
		}

		$three_d_transaction_types = array(
			Types::AUTHORIZE_3D,
			Types::SALE_3D,
		);

		$selected_transaction_types = $this->get_method_setting( self::SETTING_KEY_TRANSACTION_TYPE );

		return in_array( $selected_transaction_types, $three_d_transaction_types, true );
	}

	/**
	 * Returns a list with data used for preparing a request to the gateway
	 *
	 * @param WC_Order $order Order object.
	 * @param bool     $is_recurring Defines that request should be recurring or not. Default false.
	 *
	 * @return array
	 *
	 * @throws Exception Throws Invalid WooCommerce Order.
	 */
	protected function populate_gate_request_data( $order, $is_recurring = false ) {
		$data = parent::populate_gate_request_data( $order, $is_recurring );

		$card_info            = $this->populate_cc_data( $order );
		$data['browser_data'] = $this->populate_browser_parameters();

		list( $month, $year )      = explode( '/', $card_info['expiration'] );
		$card_info['expire_month'] = trim( $month );
		$card_info['expire_year']  = trim( $year );

		if ( ! $card_info['encrypted'] ) {
			$card_info['expire_year'] = substr( gmdate( 'Y' ), 0, 2 ) . substr( $card_info['expire_year'], -2 );
		}

		$data['card'] = $card_info;

		return array_merge(
			$data,
			array(
				'remote_ip'        =>
					WC_Emerchantpay_Helper::get_client_remote_ip_address(),
				'transaction_type' =>
					$is_recurring
						? $this->get_method_setting( self::SETTING_KEY_INIT_RECURRING_TXN_TYPE )
						: $this->get_method_setting( self::SETTING_KEY_TRANSACTION_TYPE ),
				'card'             =>
					$card_info,
			)
		);
	}

	/**
	 * Initiate Gateway Payment Session
	 *
	 * @param int $order_id Order identifier.
	 *
	 * @return bool|array
	 *
	 * @throws Exception Invalid Woocommerce Order.
	 */
	protected function process_order_payment( $order_id ) {
		global $woocommerce;

		$order = wc_emerchantpay_order_proxy()->get_order_by_id( $order_id );

		$data = $this->populate_gate_request_data( $order );

		try {
			$this->set_credentials();

			$genesis = $this->prepare_initial_genesis_request( $data );

			$genesis = $this->add_business_data_to_gateway_request( $genesis, $order );
			if ( $this->is_3dsv2_enabled() && $this->is_3d_transaction( false ) ) {
				$this->add_3dsv2_parameters( $genesis, $order, $data, false );
			}
			$this->add_sca_exemption_parameters( $genesis );

			$genesis->execute();

			if ( ! $genesis->response()->isSuccessful() ) {
				throw new \Exception( $genesis->response()->getErrorDescription() );
			}

			$response = $genesis->response()->getResponseObject();

			// Saves the entire transaction.
			$this->save_direct_trx_data_to_order( $response, $order, $data );

			if ( ! WC_emerchantpay_Subscription_Helper::is_init_gateway_response_successful( $response ) ) {
				$message         = static::get_translated_text( 'We were unable to process your order!<br/>Please double check your data and try again.' );
				$gateway_message = WC_Emerchantpay_Genesis_Helper::fetch_gateway_response_message( $response );

				throw new Exception( "$message $gateway_message" );
			}

			// Save the Checkout Id.
			wc_emerchantpay_order_proxy()->set_order_meta_data( $order, $this->get_checkout_transaction_id_meta_key(), $response->unique_id );
			wc_emerchantpay_order_proxy()->set_order_meta_data( $order, self::META_TRANSACTION_TYPE, $response->transaction_type );

			switch ( true ) {
				case ( isset( $response->threeds_method_url ) ):
					$unique_id_hash = hash( 'sha256', $response->unique_id );
					$url_params     = http_build_query(
						array(
							'order_id' => $order_id,
							'checksum' => $unique_id_hash,
						)
					);
					$response_array = $this->create_response(
						WC()->api_request_url( WC_Emerchantpay_Threeds_Form_Helper::class ) . "?{$url_params}",
						$this->is_iframe_blocks()
					);
					break;
				case ( isset( $response->redirect_url ) ):
					$response_array = $this->create_response( $response->redirect_url );
					break;
				default:
					$woocommerce->cart->empty_cart();
					$this->update_order_status( $order, $response );
					$response_array = $this->create_response( $data['return_success_url'] );
			}

			return $response_array;
		} catch ( \Exception $exception ) {
			$message    = static::get_translated_text( 'Direct payment error:' );
			$concat_msg = "$message {$exception->getMessage()}";

			WC_Emerchantpay_Helper::log_exception( $exception );
			// Add the error on the Admin Order view
			$order->add_order_note( $concat_msg );

			WC()->session->reload_checkout = true;

			throw new Exception( esc_html( $concat_msg ) );
		} // End of try section.
	}

	/**
	 * Add initial data to the Request
	 *
	 * @param array $data Transaction data.
	 * @param array $is_recurring Indicates recurring payment creation
	 *
	 * @return Genesis
	 *
	 * @throws \Genesis\Exceptions\DeprecatedMethod Deprecated method exception.
	 * @throws \Genesis\Exceptions\InvalidArgument  Invalid argument exception.
	 * @throws \Genesis\Exceptions\InvalidMethod    Invalid method exception.
	 */
	protected function prepare_initial_genesis_request( $data, $is_recurring = false ) {
		$genesis = WC_emerchantpay_Genesis_Helper::get_gateway_request_by_txn_type( $data['transaction_type'] );

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

		// Billing address data.
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

		// Shipping address data.
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

		if ( $this->is_3d_transaction( $is_recurring ) ) {
			$genesis
				->request()
					->setNotificationUrl( $data['notification_url'] )
					->setReturnSuccessUrl( $data['return_success_url'] )
					->setReturnFailureUrl( $data['return_failure_url'] );
		}

		// Client Side Encryption
		$genesis->request()->setClientSideEncryption( $data['card']['encrypted'] );

		return $genesis;
	}

	/**
	 * Initiate Gateway Payment Session
	 *
	 * @param int $order_id Order identifier.
	 *
	 * @return bool|array
	 *
	 * @throws Exception Invalid Woocommerce Order.
	 */
	protected function process_init_subscription_payment( $order_id ) {
		global $woocommerce;

		$order = wc_emerchantpay_order_proxy()->get_order_by_id( $order_id );

		$data = $this->populate_gate_request_data( $order, true );

		try {
			$this->set_credentials();

			$genesis = $this->prepare_initial_genesis_request( $data, true );
			$this->set_recurring_attribute( $genesis, $data );

			if ( $this->is_3dsv2_enabled() && $this->is_3d_transaction( true ) ) {
				$this->add_3dsv2_parameters( $genesis, $order, $data, true );
			}
			if ( $this->is_3d_transaction( true ) ) {
				$this->add_sca_exemption_parameters( $genesis );
			}

			$genesis->execute();

			if ( ! $genesis->response()->isSuccessful() ) {
				throw new \Exception( $genesis->response()->getErrorDescription() );
			}

			$response = $genesis->response()->getResponseObject();
			wc_emerchantpay_order_proxy()->save_initial_trx_to_order( $order, $response, $data );

			// Create One-time token to prevent redirect abuse.
			$this->set_one_time_token( $order, static::generate_transaction_id() );

			if ( ! WC_emerchantpay_Subscription_Helper::is_init_gateway_response_successful( $response ) ) {
				$message         = static::get_translated_text( 'We were unable to process your order!<br/>Please double check your data and try again.' );
				$gateway_message = WC_Emerchantpay_Genesis_Helper::fetch_gateway_response_message( $response );

				throw new Exception( "$message $gateway_message" );
			}

			// Save the Checkout Id.
			wc_emerchantpay_order_proxy()->set_order_meta_data( $order, $this->get_checkout_transaction_id_meta_key(), $response->unique_id );
			wc_emerchantpay_order_proxy()->set_order_meta_data( $order, self::META_TRANSACTION_TYPE, $response->transaction_type );
			switch ( true ) {
				case isset( $response->threeds_method_continue_url ):
					$unique_id_hash = hash( 'sha256', $response->unique_id );
					$url_params     = http_build_query(
						array(
							'order_id' => $order_id,
							'checksum' => $unique_id_hash,
						)
					);
					$response_array = $this->create_response(
						WC()->api_request_url( WC_Emerchantpay_Threeds_Form_Helper::class ) . "?{$url_params}",
						$this->is_iframe_blocks()
					);
					break;
				case ( isset( $response->redirect_url ) ):
					$response_array = $this->create_response( $response->redirect_url );
					break;
				default:
					$this->update_order_status( $order, $response );
					if ( ! $this->process_after_init_recurring_payment( $order, $response ) ) {
						throw new Exception( WC_Emerchantpay_Genesis_Helper::fetch_gateway_response_message( $response ) );
					}
					$woocommerce->cart->empty_cart();
					$response_array = $this->create_response( $data['return_success_url'] );
			}

			return $response_array;
		} catch ( \Exception $exception ) {
			$message    = static::get_translated_text( 'Init subscription payment error:' );
			$concat_msg = "$message {$exception->getMessage()}";

			WC_Emerchantpay_Helper::log_exception( $exception );
			// Add the error on the Admin Order view
			$order->add_order_note( $concat_msg );

			WC()->session->reload_checkout = true;

			throw new Exception( esc_html( $concat_msg ) );
		} // End of try block.
	}

	/**
	 * Set the Genesis PHP Lib Credentials, based on the customer's admin settings
	 *
	 * @return void
	 *
	 * @throws \Genesis\Exceptions\InvalidArgument
	 */
	public function set_credentials() {
		parent::set_credentials();

		$terminal_token = $this->get_method_setting( self::SETTING_KEY_TOKEN ) ?? null;

		Config::setToken( $terminal_token );

		if ( ! Config::getToken() ) {
			Config::setForceSmartRouting( true );
		}
	}

	/**
	 * Set the Terminal token associated with an order
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return bool
	 */
	protected function set_terminal_token( $order ) {
		if ( Config::getForceSmartRouting() ) {
			// Skip terminal token when Smart Routing is turned on
			return false;
		}

		// Default Terminal Token, used for Credit Card based transaction
		$terminal_token = wc_emerchantpay_order_proxy()->get_order_meta_data( $order, self::META_TRANSACTION_TERMINAL_TOKEN );

		if ( ! empty( $teminal_token ) ) {
			Config::setToken( $terminal_token );
		}

		// Used for the Recurring transactions. Subscriptions token overrides the default token
		$recurring_token = wc_emerchantpay_order_proxy()->get_order_meta_data( $order, WC_emerchantpay_Subscription_Helper::META_RECURRING_TERMINAL_TOKEN );

		if ( ! empty( $recurring_token ) ) {
			Config::setToken( $recurring_token );
		}

		return true;
	}

	/**
	 * Determines the Recurring Token, which needs to used for the RecurringSale Transactions
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return string
	 */
	protected function get_recurring_token( $order ) {
		$recurring_token = parent::get_recurring_token( $order );

		if ( ! empty( $recurring_token ) ) {
			return $recurring_token;
		}

		return $this->get_method_setting( self::SETTING_KEY_TOKEN );
	}

	/**
	 * Set terminal token or use Smart Router
	 *
	 * @param ArrayObject $notification_object Notification object.
	 *
	 * @return void
	 */
	protected function set_notification_terminal_token( $notification_object ) {
		$terminal_token = $notification_object->terminal_token ?? null;

		if ( $terminal_token && ( ! Config::getToken() ) ) {
			Config::setToken( $terminal_token );
		}
	}

	/**
	 * Sets terminal token for init_recurring and disables smart router
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return void
	 */
	protected function init_recurring_token( $order ) {
		if ( empty( $this->get_recurring_token( $order ) ) ) {
			return;
		}

		Config::setForceSmartRouting( false );
		parent::init_recurring_token( $order );
	}

	/**
	 * Store the initial transaction to order
	 *
	 * @param \stdClass $response_obj Gateway Response Obj
	 * @param WC_Order  $order WC Order
	 * @param array     $data Mapped data
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function save_direct_trx_data_to_order( $response_obj, $order, $data ) {
		wc_emerchantpay_order_proxy()->save_initial_trx_to_order( $order, $response_obj, $data );

		// Empty token will mean that Smart Routing is used
		wc_emerchantpay_order_proxy()->set_order_meta_data( $order, self::META_TRANSACTION_TERMINAL_TOKEN, $this->get_method_setting( self::SETTING_KEY_TOKEN ) );

		// Create One-time token to prevent redirect abuse. Used for 3DSv2 payment flow
		$this->set_one_time_token( $order, static::generate_transaction_id() );
	}

	/**
	 * Check the input and populate Credit Card data
	 *
	 * @param object $order Order object.
	 *
	 * @return array
	 */
	private function populate_cc_data( $order ) {
		$holder = sprintf(
			'%s %s',
			$order->get_billing_first_name(),
			$order->get_billing_last_name()
		);
		// TODO Check fixing the error.
		// phpcs:disable WordPress.Security.NonceVerification
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
		// phpcs:enable
		return array(
			'holder'     => $holder,
			'number'     => $number,
			'expiration' => $expiration,
			'cvv'        => $cvc,
			'encrypted'  => strlen( $number ?? '' ) > 19,
		);
	}

	/**
	 * Retrieve Recurring WPF Transaction Types with translations
	 *
	 * @return array
	 */
	protected function get_recurring_transaction_types() {
		return $this->get_translated_recurring_trx_types(
			array_merge(
				WC_Emerchantpay_Constants::COMMON_RECURRING_PAYMENT_METHODS,
				WC_Emerchantpay_Constants::RECURRING_METHODS_V2,
			)
		);
	}

	/**
	 * Adds 3DSv2 parameters to the Request
	 *
	 * @param Genesis  $genesis      Genesis object.
	 * @param WC_Order $order        Order identifier.
	 * @param array    $data         Request data.
	 * @param bool     $is_recurring Defines that request should be recurring or not. Default false.
	 *
	 * @return void
	 *
	 * @throws Exception Exception class.
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
	 * @param Genesis $genesis Genesis object.
	 * @param array   $data Request data.
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
		// TODO Check fixing the error.
		// phpcs:disable WordPress.Security.NonceVerification
		array_walk(
			$field_names,
			function ( $field_name ) use ( &$data ) {
				$data[ $field_name ] = isset( $_POST[ "{$this->id}_{$field_name}" ] )
					? sanitize_text_field( wp_unslash( $_POST[ "{$this->id}_{$field_name}" ] ) )
					: null;
			}
		);
		// phpcs:enable
		return $data;
	}

	/**
	 * Registers all custom actions used in the payment methods
	 *
	 * @return void
	 */
	private function register_custom_actions() {
		if ( ! ( isset( $this->options['blocks_instantiate'] ) && true === $this->options['blocks_instantiate'] ) ) {
			$this->add_wp_simple_actions(
				self::WC_ACTION_CREDIT_CARD_FORM_START,
				'before_cc_form'
			);
		}
	}

	/**
	 * Set recurring_type attribute according to the chosen transaction type
	 *
	 * @param $genesis
	 * @param $data
	 *
	 * @return void
	 */
	private function set_recurring_attribute( $genesis, $data ) {
		if ( in_array( $data['transaction_type'], array( Types::SALE, Types::SALE_3D ), true ) ) {
			$genesis->request()->setRecurringType( RecurringTypes::INITIAL );
		}
	}
}

WC_emerchantpay_Direct::registerStaticActions();
