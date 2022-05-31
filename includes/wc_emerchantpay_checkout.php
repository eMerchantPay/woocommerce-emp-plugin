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

use Genesis\API\Constants\Transaction\Names;
use Genesis\API\Constants\Transaction\Types;
use Genesis\API\Constants\Banks;
use Genesis\Utils\Common as CommonUtils;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

if ( ! class_exists( 'WC_emerchantpay_Method' ) ) {
	require_once dirname( dirname( __FILE__ ) ) . '/classes/wc_emerchantpay_method_base.php';
}

/**
 * emerchantpay Checkout
 *
 * @class   WC_emerchantpay_Checkout
 * @extends WC_Payment_Gateway
 *
 * @SuppressWarnings(PHPMD)
 */
class WC_emerchantpay_Checkout extends WC_emerchantpay_Method {

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

	/**
	 * Additional Order/User Meta Constants
	 */
	const META_CHECKOUT_TRANSACTION_ID  = '_genesis_checkout_id';
	const META_TOKENIZATION_CONSUMER_ID = '_consumer_id';

	/**
	 * @return string
	 */
	protected function getModuleTitle() {
		return static::getTranslatedText( 'emerchantpay Checkout' );
	}

	/**
	 * Holds the Meta Key used to extract the checkout Transaction
	 *   - Checkout Method -> WPF Unique Id
	 *
	 * @return string
	 */
	protected function getCheckoutTransactionIdMetaKey() {
		return self::META_CHECKOUT_TRANSACTION_ID;
	}

	/**
	 * Determines if the a post notification is a valida Gateway Notification
	 *
	 * @param array $postValues
	 * @return bool
	 */
	protected function getIsValidNotification( $postValues ) {
		return parent::getIsValidNotification( $postValues ) &&
			isset( $postValues['wpf_unique_id'] );
	}

	/**
	 * Setup and initialize this module
	 */
	public function __construct() {
		parent::__construct();
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
			$this->getMethodHasSetting(
				self::SETTING_KEY_TRANSACTION_TYPES
			);
	}

	/**
	 * Determines if the Payment Method can be used for the configured Store
	 *
	 * @return bool
	 */
	protected function is_applicable() {
		return parent::is_applicable();
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

		$areApiTransactionTypesDefined = true;

		if ( $_SERVER['REQUEST_METHOD'] == 'GET' ) {
			if ( ! $this->getMethodHasSetting( self::SETTING_KEY_TRANSACTION_TYPES ) ) {
				$areApiTransactionTypesDefined = false;
			}
		} elseif ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
			$transactionTypesPostParamName = $this->getMethodAdminSettingPostParamName(
				self::SETTING_KEY_TRANSACTION_TYPES
			);

			if ( ! isset( $_POST[ $transactionTypesPostParamName ] ) || empty( $_POST[ $transactionTypesPostParamName ] ) ) {
				$areApiTransactionTypesDefined = false;

			}
		}

		if ( ! $areApiTransactionTypesDefined ) {
			WC_emerchantpay_Helper::printWpNotice(
				static::getTranslatedText( 'You must specify at least one transaction type in order to be able to use this payment method!' ),
				WC_emerchantpay_Helper::WP_NOTICE_TYPE_ERROR
			);
		}

		if ( $this->getMethodBoolSetting( self::SETTING_KEY_TOKENIZATION ) ) {
			WC_emerchantpay_Helper::printWpNotice(
				static::getTranslatedText(
					'Tokenization is enabled for Web Payment Form, ' .
					'please make sure Guest Checkout is disabled.'
				),
				WC_emerchantpay_Helper::WP_NOTICE_TYPE_ERROR
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
		// Admin description
		$this->method_description =
			static::getTranslatedText( 'emerchantpay\'s Gateway works by sending your client, to our secure (PCI-DSS certified) server.' );

		parent::init_form_fields();

		$this->form_fields += array(
			self::SETTING_KEY_TRANSACTION_TYPES => array(
				'type'        => 'multiselect',
				'css'         => 'height:auto',
				'title'       => static::getTranslatedText( 'Transaction Type' ),
				'options'     => $this->get_wpf_transaction_types(),
				'description' => static::getTranslatedText( 'Select transaction type for the payment transaction' ),
				'desc_tip'    => true,
			),
			self::SETTING_KEY_BANK_CODES        => array(
				'type'        => 'multiselect',
				'css'         => 'height:auto',
				'title'       => static::getTranslatedText( 'Bank code(s) for Online banking' ),
				'options'     => $this->get_available_bank_codes(),
				'description' => static::getTranslatedText( 'Select Bank code(s) for Online banking transaction type' ),
				'desc_tip'    => true,
			),
			self::SETTING_KEY_CHECKOUT_LANGUAGE => array(
				'type'        => 'select',
				'title'       => static::getTranslatedText( 'Checkout Language' ),
				'options'     => $this->get_wpf_languages(),
				'description' => __( 'Select language for the customer UI on the remote server' ),
				'desc_tip'    => true,
			),
			self::SETTING_KEY_TOKENIZATION      => array(
				'type'    => 'checkbox',
				'title'   => static::getTranslatedText( 'Enable/Disable' ),
				'label'   => static::getTranslatedText( 'Enable Tokenization' ),
				'default' => self::SETTING_VALUE_NO,
			),
		);

		$this->form_fields += $this->build_subscription_form_fields();

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
		$excluded_types    = array_map(
			function ( $value ) {
				return $value;
			},
			$this->get_wpf_recurring_transaction_types()
		);

		// Exclude SDD Recurring
		array_push( $excluded_types, Types::SDD_INIT_RECURRING_SALE );

		// Exlucde PPRO transaction. This is not standalone transaction type
		array_push( $excluded_types, Types::PPRO );

		// Exclude GooglePay transaction in order to provide choosable payment types
		array_push( $excluded_types, Types::GOOGLE_PAY );

		// Exclude PayPal transaction in order to provide choosable payment types
		array_push( $excluded_types, Types::PAY_PAL );

		// Exclude Apple Pay transaction in order to provide choosable payment types
		array_push( $excluded_types, Types::APPLE_PAY );

		// Exclude Transaction types
		$transaction_types = array_diff( $transaction_types, $excluded_types );

		// Add PPRO Types
		$ppro_types = array_map(
			function ( $type ) {
				return $type . WC_emerchantpay_Method::PPRO_TRANSACTION_SUFFIX;
			},
			\Genesis\API\Constants\Payment\Methods::getMethods()
		);

		// Add Google Pay Methods
		$google_pay_types = array_map(
			function ( $type ) {
				return WC_emerchantpay_Method::GOOGLE_PAY_TRANSACTION_PREFIX . $type;
			},
			[
				WC_emerchantpay_Method::GOOGLE_PAY_PAYMENT_TYPE_AUTHORIZE,
				WC_emerchantpay_Method::GOOGLE_PAY_PAYMENT_TYPE_SALE,
			]
		);

		// Add PayPal Methods
		$paypal_types = array_map(
			function ( $type ) {
				return WC_emerchantpay_Method::PAYPAL_TRANSACTION_PREFIX . $type;
			},
			[
				WC_emerchantpay_Method::PAYPAL_PAYMENT_TYPE_AUTHORIZE,
				WC_emerchantpay_Method::PAYPAL_PAYMENT_TYPE_SALE,
				WC_emerchantpay_Method::PAYPAL_PAYMENT_TYPE_EXPRESS,
			]
		);

		// Add Apple Pay Methods
		$apple_pay_types = array_map(
			function ( $type ) {
				return WC_emerchantpay_Method::APPLE_PAY_TRANSACTION_PREFIX . $type;
			},
			[
				WC_emerchantpay_Method::APPLE_PAY_PAYMENT_TYPE_AUTHORIZE,
				WC_emerchantpay_Method::APPLE_PAY_PAYMENT_TYPE_SALE,
			]
		);

		$transaction_types = array_merge(
			$transaction_types,
			$ppro_types,
			$google_pay_types,
			$paypal_types,
			$apple_pay_types
		);
		asort( $transaction_types );

		foreach ( $transaction_types as $type ) {
			$name = \Genesis\API\Constants\Transaction\Names::getName( $type );
			if ( ! \Genesis\API\Constants\Transaction\Types::isValidTransactionType( $type ) ) {
				$name = strtoupper( $type );
			}

			$data[ $type ] = static::getTranslatedText( $name );
		}

		return $data;
	}

	/**
	 * Return available Bank codes for Online banking transaction type
	 *
	 * @return array
	 */
	protected function get_available_bank_codes() {
		return array(
			Banks::CPI => 'Interac Combined Pay-in',
		);
	}

	/**
	 * Retrieve WPF available languages
	 *
	 * @return array
	 */
	protected function get_wpf_languages() {
		$data = array();

		$names = [
			\Genesis\API\Constants\i18n::AR => 'Arabic',
			\Genesis\API\Constants\i18n::BG => 'Bulgarian',
			\Genesis\API\Constants\i18n::DE => 'German',
			\Genesis\API\Constants\i18n::EN => 'English',
			\Genesis\API\Constants\i18n::ES => 'Spanish',
			\Genesis\API\Constants\i18n::FR => 'French',
			\Genesis\API\Constants\i18n::HI => 'Hindu',
			\Genesis\API\Constants\i18n::JA => 'Japanese',
			\Genesis\API\Constants\i18n::IS => 'Icelandic',
			\Genesis\API\Constants\i18n::IT => 'Italian',
			\Genesis\API\Constants\i18n::NL => 'Dutch',
			\Genesis\API\Constants\i18n::PT => 'Portuguese',
			\Genesis\API\Constants\i18n::PL => 'Polish',
			\Genesis\API\Constants\i18n::RU => 'Russian',
			\Genesis\API\Constants\i18n::TR => 'Turkish',
			\Genesis\API\Constants\i18n::ZH => 'Mandarin Chinese',
			\Genesis\API\Constants\i18n::ID => 'Indonesian',
			\Genesis\API\Constants\i18n::MS => 'Malay',
			\Genesis\API\Constants\i18n::TH => 'Thai',
			\Genesis\API\Constants\i18n::CS => 'Czech',
			\Genesis\API\Constants\i18n::HR => 'Croatian',
			\Genesis\API\Constants\i18n::SL => 'Slovenian',
			\Genesis\API\Constants\i18n::FI => 'Finnish',
			\Genesis\API\Constants\i18n::NO => 'Norwegian',
			\Genesis\API\Constants\i18n::DA => 'Danish',
			\Genesis\API\Constants\i18n::SV => 'Swedish',
		];

		foreach ( \Genesis\API\Constants\i18n::getAll() as $language ) {
			$name = array_key_exists( $language, $names ) ? $names[ $language ] : strtoupper( $language );

			$data[ $language ] = self::getTranslatedText( $name );
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
					'title'       => static::getTranslatedText( 'Init Recurring Transaction Types' ),
					'options'     => $this->get_wpf_recurring_transaction_types(),
					'description' => static::getTranslatedText( 'Select transaction types for the initial recurring transaction' ),
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
			Types::INIT_RECURRING_SALE =>
				static::getTranslatedText( Names::getName( Types::INIT_RECURRING_SALE ) ),
			Types::INIT_RECURRING_SALE_3D =>
				static::getTranslatedText( Names::getName( Types::INIT_RECURRING_SALE_3D ) ),
		);
	}

	/**
	 * Returns a list with data used for preparing a request to the gateway
	 *
	 * @param WC_Order $order
	 * @param bool     $isRecurring
	 * @return array
	 */
	protected function populateGateRequestData( $order, $isRecurring = false ) {
		$data = parent::populateGateRequestData( $order, $isRecurring );

		return array_merge(
			$data,
			array(
				'return_cancel_url' => $order->get_cancel_order_url_raw(),
			)
		);
	}

	/**
	 * @param array $data
	 * @return \Genesis\Genesis
	 */
	protected function prepareInitialGenesisRequest( $data ) {
		$genesis = new \Genesis\Genesis( 'WPF\Create' );

		/** @var \Genesis\API\Request\WPF\Create $wpf_request */
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
		if ( $this->getMethodHasSetting( self::SETTING_KEY_CHECKOUT_LANGUAGE ) ) {
			$wpf_request->setLanguage(
				$this->getMethodSetting( self::SETTING_KEY_CHECKOUT_LANGUAGE )
			);
		}

		if ( $this->isTokenizationAvailable( $data['customer_email'] ) ) {
			$consumer_id = $this->getGatewayConsumerIdFor( $data['customer_email'] );

			if ( empty( $consumer_id ) ) {
				$consumer_id = $this->retrieveConsumerIdFromEmail( $data['customer_email'] );
			}

			if ( $consumer_id ) {
				$wpf_request->setConsumerId( $consumer_id );
			}

			$wpf_request->setRememberCard( true );
		}

		return $genesis;
	}

	/**
	 * @param $customerEmail
	 *
	 * @return bool
	 */
	protected function isTokenizationAvailable( $customerEmail ) {
		return ! empty( $customerEmail ) &&
			   $this->getMethodBoolSetting( self::SETTING_KEY_TOKENIZATION ) &&
			   get_current_user_id() !== 0;
	}

	/**
	 * @param $customerEmail
	 *
	 * @return string|null
	 */
	protected function getGatewayConsumerIdFor( $customerEmail ) {
		$meta = $this->getMetaConsumerIdForLoggedUser();

		return ! empty( $meta[ $customerEmail ] ) ? $meta[ $customerEmail ] : null;
	}

	/**
	 * @param string $email
	 *
	 * @return null|int
	 */
	protected function retrieveConsumerIdFromEmail( $email ) {
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
	 * @return array
	 */
	protected function getMetaConsumerIdForLoggedUser() {
		if ( ! WC_emerchantpay_Helper::isUserLogged() ) {
			return [];
		}

		$meta = json_decode(
			get_user_meta( get_current_user_id(), self::META_TOKENIZATION_CONSUMER_ID, true ),
			true
		);

		return is_array( $meta ) ? $meta : [];
	}

	/**
	 * @param $customerEmail
	 * @param $consumerId
	 */
	protected function setGatewayConsumerIdFor( $customerEmail, $consumerId ) {
		if ( ! WC_emerchantpay_Helper::isUserLogged() ) {
			return;
		}

		$meta = $this->getMetaConsumerIdForLoggedUser();

		$meta[ $customerEmail ] = $consumerId;

		update_user_meta( get_current_user_id(), self::META_TOKENIZATION_CONSUMER_ID, json_encode( $meta ) );
	}

	/**
	 * @param \Genesis\Genesis $genesis
	 * @param WC_Order         $order
	 * @param array            $requestData
	 * @param bool             $isRecurring
	 * @return void
	 */
	protected function addTransactionTypesToGatewayRequest( $genesis, $order, $requestData, $isRecurring ) {
		/** @var \Genesis\API\Request\WPF\Create $wpfRequest */
		$wpfRequest = $genesis->request();

		if ( $isRecurring ) {
			$recurring_types = $this->get_recurring_payment_types();
			foreach ( $recurring_types as $type ) {
				$wpfRequest->addTransactionType( $type );
			}

			return;
		}

		$this->addCustomParametersToTrxTypes( $wpfRequest, $order, $requestData );
	}

	/**
	 * @param \Genesis\API\Request\WPF\Create $wpf_request $wpfRequest
	 * @param WC_Order                        $order
	 * @param array                           $request_data
	 */
	private function addCustomParametersToTrxTypes( $wpf_request, WC_Order $order, $request_data ) {
		$types = $this->get_payment_types();

		foreach ( $types as $type ) {
			if ( is_array( $type ) ) {
				$wpf_request->addTransactionType( $type['name'], $type['parameters'] );

				continue;
			}

			switch ( $type ) {
				case Types::IDEBIT_PAYIN:
				case Types::INSTA_DEBIT_PAYIN:
					$user_id_hash              = WC_emerchantpay_Genesis_Helper::getCurrentUserIdHash();
					$transaction_custom_params = array(
						'customer_account_id' => $user_id_hash,
					);
					break;
				case Types::KLARNA_AUTHORIZE:
					$transaction_custom_params = WC_emerchantpay_Order_Helper::getKlarnaCustomParamItems( $order )->toArray();
					break;
				case Types::TRUSTLY_SALE:
					$user_id         = WC_emerchantpay_Genesis_Helper::getCurrentUserId();
					$trustly_user_id = empty( $user_id ) ? WC_emerchantpay_Genesis_Helper::getCurrentUserIdHash() : $user_id;

					$transaction_custom_params = array(
						'user_id' => $trustly_user_id,
					);
					break;
				case Types::ONLINE_BANKING_PAYIN:
					$available_bank_codes = $this->getMethodSetting( self::SETTING_KEY_BANK_CODES );
					if ( CommonUtils::isValidArray( $available_bank_codes ) ) {
						$transaction_custom_params['bank_codes'] = array_map(
							function ($value) {
								return ['bank_code' => $value];
							},
							$available_bank_codes
						);
					}
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
	 * @param int $order_id
	 * @return array|bool
	 */
	protected function process_order_payment( $order_id ) {
		return $this->process_common_payment( $order_id, false );
	}

	/**
	 * Initiate Gateway Payment Session
	 *
	 * @param int $order_id
	 * @return array|bool
	 */
	protected function process_init_subscription_payment( $order_id ) {
		return $this->process_common_payment( $order_id, true );
	}

	/**
	 * Initiate Order Checkout session
	 *   or
	 * Init Recurring Checkout
	 *
	 * @param int  $order_id
	 * @param bool $isRecurring
	 * @return array|bool
	 */
	protected function process_common_payment( $order_id, $isRecurring ) {
		$order = new WC_Order( absint( $order_id ) );

		$data = $this->populateGateRequestData( $order, $isRecurring );

		try {
			$this->set_credentials();

			$genesis = $this->prepareInitialGenesisRequest( $data );
			$genesis = $this->add_business_data_to_gateway_request( $genesis, $order );
			$this->addTransactionTypesToGatewayRequest( $genesis, $order, $data, $isRecurring );

			$genesis->execute();

			$response = $genesis->response()->getResponseObject();

			$isWpfSuccessfullyCreated =
				( $response->status == \Genesis\API\Constants\Transaction\States::NEW_STATUS ) &&
				isset( $response->redirect_url );

			if ( $isWpfSuccessfullyCreated ) {
				$this->save_checkout_trx_to_order( $response, WC_emerchantpay_Order_Helper::getOrderProp( $order, 'id' ) );

				if ( ! empty( $data['customer_email'] ) ) {
					$this->save_tokenization_data( $data['customer_email'], $response );
				}

				// Create One-time token to prevent redirect abuse
				$this->set_one_time_token( $order_id, $this->generateTransactionId() );

				return array(
					'result'   => static::RESPONSE_SUCCESS,
					'redirect' => $response->redirect_url,
				);
			} else {
				throw new \Exception(
					static::getTranslatedText(
						'An error has been encountered while initiating Web Payment Form! Please try again later.'
					)
				);
			}
		} catch ( \Exception $exception ) {
			if ( isset( $genesis ) && isset( $genesis->response()->getResponseObject()->message ) ) {
				$error_message = $genesis->response()->getResponseObject()->message;
			} else {
				$error_message = self::getTranslatedText(
					'We were unable to process your order!' . '<br/>' .
					'Please double check your data and try again.'
				);
			}

			WC_emerchantpay_Message_Helper::addErrorNotice( $error_message );

			WC_emerchantpay_Helper::logException( $exception );

			return false;
		}
	}

	protected function save_checkout_trx_to_order( $response_obj, $order_id ) {
		// Save the Checkout Id
		WC_emerchantpay_Order_Helper::setOrderMetaData(
			$order_id,
			self::META_CHECKOUT_TRANSACTION_ID,
			$response_obj->unique_id
		);

		// Save whole trx
		WC_emerchantpay_Order_Helper::saveInitialTrxToOrder( $order_id, $response_obj );
	}

	/**
	 * @param $customer_email
	 * @param $response_obj
	 */
	protected function save_tokenization_data( $customer_email, $response_obj ) {
		if ( ! empty( $response_obj->consumer_id ) ) {
			$this->setGatewayConsumerIdFor( $customer_email, $response_obj->consumer_id );
		}
	}

	/**
	 * Set the Terminal token associated with an order
	 *
	 * @param $order
	 *
	 * @return bool
	 */
	protected function set_terminal_token( $order ) {
		$token = WC_emerchantpay_Order_Helper::getOrderMetaData(
			$order->get_id(),
			self::META_TRANSACTION_TERMINAL_TOKEN
		);

		// Check for Recurring Token
		if ( empty( $token ) ) {
			$token = WC_emerchantpay_Order_Helper::getOrderMetaData(
				$order->get_id(),
				WC_emerchantpay_Subscription_Helper::META_RECURRING_TERMINAL_TOKEN
			);
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
		$alias_map      = array();

		$selected_types = $this->getMethodSetting( self::SETTING_KEY_TRANSACTION_TYPES );
		$methods        = \Genesis\API\Constants\Payment\Methods::getMethods();

		foreach ( $methods as $method ) {
			$alias_map[ $method . self::PPRO_TRANSACTION_SUFFIX ] = Types::PPRO;
		}

		$alias_map = array_merge($alias_map, [
			self::GOOGLE_PAY_TRANSACTION_PREFIX . self::GOOGLE_PAY_PAYMENT_TYPE_AUTHORIZE => Types::GOOGLE_PAY,
			self::GOOGLE_PAY_TRANSACTION_PREFIX . self::GOOGLE_PAY_PAYMENT_TYPE_SALE      => Types::GOOGLE_PAY,
			self::PAYPAL_TRANSACTION_PREFIX . self::PAYPAL_PAYMENT_TYPE_AUTHORIZE         => Types::PAY_PAL,
			self::PAYPAL_TRANSACTION_PREFIX . self::PAYPAL_PAYMENT_TYPE_SALE              => Types::PAY_PAL,
			self::PAYPAL_TRANSACTION_PREFIX . self::PAYPAL_PAYMENT_TYPE_EXPRESS           => Types::PAY_PAL,
			self::APPLE_PAY_TRANSACTION_PREFIX . self::APPLE_PAY_PAYMENT_TYPE_AUTHORIZE   => Types::APPLE_PAY,
			self::APPLE_PAY_TRANSACTION_PREFIX . self::APPLE_PAY_PAYMENT_TYPE_SALE        => Types::APPLE_PAY,
		]);

		foreach ( $selected_types as $selected_type ) {
			if ( array_key_exists( $selected_type, $alias_map ) ) {
				$transaction_type = $alias_map[ $selected_type ];

				$processed_list[ $transaction_type ]['name'] = $transaction_type;

				$key = $this->get_custom_parameter_key( $transaction_type );

				$processed_list[ $transaction_type ]['parameters'][] = array(
					$key => str_replace(
						[
							self::PPRO_TRANSACTION_SUFFIX,
							self::GOOGLE_PAY_TRANSACTION_PREFIX,
							self::PAYPAL_TRANSACTION_PREFIX,
							self::APPLE_PAY_TRANSACTION_PREFIX,
						],
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
	 * @return array
	 */
	protected function get_recurring_payment_types() {
		return $this->getMethodSetting( self::SETTING_KEY_INIT_RECURRING_TXN_TYPES );
	}

	/**
	 * @return bool
	 */
	protected function isSubscriptionEnabled() {
		return parent::isSubscriptionEnabled() &&
			$this->getMethodHasSetting(
				self::SETTING_KEY_INIT_RECURRING_TXN_TYPES
			);
	}

	/**
	 * Determines the Recurring Token, which needs to used for the RecurringSale Transactions
	 *
	 * @param WC_Order $order WC Order Object.
	 * @return string
	 */
	protected function getRecurringToken( $order ) {
		$recurringToken = parent::getRecurringToken( $order );

		if ( ! empty( $recurringToken ) ) {
			return $recurringToken;
		}

		return WC_emerchantpay_Subscription_Helper::getTerminalTokenMetaFromSubscriptionOrder( $order->get_id() );
	}

	/**
	 * @param $transaction_type
	 * @return string
	 */
	private function get_custom_parameter_key( $transaction_type ) {
		switch ( $transaction_type ) {
			case Types::PPRO:
				$result = 'payment_method';
				break;
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
}

WC_emerchantpay_Checkout::registerStaticActions();
