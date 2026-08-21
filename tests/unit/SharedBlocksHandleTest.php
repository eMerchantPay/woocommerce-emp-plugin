<?php
/**
 * Tests for shared WooCommerce Blocks bundle registration.
 *
 * @package WC_Emerchantpay\Tests\Unit
 */

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 2 ) . '/includes/blocks/class-wc-emerchantpay-blocks-base.php';
require_once dirname( __DIR__, 2 ) . '/includes/blocks/class-wc-emerchantpay-blocks-checkout.php';
require_once dirname( __DIR__, 2 ) . '/includes/blocks/class-wc-emerchantpay-blocks-direct.php';

class SharedBlocksHandleTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		wc_emerchantpay_test_reset_scripts();
	}

	public function test_checkout_and_direct_share_a_single_registered_bundle_handle() {
		$checkout_handles = ( new WC_Emerchantpay_Blocks_Checkout() )->get_payment_method_script_handles();
		$direct_handles   = ( new WC_Emerchantpay_Blocks_Direct() )->get_payment_method_script_handles();

		$this->assertSame( array( WC_Emerchantpay_Blocks_Base::BLOCKS_SCRIPT_HANDLE ), $checkout_handles );
		$this->assertSame( $checkout_handles, $direct_handles );

		global $wp_test_registered_scripts, $wp_test_script_translations;
		$this->assertCount( 1, $wp_test_registered_scripts );
		$this->assertArrayHasKey( WC_Emerchantpay_Blocks_Base::BLOCKS_SCRIPT_HANDLE, $wp_test_registered_scripts );
		$this->assertSame(
			array( 'react', 'wc-blocks-registry', 'wc-settings', 'wp-html-entities', 'wp-i18n' ),
			$wp_test_registered_scripts[ WC_Emerchantpay_Blocks_Base::BLOCKS_SCRIPT_HANDLE ]['deps']
		);
		$this->assertTrue( $wp_test_registered_scripts[ WC_Emerchantpay_Blocks_Base::BLOCKS_SCRIPT_HANDLE ]['in_footer'] );
		$this->assertCount( 1, $wp_test_script_translations );
	}
}
