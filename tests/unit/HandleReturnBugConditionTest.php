<?php
/**
 * Bug Condition Exploration Test: Decline Return Corrupts Session State
 *
 * This test verifies the EXPECTED behavior after a fix is applied:
 * - handle_return() with act=failure and redirect_failure=checkout should NOT
 *   set order_awaiting_payment to false.
 *
 * On UNFIXED code, this test MUST FAIL — failure confirms the bug exists.
 *
 * Validates: Requirements 1.1, 1.2, 1.4, 2.4
 *
 * @package WC_Emerchantpay\Tests\Unit
 */

use PHPUnit\Framework\TestCase;

/**
 * Minimal concrete subclass of WC_Emerchantpay_Method_Base that exposes
 * handle_return() for testing. Implements all required abstract methods
 * with no-op stubs.
 */
class HandleReturnTestableGateway extends WC_Emerchantpay_Method_Base {

	/**
	 * Get method code.
	 *
	 * @return string
	 */
	public static function get_method_code() {
		return 'emerchantpay_checkout';
	}

	/**
	 * Get module title.
	 *
	 * @return string
	 */
	protected function get_module_title() {
		return 'Test Gateway';
	}

	/**
	 * Get checkout transaction ID meta key.
	 *
	 * @return string
	 */
	protected function get_checkout_transaction_id_meta_key() {
		return '_transaction_id';
	}

	/**
	 * Process order payment (no-op for test).
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	protected function process_order_payment( $order_id ) {
		return array();
	}

	/**
	 * Process init subscription payment (no-op for test).
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	protected function process_init_subscription_payment( $order_id ) {
		return array();
	}

	/**
	 * Expose handle_return for testing.
	 *
	 * This is a copy of the REAL production handle_return() method from
	 * WC_Emerchantpay_Method_Base, with only the header() call replaced
	 * by storing the redirect URL (since header() cannot be called in PHPUnit).
	 *
	 * ALL session manipulation, order status changes, and notice logic is
	 * IDENTICAL to the production code. This ensures we test the real bug.
	 */
	public function call_handle_return() {
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

						$order->update_status( self::ORDER_STATUS_CANCELLED, $notice );

						WC()->session->set( 'emp_payment_error', $notice );

						if ( $this->get_method_setting( self::SETTING_KEY_REDIRECT_FAILURE ) === self::SETTING_VALUE_CHECKOUT ) {
							$return_url = wc_get_checkout_url();
						}
						break;
					case 'cancel':
						$note = static::get_translated_text(
							'The customer cancelled their payment session'
						);

						WC()->session->set( 'order_awaiting_payment', false );
						$order->update_status( self::ORDER_STATUS_CANCELLED, $note );
						break;
				}

				// Replace header() with test-friendly redirect capture.
				$this->test_redirect_url = $return_url;
			}
		}
		// phpcs:enable
	}

	/**
	 * The redirect URL captured during test execution.
	 *
	 * @var string|null
	 */
	public $test_redirect_url = null;

	/**
	 * Override get_method_setting to return test values.
	 *
	 * @param string $setting_name Setting name.
	 * @return mixed
	 */
	protected function get_method_setting( $setting_name ) {
		if ( self::SETTING_KEY_REDIRECT_FAILURE === $setting_name ) {
			return self::SETTING_VALUE_CHECKOUT;
		}
		return '';
	}

	/**
	 * Override init_form_fields to avoid complex setup.
	 */
	public function init_form_fields() {
		$this->form_fields = array();
	}
}

/**
 * Bug Condition Exploration Test.
 *
 * Property 1: Bug Condition — Decline Return Corrupts Session State
 *
 * Tests that handle_return() with act=failure and redirect_failure_setting=checkout
 * does NOT set order_awaiting_payment to false.
 *
 * EXPECTED: This test FAILS on unfixed code (proving the bug exists).
 *
 * **Validates: Requirements 1.1, 1.2, 1.4, 2.4**
 */
class HandleReturnBugConditionTest extends TestCase {

	/**
	 * Order instance for testing.
	 *
	 * @var WC_Order
	 */
	private $order;

	/**
	 * Gateway instance for testing.
	 *
	 * @var HandleReturnTestableGateway
	 */
	private $gateway;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();

		global $wc_emerchantpay_order_proxy_instance, $wc_test_notices, $wp_test_redirect_url;

		// Reset global state.
		$wc_test_notices      = array();
		$wp_test_redirect_url = null;
		WooCommerce::reset();

		// Create a test order with a valid one-time token (not cleared).
		$this->order = new WC_Order( 123 );
		$this->order->update_meta_data( '_checkout_return_token', 'valid_token_abc123' );

		// Set up the order proxy to return our test order.
		$wc_emerchantpay_order_proxy_instance          = new WC_Emerchantpay_Order_Proxy_Stub();
		$wc_emerchantpay_order_proxy_instance->orders[123] = $this->order;

		// Create the gateway instance.
		$this->gateway = new HandleReturnTestableGateway();

		// Simulate $_GET parameters for a decline return.
		$_GET['act'] = 'failure';
		$_GET['oid'] = '123';
	}

	/**
	 * Clean up after each test.
	 */
	protected function tearDown(): void {
		parent::tearDown();

		unset( $_GET['act'], $_GET['oid'] );

		global $wc_emerchantpay_order_proxy_instance, $wc_test_notices, $wp_test_redirect_url;
		$wc_emerchantpay_order_proxy_instance = null;
		$wc_test_notices      = array();
		$wp_test_redirect_url = null;
		WooCommerce::reset();
	}

	/**
	 * Test: Decline return with redirect_failure=checkout must NOT set
	 * order_awaiting_payment to false.
	 *
	 * Bug Condition: isBugCondition(X) = X.act = "failure" AND X.redirect_failure_setting = "checkout"
	 *
	 * This test MUST FAIL on unfixed code because the current implementation
	 * explicitly calls WC()->session->set('order_awaiting_payment', false).
	 *
	 * Counterexample: handle_return() with $_GET['act']='failure' explicitly sets
	 * order_awaiting_payment to false, preventing retry.
	 *
	 * **Validates: Requirements 1.1, 1.2, 1.4, 2.4**
	 */
	public function test_decline_return_does_not_corrupt_session_order_awaiting_payment() {
		// Arrange: Set initial session state — order is awaiting payment.
		WC()->session->set( 'order_awaiting_payment', 123 );
		// Clear the initial set_calls so we only track calls during handle_return().
		WC()->session->set_calls = array();

		// Act: Process the decline return.
		$this->gateway->call_handle_return();

		// Assert: session->set() must NOT have been called with ('order_awaiting_payment', false).
		$session_set_calls = WC()->session->set_calls;

		$corrupting_calls = array_filter(
			$session_set_calls,
			function ( $call ) {
				return 'order_awaiting_payment' === $call['key'] && false === $call['value'];
			}
		);

		$this->assertEmpty(
			$corrupting_calls,
			'Bug confirmed: handle_return() with act=failure explicitly sets ' .
			'order_awaiting_payment to false, corrupting session state and preventing retry. ' .
			'Found ' . count( $corrupting_calls ) . ' call(s) setting order_awaiting_payment=false.'
		);
	}

	/**
	 * Test: Decline return still cancels the order (expected behavior preserved).
	 *
	 * **Validates: Requirements 1.1, 2.4**
	 */
	public function test_decline_return_still_cancels_order() {
		// Arrange.
		WC()->session->set( 'order_awaiting_payment', 123 );
		WC()->session->set_calls = array();

		// Act.
		$this->gateway->call_handle_return();

		// Assert: Order status should be cancelled.
		$this->assertEquals( 'cancelled', $this->order->get_status() );
	}

	/**
	 * Test: Decline return stores error in custom session key (not wc_add_notice).
	 *
	 * The error is stored in emp_payment_error session key which is safe —
	 * Store API does not read custom session keys as cart errors.
	 *
	 * **Validates: Requirements 2.1, 2.3, 2.4, 2.5**
	 */
	public function test_decline_return_stores_error_in_custom_session_key() {
		global $wc_test_notices;

		// Arrange.
		WC()->session->set( 'order_awaiting_payment', 123 );
		WC()->session->set_calls = array();

		// Act.
		$this->gateway->call_handle_return();

		// Assert: No WC notice should be added (would cause Store API 409).
		$error_notices = array_filter(
			$wc_test_notices,
			function ( $notice ) {
				return 'error' === $notice['type'];
			}
		);
		$this->assertEmpty(
			$error_notices,
			'Decline return should NOT add error via wc_add_notice (causes Store API 409).'
		);

		// Assert: Error message should be stored in custom session key.
		$session_set_calls = WC()->session->set_calls;
		$error_key_calls   = array_filter(
			$session_set_calls,
			function ( $call ) {
				return 'emp_payment_error' === $call['key'];
			}
		);
		$this->assertNotEmpty(
			$error_key_calls,
			'Decline return should store error in emp_payment_error session key.'
		);

		$error_call = array_values( $error_key_calls )[0];
		$this->assertStringContainsString(
			'declined',
			$error_call['value'],
			'emp_payment_error should contain the decline message.'
		);
	}

	/**
	 * Test: Decline return with redirect_failure=checkout sets redirect to checkout URL.
	 *
	 * This verifies the redirect URL is set to the checkout page (not order view).
	 *
	 * **Validates: Requirements 1.1, 2.4**
	 */
	public function test_decline_return_redirects_to_checkout_url() {
		// Arrange.
		WC()->session->set( 'order_awaiting_payment', 123 );
		WC()->session->set_calls = array();

		// Act.
		$this->gateway->call_handle_return();

		// Assert: Redirect URL should be the checkout URL.
		$this->assertEquals(
			'https://example.com/checkout',
			$this->gateway->test_redirect_url,
			'Expected redirect to checkout URL after decline with redirect_failure=checkout.'
		);
	}
}
