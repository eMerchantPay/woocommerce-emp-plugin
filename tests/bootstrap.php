<?php
/**
 * PHPUnit bootstrap file for the emerchantpay WooCommerce plugin tests.
 *
 * Sets up minimal stubs for WordPress and WooCommerce functions/classes
 * so that the plugin code can be loaded and tested in isolation.
 *
 * @package WC_Emerchantpay
 */

define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'WOOCOMMERCE_VERSION', '9.0.0' );

// Autoload Composer dependencies (PHPUnit, Genesis SDK, etc.).
require_once dirname( __DIR__ ) . '/libraries/vendor/autoload.php';

// Load test stubs and helpers.
require_once __DIR__ . '/stubs/wordpress-stubs.php';
require_once __DIR__ . '/stubs/woocommerce-stubs.php';
require_once __DIR__ . '/stubs/blocks-and-scripts-stubs.php';

// Load the message helper (real production code — it's simple enough).
require_once dirname( __DIR__ ) . '/classes/class-wc-emerchantpay-message-helper.php';

// Load the method base class (production code under test).
require_once dirname( __DIR__ ) . '/classes/class-wc-emerchantpay-method-base.php';
