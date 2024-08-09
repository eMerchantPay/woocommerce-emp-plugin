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
 * @package     classes\class-wc-emerchantpay-checkout
 */

use Genesis\Api\Constants\i18n;
use Genesis\Api\Constants\Payment\Methods;
use Genesis\Api\Constants\Transaction\Names;
use Genesis\Api\Constants\Transaction\Types;
use Genesis\Api\Constants\Banks;
use Genesis\Utils\Common as CommonUtils;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

if ( ! class_exists( 'WC_Emerchantpay_Method_Base' ) ) {
	require_once dirname( __DIR__ ) . '/classes/class-wc-emerchantpay-method-base.php';
}

/**
 * Emerchantpay Checkout
 *
 * @class   WC_Emerchantpay_Checkout
 * @extends WC_Payment_Gateway
 *
 * @SuppressWarnings(PHPMD)
 */
class WC_Emerchantpay_Checkout extends WC_Emerchantpay_Method_Base {

	/**
	 * Payment Method Code
	 *
	 * @var null|string
	 */
	protected static $method_code = 'emerchantpay_checkout';

	/**
	 * Additional Method Setting Keys
	 */
	const SETTING_KEY_TRANSACTION_TYPES        = 'transaction_types';
	const SETTING_KEY_CHECKOUT_LANGUAGE        = 'checkout_language';
	const SETTING_KEY_INIT_RECURRING_TXN_TYPES = 'init_recurring_txn_types';
	const SETTING_KEY_TOKENIZATION             = 'tokenization';
	const SETTING_KEY_BANK_CODES               = 'bank_codes';
	const SETTING_KEY_WEB_PAYMENT_FORM_ID      = 'web_payment_form_id';

	/**
	 * Additional Order/User Meta Constants
	 */
	const META_CHECKOUT_TRANSACTION_ID  = '_genesis_checkout_id';
	const META_TOKENIZATION_CONSUMER_ID = '_consumer_id';

	/**
	 * Returns module name
	 *
	 * @return string
	 */
	protected function get_module_title() {
		return static::get_translated_text( 'emerchantpay Checkout' );
	}

	/**
	 * Holds the Meta Key used to extract the checkout Transaction
	 *   - Checkout Method -> WPF Unique Id
	 *
	 * @return string
	 */
	protected function get_checkout_transaction_id_meta_key() {
		return self::META_CHECKOUT_TRANSACTION_ID;
	}

	/**
	 * Determines if the a post notification is a valida Gateway Notification
	 *
	 * @param array $post_values Post notifications values.
	 *
	 * @return bool
	 */
	protected function get_is_valid_notification( $post_values ) {
		return parent::get_is_valid_notification( $post_values ) &&
			isset( $post_values['wpf_unique_id'] );
	}

	/**
	 * Setup and initialize this module
	 *
	 * @param array $options  Options array.
	 */
	public function __construct( $options = array() ) {
		parent::__construct( $options );

		$this->has_fields = false;
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
			$this->get_method_has_setting(
				self::SETTING_KEY_TRANSACTION_TYPES
			);
	}

	/**
	 * Return if iframe processing is enabled
	 *
	 * @return bool
	 */
	public function is_iframe_enabled() {
		return $this->get_option( self::SETTING_KEY_IFRAME_PROCESSING ) === self::SETTING_VALUE_YES;
	}

	/**
	 * Override method to show the description instead of any fields
	 *
	 * @return void
	 */
	public function payment_fields() {
		$description = $this->get_description();
		echo esc_html( $description );
	}

	/**
	 * Event Handler for displaying Admin Notices
	 *
	 * @return bool
	 */
	public function admin_notices() {
		if ( ! parent::admin_notices() ) {
			return false;
		}

		$are_api_transaction_types_defined = true;

		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'GET' === $_SERVER['REQUEST_METHOD'] ) {
			if ( ! $this->get_method_has_setting( self::SETTING_KEY_TRANSACTION_TYPES ) ) {
				$are_api_transaction_types_defined = false;
			}
		} elseif ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			$transaction_types_post_param_name = $this->get_method_admin_setting_post_param_name(
				self::SETTING_KEY_TRANSACTION_TYPES
			);
			// TODO Check fixing the error.
			// phpcs:disable WordPress.Security.NonceVerification
			if ( ! isset( $_POST[ $transaction_types_post_param_name ] ) || empty( $_POST[ $transaction_types_post_param_name ] ) ) {
				$are_api_transaction_types_defined = false;
			}
			// phpcs:enable
		}

		if ( ! $are_api_transaction_types_defined ) {
			WC_Emerchantpay_Helper::print_wp_notice(
				static::get_translated_text( 'You must specify at least one transaction type in order to be able to use this payment method!' ),
				WC_Emerchantpay_Helper::WP_NOTICE_TYPE_ERROR
			);
		}

		if ( $this->get_method_bool_setting( self::SETTING_KEY_TOKENIZATION ) ) {
			WC_Emerchantpay_Helper::print_wp_notice(
				static::get_translated_text(
					'Tokenization is enabled for Web Payment Form, ' .
					'please make sure Guest Checkout is disabled.'
				),
				WC_Emerchantpay_Helper::WP_NOTICE_TYPE_ERROR
			);
		}

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
			static::get_translated_text( 'emerchantpay\'s Gateway works by sending your client, to our secure (PCI-DSS certified) server.' );

		parent::init_form_fields();

		$this->form_fields += array(
			self::SETTING_KEY_IFRAME_PROCESSING   => array(
				'type'        => 'checkbox',
				'title'       => static::get_translated_text( 'Enable/Disable' ),
				'label'       => static::get_translated_text( 'Enable payment processing into an iframe' ),
				'default'     => self::SETTING_VALUE_NO,
				'description' => static::get_translated_text(
					'Enable payment processing into an iframe by removing the redirects to the Gateway Web Payment ' .
					'Form Page. The iFrame processing requires a specific setting inside Merchant Console. For more' .
					' info ask: <a href="mailto:tech-support@emerchantpay.com">tech-support@emerchantpay.com</a>'
				),
			),
			self::SETTING_KEY_TRANSACTION_TYPES   => array(
				'type'        => 'multiselect',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Transaction Type' ),
				'options'     => $this->get_wpf_transaction_types(),
				'description' => static::get_translated_text( 'Select transaction type for the payment transaction' ),
				'desc_tip'    => true,
			),
			self::SETTING_KEY_BANK_CODES          => array(
				'type'        => 'multiselect',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Bank code(s) for Online banking' ),
				'options'     => $this->get_available_bank_codes(),
				'description' => static::get_translated_text( 'Select Bank code(s) for Online banking transaction type' ),
				'desc_tip'    => true,
			),
			self::SETTING_KEY_CHECKOUT_LANGUAGE   => array(
				'type'        => 'select',
				'title'       => static::get_translated_text( 'Checkout Language' ),
				'options'     => $this->get_wpf_languages(),
				'description' => __( 'Select language for the customer UI on the remote server' ),
				'desc_tip'    => true,
			),
			self::SETTING_KEY_TOKENIZATION        => array(
				'type'    => 'checkbox',
				'title'   => static::get_translated_text( 'Enable/Disable' ),
				'label'   => static::get_translated_text( 'Enable Tokenization' ),
				'default' => self::SETTING_VALUE_NO,
			),
			self::SETTING_KEY_WEB_PAYMENT_FORM_ID => array(
				'type'        => 'text',
				'title'       => static::get_translated_text( 'Web payment form unique ID:' ),
				'description' => static::get_translated_text( 'The unique ID of the the web payment form configuration to be displayed for the current payment.' ),
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
	 * Retrieve WPF Transaction types from SDK
	 *
	 * @return array
	 */
	protected function get_wpf_transaction_types() {
		$data = array();

		$transaction_types = Types::getWPFTransactionTypes();
		$excluded_types    = $this->excluded_transaction_types();

		// Exclude Transaction types.
		$transaction_types = array_diff( $transaction_types, $excluded_types );

		// Add Google Pay Methods.
		$google_pay_types = array_map(
			function ( $type ) {
				return WC_Emerchantpay_Method_Base::GOOGLE_PAY_TRANSACTION_PREFIX . $type;
			},
			array(
				WC_Emerchantpay_Method_Base::GOOGLE_PAY_PAYMENT_TYPE_AUTHORIZE,
				WC_Emerchantpay_Method_Base::GOOGLE_PAY_PAYMENT_TYPE_SALE,
			)
		);

		// Add PayPal Methods.
		$paypal_types = array_map(
			function ( $type ) {
				return WC_Emerchantpay_Method_Base::PAYPAL_TRANSACTION_PREFIX . $type;
			},
			array(
				WC_Emerchantpay_Method_Base::PAYPAL_PAYMENT_TYPE_AUTHORIZE,
				WC_Emerchantpay_Method_Base::PAYPAL_PAYMENT_TYPE_SALE,
				WC_Emerchantpay_Method_Base::PAYPAL_PAYMENT_TYPE_EXPRESS,
			)
		);

		// Add Apple Pay Methods.
		$apple_pay_types = array_map(
			function ( $type ) {
				return WC_Emerchantpay_Method_Base::APPLE_PAY_TRANSACTION_PREFIX . $type;
			},
			array(
				WC_Emerchantpay_Method_Base::APPLE_PAY_PAYMENT_TYPE_AUTHORIZE,
				WC_Emerchantpay_Method_Base::APPLE_PAY_PAYMENT_TYPE_SALE,
			)
		);

		$transaction_types = array_merge(
			$transaction_types,
			$google_pay_types,
			$paypal_types,
			$apple_pay_types
		);
		asort( $transaction_types );

		foreach ( $transaction_types as $type ) {
			$name = Names::getName( $type );
			if ( ! Types::isValidTransactionType( $type ) ) {
				$name = strtoupper( $type );
			}

			$data[ $type ] = static::get_translated_text( $name );
		}

		return $data;
	}

	/**
	 * List of transaction types to be excluded
	 *
	 * @return array
	 */
	protected function excluded_transaction_types() {
		$excluded_init_recurring_types = array_keys( $this->get_wpf_recurring_transaction_types() );

		$excluded_types = array(
			Types::PPRO,        // Exclude PPRO transaction. This is not standalone transaction type.
			Types::GOOGLE_PAY,  // Exclude GooglePay transaction in order to provide choosable payment types.
			Types::PAY_PAL,     // Exclude PayPal transaction in order to provide choosable payment types.
			Types::APPLE_PAY,    // Exclude Apple Pay transaction in order to provide choosable payment types.
		);

		return array_merge( $excluded_init_recurring_types, $excluded_types );
	}

	/**
	 * Return available Bank codes for Online banking transaction type
	 *
	 * @return array
	 */
	protected function get_available_bank_codes() {
		return array(
			Banks::CPI => 'Interac Combined Pay-in',
			Banks::BCT => 'Bancontact',
			Banks::BLK => 'BLIK',
			Banks::SE  => 'SPEI',
			Banks::PID => 'LatiPay',
		);
	}

	/**
	 * Retrieve WPF available languages
	 *
	 * @return array
	 */
	protected function get_wpf_languages() {
		$data = array();

		$names = array(
			i18n::AR => 'Arabic',
			i18n::BG => 'Bulgarian',
			i18n::DE => 'German',
			i18n::EN => 'English',
			i18n::ES => 'Spanish',
			i18n::FR => 'French',
			i18n::HI => 'Hindu',
			i18n::JA => 'Japanese',
			i18n::IS => 'Icelandic',
			i18n::IT => 'Italian',
			i18n::NL => 'Dutch',
			i18n::PT => 'Portuguese',
			i18n::PL => 'Polish',
			i18n::RU => 'Russian',
			i18n::TR => 'Turkish',
			i18n::ZH => 'Mandarin Chinese',
			i18n::ID => 'Indonesian',
			i18n::MS => 'Malay',
			i18n::TH => 'Thai',
			i18n::CS => 'Czech',
			i18n::HR => 'Croatian',
			i18n::SL => 'Slovenian',
			i18n::FI => 'Finnish',
			i18n::NO => 'Norwegian',
			i18n::DA => 'Danish',
			i18n::SV => 'Swedish',
		);

		foreach ( i18n::getAll() as $language ) {
			$name = array_key_exists( $language, $names ) ? $names[ $language ] : strtoupper( $language );

			$data[ $language ] = self::get_translated_text( $name );
		}

		asort( $data );

		return $data;
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
				self::SETTING_KEY_INIT_RECURRING_TXN_TYPES => array(
					'type'        => 'multiselect',
					'css'         => 'height:auto',
					'title'       => static::get_translated_text( 'Init Recurring Transaction Types' ),
					'options'     => $this->get_wpf_recurring_transaction_types(),
					'description' => static::get_translated_text( 'Select transaction types for the initial recurring transaction' ),
					'desc_tip'    => true,
				),
			)
		);
	}

	/**
	 * Retrieve Recurring WPF Transaction Types with translations
	 *
	 * @return array
	 */
	protected function get_wpf_recurring_transaction_types() {
		return array(
			Types::INIT_RECURRING_SALE     =>
				static::get_translated_text( Names::getName( Types::INIT_RECURRING_SALE ) ),
			Types::INIT_RECURRING_SALE_3D  =>
				static::get_translated_text( Names::getName( Types::INIT_RECURRING_SALE_3D ) ),
			Types::SDD_INIT_RECURRING_SALE =>
				static::get_translated_text( Names::getName( Types::SDD_INIT_RECURRING_SALE ) ),
		);
	}

	/**
	 * Returns a list with data used for preparing a request to the gateway
	 *
	 * @param WC_Order $order Order object.
	 * @param bool     $is_recurring Defines that request should be recurring or not. Default false.
	 *
	 * @return array
	 * @throws Exception Throws invalid Woocommerce Order.
	 */
	protected function populate_gate_request_data( $order, $is_recurring = false ) {
		$data       = parent::populate_gate_request_data( $order, $is_recurring );
		$return_url = $order->get_view_order_url();

		if ( $this->get_method_setting( self::SETTING_KEY_REDIRECT_CANCEL ) === self::SETTING_VALUE_CHECKOUT ) {
			$return_url = wc_get_checkout_url();
		}

		$return_cancel_url = $this->build_iframe_url( $order->get_cancel_order_url_raw( wp_slash( $return_url ) ) );

		return array_merge(
			$data,
			array(
				'return_cancel_url' => $return_cancel_url,
			)
		);
	}

	/**
	 *  Prepare Initial Genesis Request
	 *
	 * @param array $data Request data.
	 *
	 * @return \Genesis\Genesis
	 * @throws \Genesis\Exceptions\DeprecatedMethod Deprecated method exception.
	 * @throws \Genesis\Exceptions\ErrorParameter Error parameter exception.
	 * @throws \Genesis\Exceptions\InvalidArgument Invalid argument exception.
	 * @throws \Genesis\Exceptions\InvalidMethod Invalid method exception.
	 */
	protected function prepare_initial_genesis_request( $data ) {
		$genesis = new \Genesis\Genesis( 'Wpf\Create' );

		/**
		 * WPF request
		 *
		 * @var \Genesis\Api\Request\Wpf\Create $wpf_request
		 */
		$wpf_request = $genesis->request();

		$wpf_request
			->setTransactionId(
				$data['transaction_id']
			)
			->setCurrency(
				$data['currency']
			)
			->setAmount(
				$data['amount']
			)
			->setUsage(
				$data['usage']
			)
			->setDescription(
				$data['description']
			)
			->setCustomerEmail(
				$data['customer_email']
			)
			->setCustomerPhone(
				$data['customer_phone']
			);

		/**
		 * Notification & Urls
		 */
		$wpf_request
			->setNotificationUrl(
				$data['notification_url']
			)
			->setReturnSuccessUrl(
				$data['return_success_url']
			)
			->setReturnPendingUrl(
				$data['return_success_url']
			)
			->setReturnFailureUrl(
				$data['return_failure_url']
			)
			->setReturnCancelUrl(
				$data['return_cancel_url']
			);

		/**
		 * Billing
		 */
		$wpf_request
			->setBillingFirstName(
				$data['billing']['first_name']
			)
			->setBillingLastName(
				$data['billing']['last_name']
			)
			->setBillingAddress1(
				$data['billing']['address1']
			)
			->setBillingAddress2(
				$data['billing']['address2']
			)
			->setBillingZipCode(
				$data['billing']['zip_code']
			)
			->setBillingCity(
				$data['billing']['city']
			)
			->setBillingState(
				$data['billing']['state']
			)
			->setBillingCountry(
				$data['billing']['country']
			);

		/**
		 * Shipping
		 */
		$wpf_request
			->setShippingFirstName(
				$data['shipping']['first_name']
			)
			->setShippingLastName(
				$data['shipping']['last_name']
			)
			->setShippingAddress1(
				$data['shipping']['address1']
			)
			->setShippingAddress2(
				$data['shipping']['address2']
			)
			->setShippingZipCode(
				$data['shipping']['zip_code']
			)
			->setShippingCity(
				$data['shipping']['city']
			)
			->setShippingState(
				$data['shipping']['state']
			)
			->setShippingCountry(
				$data['shipping']['country']
			);

		/**
		 * WPF Language
		 */
		if ( $this->get_method_has_setting( self::SETTING_KEY_CHECKOUT_LANGUAGE ) ) {
			$wpf_request->setLanguage(
				$this->get_method_setting( self::SETTING_KEY_CHECKOUT_LANGUAGE )
			);
		}

		if ( $this->is_tokenization_available( $data['customer_email'] ) ) {
			$consumer_id = $this->get_gateway_consumer_id_for( $data['customer_email'] );

			if ( empty( $consumer_id ) ) {
				$consumer_id = $this->retrieve_consumer_id_from_email( $data['customer_email'] );
			}

			if ( $consumer_id ) {
				$wpf_request->setConsumerId( $consumer_id );
			}

			$wpf_request->setRememberCard( true );
		}

		/**
		 * WPF Web form unique id
		 */
		$wpf_request->setWebPaymentFormId( $this->get_method_setting( self::SETTING_KEY_WEB_PAYMENT_FORM_ID ) );

		return $genesis;
	}

	/**
	 * Checks availability of Tokenization
	 *
	 * @param string $customer_email Customer e-mail.
	 *
	 * @return bool
	 */
	protected function is_tokenization_available( $customer_email ) {
		return ! empty( $customer_email ) &&
			$this->get_method_bool_setting( self::SETTING_KEY_TOKENIZATION ) &&
				get_current_user_id() !== 0;
	}

	/**
	 * Returns customer e-mail
	 *
	 * @param string $customer_email Customer e-mail.
	 *
	 * @return string|null
	 */
	protected function get_gateway_consumer_id_for( $customer_email ) {
		$meta = $this->get_meta_consumer_id_for_logged_user();

		return ! empty( $meta[ $customer_email ] ) ? $meta[ $customer_email ] : null;
	}

	/**
	 * Returns consumer id from e-mail
	 *
	 * @param string $email The email from which the consumer ID is obtained.
	 *
	 * @return null|int
	 */
	protected function retrieve_consumer_id_from_email( $email ) {
		try {
			$genesis = new \Genesis\Genesis( 'NonFinancial\Consumers\Retrieve' );
			$genesis->request()->setEmail( $email );

			$genesis->execute();

			$response = $genesis->response()->getResponseObject();

			if ( ! empty( $response->consumer_id ) ) {
				return $response->consumer_id;
			}
		} catch ( \Exception $exception ) {
			return null;
		}

		return null;
	}

	/**
	 * Checks that user is logged
	 *
	 * @return array
	 */
	protected function get_meta_consumer_id_for_logged_user() {
		if ( ! WC_Emerchantpay_Helper::is_user_logged() ) {
			return array();
		}

		$meta = json_decode(
			get_user_meta( get_current_user_id(), self::META_TOKENIZATION_CONSUMER_ID, true ),
			true
		);

		return is_array( $meta ) ? $meta : array();
	}

	/**
	 * Updates meta for logged user
	 *
	 * @param string $customer_email Customer e-mail.
	 * @param string $consumer_id Consumer ID.
	 */
	protected function set_gateway_consumer_id_for( $customer_email, $consumer_id ) {
		if ( ! WC_Emerchantpay_Helper::is_user_logged() ) {
			return;
		}

		$meta = $this->get_meta_consumer_id_for_logged_user();

		$meta[ $customer_email ] = $consumer_id;

		update_user_meta( get_current_user_id(), self::META_TOKENIZATION_CONSUMER_ID, wp_json_encode( $meta ) );
	}

	/**
	 * Add transaction type to Gateway request.
	 *
	 * @param \Genesis\Genesis $genesis Genesis instance.
	 * @param WC_Order         $order Order object.
	 * @param array            $request_data Requested data.
	 * @param bool             $is_recurring Defines that request should be recurring or not. Default false.
	 *
	 * @return void
	 * @throws \Genesis\Exceptions\ErrorParameter Throws error parameter exception.
	 */
	protected function add_transaction_types_to_gateway_request( $genesis, $order, $request_data, $is_recurring ) {

		/**
		 * Web Payment Form request object.
		 *
		 * @var \Genesis\Api\Request\Wpf\Create $wpf_request
		 */
		$wpf_request = $genesis->request();

		if ( $is_recurring ) {
			$recurring_types = $this->get_recurring_payment_types();
			foreach ( $recurring_types as $type ) {
				$wpf_request->addTransactionType( $type );
			}

			return;
		}

		$this->addCustomParametersToTrxTypes( $wpf_request, $order, $request_data );
	}

	/**
	 * Add customer parameters to transaction types
	 *
	 * @param \Genesis\Api\Request\Wpf\Create $wpf_request Web Payment Form request object.
	 * @param WC_Order                        $order Order object.
	 * @param array                           $request_data Request data object.
	 *
	 * @throws \Genesis\Exceptions\ErrorParameter Error parameters exception.
	 */
	private function addCustomParametersToTrxTypes( $wpf_request, WC_Order $order, $request_data ) {
		$types                     = $this->get_payment_types();
		$transaction_custom_params = array();

		foreach ( $types as $type ) {
			if ( is_array( $type ) ) {
				$wpf_request->addTransactionType( $type['name'], $type['parameters'] );

				continue;
			}

			switch ( $type ) {
				case Types::IDEBIT_PAYIN:
				case Types::INSTA_DEBIT_PAYIN:
					$user_id_hash              = WC_emerchantpay_Genesis_Helper::get_current_user_id_hash();
					$transaction_custom_params = array(
						'customer_account_id' => $user_id_hash,
					);
					break;
				case Types::KLARNA_AUTHORIZE:
					$transaction_custom_params = WC_emerchantpay_Order_Helper::get_klarna_custom_param_items( $order )->toArray();
					break;
				case Types::TRUSTLY_SALE:
					$user_id         = WC_emerchantpay_Genesis_Helper::get_current_user_id();
					$trustly_user_id = empty( $user_id ) ? WC_emerchantpay_Genesis_Helper::get_current_user_id_hash() : $user_id;

					$transaction_custom_params = array(
						'user_id' => $trustly_user_id,
					);
					break;
				case Types::ONLINE_BANKING_PAYIN:
					$available_bank_codes = $this->get_method_setting( self::SETTING_KEY_BANK_CODES );
					if ( CommonUtils::isValidArray( $available_bank_codes ) ) {
						$transaction_custom_params['bank_codes'] = array_map(
							function ( $value ) {
								return array(
									'bank_code' => $value,
								);
							},
							$available_bank_codes
						);
					}
					break;
				case Types::PAYSAFECARD:
					$user_id     = WC_emerchantpay_Genesis_Helper::get_current_user_id();
					$customer_id = empty( $user_id ) ? WC_emerchantpay_Genesis_Helper::get_current_user_id_hash() : $user_id;

					$transaction_custom_params = array(
						'customer_id' => $customer_id,
					);
					break;
				default:
					$transaction_custom_params = array();
			}

			$wpf_request->addTransactionType( $type, $transaction_custom_params );
		}
	}

	/**
	 * Initiate Order Checkout session
	 *
	 * @param int $order_id Order identifier.
	 *
	 * @return array|bool
	 * @throws Exception Throws exception if appeared error when initiating Web Payment Form.
	 */
	protected function process_order_payment( $order_id ) {
		return $this->process_common_payment( $order_id, false );
	}

	/**
	 * Initiate Gateway Payment Session
	 *
	 * @param int $order_id Order identifier.
	 *
	 * @return array|bool
	 * @throws Exception Throws exception if appeared error when initiating Web Payment Form.
	 */
	protected function process_init_subscription_payment( $order_id ) {
		return $this->process_common_payment( $order_id, true );
	}

	/**
	 * Initiate Order Checkout session
	 *   or
	 * Init Recurring Checkout
	 *
	 * @param int  $order_id Order identifier.
	 * @param bool $is_recurring Defines that request should be recurring or not. Default false.
	 *
	 * @return array|bool
	 * @throws \Exception Throws exception if appeared error when initiating Web Payment Form.
	 */
	protected function process_common_payment( $order_id, $is_recurring ) {
		$order = new WC_Order( absint( $order_id ) );
		$data  = $this->populate_gate_request_data( $order, $is_recurring );

		try {
			$this->set_credentials();

			$genesis = $this->prepare_initial_genesis_request( $data );
			$genesis = $this->add_business_data_to_gateway_request( $genesis, $order );
			$this->add_transaction_types_to_gateway_request( $genesis, $order, $data, $is_recurring );

			if ( $this->is_3dsv2_enabled() ) {
				$this->add_3dsv2_parameters_to_gateway_request( $genesis, $order, $is_recurring );
			}
			$this->add_sca_exemption_parameters( $genesis );

			$genesis->execute();

			if ( ! $genesis->response()->isSuccessful() ) {
				throw new \Exception( $genesis->response()->getErrorDescription() );
			}

			$response = $genesis->response()->getResponseObject();

			if ( $genesis->response()->isNew() && isset( $response->redirect_url ) ) {
				$this->save_checkout_trx_to_order( $response, $order );

				if ( ! empty( $data['customer_email'] ) ) {
					$this->save_tokenization_data( $data['customer_email'], $response );
				}

				return $this->create_response( $response->redirect_url, $this->is_iframe_blocks() );
			}

			$message         = static::get_translated_text( 'An error has been encountered while initiating Web Payment Form! Please try again later.' );
			$gateway_message = WC_Emerchantpay_Genesis_Helper::fetch_gateway_response_message( $response );

			throw new \Exception( "$message $gateway_message" );
		} catch ( \Exception $exception ) {
			$message    = static::get_translated_text( 'Checkout payment error:' );
			$concat_msg = "$message {$exception->getMessage()}";

			WC_Emerchantpay_Helper::log_exception( $exception );
			// Adds the error on the Admin Order view in the notes
			$order->add_order_note( $concat_msg );

			throw new \Exception( esc_html( $concat_msg ) );
		}
	}

	/**
	 * Sets transaction to order transaction
	 *
	 * @param stdClass $response_obj Response object from Gateway.
	 * @param WC_Order $order Order identifier.
	 *
	 * @throws Exception
	 */
	protected function save_checkout_trx_to_order( $response_obj, $order ) {
		// Save the Checkout Id.
		wc_emerchantpay_order_proxy()->set_order_meta_data( $order, self::META_CHECKOUT_TRANSACTION_ID, $response_obj->unique_id );

		// Save whole trx.
		wc_emerchantpay_order_proxy()->save_initial_trx_to_order( $order, $response_obj );

		// Create One-time token to prevent redirect abuse.
		$this->set_one_time_token( $order, self::generate_transaction_id() );
	}

	/**
	 * Save`s  tokanization data
	 *
	 * @param string   $customer_email Customer e-mail.
	 * @param stdClass $response_obj Response object.
	 */
	protected function save_tokenization_data( $customer_email, $response_obj ) {
		if ( ! empty( $response_obj->consumer_id ) ) {
			$this->set_gateway_consumer_id_for( $customer_email, $response_obj->consumer_id );
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
		$token = wc_emerchantpay_order_proxy()->get_order_meta_data( $order, self::META_TRANSACTION_TERMINAL_TOKEN );

		// Check for Recurring Token.
		if ( empty( $token ) ) {
			$token = wc_emerchantpay_order_proxy()->get_order_meta_data( $order, WC_emerchantpay_Subscription_Helper::META_RECURRING_TERMINAL_TOKEN );
		}

		if ( empty( $token ) ) {
			return false;
		}

		\Genesis\Config::setToken( $token );

		return true;
	}

	/**
	 * Get payment/transaction types array
	 *
	 * @return array
	 */
	private function get_payment_types() {
		$processed_list = array();
		$selected_types = $this->order_card_transaction_types( $this->get_method_setting( self::SETTING_KEY_TRANSACTION_TYPES ) );
		$alias_map      = array(
			self::GOOGLE_PAY_TRANSACTION_PREFIX . self::GOOGLE_PAY_PAYMENT_TYPE_AUTHORIZE => Types::GOOGLE_PAY,
			self::GOOGLE_PAY_TRANSACTION_PREFIX . self::GOOGLE_PAY_PAYMENT_TYPE_SALE      => Types::GOOGLE_PAY,
			self::PAYPAL_TRANSACTION_PREFIX . self::PAYPAL_PAYMENT_TYPE_AUTHORIZE         => Types::PAY_PAL,
			self::PAYPAL_TRANSACTION_PREFIX . self::PAYPAL_PAYMENT_TYPE_SALE              => Types::PAY_PAL,
			self::PAYPAL_TRANSACTION_PREFIX . self::PAYPAL_PAYMENT_TYPE_EXPRESS           => Types::PAY_PAL,
			self::APPLE_PAY_TRANSACTION_PREFIX . self::APPLE_PAY_PAYMENT_TYPE_AUTHORIZE   => Types::APPLE_PAY,
			self::APPLE_PAY_TRANSACTION_PREFIX . self::APPLE_PAY_PAYMENT_TYPE_SALE        => Types::APPLE_PAY,
		);

		foreach ( $selected_types as $selected_type ) {
			if ( array_key_exists( $selected_type, $alias_map ) ) {
				$transaction_type = $alias_map[ $selected_type ];

				$processed_list[ $transaction_type ]['name'] = $transaction_type;

				$key = $this->get_custom_parameter_key( $transaction_type );

				$processed_list[ $transaction_type ]['parameters'][] = array(
					$key => str_replace(
						array(
							self::GOOGLE_PAY_TRANSACTION_PREFIX,
							self::PAYPAL_TRANSACTION_PREFIX,
							self::APPLE_PAY_TRANSACTION_PREFIX,
						),
						'',
						$selected_type
					),
				);
			} else {
				$processed_list[] = $selected_type;
			}
		}

		return $processed_list;
	}

	/**
	 * Returns recurring method
	 *
	 * @return array
	 */
	protected function get_recurring_payment_types() {
		return $this->get_method_setting( self::SETTING_KEY_INIT_RECURRING_TXN_TYPES );
	}

	/**
	 * Check that subscription is enabled
	 *
	 * @return bool
	 */
	protected function is_subscription_enabled() {
		return parent::is_subscription_enabled() &&
			$this->get_method_has_setting(
				self::SETTING_KEY_INIT_RECURRING_TXN_TYPES
			);
	}

	/**
	 * Determines the Recurring Token, which needs to used for the RecurringSale Transactions
	 *
	 * @param WC_Order $order WC Order Object.
	 * @return string
	 */
	protected function get_recurring_token( $order ) {
		$recurring_token = parent::get_recurring_token( $order );

		if ( ! empty( $recurring_token ) ) {
			return $recurring_token;
		}

		return WC_emerchantpay_Subscription_Helper::get_terminal_token_meta_from_subscription_order( $order );
	}

	/**
	 * Returns custome parameter by added transaction type
	 *
	 * @param string $transaction_type Transaction type.
	 * @return string
	 */
	private function get_custom_parameter_key( $transaction_type ) {
		switch ( $transaction_type ) {
			case Types::PAY_PAL:
				$result = 'payment_type';
				break;
			case Types::GOOGLE_PAY:
			case Types::APPLE_PAY:
				$result = 'payment_subtype';
				break;
			default:
				$result = 'unknown';
		}

		return $result;
	}

	/**
	 * Order transaction types with Card Transaction types in front
	 *
	 * @param array $selected_types Selected transaction types.
	 *
	 * @return array
	 */
	private function order_card_transaction_types( $selected_types ) {
		$credit_card_types = Types::getCardTransactionTypes();

		asort( $selected_types );

		$sorted_array = array_intersect( $credit_card_types, $selected_types );

		return array_merge(
			$sorted_array,
			array_diff( $selected_types, $sorted_array )
		);
	}
}

WC_Emerchantpay_Checkout::registerStaticActions();
