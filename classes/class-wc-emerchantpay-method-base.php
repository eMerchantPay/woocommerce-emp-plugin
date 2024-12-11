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
 * @package     classes\class-wc-emerchantpay-method-base
 */

use Genesis\Api\Constants\DateTimeFormat;
use Genesis\Api\Constants\Endpoints;
use Genesis\Api\Constants\Environments;
use Genesis\Api\Constants\Transaction\Names;
use Genesis\Api\Constants\Transaction\Parameters\ScaExemptions;
use Genesis\Api\Constants\Transaction\Parameters\Threeds\V2\CardHolderAccount\RegistrationIndicators;
use Genesis\Api\Constants\Transaction\Parameters\Threeds\V2\Control\ChallengeIndicators;
use Genesis\Api\Constants\Transaction\Parameters\Threeds\V2\MerchantRisk\DeliveryTimeframes;
use Genesis\Api\Constants\Transaction\Parameters\Threeds\V2\Purchase\Categories;
use Genesis\Api\Constants\Transaction\States;
use Genesis\Api\Constants\Transaction\Parameters\Recurring\Types as RecurringTypes;
use Genesis\Api\Constants\Transaction\Types;
use Genesis\Api\Notification;
use Genesis\Config;
use Genesis\Genesis;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Class emerchantpay base method
 *
 * @class   WC_Emerchantpay_Method
 * @extends WC_Payment_Gateway
 *
 * @SuppressWarnings(PHPMD)
 */
abstract class WC_Emerchantpay_Method_Base extends WC_Payment_Gateway_CC {

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
	const META_HPOS_DIRECT_TRANSACTION_ID = '_direct_transaction_id';

	/**
	 * Method Setting Keys
	 */
	const SETTING_KEY_ENABLED                             = 'enabled';
	const SETTING_KEY_TITLE                               = 'title';
	const SETTING_KEY_DESCRIPTION                         = 'description';
	const SETTING_KEY_TEST_MODE                           = 'test_mode';
	const SETTING_KEY_USERNAME                            = 'username';
	const SETTING_KEY_PASSWORD                            = 'password';
	const SETTING_KEY_ALLOW_CAPTURES                      = 'allow_captures';
	const SETTING_KEY_ALLOW_REFUNDS                       = 'allow_refunds';
	const SETTING_KEY_ALLOW_SUBSCRIPTIONS                 = 'allow_subscriptions';
	const SETTING_KEY_RECURRING_TOKEN                     = 'recurring_token';
	const SETTING_KEY_BUSINESS_ATTRIBUTES_ENABLED         = 'business_attributes_enabled';
	const SETTING_KEY_BUSINESS_FLIGHT_ARRIVAL_DATE        = 'business_flight_arrival_date';
	const SETTING_KEY_BUSINESS_FLIGHT_DEPARTURE_DATE      = 'business_flight_departure_date';
	const SETTING_KEY_BUSINESS_AIRLINE_CODE               = 'business_airline_code';
	const SETTING_KEY_BUSINESS_AIRLINE_FLIGHT_NUMBER      = 'business_airline_flight_number';
	const SETTING_KEY_BUSINESS_FLIGHT_TICKET_NUMBER       = 'business_flight_ticket_number';
	const SETTING_KEY_BUSINESS_FLIGHT_ORIGIN_CITY         = 'business_flight_origin_city';
	const SETTING_KEY_BUSINESS_FLIGHT_DESTINATION_CITY    = 'business_flight_destination_city';
	const SETTING_KEY_BUSINESS_AIRLINE_TOUR_OPERATOR_NAME = 'business_airline_tour_operator_name';
	const SETTING_KEY_BUSINESS_EVENT_START_DATE           = 'business_event_start_date';
	const SETTING_KEY_BUSINESS_EVENT_END_DATE             = 'business_event_end_date';
	const SETTING_KEY_BUSINESS_EVENT_ORGANIZER_ID         = 'business_event_organizer_id';
	const SETTING_KEY_BUSINESS_EVENT_ID                   = 'business_event_id';
	const SETTING_KEY_BUSINESS_DATE_OF_ORDER              = 'business_date_of_order';
	const SETTING_KEY_BUSINESS_DELIVERY_DATE              = 'business_delivery_date';
	const SETTING_KEY_BUSINESS_NAME_OF_THE_SUPPLIER       = 'business_name_of_the_supplier';
	const SETTING_KEY_BUSINESS_CHECK_IN_DATE              = 'business_check_in_date';
	const SETTING_KEY_BUSINESS_CHECK_OUT_DATE             = 'business_check_out_date';
	const SETTING_KEY_BUSINESS_TRAVEL_AGENCY_NAME         = 'business_travel_agency_name';
	const SETTING_KEY_BUSINESS_VEHICLE_PICK_UP_DATE       = 'business_vehicle_pick_up_date';
	const SETTING_KEY_BUSINESS_VEHICLE_RETURN_DATE        = 'business_vehicle_return_date';
	const SETTING_KEY_BUSINESS_SUPPLIER_NAME              = 'business_supplier_name';
	const SETTING_KEY_BUSINESS_CRUISE_START_DATE          = 'business_cruise_start_date';
	const SETTING_KEY_BUSINESS_CRUISE_END_DATE            = 'business_cruise_end_date';
	const SETTING_KEY_BUSINESS_ARRIVAL_DATE               = 'business_arrival_date';
	const SETTING_KEY_BUSINESS_DEPARTURE_DATE             = 'business_departure_date';
	const SETTING_KEY_BUSINESS_CARRIER_CODE               = 'business_carrier_code';
	const SETTING_KEY_BUSINESS_FLIGHT_NUMBER              = 'business_flight_number';
	const SETTING_KEY_BUSINESS_TICKET_NUMBER              = 'business_ticket_number';
	const SETTING_KEY_BUSINESS_ORIGIN_CITY                = 'business_origin_city';
	const SETTING_KEY_BUSINESS_DESTINATION_CITY           = 'business_destination_city';
	const SETTING_KEY_BUSINESS_TRAVEL_AGENCY              = 'business_travel_agency';
	const SETTING_KEY_BUSINESS_CONTRACTOR_NAME            = 'business_contractor_name';
	const SETTING_KEY_BUSINESS_ATOL_CERTIFICATE           = 'business_atol_certificate';
	const SETTING_KEY_BUSINESS_PICK_UP_DATE               = 'business_pick_up_date';
	const SETTING_KEY_BUSINESS_RETURN_DATE                = 'business_return_date';
	const SETTING_KEY_REDIRECT_FAILURE                    = 'redirect_failure';
	const SETTING_KEY_REDIRECT_CANCEL                     = 'redirect_cancel';
	const SETTING_KEY_THREEDS_ALLOWED                     = 'threeds_allowed';
	const SETTING_KEY_THREEDS_CHALLENGE_INDICATOR         = 'threeds_challenge_indicator';
	const SETTING_KEY_SCA_EXEMPTION                       = 'sca_exemption';
	const SETTING_KEY_SCA_EXEMPTION_AMOUNT                = 'sca_exemption_amount';
	const SETTING_KEY_IFRAME_PROCESSING                   = 'iframe_processing';
	const SETTING_KEY_CUSTOM_THANKYOU_URL                 = 'custom_thankyou_url';

	/**
	 * Order cancel/failure settings
	 */
	const SETTING_VALUE_ORDER    = 'order';
	const SETTING_VALUE_CHECKOUT = 'checkout';

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

	const SETTING_VALUE_YES = 'yes';
	const SETTING_VALUE_NO  = 'no';

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
	const WP_ACTION_ADMIN_FOOTER                      = 'admin_footer';
	const WC_ADMIN_ACTION_SETTINGS_START              = 'woocommerce_settings_start';
	const WC_ADMIN_ACTION_SETTINGS_SAVED              = 'woocommerce_settings_saved';

	const RESPONSE_SUCCESS = 'success';

	const PLATFORM_TRANSACTION_PREFIX = 'wc-';

	/**
	 * Google Pay transaction prefix and payment methods constants
	 */
	const GOOGLE_PAY_TRANSACTION_PREFIX     = 'google_pay_';
	const GOOGLE_PAY_PAYMENT_TYPE_AUTHORIZE = 'authorize';
	const GOOGLE_PAY_PAYMENT_TYPE_SALE      = 'sale';

	/**
	 * PayPal transaction prefix and payment methods constants
	 */
	const PAYPAL_TRANSACTION_PREFIX     = 'pay_pal_';
	const PAYPAL_PAYMENT_TYPE_AUTHORIZE = 'authorize';
	const PAYPAL_PAYMENT_TYPE_SALE      = 'sale';
	const PAYPAL_PAYMENT_TYPE_EXPRESS   = 'express';

	/**
	 * Apple Pay transaction prefix and payment methods constants
	 */
	const APPLE_PAY_TRANSACTION_PREFIX     = 'apple_pay_';
	const APPLE_PAY_PAYMENT_TYPE_AUTHORIZE = 'authorize';
	const APPLE_PAY_PAYMENT_TYPE_SALE      = 'sale';

	const METHOD_ACTION_CAPTURE = 'capture';
	const METHOD_ACTION_REFUND  = 'refund';

	/**
	 * Date format
	 */
	const DATE_FORMAT = 'Y-m-d';

	/**
	 * Language domain
	 */
	const LANG_DOMAIN = 'woocommerce-emerchantpay';

	/**
	 * Payment Method Code
	 *
	 * @var null|string
	 */
	protected static $method_code = null;

	/**
	 * Used for hook admin_footer to check, if we should load assets for enqueueTransactionsListAssets.
	 *
	 * @var bool
	 */
	protected $should_execute_admin_footer_hook = false;

	/**
	 * Define default options
	 *
	 * @var array
	 */
	protected $options = array(
		'draw_transaction_tree'          => true, // Conditionally draw the table with the transaction tree.
		'register_renewal_subscriptions' => true, //Conditionally register WooCommerce Subscriptions Scheduled Renew Payment
	);

	/**
	 * Module title
	 *
	 * @return string
	 */
	abstract protected function get_module_title();

	/**
	 * Holds the Meta Key used to extract the checkout Transaction
	 *   - Checkout Method -> WPF Unique Id
	 *   - Direct Method -> Transaction Unique Id
	 *
	 * @return string
	 */
	abstract protected function get_checkout_transaction_id_meta_key();

	/**
	 * Initializes Order Payment Session.
	 *
	 * @param int $order_id Order identifier.
	 * @return array
	 */
	abstract protected function process_order_payment( $order_id );

	/**
	 * Initializes Order Payment Session.
	 *
	 * @param int $order_id Order identifier.
	 * @return array
	 */
	abstract protected function process_init_subscription_payment( $order_id );

	/**
	 * Registers all admin actions used in the payment methods
	 *
	 * @return void
	 */
	public function register_admin_actions() {
		$this->add_wp_simple_actions(
			array(
				self::WC_ACTION_ADMIN_ORDER_TOTALS_AFTER_TOTAL,
				self::WP_ACTION_ADMIN_NOTICES,
			),
			array(
				'display_admin_order_after_totals',
				'admin_notices',
			)
		);

		// Hooks for transactions list in admin order view.
		if ( $this->get_is_woocommerce_admin_order() && $this->options['draw_transaction_tree'] ) {
			$this->add_wp_simple_actions(
				array(
					self::WC_ACTION_ADMIN_ORDER_TOTALS_AFTER_TOTAL,
					self::WP_ACTION_ADMIN_FOOTER,
				),
				array(
					'display_transactions_list_for_order',
					'enqueue_transactions_list_assets',
				)
			);
		}

		if ( $this->get_is_woocommerce_admin_settings() ) {
			$this->add_wp_simple_actions(
				array(
					self::WC_ADMIN_ACTION_SETTINGS_START,
					self::WC_ADMIN_ACTION_SETTINGS_SAVED,
				),
				array(
					'enqueue_woocommerce_payment_settings_assets',
					'enqueue_woocommerce_payment_settings_assets',
				)
			);
		}
	}

	/**
	 * Retrieves a list with the Required Api Settings
	 *
	 * @return array
	 */
	protected function get_required_api_setting_keys() {
		return array(
			self::SETTING_KEY_USERNAME,
			self::SETTING_KEY_PASSWORD,
		);
	}

	/**
	 * Determines if the a post notification is a valid Gateway Notification
	 *
	 * @param array $post_values Post values.
	 *
	 * @return bool
	 */
	protected function get_is_valid_notification( $post_values ) {
		return isset( $post_values['signature'] );
	}

	/**
	 * Checks Woocommerce admin order
	 *
	 * @return bool
	 */
	protected function get_is_woocommerce_admin_order() {
		if ( is_admin() && function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();

			return null !== $screen && in_array( $screen->id, array( 'shop_order', 'shop_subscription', 'woocommerce_page_wc-orders', 'woocommerce_page_wc-orders--shop_subscription' ), true );
		}

		return false;
	}

	/**
	 * Checks if is woocommerce admin settings
	 *
	 * @return bool
	 */
	protected function get_is_woocommerce_admin_settings() {
		if ( is_admin() && function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			// TODO Check and fix nonce verification error.
			// phpcs:disable WordPress.Security.NonceVerification
			return null !== $screen && 'woocommerce_page_wc-settings' === $screen->base &&
					array_key_exists( 'section', $_REQUEST ) && $this::$method_code === $_REQUEST['section'];
			// phpcs:enable
		}
		// TODO Check and fix nonce verification error.
		// phpcs:disable WordPress.Security.NonceVerification
		if ( is_array( $_REQUEST ) ) {

			return array_key_exists( 'page', $_REQUEST ) && 'wc-settings' === $_REQUEST['page'] &&
					array_key_exists( 'section', $_REQUEST ) && $this::$method_code === $_REQUEST['section'];
		}
		// phpcs:enable

		return false;
	}

	/**
	 * Inject the WooCommerce Settings Custom JS files
	 */
	public function enqueue_woocommerce_payment_settings_assets() {
		$version = WC_Emerchantpay_Helper::get_plugin_version();

		wp_enqueue_script(
			'business-attributes',
			plugins_url(
				'assets/javascript/payment_settings_business_attributes.js',
				plugin_dir_path( __FILE__ )
			),
			array(),
			$version,
			true
		);
	}

	/**
	 * Determines if the user is currently reviewing
	 * WooCommerce settings checkout page
	 *
	 * @return bool
	 */
	protected function get_is_settings_checkout_page() {
		// TODO Check and fix nonce verification error.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		return isset( $_GET['page'] ) && ( 'wc-settings' === $_GET['page'] ) &&
			isset( $_GET['tab'] ) && ( 'checkout' === $_GET['tab'] );
		// phpcs:enable
	}

	/**
	 * Determines if the user is currently reviewing
	 * WooCommerce settings checkout page with the module selected
	 *
	 * @return bool
	 */
	protected function get_is_settings_checkout_module_page() {
		if ( ! $this->get_is_settings_checkout_page() ) {
			return false;
		}
		// TODO Check and fix nonce verification error.
		// phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
		return isset( $_GET['section'] ) && WC_Emerchantpay_Helper::get_string_ends_with( $_GET['section'], $this->id );
	}

	/**
	 * Adds assets needed for the transactions list in admin order
	 */
	public function enqueue_transactions_list_assets() {
		if ( ! $this->should_execute_admin_footer_hook ) {
			return;
		}

		$version = WC_Emerchantpay_Helper::get_plugin_version();

		wp_enqueue_style(
			'treegrid-css',
			plugins_url( 'assets/css/treegrid.css', plugin_dir_path( __FILE__ ) ),
			array(),
			$version
		);
		wp_enqueue_style(
			'order-transactions-tree',
			plugins_url( 'assets/css/order_transactions_tree.css', plugin_dir_path( __FILE__ ) ),
			array(),
			$version
		);
		wp_enqueue_style(
			'bootstrap',
			plugins_url( 'assets/css/bootstrap/bootstrap.min.css', plugin_dir_path( __FILE__ ) ),
			array(),
			$version
		);
		wp_enqueue_style(
			'bootstrap-validator',
			plugins_url( 'assets/css/bootstrap/bootstrapValidator.min.css', plugin_dir_path( __FILE__ ) ),
			array( 'bootstrap' ),
			$version
		);
		wp_enqueue_script(
			'treegrid-cookie',
			plugins_url( 'assets/javascript/treegrid/cookie.js', plugin_dir_path( __FILE__ ) ),
			array(),
			$version,
			true
		);
		wp_enqueue_script(
			'treegrid-main',
			plugins_url( 'assets/javascript/treegrid/treegrid.js', plugin_dir_path( __FILE__ ) ),
			array( 'treegrid-cookie' ),
			$version,
			true
		);
		wp_enqueue_script(
			'jquery-number',
			plugins_url( 'assets/javascript/jQueryExtensions/jquery.number.min.js', plugin_dir_path( __FILE__ ) ),
			array( 'jquery' ),
			$version,
			true
		);
		wp_enqueue_script(
			'bootstrap-validator',
			plugins_url( 'assets/javascript/bootstrap/bootstrapValidator.min.js', plugin_dir_path( __FILE__ ) ),
			array( 'jquery' ),
			$version,
			true
		);
		wp_enqueue_script(
			'bootstrap-modal',
			plugins_url( 'assets/javascript/bootstrap/bootstrap.modal.min.js', plugin_dir_path( __FILE__ ) ),
			array( 'jquery' ),
			$version,
			true
		);
		wp_enqueue_script(
			'order-transactions-tree',
			plugins_url( 'assets/javascript/order_transactions_tree.js', plugin_dir_path( __FILE__ ) ),
			array( 'treegrid-main', 'jquery-number', 'bootstrap-validator' ),
			$version,
			true
		);
		wp_enqueue_script( 'jquery-ui-tooltip' );
	}

	/**
	 * Displays order transaction list
	 *
	 * @param int $order_id Order identifier.
	 */
	public function display_transactions_list_for_order( $order_id ) {
		$order = wc_emerchantpay_order_proxy()->get_order_by_id( $order_id );

		if ( WC_Emerchantpay_Order_Helper::get_order_prop( $order, 'payment_method' ) !== $this->id ) {
			return;
		}

		$this->should_execute_admin_footer_hook = true;

		$transactions = WC_Emerchantpay_Transactions_Tree::create_from_order( $order );
		$parent_id    = $order->get_data()['parent_id'] ?? null;
		if ( count( $transactions->trx_list ) === 0 && $parent_id ) {
			$order        = wc_emerchantpay_order_proxy()->get_order_by_id( $parent_id );
			$transactions = WC_Emerchantpay_Transactions_Tree::create_from_order( $order );
		}

		if ( ! empty( $transactions ) ) {
			$method_transaction_types   = $this->get_method_selected_transaction_types();
			$selected_transaction_types = is_array( $method_transaction_types ) ? $method_transaction_types : array();

			$this->fetch_template(
				'admin/order/transactions.php',
				array(
					'payment_method'        => $this,
					'order'                 => $order,
					'order_currency'        => WC_Emerchantpay_Order_Helper::get_order_prop( $order, 'currency' ),
					'transactions'          => array_map(
						function ( $v ) {
							return (array) $v;
						},
						WC_Emerchantpay_Transactions_Tree::get_transaction_tree(
							$transactions->trx_list,
							$selected_transaction_types
						)
					),
					'allow_partial_capture' => $this->allow_partial_capture(
						$transactions->get_authorize_trx()
					),
					'allow_partial_refund'  => $this->allow_partial_refund(
						$transactions->get_settlement_trx()
					),
				)
			);
		}
	}

	/**
	 * Get the current method selected transaction types
	 *
	 * @return mixed
	 */
	protected function get_method_selected_transaction_types() {
		return $this->get_method_setting( $this->get_method_transaction_setting_key() );
	}

	/**
	 * Get the Current Transaction Type config
	 *
	 * @return string
	 */
	protected function get_method_transaction_setting_key() {
		$key = '';

		if ( WC_Emerchantpay_Checkout::get_method_code() === self::get_method_code() ) {
			$key = WC_Emerchantpay_Checkout::SETTING_KEY_TRANSACTION_TYPES;
		}

		if ( WC_Emerchantpay_Direct::get_method_code() === self::get_method_code() ) {
			$key = WC_Emerchantpay_Direct::META_TRANSACTION_TYPE;
		}

		return $key;
	}

	/**
	 * Allows partial capture
	 *
	 * @param WC_Emerchantpay_Transaction $authorize Authorize object.
	 *
	 * @return bool
	 */
	private function allow_partial_capture( $authorize ) {
		return empty( $authorize ) ? false : Types::INVOICE !== $authorize->type;
	}

	/**
	 * Allows partial refund
	 *
	 * @param WC_Emerchantpay_Transaction $capture Capture object.
	 *
	 * @return bool
	 */
	private function allow_partial_refund( $capture ) {
		return empty( $capture ) ? false : Types::INVOICE_CAPTURE !== $capture->type;
	}

	/**
	 * Event Handler for displaying Admin Notices
	 *
	 * @return bool
	 */
	public function admin_notices() {
		if ( ! $this->should_show_admin_notices() ) {
			return false;
		}

		$this->admin_notices_genesis_requirements();
		$this->admin_notices_api_credentials();
		$this->admin_notices_subscriptions();

		return true;
	}

	/**
	 * Checks if page is settings and plug-in is enabled.
	 *
	 * @return bool
	 */
	protected function should_show_admin_notices() {
		if ( ! $this->get_is_settings_checkout_module_page() ) {
			return false;
		}

		if ( WC_Emerchantpay_Helper::is_get_request() ) {
			if ( self::SETTING_VALUE_YES !== $this->enabled ) {
				return false;
			}
		} elseif ( WC_Emerchantpay_Helper::is_post_request() ) {
			if ( ! $this->get_post_bool_setting_value( self::SETTING_KEY_ENABLED ) ) {
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
	protected function admin_notices_genesis_requirements() {
		if ( $this->is_ssl_required() && ! WC_Emerchantpay_Helper::is_store_over_secured_connection() ) {
			WC_Emerchantpay_Helper::print_wp_notice(
				static::get_translated_text(
					sprintf(
						'%s payment method requires HTTPS connection in order to process payment data!',
						$this->get_module_title()
					)
				),
				WC_Emerchantpay_Helper::WP_NOTICE_TYPE_ERROR
			);
		}

		$genesis_requirements_verified = WC_Emerchantpay_Genesis_Helper::check_genesis_requirements_verified();

		if ( true !== $genesis_requirements_verified ) {
			WC_Emerchantpay_Helper::print_wp_notice(
				static::get_translated_text(
					$genesis_requirements_verified->get_error_message()
				),
				WC_Emerchantpay_Helper::WP_NOTICE_TYPE_ERROR
			);
		}
	}

	/**
	 * Check if required plug-ins settings are set.
	 */
	protected function admin_notices_api_credentials() {
		$are_api_credentials_defined = true;
		if ( WC_Emerchantpay_Helper::is_get_request() ) {
			foreach ( $this->get_required_api_setting_keys() as $required_api_setting ) {
				if ( empty( $this->get_method_setting( $required_api_setting ) ) ) {
					$are_api_credentials_defined = false;
				}
			}
		} elseif ( WC_Emerchantpay_Helper::is_post_request() ) {
			foreach ( $this->get_required_api_setting_keys() as $required_api_setting ) {
				$api_setting_post_param_name = $this->get_method_admin_setting_post_param_name(
					$required_api_setting
				);
				// TODO Check and fix nonce verification error.
				// phpcs:disable WordPress.Security.NonceVerification
				if ( ! isset( $_POST[ $api_setting_post_param_name ] ) || empty( $_POST[ $api_setting_post_param_name ] ) ) {
					$are_api_credentials_defined = false;

					break;
				}
				// phpcs:enable
			}
		}

		if ( ! $are_api_credentials_defined ) {
			WC_Emerchantpay_Helper::print_wp_notice(
				'You need to set the API credentials in order to use this payment method!',
				WC_Emerchantpay_Helper::WP_NOTICE_TYPE_ERROR
			);
		}
	}

	/**
	 * Shows subscription notices, if subscriptions are enabled and WooCommerce is missing.
	 * Also shows general information about subscriptions, if they are enabled.
	 */
	protected function admin_notices_subscriptions() {
		$is_subscriptions_allowed =
			( WC_Emerchantpay_Helper::is_get_request() && $this->is_subscription_enabled() ) ||
			( WC_Emerchantpay_Helper::is_post_request() && $this->get_post_bool_setting_value( self::SETTING_KEY_ALLOW_SUBSCRIPTIONS ) );
		if ( $is_subscriptions_allowed ) {
			if ( ! WC_Emerchantpay_Subscription_Helper::is_wc_subscriptions_installed() ) {
				WC_Emerchantpay_Helper::print_wp_notice(
					static::get_translated_text(
						sprintf(
							'<a href="%s">WooCommerce Subscription Plugin</a> is required for handling <strong>Subscriptions</strong>, which is disabled or not installed!',
							WC_Emerchantpay_Subscription_Helper::WC_SUBSCRIPTIONS_PLUGIN_URL
						)
					),
					WC_Emerchantpay_Helper::WP_NOTICE_TYPE_ERROR
				);
			}
		}
	}

	/**
	 * Builds the complete input post param for a wooCommerce payment method
	 *
	 * @param string $setting_key Setting key.
	 *
	 * @return string
	 */
	protected function get_method_admin_setting_post_param_name( $setting_key ) {
		return sprintf(
			'woocommerce_%s_%s',
			$this->id,
			$setting_key
		);
	}

	/**
	 * Setup and initialize this module
	 *
	 * WC_Emerchantpay_Method_Base constructor.
	 *
	 * @param array $options Options array.
	 */
	public function __construct( $options = array() ) {
		$this->id      = static::$method_code;
		$this->options = array_merge( $this->options, $options );

		$this->supports = array(
			self::FEATURE_PRODUCTS,
			self::FEATURE_CAPTURES,
			self::FEATURE_REFUNDS,
			self::FEATURE_VOIDS,
		);

		if ( $this->is_subscription_enabled() ) {
			$this->add_subscription_support();
		}

		$this->icon       = plugins_url( "assets/images/{$this->id}.png", plugin_dir_path( __FILE__ ) );
		$this->has_fields = true;

		// Public title/description.
		$this->title       = $this->get_option( self::SETTING_KEY_TITLE );
		$this->description = $this->get_option( self::SETTING_KEY_DESCRIPTION );

		// Register the method callback.
		$this->add_wp_simple_actions(
			'woocommerce_api_' . strtolower( get_class( $this ) ),
			'callback_handler'
		);

		// Save admin-panel options.
		if ( defined( 'WOOCOMMERCE_VERSION' ) && version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
			$this->add_wp_action(
				self::WC_ACTION_UPDATE_OPTIONS_PAYMENT_GATEWAY,
				'process_admin_options'
			);
		} else {
			$this->add_wp_simple_actions(
				self::WC_ACTION_UPDATE_OPTIONS_PAYMENT_GATEWAY,
				'process_admin_options'
			);
		}

		add_action( 'current_screen', array( $this, 'register_admin_actions' ) );

		// Initialize admin options.
		$this->init_form_fields();

		// Fetch module settings.
		$this->init_settings();
	}

	/**
	 * Enables Subscriptions for the current payment method
	 *
	 * @return void
	 */
	protected function add_subscription_support() {
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
					self::FEATURE_SUBSCRIPTION_PAYMENT_METHOD_CHANGE,
				)
			)
		);

		if ( WC_Emerchantpay_Subscription_Helper::is_wc_subscriptions_installed() && $this->options['register_renewal_subscriptions'] ) {
			// Add handler for Recurring Sale Transactions.
			$this->add_wp_action(
				self::WC_ACTION_SCHEDULED_SUBSCRIPTION_PAYMENT,
				'process_scheduled_subscription_payment',
				true,
				10,
				2
			);
		}
	}

	/**
	 * Transaction handler
	 *
	 * @param string $tag Sets tag.
	 * @param string $instance_method_name Sets instance method name.
	 * @param bool   $use_prefixed_tag Allows using of prefixed tag.
	 * @param int    $priority Sets priority. Default value 10.
	 * @param int    $accepted_args Sets count of accepted arguments. Default value 1.
	 *
	 * @return true
	 */
	protected function add_wp_action( $tag, $instance_method_name, $use_prefixed_tag = true, $priority = 10, $accepted_args = 1 ) {
		return add_action(
			$use_prefixed_tag ? "{$tag}_{$this->id}" : $tag,
			array(
				$this,
				$instance_method_name,
			),
			$priority,
			$accepted_args
		);
	}

	/**
	 * Simple transaction handler
	 *
	 * @param array|string $tags Array of tags.
	 * @param array|string $instance_method_names Sets instance method name.
	 *
	 * @return bool
	 */
	protected function add_wp_simple_actions( $tags, $instance_method_names ) {
		if ( is_string( $tags ) && is_string( $instance_method_names ) ) {
			return $this->add_wp_action( $tags, $instance_method_names, false );
		}

		if ( ! is_array( $tags ) || ! is_array( $instance_method_names ) || count( $tags ) !== count( $instance_method_names ) ) {
			return false;
		}

		foreach ( $tags as $tag_index => $tag ) {
			$this->add_wp_action( $tag, $instance_method_names[ $tag_index ], false );
		}

		return true;
	}

	/**
	 * Check if a gateway supports a given feature.
	 *
	 * @param string $feature Current feature.
	 *
	 * @return bool
	 */
	public function supports( $feature ) {
		$is_feature_supported = parent::supports( $feature );

		if ( self::FEATURE_CAPTURES === $feature ) {
			return $is_feature_supported &&
				$this->get_method_bool_setting( self::SETTING_KEY_ALLOW_CAPTURES );
		} elseif ( self::FEATURE_REFUNDS === $feature ) {
			return $is_feature_supported &&
				$this->get_method_bool_setting( self::SETTING_KEY_ALLOW_REFUNDS );
		}

		return $is_feature_supported;
	}

	/**
	 * Wrapper of wc_get_template to relate directly to s4wc
	 *
	 * @param       string $template_name The name of template.
	 * @param       array  $args Array of args.
	 */
	protected function fetch_template( $template_name, $args = array() ) {
		$default_path = dirname( plugin_dir_path( __FILE__ ) ) . '/templates/';

		echo esc_attr( wc_get_template( $template_name, $args, '', $default_path ) );
	}

	/**
	 * Retrieves a translated text by key
	 *
	 * @param string $text The text to be translated.
	 * @return string
	 */
	public static function get_translated_text( $text ) {
		// phpcs:disable
		return __( $text, self::LANG_DOMAIN );
		// phpcs:enable
	}

	/**
	 * Registers all custom static actions
	 * Used for processing backend transactions
	 *
	 * @return void
	 */
	public static function registerStaticActions() {
		add_action(
			'wp_ajax_' . static::$method_code . '_capture',
			array(
				__CLASS__,
				'capture',
			)
		);

		add_action(
			'wp_ajax_' . static::$method_code . '_void',
			array(
				__CLASS__,
				'void',
			)
		);

		add_action(
			'wp_ajax_' . static::$method_code . '_refund',
			array(
				__CLASS__,
				'refund',
			)
		);
	}

	/**
	 * Initializes Payment Session.
	 *
	 * @param int $order_id Order identifier.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		if ( WC_Emerchantpay_Subscription_Helper::has_order_subscriptions( $order_id ) ) {
			return $this->process_init_subscription_payment( $order_id );
		}

		return $this->process_order_payment( $order_id );
	}

	/**
	 * Post init recurring payment process.
	 *
	 * @param WC_Order  $order Order object.
	 * @param \stdClass $gateway_response Gateway response object.
	 *
	 * @return bool
	 */
	protected function process_after_init_recurring_payment( $order, $gateway_response ) {
		if ( WC_Emerchantpay_Subscription_Helper::is_init_recurring_order_finished( $order ) ) {
			return false;
		}

		if ( ! $gateway_response instanceof \stdClass ) {
			return false;
		}

		$payment_transaction_response = WC_Emerchantpay_Genesis_Helper::get_reconcile_payment_transaction( $gateway_response );

		$payment_txn_status = WC_Emerchantpay_Genesis_Helper::get_gateway_status_instance( $payment_transaction_response );

		if ( ! $payment_txn_status->isApproved() ) {
			return false;
		}

		WC_Emerchantpay_Subscription_Helper::save_init_recurring_response_to_order_subscriptions( $order, $payment_transaction_response );
		WC_Emerchantpay_Subscription_Helper::set_init_recurring_order_finished( $order );

		return true;
	}

	/**
	 * Estimates whether it can do Capture transaction
	 *
	 * @param WC_Order $order Order object.
	 * @param string   $unique_id Unique identifier.
	 *
	 * @return bool
	 */
	public static function can_capture( WC_Order $order, $unique_id ) {
		return WC_Emerchantpay_Transactions_Tree::can_capture(
			WC_Emerchantpay_Transactions_Tree::get_trx_from_order( $order, $unique_id )
		);
	}

	/**
	 * Estimates whether it can do Void
	 *
	 * @param WC_Order $order Order object.
	 * @param string   $unique_id Unique identifier.
	 *
	 * @return bool
	 */
	public static function can_void( WC_Order $order, $unique_id ) {
		$trx_tree = WC_Emerchantpay_Transactions_Tree::get_transactions_list_from_order( $order );
		return WC_Emerchantpay_Transactions_Tree::can_void(
			$trx_tree,
			WC_Emerchantpay_Transactions_Tree::get_trx_from_order( $order, $unique_id, $trx_tree )
		);
	}

	/**
	 * Estimates whether it can do Refund
	 *
	 * @param WC_Order $order Order object.
	 * @param string   $unique_id Unique identifier.
	 *
	 * @return bool
	 */
	public static function can_refund( WC_Order $order, $unique_id ) {
		return WC_Emerchantpay_Transactions_Tree::can_refund(
			(array) WC_Emerchantpay_Transactions_Tree::get_trx_from_order( $order, $unique_id )
		);
	}

	/**
	 * Processes a capture transaction to the gateway
	 *
	 * @param array $data Transaction data.
	 * @return stdClass|WP_Error
	 */
	protected static function process_capture( $data ) {
		$order_id = $data['order_id'];
		$reason   = $data['reason'];
		$amount   = $data['amount'];

		$order = wc_emerchantpay_order_proxy()->get_order_by_id( $order_id );

		$payment_gateway = wc_emerchantpay_order_proxy()->get_payment_method_instance_by_order( $order );

		if ( ! $order || ! $order->get_transaction_id() ) {
			return WC_Emerchantpay_Helper::get_wp_error( 'No order exists with the specified reference id' );
		}
		try {
			$payment_gateway->set_credentials();
			$payment_gateway->set_terminal_token( $order );

			$type    = self::get_capture_trx_type( $order );
			$genesis = new \Genesis\Genesis( $type );

			$genesis
				->request()
					->setTransactionId(
						$payment_gateway::generate_transaction_id( $order_id )
					)
					->setUsage(
						$reason
					)
					->setRemoteIp(
						WC_Emerchantpay_Helper::get_client_remote_ip_address()
					)
					->setReferenceId(
						$data['trx_id']
					)
					->setCurrency(
						$order->get_currency()
					)
					->setAmount(
						$amount
					);

			if ( Types::getCaptureTransactionClass( Types::INVOICE ) === $type ) {
				$genesis->request()->setItems( WC_Emerchantpay_Order_Helper::get_invoice_custom_param_items( $order ) );
			}

			$genesis->execute();

			if ( ! $genesis->response()->isSuccessful() ) {
				throw new \Exception( $genesis->response()->getErrorDescription() );
			}

			$response = $genesis->response()->getResponseObject();

			if ( $genesis->response()->isApproved() ) {
				wc_emerchantpay_order_proxy()->set_order_meta_data( $order, self::META_TRANSACTION_CAPTURE_ID, $response->unique_id );

				$order->add_order_note(
					static::get_translated_text( 'Payment Captured!' ) . PHP_EOL . PHP_EOL .
					static::get_translated_text( 'Id: ' ) . $response->unique_id . PHP_EOL .
					static::get_translated_text( 'Captured amount: ' ) . $response->amount . PHP_EOL
				);

				$response->parent_id = $data['trx_id'];

				wc_emerchantpay_order_proxy()->save_trx_list_to_order( $order, array( $response ) );

				return $response;
			}

			return WC_Emerchantpay_Helper::get_wp_error( WC_Emerchantpay_Genesis_Helper::fetch_gateway_response_message( $response ) );
		} catch ( \Exception $exception ) {
			WC_Emerchantpay_Helper::log_exception( $exception );

			return WC_Emerchantpay_Helper::get_wp_error( $exception );
		}
	}

	/**
	 * Get capture transaction type from order
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @throws Exception If Authorize trx is missing or of unknown type.
	 * @return string
	 */
	protected static function get_capture_trx_type( WC_Order $order ) {
		$auth = WC_Emerchantpay_Transactions_Tree::create_from_order( $order )->get_authorize_trx();

		if ( empty( $auth ) ) {
			throw new Exception( 'Missing Authorize transaction' );
		}

		if (
			Types::isAuthorize( $auth->type ) ||
			Types::GOOGLE_PAY === $auth->type ||
			Types::PAY_PAL === $auth->type ||
			Types::APPLE_PAY === $auth->type
		) {
			return Types::getCaptureTransactionClass( $auth->type );
		}

		throw new Exception( 'Invalid trx type: ' . esc_html( $auth->type ) );
	}

	/**
	 * Event Handler for executing capture transaction
	 * Called in templates/admin/order/dialogs/capture.php
	 *
	 * @throws Exception If capture amount is invalid, Gateway response has error or status is different from approved.
	 * @return void
	 */
	public static function capture() {
		ob_start();

		check_ajax_referer( 'order-item', 'security' );
		// TODO Check and fix Found unknown capability "edit_shop_orders" in function call to current_user_can(). Please check the spelling of the capability.
		// phpcs:disable
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			die( -1 );
		}
		// phpcs:enable

		try {
			if ( empty( $_POST['order_id'] ) ) {
				throw new exception( static::get_translated_text( 'Order id is empty!' ) );
			}

			$order_id = absint( $_POST['order_id'] );
			$order    = wc_emerchantpay_order_proxy()->get_order_by_id( $order_id );

			if ( empty( $_POST['trx_id'] ) ) {
				throw new exception( static::get_translated_text( 'Empty transaction id!' ) );
			}

			$trx_id = sanitize_text_field( wp_unslash( $_POST['trx_id'] ) );

			if ( ! static::can_capture( $order, $trx_id ) ) {
				wp_send_json_error(
					array(
						'error' => static::get_translated_text( 'You can do this only on a not-fully captured Authorize Transaction!' ),
					)
				);
				return;
			}

			if ( empty( $_POST['capture_amount'] ) ) {
				throw new exception( static::get_translated_text( 'Empty capture amount!' ) );
			}

			$capture_amount = wc_format_decimal( sanitize_text_field( wp_unslash( $_POST['capture_amount'] ) ) );

			if ( empty( $_POST['capture_reason'] ) ) {
				throw new exception( static::get_translated_text( 'Empty capture reason!' ) );
			}

			$capture_reason = sanitize_text_field( wp_unslash( $_POST['capture_reason'] ) );

			$captured_amount = WC_Emerchantpay_Transactions_Tree::get_total_captured_amount( $order );
			$max_capture     = wc_format_decimal( $order->get_total() - $captured_amount );

			if ( ! $capture_amount || $max_capture < $capture_amount || 0 > $capture_amount ) {
				throw new exception( static::get_translated_text( 'Invalid capture amount' ) );
			}

			// Create the refund object.
			$gateway_response = static::process_capture(
				array(
					'trx_id'   => $trx_id,
					'order_id' => $order_id,
					'amount'   => $capture_amount,
					'reason'   => $capture_reason,
				)
			);

			if ( is_wp_error( $gateway_response ) ) {
				throw new Exception( $gateway_response->get_error_message() );
			}

			if ( States::APPROVED !== $gateway_response->status ) {
				throw new Exception( WC_Emerchantpay_Genesis_Helper::fetch_gateway_response_message( $gateway_response ) );
			}

			$captured_amount += (float) $capture_amount;

			$capture_left = $order->get_total() - $captured_amount;

			$response_data = array(
				'gateway' => $gateway_response,
				'form'    => array(
					'capture' => array(
						'total'           => array(
							'amount'    => $captured_amount,
							'formatted' => WC_Emerchantpay_Order_Helper::format_price(
								$captured_amount,
								$order
							),
						),
						'total_available' => array(
							'amount'    => $capture_left > 0 ? $capture_left : '',
							'formatted' => WC_Emerchantpay_Order_Helper::format_price(
								$capture_left,
								$order
							),
						),
					),
				),
			);

			wp_send_json_success( $response_data );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'error' => $e->getMessage() ) );
		}
	}

	/**
	 * Event Handler for executing void transaction
	 *
	 * @throws Exception Throws error of class WP_Error.
	 * @return bool
	 */
	public static function void() {
		ob_start();

		check_ajax_referer( 'order-item', 'security' );
		// TODO Check and fix Found unknown capability "edit_shop_orders" in function call to current_user_can(). Please check the spelling of the capability.
		// phpcs:disable
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			die( -1 );
		}
		// phpcs:enable

		try {
			if ( empty( $_POST['order_id'] ) ) {
				throw new exception( static::get_translated_text( 'Order id is empty!' ) );
			}

			$order_id = absint( $_POST['order_id'] );
			$order    = wc_emerchantpay_order_proxy()->get_order_by_id( $order_id );

			if ( empty( $_POST['trx_id'] ) ) {
				throw new exception( static::get_translated_text( 'Empty transaction id!' ) );
			}

			$void_trx_id = sanitize_text_field( wp_unslash( $_POST['trx_id'] ) );

			if ( ! static::can_void( $order, $void_trx_id ) ) {
				wp_send_json_error(
					array(
						'error' => static::get_translated_text( 'You cannot void non-authorize payment or already captured payment!' ),
					)
				);

				return false;
			}

			if ( empty( $_POST['void_reason'] ) ) {
				throw new exception( static::get_translated_text( 'Empty void reason!' ) );
			}

			$void_reason = sanitize_text_field( wp_unslash( $_POST['void_reason'] ) );

			$payment_gateway = wc_emerchantpay_order_proxy()->get_payment_method_instance_by_order( $order );

			if ( ! $order || ! $order->get_transaction_id() ) {
				return false;
			}

			$payment_gateway->set_credentials();
			$payment_gateway->set_terminal_token( $order );

			$void = new \Genesis\Genesis( 'Financial\Cancel' );

			$void
				->request()
					->setTransactionId(
						$payment_gateway::generate_transaction_id( $order_id )
					)
					->setUsage(
						$void_reason
					)
					->setRemoteIp(
						WC_Emerchantpay_Helper::get_client_remote_ip_address()
					)
					->setReferenceId(
						$void_trx_id
					);

			try {
				$void->execute();

				if ( ! $void->response()->isSuccessful() ) {
					throw new \Exception( $void->response()->getErrorDescription() );
				}

				// Create the refund object.
				$gateway_response = $void->response()->getResponseObject();
			} catch ( \Exception $exception ) {
				$gateway_response = WC_Emerchantpay_Helper::get_wp_error( $exception );
			}

			if ( is_wp_error( $gateway_response ) ) {
				throw new Exception( $gateway_response->get_error_message() );
			}

			if ( ! $void->response()->isApproved() ) {
				throw new Exception( WC_Emerchantpay_Genesis_Helper::fetch_gateway_response_message( $gateway_response ) );
			}

			$order->add_order_note( static::get_translated_text( 'Payment Voided!' ) . PHP_EOL . PHP_EOL . static::get_translated_text( 'Id: ' ) . $gateway_response->unique_id );
			$order->update_status( self::ORDER_STATUS_CANCELLED, WC_Emerchantpay_Genesis_Helper::fetch_gateway_response_message( $gateway_response ) );
			$gateway_response->parent_id = $void_trx_id;
			wc_emerchantpay_order_proxy()->save_trx_list_to_order( $order, array( $gateway_response ) );

			wp_send_json_success( array( 'gateway' => $gateway_response ) );

			return true;
		} catch ( Exception $exception ) {
			WC_Emerchantpay_Helper::log_exception( $exception );

			wp_send_json_error( array( 'error' => $exception->getMessage() ) );

			return false;
		}
	}

	/**
	 * Admin Action Handler for displaying custom code after order totals
	 *
	 * @param int $order_id Order identifier.
	 * @return void
	 */
	public function display_admin_order_after_totals( $order_id ) {
		$order = wc_emerchantpay_order_proxy()->get_order_by_id( $order_id );

		if ( $order->get_payment_method() !== $this->id ) {
			return;
		}

		$captured_amount = WC_Emerchantpay_Transactions_Tree::get_total_captured_amount( $order );

		if ( $captured_amount ) {
			$this->fetch_template(
				'admin/order/totals/capture.php',
				array(
					'payment_method'  => $this,
					'order'           => $order,
					'captured_amount' => $captured_amount,
				)
			);
		}

		$this->fetch_template(
			'admin/order/totals/common.php',
			array(
				'payment_method' => $this,
				'order'          => $order,
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
		if ( $this->get_is_settings_checkout_page() ) {
			return true;
		}

		if ( self::SETTING_VALUE_YES !== $this->enabled ) {
			return false;
		}

		foreach ( $this->get_required_api_setting_keys() as $required_api_setting_key ) {
			if ( empty( $this->get_method_setting( $required_api_setting_key ) ) ) {
				return false;
			}
		}

		if ( ! $this->meet_subscription_requirements() ) {
			return false;
		}

		if ( ! $this->is_applicable() ) {
			return false;
		}

		return parent::is_available();
	}

	/**
	 * If we have subscription product the Plugin is available only if Subscriptions are configured
	 *
	 * @return bool
	 */
	protected function meet_subscription_requirements() {
		// The Method Availability is only limited to the WooCommerce Checkout process.
		if ( null === WC_Emerchantpay_Subscription_Helper::get_cart() ) {
			return true;
		}

		// If the plugin Method does not have Subscriptions enabled, do not show it on the Checkout.
		if ( ! $this->is_subscription_enabled() && WC_Emerchantpay_Subscription_Helper::is_cart_has_subscriptions() ) {
			return false;
		}

		return true;
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
		return WC_Emerchantpay_Genesis_Helper::check_genesis_requirements_verified() === true;
	}

	/**
	 * Determines if the Payment Module Requires Securect HTTPS Connection
	 *
	 * @return bool
	 */
	protected function is_ssl_required() {
		return false;
	}

	/**
	 * Admin Panel Field Definition
	 *
	 * @return void
	 */
	public function init_form_fields() {
		// Admin title/description.
		$this->method_title = $this->get_module_title();

		$this->form_fields = array(
			self::SETTING_KEY_ENABLED        => array(
				'type'    => 'checkbox',
				'title'   => static::get_translated_text( 'Enable/Disable' ),
				'label'   => static::get_translated_text( 'Enable Payment Method' ),
				'default' => self::SETTING_VALUE_NO,
			),
			self::SETTING_KEY_TITLE          => array(
				'type'        => 'text',
				'title'       => static::get_translated_text( 'Title:' ),
				'description' => static::get_translated_text( 'Title for this payment method, during customer checkout.' ),
				'default'     => $this->method_title,
				'desc_tip'    => true,
			),
			self::SETTING_KEY_DESCRIPTION    => array(
				'type'        => 'textarea',
				'title'       => static::get_translated_text( 'Description:' ),
				'description' => static::get_translated_text( 'Text describing this payment method to the customer, during checkout.' ),
				'default'     => static::get_translated_text( 'Pay safely through emerchantpay\'s Secure Gateway.' ),
				'desc_tip'    => true,
			),
			'api_credentials'                => array(
				'type'        => 'title',
				'title'       => static::get_translated_text( 'API Credentials' ),
				'description' =>
					sprintf(
						static::get_translated_text(
							'Enter Genesis API Credentials below, in order to access the Gateway.' .
							'If you don\'t have credentials, %sget in touch%s with our technical support.'
						),
						'<a href="mailto:tech-support@emerchantpay.com">',
						'</a>'
					),
			),
			self::SETTING_KEY_TEST_MODE      => array(
				'type'        => 'checkbox',
				'title'       => static::get_translated_text( 'Test Mode' ),
				'label'       => static::get_translated_text( 'Use test (staging) environment' ),
				'description' => static::get_translated_text(
					'Selecting this would route all requests through our test environment.' .
					'<br/>' .
					'NO Funds WILL BE transferred!'
				),
				'desc_tip'    => true,
				'default'     => self::SETTING_VALUE_YES,
			),
			self::SETTING_KEY_ALLOW_CAPTURES => array(
				'type'        => 'checkbox',
				'title'       => static::get_translated_text( 'Enable Captures' ),
				'label'       => static::get_translated_text( 'Enable / Disable Captures on the Order Preview Page' ),
				'description' => static::get_translated_text( 'Decide whether to Enable / Disable online Captures on the Order Preview Page.' ) .
				'<br /> <br />' .
				static::get_translated_text( 'It depends on how the genesis gateway is configured' ),
				'default'     => self::SETTING_VALUE_YES,
				'desc_tip'    => true,
			),
			self::SETTING_KEY_ALLOW_REFUNDS  => array(
				'type'        => 'checkbox',
				'title'       => static::get_translated_text( 'Enable Refunds' ),
				'label'       => static::get_translated_text( 'Enable / Disable Refunds on the Order Preview Page' ),
				'description' => static::get_translated_text( 'Decide whether to Enable / Disable online Refunds on the Order Preview Page.' ) .
				'<br /> <br />' .
				static::get_translated_text( 'It depends on how the genesis gateway is configured' ),
				'default'     => self::SETTING_VALUE_YES,
				'desc_tip'    => true,
			),
			self::SETTING_KEY_USERNAME       => array(
				'type'        => 'text',
				'title'       => static::get_translated_text( 'Username' ),
				'description' => static::get_translated_text( 'This is your Genesis username.' ),
				'desc_tip'    => true,
			),
			self::SETTING_KEY_PASSWORD       => array(
				'type'        => 'text',
				'title'       => static::get_translated_text( 'Password' ),
				'description' => static::get_translated_text( 'This is your Genesis password.' ),
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Admin panel Return redirect urls fields definition
	 *
	 * @return array
	 */
	protected function build_redirect_form_fields() {
		$form_fields = array(
			'return_redirect'                     => array(
				'type'  => 'title',
				'title' => 'Return redirects',
			),
			self::SETTING_KEY_CUSTOM_THANKYOU_URL => array(
				'type'        => 'text',
				'title'       => static::get_translated_text( 'Custom Thank You page URL' ),
				'description' => static::get_translated_text( 'Provide a complete URL where the customer will be redirected upon successful payment. Works only with default WooCommerce Thank You page hooks.' ),
				'desc_tip'    => true,
			),
			self::SETTING_KEY_REDIRECT_FAILURE    => array(
				'type'        => 'select',
				'title'       => static::get_translated_text( 'Redirect upon failure' ),
				'options'     => $this->get_available_redirect_pages(),
				'description' => static::get_translated_text( 'Select page where to redirect customer upon failed payment' ),
				'desc_tip'    => true,
				'default'     => self::SETTING_VALUE_ORDER,
			),
		);

		if ( WC_Emerchantpay_Checkout::get_method_code() === self::get_method_code() ) {
			$form_fields += array(
				self::SETTING_KEY_REDIRECT_CANCEL => array(
					'type'        => 'select',
					'title'       => static::get_translated_text( 'Redirect upon cancel' ),
					'options'     => $this->get_available_redirect_pages(),
					'description' => static::get_translated_text( 'Select page where to redirect customer upon cancelled payment' ),
					'desc_tip'    => true,
					'default'     => self::SETTING_VALUE_ORDER,
				),
			);
		}

		return $form_fields;
	}

	/**
	 * Admin Panel Subscription Field Definition
	 *
	 * @return array
	 */
	protected function build_subscription_form_fields() {
		return array(
			'subscription_settings'               => array(
				'type'        => 'title',
				'title'       => static::get_translated_text( 'Subscription Settings' ),
				'description' => static::get_translated_text(
					'Here you can manage additional settings for the recurring payments (Subscriptions)'
				),
			),
			self::SETTING_KEY_ALLOW_SUBSCRIPTIONS => array(
				'type'    => 'checkbox',
				'title'   => static::get_translated_text( 'Enable/Disable' ),
				'label'   => static::get_translated_text( 'Enable/Disable Subscription Payments' ),
				'default' => self::SETTING_VALUE_NO,
			),
			self::SETTING_KEY_RECURRING_TOKEN     => array(
				'type'        => 'text',
				'title'       => static::get_translated_text( 'Recurring Token' ),
				'description' => static::get_translated_text(
					'This is your Genesis Token for Recurring Transaction (Must be CVV-OFF).' .
					'Leave it empty in order to use the token, which has been used for the processing transaction.' .
					'If you are using Sale or Sale 3D for recurring transactions contact tech support for recurring ' .
					'tokens used for the renewal payments.'
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
	public function admin_options() {
		?>
		<h3>
			<?php echo esc_html( $this->method_title ); ?>
		</h3>
		<p>
			<?php echo esc_html( $this->method_description ); ?>
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
	 * @throws \Genesis\Exceptions\InvalidArgument Invalid argument.
	 */
	public function callback_handler() {
		// TODO Check how to fix silencing error.
		// phpcs:disable
		@ob_clean();
		// php:enable

		$this->set_credentials();

		// Handle Customer returns.
		$this->handle_return();

		// Handle Gateway notifications.
		$this->handle_notification();

		exit( 0 );
	}

	/**
	 * Handle customer return and update their order status
	 *
	 * @return void
	 */
	protected function handle_return() {
		// TODO Check and fix nonce verification error.
		// phpcs:disable WordPress.Security.NonceVerification
		if ( isset( $_GET['act'] ) && isset( $_GET['oid'] ) ) {
			$order_id = absint( $_GET['oid'] );
			$order    = wc_emerchantpay_order_proxy()->get_order_by_id( $order_id );

			if ( $this->get_one_time_token( $order ) === '|CLEAR|' ) {
				wp_safe_redirect( wc_get_page_permalink( 'cart' ) );
			} else {
				$this->set_one_time_token( $order, '|CLEAR|' );
				$return_url = $order->get_view_order_url();

				switch ( esc_sql( sanitize_text_field( wp_unslash( $_GET['act'] ) ) ) ) {
					case 'success':
						$notice = static::get_translated_text(
							'Your payment has been completed successfully.'
						);

						WC_Emerchantpay_Message_Helper::add_success_notice( $notice );
						break;
					case 'failure':
						$notice = static::get_translated_text(
							'Your payment has been declined, please check your data and try again'
						);

						WC()->session->set( 'order_awaiting_payment', false );
						$order->update_status( self::ORDER_STATUS_CANCELLED, $notice );

						WC_Emerchantpay_Message_Helper::add_error_notice( $notice );

						if ( $this->get_method_setting( self::SETTING_KEY_REDIRECT_FAILURE ) === self::SETTING_VALUE_CHECKOUT ) {
							$return_url = wc_get_checkout_url();
						}
						break;
					// TODO Remove unused 'cancel' case.
					case 'cancel':
						$note = static::get_translated_text(
							'The customer cancelled their payment session'
						);

						WC()->session->set( 'order_awaiting_payment', false );
						$order->update_status( self::ORDER_STATUS_CANCELLED, $note );
						break;
				}

				header( 'Location: ' . $return_url );
			}
		}
		// phpcs:enable
	}

	/**
	 * Handle gateway notifications
	 *
	 * @throws \Exception If WooCommerce order is invalid.
	 * @return void
	 */
	protected function handle_notification() {
		// TODO Check and fix nonce verification error.
		if ( ! $this->get_is_valid_notification( $_POST ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		try {
			// TODO Check and fix nonce verification error.
			$notification = new Notification( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification

			if ( $notification->isAuthentic() ) {
				$notification_object = $notification->getNotificationObject();
				$this->set_notification_terminal_token( $notification_object );

				$notification->initReconciliation();
				$reconcile = $notification->getReconciliationObject();

				if ( $reconcile ) {
					$order = wc_emerchantpay_order_proxy()->load_order_from_reconcile_object(
						$reconcile,
						$this->get_checkout_transaction_id_meta_key()
					);

					if ( ! WC_Emerchantpay_Order_Helper::is_valid_order( $order ) ) {
						throw new \Exception( 'Invalid WooCommerce Order!' );
					}

					$this->update_order_status( $order, $reconcile );

					if ( WC_Emerchantpay_Subscription_Helper::is_init_recurring_reconciliation( $reconcile ) ) {
						$this->process_init_recurring_reconciliation( $order, $reconcile );
					}

					$notification->renderResponse();
				}
			}
		} catch ( \Exception $e ) {
			header( 'HTTP/1.1 403 Forbidden' );
		}
	}

	/**
	 *
	 * Process init recurring reconciliation
	 *
	 * @param \WC_Order $order Order object.
	 * @param \stdClass $reconcile Reconcile object.
	 * @return bool
	 */
	protected function process_init_recurring_reconciliation( $order, $reconcile ) {
		return $this->process_after_init_recurring_payment( $order, $reconcile );
	}

	/**
	 * Returns a list with data used for preparing a request to the gateway
	 *
	 * @param WC_Order $order Order object.
	 * @param bool     $is_recurring Defines that request should be recurring or not. Default false.
	 *
	 * @return array
	 * @throws \Exception Invalid Woocommerce Order.
	 */
	protected function populate_gate_request_data( $order, $is_recurring = false ) {
		if ( ! WC_Emerchantpay_Order_Helper::is_valid_order( $order ) ) {
			throw new \Exception( 'Invalid WooCommerce Order!' );
		}

		$return_success_url = $this->build_iframe_url( $this->get_return_url( $order ) );
		$return_failure_url = $this->build_iframe_url(
			$this->append_to_url(
				WC()->api_request_url( get_class( $this ) ),
				array(
					'act' => 'failure',
					'oid' => $order->get_id(),
				)
			)
		);

		$data = array(
			'transaction_id'     => static::generate_transaction_id( $order->get_id() ),
			'amount'             => (float) $order->get_total(),
			'currency'           => $order->get_currency(),
			'usage'              => WC_Emerchantpay_Genesis_Helper::get_payment_transaction_usage( false ),
			'description'        => $this->get_item_description( $order ),
			'customer_email'     => $order->get_billing_email(),
			'customer_phone'     => true === $is_recurring
				? WC_Emerchantpay_Genesis_Helper::handle_phone_number( $order->get_billing_phone() )
				: $order->get_billing_phone(),
			// URLs.
			'notification_url'   => WC()->api_request_url( get_class( $this ) ),
			'return_success_url' => $return_success_url,
			'return_failure_url' => $return_failure_url,
			'billing'            => self::get_order_billing_address( $order ),
			'shipping'           => self::get_order_shipping_address( $order ),
		);

		return $data;
	}

	/**
	 * Gets order billing address
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return array
	 */
	protected static function get_order_billing_address( WC_Order $order ) {
		return array(
			'first_name' => $order->get_billing_first_name(),
			'last_name'  => $order->get_billing_last_name(),
			'address1'   => $order->get_billing_address_1(),
			'address2'   => $order->get_billing_address_2(),
			'zip_code'   => $order->get_billing_postcode(),
			'city'       => $order->get_billing_city(),
			'state'      => $order->get_billing_state(),
			'country'    => $order->get_billing_country(),
		);
	}

	/**
	 * Gets order shipping address
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return array
	 */
	protected static function get_order_shipping_address( WC_Order $order ) {
		if ( self::is_shipping_address_missing( $order ) ) {
			return self::get_order_billing_address( $order );
		}

		return array(
			'first_name' => $order->get_shipping_first_name(),
			'last_name'  => $order->get_shipping_last_name(),
			'address1'   => $order->get_shipping_address_1(),
			'address2'   => $order->get_shipping_address_2(),
			'zip_code'   => $order->get_shipping_postcode(),
			'city'       => $order->get_shipping_city(),
			'state'      => $order->get_shipping_state(),
			'country'    => $order->get_shipping_country(),
		);
	}

	/**
	 * Checks if shipping address missing
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return bool
	 */
	protected static function is_shipping_address_missing( WC_Order $order ) {
		return empty( $order->get_shipping_country() ) ||
			empty( $order->get_shipping_city() ) ||
			empty( $order->get_shipping_address_1() );
	}

	/**
	 * Updates the Order Status and creates order note
	 *
	 * @param WC_Order          $order Order object.
	 * @param stdClass|WP_Error $gateway_response_object Gateway response object.
	 *
	 * @return void
	 * @throws \Exception If WooCommerce order is invalid.
	 */
	protected function update_order_status( $order, $gateway_response_object ) {
		if ( ! WC_Emerchantpay_Order_Helper::is_valid_order( $order ) ) {
			throw new \Exception( 'Invalid WooCommerce Order!' );
		}

		if ( is_wp_error( $gateway_response_object ) ) {
			$order->add_order_note(
				static::get_translated_text( 'Payment transaction returned an error!' )
			);

			$order->update_status(
				self::ORDER_STATUS_FAILED,
				$gateway_response_object->get_error_message()
			);

			return;
		}

		$payment_transaction = WC_Emerchantpay_Genesis_Helper::get_reconcile_payment_transaction( $gateway_response_object );

		// Tweak for handling Processing Genesis Events that are missing in the Trx List & Hierarchy.
		if ( isset( $payment_transaction->reference_transaction_unique_id ) ) {
			$payment_transaction->parent_id = $payment_transaction->reference_transaction_unique_id;
		}

		// Tweak for handling WPF Genesis Events that are missing in the Trx List & Hierarchy.
		if ( $payment_transaction instanceof ArrayObject && $payment_transaction->count() === 2 ) {
			$payment_transaction[1]->parent_id = $payment_transaction[0]->unique_id;
		}

		$raw_trx_list = $this->get_trx_list( $payment_transaction );

		switch ( $gateway_response_object->status ) {
			case States::APPROVED:
				$this->update_order_status_approved(
					$order,
					$gateway_response_object,
					$payment_transaction,
					$raw_trx_list
				);

				if ( isset( $gateway_response_object->transaction_type ) && Types::isRefund( $gateway_response_object->transaction_type ) ) {
					self::update_order_status_refunded( $order, $gateway_response_object );
				}

				break;
			case States::DECLINED:
				$has_payment = WC_Emerchantpay_Genesis_Helper::has_payment( $gateway_response_object );
				if ( ! $has_payment ) {
					$this->update_order_status_cancelled(
						$order,
						$gateway_response_object,
						static::get_translated_text( 'Payment has been cancelled by the customer.' )
					);
					break;
				}

				$this->update_order_status_declined( $order, $gateway_response_object );
				break;
			case States::ERROR:
				$this->update_order_status_error( $order, $gateway_response_object );

				break;
			case States::REFUNDED:
				if ( isset( $gateway_response_object->transaction_type ) &&
					! Types::isRefund( $gateway_response_object->transaction_type )
				) {
					break;
				}

				self::update_order_status_refunded( $order, $gateway_response_object );
				break;
			case States::TIMEOUT:
				$this->update_order_status_cancelled(
					$order,
					$gateway_response_object,
					static::get_translated_text( 'The payment expired.' )
				);
				break;
			case States::VOIDED:
				$this->update_order_status_cancelled(
					$order,
					$gateway_response_object,
					static::get_translated_text( 'Payment has been voided.' )
				);
				break;
		}

		// Do not modify whole transaction tree for the reference transactions of the Init Recurring type.
		// The Reconcile Object can bring Recurring Sale transactions and their reference transactions.
		if ( count( $raw_trx_list ) > 1 && WC_Emerchantpay_Subscription_Helper::is_init_recurring( $raw_trx_list[0] ) ) {
			$raw_trx_list = array( $raw_trx_list[0] );
		}

		wc_emerchantpay_order_proxy()->save_trx_list_to_order( $order, $raw_trx_list );

		// Update the order, just to be sure, sometimes transaction is not being set!
		// WC_Emerchantpay_Order_Helper::setOrderMetaData($order->id, self::META_TRANSACTION_ID, $gatewayResponseObject->unique_id);
		// Save the terminal token, through which we processed the transaction
		// WC_Emerchantpay_Order_Helper::setOrderMetaData($order->id, self::META_TRANSACTION_TERMINAL_TOKEN, $gatewayResponseObject->terminal_token);.
	}

	/** Gets transaction list
	 *
	 * @param ArrayObject $payment_transaction Current payment transaction.
	 *
	 * @return array
	 */
	protected function get_trx_list( $payment_transaction ) {
		if ( $payment_transaction instanceof \ArrayObject ) {
			return $payment_transaction->getArrayCopy();
		} else {
			return array( $payment_transaction );
		}
	}

	/**
	 * Updates refunded order status.
	 *
	 * @param WC_Order $order Order object.
	 * @param stdClass $gateway_response_object Gateway response object.
	 */
	protected static function update_order_status_refunded( WC_Order $order, $gateway_response_object ) {
		$is_initial_refund         = wc_format_decimal( 0 ) === wc_format_decimal( $order->get_total_refunded() );
		$gateway_technical_message = WC_Emerchantpay_Genesis_Helper::fetch_gateway_response_message( $gateway_response_object );
		$fully_refunded            = false;
		$total_order_amount        = $order->get_total();
		$payment_transactions      = WC_Emerchantpay_Genesis_Helper::get_reconcile_payment_transaction(
			$gateway_response_object
		);

		switch ( $order->get_payment_method() ) {
			case WC_Emerchantpay_Checkout::get_method_code():
				if ( $payment_transactions instanceof ArrayObject && $payment_transactions->count() > 1 ) {
					$refund_sum = WC_Emerchantpay_Genesis_Helper::get_total_refund_from_wpf_reconcile(
						$gateway_response_object
					);

					$fully_refunded = ( (float) $total_order_amount === (float) $refund_sum ) ? true : false;

					break;
				}

				if ( $payment_transactions instanceof stdClass ) {
					$total_refunded_amount = WC_Emerchantpay_Transactions_Tree::get_total_amount_without_unique_id(
						$payment_transactions->unique_id,
						WC_Emerchantpay_Transactions_Tree::get_transactions_list_from_order( $order ),
						Types::REFUND
					);

					$total_refund_amount = $total_refunded_amount + $payment_transactions->amount;
					$fully_refunded      = ( (float) $total_order_amount === (float) $total_refund_amount ) ? true : false;
				}

				break;
			case WC_Emerchantpay_Direct::get_method_code():
				$total_refunded_amount = WC_Emerchantpay_Transactions_Tree::get_total_amount_without_unique_id(
					$payment_transactions->unique_id,
					WC_Emerchantpay_Transactions_Tree::get_transactions_list_from_order( $order ),
					Types::REFUND
				);

				$total_refund_amount = $total_refunded_amount + $payment_transactions->amount;
				$fully_refunded      = ( (float) $total_order_amount === (float) $total_refund_amount ) ? true : false;

				break;
		}

		if ( ! $fully_refunded || ! $is_initial_refund ) {
			$order->add_order_note(
				static::get_translated_text(
					sprintf(
						'Payment transaction has been %s refunded!',
						( $fully_refunded ) ? 'fully' : 'partially'
					)
				)
			);
		}

		if ( $fully_refunded && ! $is_initial_refund ) {
			// Do not restock items
			// Order Status Update only.
			$order->update_status(
				self::ORDER_STATUS_REFUNDED,
				$order->get_payment_method() .
				static::get_translated_text( ' change Order Status to Refund.' ) . $gateway_technical_message
			);
		}

		// Only for Fully Initial Refunds
		// Create Order Refund + Items Restock.
		if ( $fully_refunded && $is_initial_refund ) {
			try {
				// Prepare the Items for restock.
				$line_items = array();
				$items      = $order->get_items();

				/**
				 * Iterate items.
				 *
				 * @var integer $item_id Item identifier.
				 * @var WC_Order_Item_Product $item Item object.
				 */
				foreach ( $items as $item_id => $item ) {
					$line_items[ $item_id ] = array(
						'qty'          => $item->get_quantity(),
						'refund_total' => $item->get_total(),
						'refund_tax'   => $item->get_taxes()['total'],
					);
				}

				wc_create_refund(
					array(
						'amount'         => $total_order_amount,
						'reason'         =>
							static::get_translated_text( 'Automated Refund via ' ) . $order->get_payment_method(),
						'order_id'       => $order->get_id(),
						'line_items'     => $line_items,
						'refund_payment' => false,
						'restock_items'  => true,
					)
				);

				$order->add_order_note(
					static::get_translated_text( 'Payment transaction has been fully refunded!' )
				);
			} catch ( Exception $e ) {
				// Ignore the Error.
				WC_Emerchantpay_Helper::log_exception( $e );

				$order->update_status(
					self::ORDER_STATUS_REFUNDED,
					$order->get_payment_method() .
					static::get_translated_text( ' change Order Status to Refunded.' ) . $gateway_technical_message
				);
			} // End try.
		} // End if.
	}

	/**
	 * Updates order status error
	 *
	 * @param WC_Order $order Order object.
	 * @param stdClass $gateway_response_object Gateway response object.
	 */
	protected function update_order_status_error( WC_Order $order, $gateway_response_object ) {
		$order->add_order_note( static::get_translated_text( 'Payment transaction returned an error!' ) );

		$order->update_status( self::ORDER_STATUS_FAILED, WC_Emerchantpay_Genesis_Helper::fetch_gateway_response_message( $gateway_response_object ) );
	}

	/**
	 * Updates order status declined
	 *
	 * @param WC_Order $order Order object.
	 * @param stdClass $gateway_response_object Gateway response object.
	 */
	protected function update_order_status_declined( WC_Order $order, $gateway_response_object ) {
		$order->add_order_note( static::get_translated_text( 'Payment transaction has been declined!' ) );

		$order->update_status( self::ORDER_STATUS_FAILED, WC_Emerchantpay_Genesis_Helper::fetch_gateway_response_message( $gateway_response_object ) );
	}

	/**
	 * Updates order status canceled
	 *
	 * @param WC_Order $order Order object.
	 * @param stdClass $gateway_response_object Gateway response object.
	 * @param string   $custom_text Custom text. Default empty string.
	 */
	protected function update_order_status_cancelled( WC_Order $order, $gateway_response_object, $custom_text = '' ) {
		$order->add_order_note( static::get_translated_text( 'Payment transaction has been cancelled!' ) );

		$gateway_message = WC_Emerchantpay_Genesis_Helper::fetch_gateway_response_message( $gateway_response_object );

		$order->update_status( self::ORDER_STATUS_CANCELLED, $gateway_message ?: static::get_translated_text( $custom_text ) ); // phpcs:ignore Universal.Operators.DisallowShortTernary.Found
	}

	/**
	 * Updates order status approved
	 *
	 * @param WC_Order    $order Order object.
	 * @param stdClass    $gateway_response_object Gateway response object.
	 * @param ArrayObject $payment_transaction Current payment transaction.
	 * @param array       $raw_trx_list Unhandled transaction list.
	 */
	protected function update_order_status_approved( WC_Order $order, $gateway_response_object, $payment_transaction, array $raw_trx_list ) {
		$payment_transaction_id = $this->get_trx_id( $raw_trx_list );

		if ( self::ORDER_STATUS_PENDING === $order->get_status() ) {
			$order->add_order_note(
				static::get_translated_text( 'Payment transaction has been approved!' )
				. PHP_EOL . PHP_EOL .
				static::get_translated_text( 'Id:' ) . ' ' . $payment_transaction_id
				. PHP_EOL . PHP_EOL .
				static::get_translated_text( 'Total:' ) . ' ' . $gateway_response_object->amount . ' ' . $gateway_response_object->currency
			);
		}

		$order->payment_complete( $payment_transaction_id );

		$this->save_approved_order_meta_data( $order, $payment_transaction );
	}

	/**
	 * Gets transaction id
	 *
	 * @param array $raw_trx_list Unhandled transaction list.
	 *
	 * @return string
	 */
	protected function get_trx_id( array $raw_trx_list ) {
		foreach ( $raw_trx_list as $trx ) {
			if ( Types::canRefund( $trx->transaction_type ) ) {
				return $trx->unique_id;
			}
		}

		return $raw_trx_list[0]->unique_id;
	}

	/**
	 * Saves approved meta data
	 *
	 * @param WC_Order $order Order object.
	 * @param stdClass $payment_transaction Current payment transaction.
	 */
	protected function save_approved_order_meta_data( WC_Order $order, $payment_transaction ) {
		$amount = 0.0;

		if ( $payment_transaction instanceof \ArrayObject ) {
			$trx = $payment_transaction[0];

			foreach ( $payment_transaction as $t ) {
				$amount += floatval( $t->amount );
			}
		} else {
			$trx    = $payment_transaction;
			$amount = $trx->amount;
		}

		wc_emerchantpay_order_proxy()->set_order_meta_data(
			$order,
			self::META_TRANSACTION_TYPE,
			$trx->transaction_type
		);

		wc_emerchantpay_order_proxy()->set_order_meta_data(
			$order,
			self::META_ORDER_TRANSACTION_AMOUNT,
			$amount
		);

		$terminal_token = $trx->terminal_token ?? null;

		if ( empty( $terminal_token ) && isset( $trx->recurring_type ) && RecurringTypes::SUBSEQUENT === $trx->recurring_type ) {
			$terminal_token = \Genesis\Config::getToken();
		}

		if ( ! empty( $terminal_token ) ) {
			wc_emerchantpay_order_proxy()->set_order_meta_data( $order, self::META_TRANSACTION_TERMINAL_TOKEN, $terminal_token );

			WC_Emerchantpay_Subscription_Helper::save_terminal_token_to_order_subscriptions( $order, $terminal_token );
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
		return false;
	}

	/**
	 * Refund ajax call used in transaction tree
	 *
	 * @throws \Exception Throws error when global variable are not set or empty.
	 * @return bool
	 */
	public static function refund() {
		ob_start();

		check_ajax_referer( 'order-item', 'security' );
		// TODO Check and fix Found unknown capability "edit_shop_orders" in function call to current_user_can(). Please check the spelling of the capability.
		// phpcs:disable
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			die( -1 );
		}
		// phpcs:enable
		try {
			if ( empty( $_POST['order_id'] ) ) {
				throw new exception( static::get_translated_text( 'Order id is empty!' ) );
			}
			$order_id = absint( $_POST['order_id'] );

			if ( empty( $_POST['amount'] ) ) {
				throw new exception( static::get_translated_text( 'Empty refund amount!' ) );
			}
			$amount = floatval( $_POST['amount'] );

			if ( empty( $_POST['reason'] ) ) {
				throw new exception( static::get_translated_text( 'Empty refund reason!' ) );
			}
			$reason = sanitize_text_field( wp_unslash( $_POST['reason'] ) );

			if ( empty( $_POST['trx_id'] ) ) {
				throw new exception( static::get_translated_text( 'Empty transaction id!' ) );
			}
			$trx_id = sanitize_text_field( wp_unslash( $_POST['trx_id'] ) );

			$result = static::do_refund( $order_id, $amount, $reason, $trx_id, true );

			if ( $result instanceof \WP_Error ) {
				wp_send_json_error(
					array(
						'error' => $result->get_error_message(),
					)
				);

				return false;
			}

			wp_send_json_success(
				array( 'gateway' => $result )
			);
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'error' => $e->getMessage() ) );
		}
	}

	/**
	 * Called internally by WooCommerce when their internal refund is used
	 * Kept for compatibility
	 *
	 * @param int    $order_id Order identifier.
	 * @param null   $amount The amount to charge.
	 * @param string $reason The usage of transaction.
	 *
	 * @return bool|WP_Error
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		return static::do_refund( $order_id, $amount, $reason );
	}

	/**
	 * Process Refund transaction
	 *
	 * @param int    $order_id Order identifier.
	 * @param null   $amount The amount to charge.
	 * @param string $reason The usage of transaction.
	 *
	 * @param string $transaction_id Transaction identifier.
	 * @param bool   $order_refund Order refund.
	 *
	 * @return bool|\WP_Error
	 */
	public static function do_refund(
		$order_id,
		$amount = null,
		$reason = '',
		$transaction_id = '',
		$order_refund = false
	) {
		try {
			$order = wc_emerchantpay_order_proxy()->get_order_by_id( $order_id );
			if ( ! $order || ! $order->get_transaction_id() ) {
				return false;
			}
			if ( $order->get_status() === self::ORDER_STATUS_PENDING ) {
				return WC_Emerchantpay_Helper::get_wp_error(
					static::get_translated_text(
						'You cannot refund a payment, because the order status is not yet updated from the payment gateway!'
					)
				);
			}

			if ( ! $transaction_id ) {
				$reference_transaction_id = wc_emerchantpay_order_proxy()->get_order_meta_data(
					$order,
					self::META_TRANSACTION_CAPTURE_ID
				);
			} else {
				$reference_transaction_id = $transaction_id;
			}
			if ( empty( $reference_transaction_id ) ) {
				$reference_transaction_id = $order->get_transaction_id();
			}
			if ( empty( $reference_transaction_id ) ) {
				return WC_Emerchantpay_Helper::get_wp_error(
					static::get_translated_text(
						'You cannot refund a payment, which has not been captured yet!'
					)
				);
			}

			if ( ! static::can_refund( $order, $reference_transaction_id ) ) {
				return WC_Emerchantpay_Helper::get_wp_error(
					static::get_translated_text(
						'You cannot refund this payment, because the payment is not captured yet or ' .
						'the gateway does not support refunds for this transaction type!'
					)
				);
			}

			$refundable_amount =
				$order->get_total() -
				WC_Emerchantpay_Transactions_Tree::get_total_refunded_amount( $order );

			if ( empty( $refundable_amount ) || $amount > $refundable_amount ) {
				if ( empty( $refundable_amount ) ) {
					return WC_Emerchantpay_Helper::get_wp_error(
						sprintf(
							static::get_translated_text(
								'You cannot refund \'%s\', because the whole amount has already been refunded in the payment gateway!'
							),
							WC_Emerchantpay_Order_Helper::format_money( $amount, $order )
						)
					);
				}

				return WC_Emerchantpay_Helper::get_wp_error(
					sprintf(
						static::get_translated_text(
							'You cannot refund \'%s\', because the available amount for refund in the payment gateway is \'%s\'!'
						),
						WC_Emerchantpay_Order_Helper::format_money( $amount, $order ),
						WC_Emerchantpay_Order_Helper::format_money( $refundable_amount, $order )
					)
				);
			}

			$payment_gateway = wc_emerchantpay_order_proxy()->get_payment_method_instance_by_order( $order );
			$payment_gateway->set_credentials();
			$payment_gateway->set_terminal_token( $order );

			$type    = self::get_refund_trx_type( $order );
			$genesis = new \Genesis\Genesis( $type );

			$genesis
				->request()
					->setTransactionId(
						static::generate_transaction_id( $order_id )
					)
					->setUsage(
						$reason
					)
					->setRemoteIp(
						WC_Emerchantpay_Helper::get_client_remote_ip_address()
					)
					->setReferenceId(
						$reference_transaction_id
					)
					->setCurrency(
						$order->get_currency()
					)
					->setAmount(
						$amount
					);

			if ( Types::getRefundTransactionClass( Types::INVOICE_CAPTURE ) === $type ) {
				$genesis->request()->setItems( WC_Emerchantpay_Order_Helper::get_invoice_custom_param_items( $order ) );
			}

			$genesis->execute();

			if ( ! $genesis->response()->isSuccessful() ) {
				throw new \Exception( $genesis->response()->getErrorDescription() );
			}

			$response = $genesis->response()->getResponseObject();

			$response->parent_id = $reference_transaction_id;

			wc_emerchantpay_order_proxy()->save_trx_list_to_order( $order, array( $response ) );

			switch ( $response->status ) {
				case States::APPROVED:
					if ( $order_refund && 0 === $refundable_amount - $response->amount ) {
						self::update_order_status_refunded( $order, $response );
					}

					$comment = static::get_translated_text( 'Refund completed!' );

					break;
				case States::PENDING_ASYNC:
					$comment = static::get_translated_text( 'Refund is pending Approval from the Gateway' );

					break;
				default:
					$message = WC_Emerchantpay_Genesis_Helper::fetch_gateway_response_message( $response );

					return WC_Emerchantpay_Helper::get_wp_error( $message ?: static::get_translated_text( 'Unknown Error' ) ); // phpcs:ignore Universal.Operators.DisallowShortTernary.Found
			}

			$order->add_order_note(
				$comment . PHP_EOL . PHP_EOL .
				static::get_translated_text( 'Id: ' ) . $response->unique_id . PHP_EOL .
				static::get_translated_text( 'Refunded amount:' ) . $response->amount . PHP_EOL
			);

			/**
			 * Cancel Subscription when Init Recurring
			 */
			if ( WC_Emerchantpay_Subscription_Helper::has_order_subscriptions( $order_id ) ) {
				$payment_gateway->cancel_order_subscriptions( $order, $reference_transaction_id );
			}

			if ( States::PENDING_ASYNC === $response->status ) {
				// Stop execution of the Refund.
				return WC_Emerchantpay_Helper::get_wp_error(
					static::get_translated_text( 'Refund is pending for Approval from the Gateway' )
				);
			}

			return $response;
		} catch ( \Exception $exception ) {
			WC_Emerchantpay_Helper::log_exception( $exception );

			return WC_Emerchantpay_Helper::get_wp_error( $exception );
		}
	}

	/**
	 * Cancels all Order Subscriptions
	 *
	 * @param WC_Order $order Order object.
	 * @param string $reference_transaction_id Reference payment transaction which determinate for initial or subsequent payment
	 * @return void
	 */
	protected function cancel_order_subscriptions( $order, $reference_transaction_id ) {
		$payment = WC_Emerchantpay_Transactions_Tree::get_trx_from_order( $order, $reference_transaction_id );

		// Map Order Trx list to gateway response
		$payment['transaction_type'] = $payment['type'];

		if ( ! WC_Emerchantpay_Subscription_Helper::is_init_recurring( (object) $payment ) ) {
			return;
		}

		WC_Emerchantpay_Subscription_Helper::update_order_subscriptions_status(
			$order,
			WC_Emerchantpay_Subscription_Helper::WC_SUBSCRIPTION_STATUS_CANCELED,
			sprintf(
				static::get_translated_text(
					'Subscription cancelled due to Refunded Order #%s'
				),
				$order->get_id()
			)
		);
	}

	/**
	 * Get refund transaction type
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @throws Exception If Capture trx is missing or of unknown type.
	 * @return string
	 */
	protected static function get_refund_trx_type( WC_Order $order ) {
		$settlement = WC_Emerchantpay_Transactions_Tree::create_from_order( $order )->get_settlement_trx();

		if ( empty( $settlement ) ) {
			throw new Exception( 'Missing Settlement Transaction' );
		}

		return Types::getRefundTransactionClass( $settlement->type );
	}

	/**
	 * Handles Recurring Sale Transactions.
	 *
	 * @param float    $amount The amount to charge.
	 * @param WC_Order $renewal_order A WC_Order object created to record the renewal payment.
	 *
	 * @access public
	 * @return void
	 * @throws Exception Throws WP_Error.
	 */
	public function process_scheduled_subscription_payment( $amount, $renewal_order ) {
		$this->set_credentials();

		$gateway_response = $this->process_subscription_payment( $renewal_order, $amount );

		$this->update_order_status( $renewal_order, $gateway_response );
	}

	/**
	 * Process Recurring Sale Transactions.
	 *
	 * @param WC_Order $order A WC_Order object created to record the renewal payment.
	 * @param float    $amount The amount to charge.
	 *
	 * @return \stdClass|\WP_Error
	 * @throws \Genesis\Exceptions\DeprecatedMethod Throws deprecates method exception.
	 * @throws \Genesis\Exceptions\InvalidArgument Throws invalid argument exception.
	 * @throws \Genesis\Exceptions\InvalidMethod Throws invalid method exception.
	 */
	protected function process_subscription_payment( $order, $amount ) {
		$reference_id = WC_Emerchantpay_Subscription_Helper::get_order_init_recurring_id_meta( $order );

		$order_subscription_transaction_type = WC_Emerchantpay_Subscription_Helper::get_order_init_recurring_transaction_type( $order );

		$subscription_class = WC_Emerchantpay_Subscription_Helper::get_order_subscription_transaction_class(
			$order_subscription_transaction_type
		);

		$this->init_recurring_token( $order );

		try {
			$genesis = WC_Emerchantpay_Genesis_Helper::get_gateway_request_by_txn_type(
				$subscription_class
			);

			$genesis
				->request()
					->setTransactionId(
						static::generate_transaction_id()
					)
					->setReferenceId(
						$reference_id
					)
					->setUsage(
						WC_Emerchantpay_Genesis_Helper::get_payment_transaction_usage( true )
					)
					->setRemoteIp(
						WC_Emerchantpay_Helper::get_client_remote_ip_address()
					)
					->setCurrency(
						$order->get_currency()
					)
					->setAmount(
						$amount
					);
			if ( Types::SALE === $subscription_class ) {
				$genesis->request()->setRecurringType( RecurringTypes::SUBSEQUENT );
			}
			$genesis->execute();

			if ( ! $genesis->response()->isSuccessful() ) {
				$message = static::get_translated_text( 'Renewal payment error:' );

				throw new \Exception( "$message {$genesis->response()->getErrorDescription()}" );
			}

			return $genesis->response()->getResponseObject();
		} catch ( Exception $recurring_exception ) {
			return WC_Emerchantpay_Helper::get_wp_error( $recurring_exception );
		}
	}

	/**
	 * Generate transaction id, unique to this instance
	 *
	 * @param string $input Added text.
	 *
	 * @return array|string
	 * @throws Exception Throws error of empty http_user_agent.
	 */
	public static function generate_transaction_id( $input = '' ) {
		// Try to gather more entropy.
		try {
			if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
				throw new Exception( 'HTTP_USER_AGENT is missing or empty.' );
			}
			$unique = sprintf(
				'|%s|%s|%s|%s|',
				WC_Emerchantpay_Helper::get_client_remote_ip_address(),
				microtime( true ),
				sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ),
				$input
			);
			return strtolower( self::PLATFORM_TRANSACTION_PREFIX . substr( sha1( $unique . md5( uniqid( wp_rand(), true ) ) ), 0, 30 ) );
		} catch ( Exception $e ) {
			echo 'Error: ' . esc_html( $e->getMessage() );
		}
	}

	/**
	 * Get the Order items in the following format:
	 *
	 * "%name% x %quantity%"
	 * "Subscription Price" if isSubscriptionProduct
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return string
	 */
	protected function get_item_description( WC_Order $order ) {
		$items = array();

		/**
		 * Extract order items description
		 *
		 * @var WC_Order_Item_Product $item Order items.
		 */
		foreach ( $order->get_items() as $item ) {
			$product_description = sprintf(
				'%s x %d',
				WC_Emerchantpay_Order_Helper::get_item_name( $item ),
				WC_Emerchantpay_Order_Helper::get_item_quantity( $item )
			);

			$subscription_description = '';
			if ( WC_Emerchantpay_Subscription_Helper::is_subscription_product( $item->get_product() ) ) {
				$subscription_description = WC_Emerchantpay_Subscription_Helper::filter_wc_subscription_price(
					static::get_cart(),
					$item->get_product(),
					WC_Emerchantpay_Order_Helper::get_item_quantity( $item )
				);
			}

			$items[] = $product_description . PHP_EOL . $subscription_description;
		}

		return implode( PHP_EOL, $items );
	}

	/**
	 * Append parameters to a base URL
	 *
	 * @param string $base Base URL.
	 * @param array  $args Args array.
	 *
	 * @return string
	 */
	protected function append_to_url( $base, $args ) {
		if ( ! is_array( $args ) ) {
			return $base;
		}

		$info = wp_parse_url( $base );

		$query = array();

		if ( isset( $info['query'] ) ) {
			parse_str( $info['query'], $query );
		}

		if ( ! is_array( $query ) ) {
			$query = array();
		}

		$params = array_merge( $query, $args );

		$result = '';

		if ( $info['scheme'] ) {
			$result .= $info['scheme'] . ':';
		}

		if ( $info['host'] ) {
			$result .= '//' . $info['host'];
		}

		if ( array_key_exists( 'port', $info ) && ! empty( $info['host'] ) ) {
			$result .= ':' . $info['port'];
		}

		if ( $info['path'] ) {
			$result .= $info['path'];
		}

		if ( $params ) {
			$result .= '?' . http_build_query( $params );
		}

		return $result;
	}

	/**
	 * Get a one-time token
	 *
	 * @param WC_Order $order Order identifier.
	 *
	 * @return mixed|string
	 */
	protected function get_one_time_token( $order ) {
		return wc_emerchantpay_order_proxy()->get_order_meta_data(
			$order,
			self::META_CHECKOUT_RETURN_TOKEN
		);
	}

	/**
	 * Set one-time token
	 *
	 * @param WC_Order $order Order identifier.
	 * @param mixed    $value The value of the post meta value.
	 */
	protected function set_one_time_token( $order, $value ) {
		wc_emerchantpay_order_proxy()->set_order_meta_data( $order, self::META_CHECKOUT_RETURN_TOKEN, $value );
	}

	/**
	 * Set the Genesis PHP Lib Credentials, based on the customer's admin settings
	 *
	 * @return void
	 * @throws \Genesis\Exceptions\InvalidArgument Invalid argument.
	 */
	public function set_credentials() {
		$env = $this->get_method_bool_setting( self::SETTING_KEY_TEST_MODE ) ? Environments::STAGING : Environments::PRODUCTION;

		Config::setEndpoint( Endpoints::EMERCHANTPAY );
		Config::setUsername( $this->get_method_setting( self::SETTING_KEY_USERNAME ) );
		Config::setPassword( $this->get_method_setting( self::SETTING_KEY_PASSWORD ) );
		Config::setEnvironment( $env );
	}

	/**
	 * Return if iframe processing is enabled
	 *
	 * @return bool
	 */
	protected function is_iframe_enabled() {
		return false;
	}

	/**
	 * Determines a method bool setting value
	 *
	 * @param string $setting_name The name of settings.
	 * @return bool
	 */
	protected function get_method_bool_setting( $setting_name ) {
		return $this->get_method_setting( $setting_name ) === self::SETTING_VALUE_YES;
	}

	/**
	 * Retrieves a bool Method Setting Value directly from the Post Request
	 * Used for showing warning notices
	 *
	 * @param string $setting_name The name of settings.
	 * @return bool
	 */
	protected function get_post_bool_setting_value( $setting_name ) {
		$complete_post_param_name = $this->get_method_admin_setting_post_param_name( $setting_name );
		// TODO Check and fix nonce verification error.
		// phpcs:disable
		return isset( $_POST[ $complete_post_param_name ] ) && ( '1' === $_POST[ $complete_post_param_name ] );
		// phpcs:enable
	}

	/**
	 * Get method setting
	 *
	 * @param string $setting_name The name of settings.
	 * @return string|array
	 */
	protected function get_method_setting( $setting_name ) {
		return $this->get_option( $setting_name );
	}

	/**
	 * Checks method for setting
	 *
	 * @param string $setting_name The name of settings.
	 * @return bool
	 */
	protected function get_method_has_setting( $setting_name ) {
		return ! empty( $this->get_method_setting( $setting_name ) );
	}

	/**
	 * Check that subscription is enabled
	 *
	 * @return bool
	 */
	protected function is_subscription_enabled() {
		return $this->get_method_bool_setting( self::SETTING_KEY_ALLOW_SUBSCRIPTIONS );
	}

	/**
	 * Determines the Recurring Token, which needs to used for the RecurringSale Transactions
	 *
	 * @param WC_Order $order Order object.
	 * @return string
	 */
	protected function get_recurring_token( $order ) {
		return $this->get_method_setting( self::SETTING_KEY_RECURRING_TOKEN );
	}

	/**
	 * Get the code of the current used payment method
	 * Checkout / Direct
	 *
	 * @return string|null
	 */
	public static function get_method_code() {
		return static::$method_code;
	}

	/**
	 * Get the Business Attributes form fields
	 *
	 * @return array
	 */
	protected function build_business_attributes_form_fields() {
		$custom_attributes = $this->get_products_custom_attributes();

		return array(
			'business_attributes'                          => array(
				'type'        => 'title',
				'title'       => static::get_translated_text( 'Business Attributes' ),
				'description' =>
					sprintf(
						static::get_translated_text(
							'Choose and map your Product Custom attribute to a specific Business Attribute. ' .
							'The mapped Product attribute value will be attached to the Genesis Transaction Request. ' .
							'For more information %sget in touch%s with our support.'
						),
						'<a href="mailto:tech-support@emerchantpay.com">',
						'</a>'
					),
			),
			self::SETTING_KEY_BUSINESS_ATTRIBUTES_ENABLED  => array(
				'type'        => 'checkbox',
				'title'       => static::get_translated_text( 'Enabled?' ),
				'label'       => static::get_translated_text( 'Enable/Disable the Business Attributes mappings' ),
				'description' => static::get_translated_text(
					'Selecting this will enable the usage of the Business attributes in the Genesis Request.'
				),
				'desc_tip'    => true,
				'default'     => self::SETTING_VALUE_NO,
			),
			'business_flight_attributes'                   => array(
				'type'  => 'title',
				'title' => static::get_translated_text( 'Airlines Air Carriers' ),
			),
			self::SETTING_KEY_BUSINESS_FLIGHT_ARRIVAL_DATE => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Flight Arrival Date' ),
				'options'     => $custom_attributes,
				'description' => sprintf(
					static::get_translated_text( 'The date when the flight departs in format %s' ),
					implode(
						static::get_translated_text( ' or ' ),
						DateTimeFormat::getDateFormats()
					)
				),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			self::SETTING_KEY_BUSINESS_FLIGHT_DEPARTURE_DATE => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Flight Departure Date' ),
				'options'     => $custom_attributes,
				'description' => sprintf(
					static::get_translated_text( 'The date when the flight arrives in format %s' ),
					implode(
						static::get_translated_text( ' or ' ),
						DateTimeFormat::getDateFormats()
					)
				),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			self::SETTING_KEY_BUSINESS_AIRLINE_CODE        => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Airline Code' ),
				'options'     => $custom_attributes,
				'description' => static::get_translated_text( 'The code of Airline' ),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			self::SETTING_KEY_BUSINESS_AIRLINE_FLIGHT_NUMBER => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'AIRLINE Flight Number' ),
				'options'     => $custom_attributes,
				'description' => static::get_translated_text( 'The flight number' ),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			self::SETTING_KEY_BUSINESS_FLIGHT_TICKET_NUMBER => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Airline Ticket Number' ),
				'options'     => $custom_attributes,
				'description' => static::get_translated_text( 'The number of the flight ticket' ),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			self::SETTING_KEY_BUSINESS_FLIGHT_ORIGIN_CITY  => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Airline Origin City' ),
				'options'     => $custom_attributes,
				'description' => static::get_translated_text( 'The origin city of the flight' ),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			self::SETTING_KEY_BUSINESS_FLIGHT_DESTINATION_CITY => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Airline Destination City' ),
				'options'     => $custom_attributes,
				'description' => static::get_translated_text( 'The destination city of the flight' ),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			self::SETTING_KEY_BUSINESS_AIRLINE_TOUR_OPERATOR_NAME => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Airline Tour Operator Name' ),
				'options'     => $custom_attributes,
				'description' => static::get_translated_text( 'The name of tour operator' ),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			'business_event_attributes'                    => array(
				'type'  => 'title',
				'title' => static::get_translated_text( 'Event Management' ),
			),
			self::SETTING_KEY_BUSINESS_EVENT_START_DATE    => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Event Start Date' ),
				'options'     => $custom_attributes,
				'description' => sprintf(
					static::get_translated_text( 'The date when event starts in format %s' ),
					implode(
						static::get_translated_text( ' or ' ),
						DateTimeFormat::getDateFormats()
					)
				),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			self::SETTING_KEY_BUSINESS_EVENT_END_DATE      => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Event End Date' ),
				'options'     => $custom_attributes,
				'description' => sprintf(
					static::get_translated_text( 'The date when event ends in format %s' ),
					implode(
						static::get_translated_text( ' or ' ),
						DateTimeFormat::getDateFormats()
					)
				),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			self::SETTING_KEY_BUSINESS_EVENT_ORGANIZER_ID  => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Event Organizer Id' ),
				'options'     => $custom_attributes,
				'description' => static::get_translated_text( 'Event Organizer Id' ),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			self::SETTING_KEY_BUSINESS_EVENT_ID            => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Event Id' ),
				'options'     => $custom_attributes,
				'description' => static::get_translated_text( 'Event Id' ),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			'business_furniture_attributes'                => array(
				'type'  => 'title',
				'title' => static::get_translated_text( 'Furniture' ),
			),
			self::SETTING_KEY_BUSINESS_DATE_OF_ORDER       => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Date of Order' ),
				'options'     => $custom_attributes,
				'description' => sprintf(
					static::get_translated_text( 'The date when order was placed in format %s' ),
					implode(
						static::get_translated_text( ' or ' ),
						DateTimeFormat::getDateFormats()
					)
				),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			self::SETTING_KEY_BUSINESS_DELIVERY_DATE       => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Delivery Date' ),
				'options'     => $custom_attributes,
				'description' => sprintf(
					static::get_translated_text( 'Date of the expected delivery in format %s' ),
					implode(
						static::get_translated_text( ' or ' ),
						DateTimeFormat::getDateFormats()
					)
				),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			self::SETTING_KEY_BUSINESS_NAME_OF_THE_SUPPLIER => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Name Of Supplier' ),
				'options'     => $custom_attributes,
				'description' => static::get_translated_text( 'Name of supplier' ),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			'business_hotel_and_estates_rentals_attributes' => array(
				'type'  => 'title',
				'title' => static::get_translated_text( 'Hotels and Real estate rentals' ),
			),
			self::SETTING_KEY_BUSINESS_CHECK_IN_DATE       => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Check-In Date' ),
				'options'     => $custom_attributes,
				'description' => sprintf(
					static::get_translated_text( 'The data when the customer check-in in format %s' ),
					implode(
						static::get_translated_text( ' or ' ),
						DateTimeFormat::getDateFormats()
					)
				),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			self::SETTING_KEY_BUSINESS_CHECK_OUT_DATE      => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Check-Out Date' ),
				'options'     => $custom_attributes,
				'description' => sprintf(
					static::get_translated_text( 'The data when the customer check-out in format %s' ),
					implode(
						static::get_translated_text( ' or ' ),
						DateTimeFormat::getDateFormats()
					)
				),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			self::SETTING_KEY_BUSINESS_TRAVEL_AGENCY_NAME  => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Travel Agency Name' ),
				'options'     => $custom_attributes,
				'description' => static::get_translated_text( 'Travel Agency Name' ),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			'business_car_boat_plane_rentals_attributes'   => array(
				'type'  => 'title',
				'title' => static::get_translated_text( 'Car, Plane and Boat Rentals' ),
			),
			self::SETTING_KEY_BUSINESS_VEHICLE_PICK_UP_DATE => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Pick-Up Date' ),
				'options'     => $custom_attributes,
				'description' => sprintf(
					static::get_translated_text( 'The date when customer takes the vehicle in format %s' ),
					implode(
						static::get_translated_text( ' or ' ),
						DateTimeFormat::getDateFormats()
					)
				),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			self::SETTING_KEY_BUSINESS_VEHICLE_RETURN_DATE => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Vehicle Return Date' ),
				'options'     => $custom_attributes,
				'description' => sprintf(
					static::get_translated_text(
						'The date when the customer returns the vehicle back in format %s'
					),
					implode(
						static::get_translated_text( ' or ' ),
						DateTimeFormat::getDateFormats()
					)
				),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			self::SETTING_KEY_BUSINESS_SUPPLIER_NAME       => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Supplier Name' ),
				'options'     => $custom_attributes,
				'description' => static::get_translated_text( 'Supplier Name' ),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			'business_cruise_attributes'                   => array(
				'type'  => 'title',
				'title' => static::get_translated_text( 'Cruise Lines' ),
			),
			self::SETTING_KEY_BUSINESS_CRUISE_START_DATE   => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Start Date' ),
				'options'     => $custom_attributes,
				'description' => sprintf(
					static::get_translated_text( 'The date when cruise begins in format %s' ),
					implode(
						static::get_translated_text( ' or ' ),
						DateTimeFormat::getDateFormats()
					)
				),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			self::SETTING_KEY_BUSINESS_CRUISE_END_DATE     => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'End Date' ),
				'options'     => $custom_attributes,
				'description' => sprintf(
					static::get_translated_text( 'The date when cruise ends in format %s' ),
					implode(
						static::get_translated_text( ' or ' ),
						DateTimeFormat::getDateFormats()
					)
				),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			'business_travel_attributes'                   => array(
				'type'  => 'title',
				'title' => static::get_translated_text( 'Travel Agencies' ),
			),
			self::SETTING_KEY_BUSINESS_ARRIVAL_DATE        => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Arrival Date' ),
				'options'     => $custom_attributes,
				'description' => sprintf(
					static::get_translated_text( 'The date of arrival in format %s' ),
					implode(
						static::get_translated_text( ' or ' ),
						DateTimeFormat::getDateFormats()
					)
				),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			self::SETTING_KEY_BUSINESS_DEPARTURE_DATE      => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Departure Date' ),
				'options'     => $custom_attributes,
				'description' => sprintf(
					static::get_translated_text( 'The date of departure in format %s' ),
					implode(
						static::get_translated_text( ' or ' ),
						DateTimeFormat::getDateFormats()
					)
				),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			self::SETTING_KEY_BUSINESS_CARRIER_CODE        => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Carrier Code' ),
				'options'     => $custom_attributes,
				'description' => static::get_translated_text( 'The code of the carrier' ),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			self::SETTING_KEY_BUSINESS_FLIGHT_NUMBER       => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Flight Number' ),
				'options'     => $custom_attributes,
				'description' => static::get_translated_text( 'The number of the flight' ),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			self::SETTING_KEY_BUSINESS_TICKET_NUMBER       => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Ticket Number' ),
				'options'     => $custom_attributes,
				'description' => static::get_translated_text( 'The number of the ticket' ),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			self::SETTING_KEY_BUSINESS_ORIGIN_CITY         => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Origin City' ),
				'options'     => $custom_attributes,
				'description' => static::get_translated_text( 'The origin city' ),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			self::SETTING_KEY_BUSINESS_DESTINATION_CITY    => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Destination City' ),
				'options'     => $custom_attributes,
				'description' => static::get_translated_text( 'The destination city' ),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			self::SETTING_KEY_BUSINESS_TRAVEL_AGENCY       => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Travel Agency' ),
				'options'     => $custom_attributes,
				'description' => static::get_translated_text( 'The name of the travel agency' ),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			self::SETTING_KEY_BUSINESS_CONTRACTOR_NAME     => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Contractor Name' ),
				'options'     => $custom_attributes,
				'description' => static::get_translated_text( 'The name of the contractor' ),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			self::SETTING_KEY_BUSINESS_ATOL_CERTIFICATE    => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'ATOL Certificate' ),
				'options'     => $custom_attributes,
				'description' => static::get_translated_text( 'ATOL certificate number' ),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			self::SETTING_KEY_BUSINESS_PICK_UP_DATE        => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Pick-up Date' ),
				'options'     => $custom_attributes,
				'description' => sprintf(
					static::get_translated_text( 'Pick-up date in format %s' ),
					implode(
						static::get_translated_text( ' or ' ),
						DateTimeFormat::getDateFormats()
					)
				),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
			self::SETTING_KEY_BUSINESS_RETURN_DATE         => array(
				'type'        => 'select',
				'css'         => 'height:auto',
				'title'       => static::get_translated_text( 'Return Date' ),
				'options'     => $custom_attributes,
				'description' => sprintf(
					static::get_translated_text( 'Return date in format %s' ),
					implode(
						static::get_translated_text( ' or ' ),
						DateTimeFormat::getDateFormats()
					)
				),
				'desc_tip'    => true,
				'default'     => 'no_mapping_attribute',
			),
		);
	}

	/**
	 * Get list with all Product Custom Attributes
	 *
	 * @return array
	 */
	protected function get_products_custom_attributes() {
		$data     = array(
			'no_mapping_attribute' => '--',
		);
		$products = wc_get_products( array() );

		if ( ! $products ) {
			return $data;
		}

		/**
		 * Collect products custom attributes
		 *
		 * @var WC_Product $product Product object.
		 */
		foreach ( $products as $product ) {
			$attributes = $product->get_attributes();

			/**
			 * Collects attributes names
			 *
			 * @var WC_Product_Attribute $attribute Attribute object.
			 */
			foreach ( $attributes as $key => $attribute ) {
				$data[ $key ] = $attribute->get_name();
			}
		}

		return $data;
	}

	/**
	 * Get Business Attributes Settings Keys
	 *
	 * @return array
	 */
	protected function get_business_attributes_setting_keys() {
		return array(
			self::SETTING_KEY_BUSINESS_FLIGHT_ARRIVAL_DATE,
			self::SETTING_KEY_BUSINESS_FLIGHT_DEPARTURE_DATE,
			self::SETTING_KEY_BUSINESS_AIRLINE_CODE,
			self::SETTING_KEY_BUSINESS_AIRLINE_FLIGHT_NUMBER,
			self::SETTING_KEY_BUSINESS_FLIGHT_TICKET_NUMBER,
			self::SETTING_KEY_BUSINESS_FLIGHT_ORIGIN_CITY,
			self::SETTING_KEY_BUSINESS_FLIGHT_DESTINATION_CITY,
			self::SETTING_KEY_BUSINESS_AIRLINE_TOUR_OPERATOR_NAME,
			self::SETTING_KEY_BUSINESS_EVENT_START_DATE,
			self::SETTING_KEY_BUSINESS_EVENT_END_DATE,
			self::SETTING_KEY_BUSINESS_EVENT_ORGANIZER_ID,
			self::SETTING_KEY_BUSINESS_EVENT_ID,
			self::SETTING_KEY_BUSINESS_DATE_OF_ORDER,
			self::SETTING_KEY_BUSINESS_DELIVERY_DATE,
			self::SETTING_KEY_BUSINESS_NAME_OF_THE_SUPPLIER,
			self::SETTING_KEY_BUSINESS_CHECK_IN_DATE,
			self::SETTING_KEY_BUSINESS_CHECK_OUT_DATE,
			self::SETTING_KEY_BUSINESS_TRAVEL_AGENCY_NAME,
			self::SETTING_KEY_BUSINESS_VEHICLE_PICK_UP_DATE,
			self::SETTING_KEY_BUSINESS_VEHICLE_RETURN_DATE,
			self::SETTING_KEY_BUSINESS_SUPPLIER_NAME,
			self::SETTING_KEY_BUSINESS_CRUISE_START_DATE,
			self::SETTING_KEY_BUSINESS_CRUISE_END_DATE,
			self::SETTING_KEY_BUSINESS_ARRIVAL_DATE,
			self::SETTING_KEY_BUSINESS_DEPARTURE_DATE,
			self::SETTING_KEY_BUSINESS_CARRIER_CODE,
			self::SETTING_KEY_BUSINESS_FLIGHT_NUMBER,
			self::SETTING_KEY_BUSINESS_TICKET_NUMBER,
			self::SETTING_KEY_BUSINESS_ORIGIN_CITY,
			self::SETTING_KEY_BUSINESS_DESTINATION_CITY,
			self::SETTING_KEY_BUSINESS_TRAVEL_AGENCY,
			self::SETTING_KEY_BUSINESS_CONTRACTOR_NAME,
			self::SETTING_KEY_BUSINESS_ATOL_CERTIFICATE,
			self::SETTING_KEY_BUSINESS_PICK_UP_DATE,
			self::SETTING_KEY_BUSINESS_RETURN_DATE,
		);
	}

	/**
	 * Get the configured Business Attributes mappings
	 *
	 * @return array
	 */
	protected function get_business_attributes_mapping() {
		$data = array();

		foreach ( $this->get_business_attributes_setting_keys() as $key ) {
			$custom_attribute = $this->get_method_setting( $key );

			if ( 'no_mapping_attribute' !== $custom_attribute ) {
				$data[ $key ] = $this->get_method_setting( $key );
			}
		}

		return $data;
	}

	/**
	 *  Add business attrinutes to gateway request.
	 *
	 * @param Genesis  $genesis Genesis object.
	 * @param WC_Order $order Order object.
	 *
	 * @return mixed
	 */
	protected function add_business_data_to_gateway_request( $genesis, $order ) {
		$business_attributes_enabled = filter_var(
			$this->get_method_setting( self::SETTING_KEY_BUSINESS_ATTRIBUTES_ENABLED ),
			FILTER_VALIDATE_BOOLEAN
		);

		if ( ! $business_attributes_enabled ) {
			return $genesis;
		}

		$mappings = $this->get_business_attributes_mapping();

		/**
		 * Order product items
		 *
		 * @var WC_Order_Item_Product $item
		 */
		foreach ( $order->get_items() as $item ) {
			/**
			 * Order products
			 *
			 * @var WC_Product $product
			 */
			$product = $item->get_product();

			foreach ( $mappings as $genesis_attribute => $product_custom_attribute ) {
				/**
				 * Product attributes object.
				 *
				 * @var WC_Product_Attribute $attribute Product attributes object.
				 */
				$attribute = $product->get_attribute( $product_custom_attribute );

				if ( $attribute ) {
					$genesis->request()
							->{'set' . \Genesis\Utils\Common::snakeCaseToCamelCase( $genesis_attribute )}( $attribute );
				}
			}
		}

		return $genesis;
	}

	/**
	 * Get the WooCommerce Cart Instance
	 *
	 * @return WC_Cart|null
	 */
	protected static function get_cart() {
		$cart = WC()->cart;

		if ( empty( $cart ) ) {
			return null;
		}

		return $cart;
	}

	/**
	 * Return list of available return pages
	 *
	 * @return array
	 */
	protected function get_available_redirect_pages() {
		return array(
			self::SETTING_VALUE_ORDER    => 'Order page (default)',
			self::SETTING_VALUE_CHECKOUT => 'Checkout page',
		);
	}

	/**
	 * Control the 3DSv2 optional parameters handling
	 *
	 * @return bool
	 */
	protected function is_3dsv2_enabled() {
		return $this->get_option( self::SETTING_KEY_THREEDS_ALLOWED ) === self::SETTING_VALUE_YES;
	}

	/**
	 * 3DSv2 Attributes form
	 *
	 * @return array
	 */
	public function build_3dsv2_attributes_form_fields() {

		return array(
			'threeds_attributes'                          => array(
				'type'  => 'title',
				'title' => static::get_translated_text( '3DSv2 options' ),
			),
			self::SETTING_KEY_THREEDS_ALLOWED             => array(
				'type'        => 'checkbox',
				'title'       => static::get_translated_text( 'Enable/Disable' ),
				'label'       => static::get_translated_text( 'Enable 3DSv2' ),
				'default'     => self::SETTING_VALUE_YES,
				'description' => static::get_translated_text( 'Enable handling of 3DSv2 optional parameters' ),
			),
			self::SETTING_KEY_THREEDS_CHALLENGE_INDICATOR => array(
				'type'        => 'select',
				'title'       => static::get_translated_text( '3DSv2 Challenge option' ),
				'options'     => $this->get_allowed_challenge_indicators(),
				'label'       => static::get_translated_text( 'Enable challenge indicator' ),
				'description' => static::get_translated_text(
					'The value has weight and might impact the decision whether ' .
					'a challenge will be required for the transaction or not. ' .
					'If not provided, it will be interpreted as no_preference.'
				),
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Helper array used for Challenge Indicator available options
	 *
	 * @return array
	 */
	protected function get_allowed_challenge_indicators() {
		return array(
			ChallengeIndicators::NO_PREFERENCE          => 'No preference',
			ChallengeIndicators::NO_CHALLENGE_REQUESTED => 'No challenge requested',
			ChallengeIndicators::PREFERENCE             => 'Preference',
			ChallengeIndicators::MANDATE                => 'Mandate',
		);
	}

	/**
	 *  Build sca exemption options
	 *
	 * @return array
	 */
	protected function build_sca_exemption_options_form_fields() {
		return array(
			'sca_exemption_attributes'             => array(
				'type'  => 'title',
				'title' => static::get_translated_text( 'SCA Exemption options' ),
			),
			self::SETTING_KEY_SCA_EXEMPTION        => array(
				'type'        => 'select',
				'title'       => static::get_translated_text( 'SCA Exemption option' ),
				'options'     => $this->get_sca_exemption_values(),
				'label'       => static::get_translated_text( 'SCA Exemption' ),
				'description' => static::get_translated_text( 'Exemption for the Strong Customer Authentication.' ),
				'default'     => ScaExemptions::EXEMPTION_LOW_VALUE,
				'desc_tip'    => true,
			),
			self::SETTING_KEY_SCA_EXEMPTION_AMOUNT => array(
				'type'        => 'text',
				'title'       => static::get_translated_text( 'SCA Exemption amount option' ),
				'label'       => static::get_translated_text( 'SCA Exemption Amount' ),
				'description' => static::get_translated_text( 'Exemption Amount determinate if the SCA Exemption should be included in the request to the Gateway.' ),
				'default'     => 100,
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Get sca exemption values
	 *
	 * @return array
	 */
	protected function get_sca_exemption_values() {
		return array(
			ScaExemptions::EXEMPTION_LOW_RISK  => static::get_translated_text( 'Low risk' ),
			ScaExemptions::EXEMPTION_LOW_VALUE => static::get_translated_text( 'Low value' ),
		);
	}

	/**
	 * Add SCA Exemption parameter to Genesis Request
	 *
	 * @param Genesis $genesis Genesis request object.
	 */
	protected function add_sca_exemption_parameters( $genesis ) {
		$wpf_amount          = (float) $genesis->request()->getAmount();
		$sca_exemption       = $this->get_option( self::SETTING_KEY_SCA_EXEMPTION );
		$sca_exemption_value = (float) $this->get_option( self::SETTING_KEY_SCA_EXEMPTION_AMOUNT );

		if ( $wpf_amount <= $sca_exemption_value ) {
			$genesis->request()->setScaExemption( $sca_exemption );
		}
	}

	/**
	 * Add 3DSv2 parameters to gateway request
	 *
	 * @param Genesis  $genesis Genesis request object.
	 * @param WC_Order $order Order object.
	 * @param bool     $is_recurring Check that is recurring object.
	 *
	 * @throws \Genesis\Exceptions\InvalidArgument Invalid argument exception.
	 */
	protected function add_3dsv2_parameters_to_gateway_request( $genesis, $order, $is_recurring ) {
		/**
		 * WPF request object
		 *
		 * @var \Genesis\Api\Request\Wpf\Create $wpf_request WPF create request.
		 */
		$wpf_request = $genesis->request();

		/**
		 * Customer instance
		 *
		 * @var WC_Customer $customer Customer object
		 */
		$customer = new WC_Customer( $order->get_customer_id() );

		$threeds    = new WC_Emerchantpay_Threeds_Helper( $order, $customer, self::DATE_FORMAT );
		$indicators = new WC_Emerchantpay_Indicators_Helper( $customer, self::DATE_FORMAT );

		$wpf_request
			// Challenge Indicator.
			->setThreedsV2ControlChallengeIndicator(
				empty( $this->get_option( self::SETTING_KEY_THREEDS_CHALLENGE_INDICATOR ) )
					? ChallengeIndicators::NO_PREFERENCE
					: $this->get_option( self::SETTING_KEY_THREEDS_CHALLENGE_INDICATOR )
			)

			// Purchase category.
			->setThreedsV2PurchaseCategory(
				$threeds->has_physical_product() ?
					Categories::GOODS :
					Categories::SERVICE
			)

			// Merchant_risk.
			->setThreedsV2MerchantRiskShippingIndicator( $threeds->fetch_shipping_indicator() )
			->setThreedsV2MerchantRiskDeliveryTimeframe(
				$threeds->has_physical_product() ?
					DeliveryTimeframes::ANOTHER_DAY :
					DeliveryTimeframes::ELECTRONICS
			)
			->setThreedsV2MerchantRiskReorderItemsIndicator( $threeds->fetch_reorder_items_indicator() );

		if ( ! $threeds->is_guest_customer() ) {
			$shipping_address_first_date_used = $threeds->get_shipping_address_date_first_used();

			// CardHolder Account.
			$wpf_request
				->setThreedsV2CardHolderAccountCreationDate( $indicators->get_customer_created_date() )
				// WC_Customer contain all the user data (Shipping, Billing and Password)
				// Update Indicator and Password Change Indicator will be the same.
				->setThreedsV2CardHolderAccountUpdateIndicator( $indicators->fetch_account_update_indicator() )
				->setThreedsV2CardHolderAccountLastChangeDate( $indicators->get_customer_modified_date() )
				->setThreedsV2CardHolderAccountPasswordChangeIndicator( $indicators->fetch_password_change_indicator() )
				->setThreedsV2CardHolderAccountPasswordChangeDate( $indicators->get_customer_modified_date() )
				->setThreedsV2CardHolderAccountShippingAddressUsageIndicator(
					$indicators->fetch_shipping_address_usage_indicator( $shipping_address_first_date_used )
				)
				->setThreedsV2CardHolderAccountShippingAddressDateFirstUsed( $shipping_address_first_date_used )

				->setThreedsV2CardHolderAccountTransactionsActivityLast24Hours(
					$threeds->get_transactions_last_24_hours()
				)
				->setThreedsV2CardHolderAccountTransactionsActivityPreviousYear(
					$threeds->get_transactions_previous_year()
				)
				->setThreedsV2CardHolderAccountPurchasesCountLast6Months(
					$threeds->get_paid_transactions_for_6_months()
				)
				->setThreedsV2CardHolderAccountRegistrationDate( $threeds->get_first_order_date() );
		}

		$wpf_request->setThreedsV2CardHolderAccountRegistrationIndicator(
			$threeds->is_guest_customer() ?
				RegistrationIndicators::GUEST_CHECKOUT :
				$indicators->fetch_registration_indicator( $threeds->get_first_order_date() )
		);

		if ( $is_recurring ) {
			$recurring_parameters = WC_Emerchantpay_Subscription_Helper::get_3dsv2_recurring_parameters( $order->get_id() );

			$wpf_request->setThreedsV2RecurringExpirationDate( $recurring_parameters['expiration_date'] );
			if ( $recurring_parameters['frequency'] ) {
				$wpf_request->setThreedsV2RecurringFrequency( $recurring_parameters['frequency'] );
			}
		}
	}

	/**
	 * Return frame handler class url
	 *
	 * @return string
	 */
	protected function get_frame_handler() {
		return WC()->api_request_url( WC_Emerchantpay_Frame_Handler::class );
	}

	/**
	 * Build iframe processing url according to the settings
	 *
	 * @param string $url Current added URL.
	 *
	 * @return string
	 */
	protected function build_iframe_url( $url ) {
		$iframe_processing_enabled = $this->is_iframe_enabled();
		$frame_handler             = $this->get_frame_handler();
		$iframe_url                = $frame_handler . '?' . rawurlencode( $url );

		return $iframe_processing_enabled ? $iframe_url : $url;
	}

	/**
	 * Set terminal token or use Smart Router
	 *
	 * @param ArrayObject $notification_object Notification object.
	 *
	 * @return bool
	 */
	protected function set_notification_terminal_token( $notification_object ) {
		return true;
	}

	/**
	 * Sets terminal token for init_recurring
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return void
	 */
	protected function init_recurring_token( $order ) {
		Config::setToken(
			$this->get_recurring_token( $order )
		);
	}

	/**
	 * Create redirect response with or without iFrame
	 *
	 * @param string $redirect_response Redirect response.
	 * @param bool   $is_blocks_iframe Check that iframe blocks is enabled.
	 *
	 * @return array
	 */
	protected function create_response( $redirect_response, $is_blocks_iframe = false ) {
		$response = array(
			'result'   => static::RESPONSE_SUCCESS,
			'redirect' => $redirect_response,
		);

		if ( $is_blocks_iframe ) {
			$response['redirect']        = '';
			$response['blocks_redirect'] = $redirect_response;
		}

		return $response;
	}

	/**
	 * Check if should redirect to an iFrame
	 *
	 * @return bool
	 */
	protected function is_iframe_blocks() {
		// TODO Check and fix nonce verification error.
		// phpcs:ignore WordPress.Security.NonceVerification
		$blocks_order = sanitize_text_field( wp_unslash( $_POST[ "{$this->id}_blocks_order" ] ?? null ) );

		return 'yes' === $this->get_method_setting( self::SETTING_KEY_IFRAME_PROCESSING ) && $blocks_order;
	}

	/**
	 * Retrieve common Recurring Transaction Types with translations
	 *
	 * @return array|string[]
	 */
	protected function get_translated_recurring_trx_types( $transaction_types ) {
		return array_combine(
			$transaction_types,
			array_map(
				function ( $type ) {
					return static::get_translated_text( Names::getName( $type ) );
				},
				$transaction_types
			)
		);
	}
}
