<?php
/**
 * Preservation Property Tests: Non-Decline Flows Unchanged
 *
 * Property 2: Preservation — for all non-bug-condition inputs
 * (act != 'failure' OR redirect_failure != 'checkout'), the behavior
 * matches observed outputs on the UNFIXED code.
 *
 * These tests MUST PASS on unfixed code — they confirm baseline behavior to preserve.
 *
 * Observed behaviors:
 * - act=success: clears one-time token to |CLEAR|, displays success notice, redirects to order view URL
 * - act=cancel: sets order_awaiting_payment to false, cancels order with cancel note
 * - Token already |CLEAR|: redirects to cart page via wp_safe_redirect regardless of act value
 * - act=failure with redirect_failure=order: redirects to order view URL (not checkout)
 *
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6**
 *
 * @package WC_Emerchantpay\Tests\Unit
 */

use PHPUnit\Framework\TestCase;

/**
 * Gateway variant that returns SETTING_VALUE_ORDER for redirect_failure setting.
 *
 * Used to test the order-page redirect flow (act=failure with redirect_failure=order).
 */
class HandleReturnOrderRedirectGateway extends HandleReturnTestableGateway {

	/**
	 * Override get_method_setting to return 'order' for redirect_failure.
	 *
	 * @param string $setting_name Setting name.
	 * @return mixed
	 */
	protected function get_method_setting( $setting_name ) {
		if ( self::SETTING_KEY_REDIRECT_FAILURE === $setting_name ) {
			return self::SETTING_VALUE_ORDER;
		}
		return '';
	}
}

/**
 * Preservation Property Tests.
 *
 * Property 2: Preservation — Non-Decline Flows Unchanged
 *
 * Tests that all non-bug-condition flows produce the same behavior on unfixed code.
 * These tests document and lock in the correct baseline behavior.
 *
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6**
 */
class HandleReturnPreservationTest extends TestCase {

	/**
	 * Order instance for testing.
	 *
	 * @var WC_Order
	 */
	private $order;

	/**
	 * Gateway instance (redirect_failure=checkout).
	 *
	 * @var HandleReturnTestableGateway
	 */
	private $gateway;

	/**
	 * Gateway instance (redirect_failure=order).
	 *
	 * @var HandleReturnOrderRedirectGateway
	 */
	private $order_redirect_gateway;

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
		$this->order = new WC_Order( 456 );
		$this->order->update_meta_data( '_checkout_return_token', 'valid_token_xyz789' );

		// Set up the order proxy to return our test order.
		$wc_emerchantpay_order_proxy_instance              = new WC_Emerchantpay_Order_Proxy_Stub();
		$wc_emerchantpay_order_proxy_instance->orders[456] = $this->order;

		// Create gateway instances.
		$this->gateway                = new HandleReturnTestableGateway();
		$this->order_redirect_gateway = new HandleReturnOrderRedirectGateway();

		// Default $_GET parameters.
		$_GET['oid'] = '456';
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

	// =========================================================================
	// SUCCESS FLOW TESTS
	// =========================================================================

	/**
	 * Test: Success return clears one-time token to |CLEAR|.
	 *
	 * Observed behavior: act=success clears one-time token to |CLEAR|.
	 *
	 * **Validates: Requirements 3.1**
	 */
	public function test_success_return_clears_one_time_token() {
		// Arrange.
		$_GET['act'] = 'success';

		// Act.
		$this->gateway->call_handle_return();

		// Assert: Token should be cleared to |CLEAR|.
		$token = $this->order->get_meta( '_checkout_return_token' );
		$this->assertEquals(
			'|CLEAR|',
			$token,
			'Success return should clear one-time token to |CLEAR|.'
		);
	}

	/**
	 * Test: Success return displays success notice.
	 *
	 * Observed behavior: act=success displays success notice.
	 *
	 * **Validates: Requirements 3.1**
	 */
	public function test_success_return_displays_success_notice() {
		global $wc_test_notices;

		// Arrange.
		$_GET['act'] = 'success';

		// Act.
		$this->gateway->call_handle_return();

		// Assert: A success notice should be displayed.
		$success_notices = array_filter(
			$wc_test_notices,
			function ( $notice ) {
				return 'success' === $notice['type'];
			}
		);

		$this->assertNotEmpty( $success_notices, 'Expected a success notice to be displayed.' );

		$notice_messages = array_column( $success_notices, 'message' );
		$this->assertContains(
			'Your payment has been completed successfully.',
			$notice_messages,
			'Expected success notice message.'
		);
	}

	/**
	 * Test: Success return redirects to order view URL.
	 *
	 * Observed behavior: act=success redirects to order view URL.
	 *
	 * **Validates: Requirements 3.1**
	 */
	public function test_success_return_redirects_to_order_view_url() {
		// Arrange.
		$_GET['act'] = 'success';

		// Act.
		$this->gateway->call_handle_return();

		// Assert: Redirect URL should be the order view URL.
		$this->assertEquals(
			'https://example.com/my-account/view-order/456',
			$this->gateway->test_redirect_url,
			'Success return should redirect to order view URL.'
		);
	}

	// =========================================================================
	// CANCEL FLOW TESTS
	// =========================================================================

	/**
	 * Test: Cancel return sets order_awaiting_payment to false.
	 *
	 * Observed behavior: act=cancel sets order_awaiting_payment to false.
	 *
	 * **Validates: Requirements 3.6**
	 */
	public function test_cancel_return_sets_order_awaiting_payment_to_false() {
		// Arrange.
		$_GET['act'] = 'cancel';
		WC()->session->set( 'order_awaiting_payment', 456 );
		WC()->session->set_calls = array();

		// Act.
		$this->gateway->call_handle_return();

		// Assert: session->set() should have been called with ('order_awaiting_payment', false).
		$session_set_calls = WC()->session->set_calls;

		$awaiting_payment_calls = array_filter(
			$session_set_calls,
			function ( $call ) {
				return 'order_awaiting_payment' === $call['key'] && false === $call['value'];
			}
		);

		$this->assertNotEmpty(
			$awaiting_payment_calls,
			'Cancel return should set order_awaiting_payment to false.'
		);
	}

	/**
	 * Test: Cancel return cancels the order with cancel note.
	 *
	 * Observed behavior: act=cancel cancels order with cancel note.
	 *
	 * **Validates: Requirements 3.6**
	 */
	public function test_cancel_return_cancels_order_with_note() {
		// Arrange.
		$_GET['act'] = 'cancel';

		// Act.
		$this->gateway->call_handle_return();

		// Assert: Order status should be cancelled.
		$this->assertEquals(
			'cancelled',
			$this->order->get_status(),
			'Cancel return should cancel the order.'
		);

		// Assert: Order note should contain the cancel message.
		$this->assertNotEmpty( $this->order->notes, 'Cancel return should add an order note.' );
		$this->assertContains(
			'The customer cancelled their payment session',
			$this->order->notes,
			'Cancel note should contain the cancellation message.'
		);
	}

	/**
	 * Test: Cancel return clears one-time token to |CLEAR|.
	 *
	 * Observed behavior: act=cancel also clears the one-time token (happens before switch).
	 *
	 * **Validates: Requirements 3.6**
	 */
	public function test_cancel_return_clears_one_time_token() {
		// Arrange.
		$_GET['act'] = 'cancel';

		// Act.
		$this->gateway->call_handle_return();

		// Assert: Token should be cleared to |CLEAR|.
		$token = $this->order->get_meta( '_checkout_return_token' );
		$this->assertEquals(
			'|CLEAR|',
			$token,
			'Cancel return should clear one-time token to |CLEAR|.'
		);
	}

	// =========================================================================
	// TOKEN ABUSE PREVENTION TESTS
	// =========================================================================

	/**
	 * Test: Second access with token already |CLEAR| redirects to cart page (act=success).
	 *
	 * Observed behavior: second access with token already |CLEAR| redirects to cart page
	 * via wp_safe_redirect regardless of act value.
	 *
	 * **Validates: Requirements 3.2**
	 */
	public function test_token_cleared_redirects_to_cart_for_success() {
		global $wp_test_redirect_url;

		// Arrange: Token is already cleared.
		$this->order->update_meta_data( '_checkout_return_token', '|CLEAR|' );
		$_GET['act'] = 'success';

		// Act.
		$this->gateway->call_handle_return();

		// Assert: Should redirect to cart page via wp_safe_redirect.
		$this->assertEquals(
			'https://example.com/cart',
			$wp_test_redirect_url,
			'Second access with cleared token should redirect to cart page.'
		);
	}

	/**
	 * Test: Second access with token already |CLEAR| redirects to cart page (act=failure).
	 *
	 * Observed behavior: second access with token already |CLEAR| redirects to cart page
	 * via wp_safe_redirect regardless of act value.
	 *
	 * **Validates: Requirements 3.2**
	 */
	public function test_token_cleared_redirects_to_cart_for_failure() {
		global $wp_test_redirect_url;

		// Arrange: Token is already cleared.
		$this->order->update_meta_data( '_checkout_return_token', '|CLEAR|' );
		$_GET['act'] = 'failure';

		// Act.
		$this->gateway->call_handle_return();

		// Assert: Should redirect to cart page via wp_safe_redirect.
		$this->assertEquals(
			'https://example.com/cart',
			$wp_test_redirect_url,
			'Second access with cleared token should redirect to cart page for failure.'
		);
	}

	/**
	 * Test: Second access with token already |CLEAR| redirects to cart page (act=cancel).
	 *
	 * Observed behavior: second access with token already |CLEAR| redirects to cart page
	 * via wp_safe_redirect regardless of act value.
	 *
	 * **Validates: Requirements 3.2**
	 */
	public function test_token_cleared_redirects_to_cart_for_cancel() {
		global $wp_test_redirect_url;

		// Arrange: Token is already cleared.
		$this->order->update_meta_data( '_checkout_return_token', '|CLEAR|' );
		$_GET['act'] = 'cancel';

		// Act.
		$this->gateway->call_handle_return();

		// Assert: Should redirect to cart page via wp_safe_redirect.
		$this->assertEquals(
			'https://example.com/cart',
			$wp_test_redirect_url,
			'Second access with cleared token should redirect to cart page for cancel.'
		);
	}

	/**
	 * Test: Token abuse prevention does NOT process the order (no status change).
	 *
	 * Observed behavior: when token is |CLEAR|, the order is not processed again.
	 *
	 * **Validates: Requirements 3.2**
	 */
	public function test_token_cleared_does_not_change_order_status() {
		// Arrange: Token is already cleared.
		$this->order->update_meta_data( '_checkout_return_token', '|CLEAR|' );
		$_GET['act'] = 'failure';

		// Act.
		$this->gateway->call_handle_return();

		// Assert: Order status should remain pending (not cancelled).
		$this->assertEquals(
			'pending',
			$this->order->get_status(),
			'Token abuse prevention should not change order status.'
		);
	}

	// =========================================================================
	// ORDER-PAGE REDIRECT TESTS (act=failure with redirect_failure=order)
	// =========================================================================

	/**
	 * Test: Failure return with redirect_failure=order redirects to order view URL.
	 *
	 * Observed behavior: act=failure with redirect_failure=order redirects to order view URL
	 * (not checkout). This is a non-bug-condition input because redirect_failure != 'checkout'.
	 *
	 * **Validates: Requirements 3.5**
	 */
	public function test_failure_with_order_redirect_goes_to_order_view_url() {
		// Arrange.
		$_GET['act'] = 'failure';

		// Act: Use the order-redirect gateway variant.
		$this->order_redirect_gateway->call_handle_return();

		// Assert: Redirect URL should be the order view URL (not checkout).
		$this->assertEquals(
			'https://example.com/my-account/view-order/456',
			$this->order_redirect_gateway->test_redirect_url,
			'Failure with redirect_failure=order should redirect to order view URL.'
		);
	}

	/**
	 * Test: Failure return with redirect_failure=order still cancels the order.
	 *
	 * Observed behavior: act=failure still cancels the order regardless of redirect setting.
	 *
	 * **Validates: Requirements 3.3, 3.5**
	 */
	public function test_failure_with_order_redirect_still_cancels_order() {
		// Arrange.
		$_GET['act'] = 'failure';

		// Act.
		$this->order_redirect_gateway->call_handle_return();

		// Assert: Order status should be cancelled.
		$this->assertEquals(
			'cancelled',
			$this->order->get_status(),
			'Failure with redirect_failure=order should still cancel the order.'
		);
	}

	/**
	 * Test: Failure return with redirect_failure=order stores error in custom session key.
	 *
	 * After the fix, error is stored in emp_payment_error session key (not wc_add_notice).
	 * This prevents Store API 409 on retry.
	 *
	 * **Validates: Requirements 3.3, 3.5**
	 */
	public function test_failure_with_order_redirect_stores_error_in_session_key() {
		global $wc_test_notices;

		// Arrange.
		$_GET['act'] = 'failure';
		WC()->session->set_calls = array();

		// Act.
		$this->order_redirect_gateway->call_handle_return();

		// Assert: No WC notice should be added.
		$error_notices = array_filter(
			$wc_test_notices,
			function ( $notice ) {
				return 'error' === $notice['type'];
			}
		);
		$this->assertEmpty(
			$error_notices,
			'Failure return should NOT add error via wc_add_notice.'
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
			'Failure return should store error in emp_payment_error session key.'
		);
	}

	/**
	 * Test: Failure return with redirect_failure=order clears one-time token.
	 *
	 * Observed behavior: act=failure clears the one-time token (happens before switch).
	 *
	 * **Validates: Requirements 3.5**
	 */
	public function test_failure_with_order_redirect_clears_token() {
		// Arrange.
		$_GET['act'] = 'failure';

		// Act.
		$this->order_redirect_gateway->call_handle_return();

		// Assert: Token should be cleared to |CLEAR|.
		$token = $this->order->get_meta( '_checkout_return_token' );
		$this->assertEquals(
			'|CLEAR|',
			$token,
			'Failure with redirect_failure=order should clear one-time token.'
		);
	}
}
