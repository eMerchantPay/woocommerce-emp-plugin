<?php
/**
 * Minimal WooCommerce class/function stubs for unit testing.
 *
 * @package WC_Emerchantpay\Tests
 */

/**
 * Stub WC_Session class.
 */
class WC_Session {
	/**
	 * Session data store.
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Track all set() calls for test assertions.
	 *
	 * @var array
	 */
	public $set_calls = array();

	/**
	 * Set session value.
	 *
	 * @param string $key   Session key.
	 * @param mixed  $value Session value.
	 */
	public function set( $key, $value ) {
		$this->set_calls[] = array( 'key' => $key, 'value' => $value );
		$this->data[ $key ] = $value;
	}

	/**
	 * Get session value.
	 *
	 * @param string $key     Session key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function get( $key, $default = null ) {
		return isset( $this->data[ $key ] ) ? $this->data[ $key ] : $default;
	}
}

/**
 * Stub WooCommerce main class.
 */
class WooCommerce {
	/**
	 * Session instance.
	 *
	 * @var WC_Session
	 */
	public $session;

	/**
	 * Singleton instance.
	 *
	 * @var WooCommerce|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return WooCommerce
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->session = new WC_Session();
	}

	/**
	 * Reset the singleton (for test isolation).
	 */
	public static function reset() {
		self::$instance = null;
	}
}

if ( ! function_exists( 'WC' ) ) {
	/**
	 * Get WooCommerce instance.
	 *
	 * @return WooCommerce
	 */
	function WC() {
		return WooCommerce::instance();
	}
}

/**
 * Stub WC_Payment_Gateway_CC class (extends WC_Payment_Gateway in WooCommerce).
 */
class WC_Payment_Gateway_CC extends WC_Payment_Gateway {}

/**
 * Stub WC_Payment_Gateway class.
 */
class WC_Payment_Gateway {
	/**
	 * Gateway ID.
	 *
	 * @var string
	 */
	public $id = '';

	/**
	 * Settings array.
	 *
	 * @var array
	 */
	public $settings = array();

	/**
	 * Get option value.
	 *
	 * @param string $key     Option key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function get_option( $key, $default = '' ) {
		return isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : $default;
	}

	/**
	 * Initialize settings from the database (no-op in tests).
	 */
	public function init_settings() {
		$this->settings = array();
	}
}

/**
 * Stub WC_Order class.
 */
class WC_Order {
	/**
	 * Order ID.
	 *
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Order status.
	 *
	 * @var string
	 */
	protected $status = 'pending';

	/**
	 * Order meta data.
	 *
	 * @var array
	 */
	protected $meta = array();

	/**
	 * Order notes.
	 *
	 * @var array
	 */
	public $notes = array();

	/**
	 * Status update calls.
	 *
	 * @var array
	 */
	public $status_updates = array();

	/**
	 * Constructor.
	 *
	 * @param int $id Order ID.
	 */
	public function __construct( $id = 0 ) {
		$this->id = $id;
	}

	/**
	 * Get order ID.
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Update order status.
	 *
	 * @param string $status New status.
	 * @param string $note   Order note.
	 */
	public function update_status( $status, $note = '' ) {
		$this->status           = $status;
		$this->status_updates[] = array( 'status' => $status, 'note' => $note );
		if ( $note ) {
			$this->notes[] = $note;
		}
	}

	/**
	 * Get order status.
	 *
	 * @return string
	 */
	public function get_status() {
		return $this->status;
	}

	/**
	 * Get view order URL.
	 *
	 * @return string
	 */
	public function get_view_order_url() {
		return 'https://example.com/my-account/view-order/' . $this->id;
	}

	/**
	 * Add order note.
	 *
	 * @param string $note Note text.
	 */
	public function add_order_note( $note ) {
		$this->notes[] = $note;
	}

	/**
	 * Get meta data.
	 *
	 * @param string $key     Meta key.
	 * @param bool   $single  Single value.
	 * @return mixed
	 */
	public function get_meta( $key, $single = true ) {
		if ( $single ) {
			return isset( $this->meta[ $key ] ) ? $this->meta[ $key ] : '';
		}
		return isset( $this->meta[ $key ] ) ? (array) $this->meta[ $key ] : array();
	}

	/**
	 * Update meta data.
	 *
	 * @param string $key   Meta key.
	 * @param mixed  $value Meta value.
	 */
	public function update_meta_data( $key, $value ) {
		$this->meta[ $key ] = $value;
	}

	/**
	 * Save the order.
	 */
	public function save() {
		// No-op in tests.
	}
}

/**
 * Stub order proxy class.
 */
class WC_Emerchantpay_Order_Proxy_Stub {
	/**
	 * Orders registry.
	 *
	 * @var array
	 */
	public $orders = array();

	/**
	 * Get order by ID.
	 *
	 * @param int $order_id Order ID.
	 * @return WC_Order|false
	 */
	public function get_order_by_id( $order_id ) {
		return isset( $this->orders[ $order_id ] ) ? $this->orders[ $order_id ] : false;
	}

	/**
	 * Get order meta data.
	 *
	 * @param WC_Order $order Order object.
	 * @param string   $key   Meta key.
	 * @return mixed
	 */
	public function get_order_meta_data( $order, $key ) {
		return $order->get_meta( $key );
	}

	/**
	 * Set order meta data.
	 *
	 * @param WC_Order $order Order object.
	 * @param string   $key   Meta key.
	 * @param mixed    $value Meta value.
	 */
	public function set_order_meta_data( $order, $key, $value ) {
		$order->update_meta_data( $key, $value );
	}

	/**
	 * Save transaction list to order.
	 *
	 * @param WC_Order $order        Order object.
	 * @param array    $transactions Transactions.
	 */
	public function save_trx_list_to_order( $order, $transactions ) {
		// No-op in tests.
	}
}

/**
 * Global order proxy instance for tests.
 *
 * @var WC_Emerchantpay_Order_Proxy_Stub|null
 */
global $wc_emerchantpay_order_proxy_instance;
$wc_emerchantpay_order_proxy_instance = null;

if ( ! function_exists( 'wc_emerchantpay_order_proxy' ) ) {
	/**
	 * Get order proxy instance.
	 *
	 * @return WC_Emerchantpay_Order_Proxy_Stub
	 */
	function wc_emerchantpay_order_proxy() {
		global $wc_emerchantpay_order_proxy_instance;
		if ( null === $wc_emerchantpay_order_proxy_instance ) {
			$wc_emerchantpay_order_proxy_instance = new WC_Emerchantpay_Order_Proxy_Stub();
		}
		return $wc_emerchantpay_order_proxy_instance;
	}
}
