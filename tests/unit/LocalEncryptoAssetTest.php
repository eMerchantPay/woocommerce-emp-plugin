<?php
/**
 * Tests for local Encrypto asset registration.
 *
 * @package WC_Emerchantpay\Tests\Unit
 */

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 2 ) . '/includes/class-wc-emerchantpay-functions.php';

class LocalEncryptoAssetTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		wc_emerchantpay_test_reset_scripts();
	}

	public function test_vendor_asset_has_the_approved_sha512_digest() {
		$asset = dirname( __DIR__, 2 ) . '/assets/javascript/encrypto.js';
		$hash  = base64_encode( hash_file( 'sha512', $asset, true ) );

		$this->assertFileExists( $asset );
		$this->assertSame(
			'sha512-fezEmqlQWwvKq3A2s897RSda3JsDen/xmPTsBmnx6TWk++rnofg2omiNLHhCbQvQ8DtEvfAvXQTsvE95DlELAw==',
			'sha512-' . $hash
		);
	}

	public function test_encryption_helper_depends_on_local_encrypto_asset() {
		global $wp;

		$wp = (object) array( 'query_vars' => array() );
		$functions = new WC_Emerchantpay_Functions( __FILE__ );
		$functions->emp_add_css_and_js_to_checkout();

		global $wp_test_enqueued_scripts;
		$this->assertArrayHasKey( 'emp-cse-direct', $wp_test_enqueued_scripts );
		$this->assertStringEndsWith(
			'/assets/javascript/encrypto.js',
			$wp_test_enqueued_scripts['emp-cse-direct']['src']
		);
		$this->assertSame( array(), $wp_test_enqueued_scripts['emp-cse-direct']['deps'] );
		$this->assertTrue( $wp_test_enqueued_scripts['emp-cse-direct']['in_footer'] );
		$this->assertSame(
			array( 'emp-cse-direct' ),
			$wp_test_enqueued_scripts['emp-cse-direct-encrypt']['deps']
		);
	}

	public function test_remote_sri_and_cors_hook_are_absent() {
		$functions_source = file_get_contents( dirname( __DIR__, 2 ) . '/includes/class-wc-emerchantpay-functions.php' );
		$direct_source    = file_get_contents( dirname( __DIR__, 2 ) . '/includes/class-wc-emerchantpay-direct.php' );

		$this->assertFalse( method_exists( WC_Emerchantpay_Functions::class, 'handle_cse_script_arguments' ) );
		$this->assertStringNotContainsString( 'd3ptmkrtf16kmh.cloudfront.net', $functions_source );
		$this->assertStringNotContainsString( 'script_loader_tag', $functions_source );
		$this->assertStringNotContainsString( 'ENCRYPT_LIBRARY_INTEGRITY_HASH', $direct_source );
	}
}
