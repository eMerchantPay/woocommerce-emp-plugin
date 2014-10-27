<?php

require 'genesis/vendor/autoload.php';

use \Genesis\Base as Genesis;
use \Genesis\Configuration as GenesisConf;

class WC_Genesis extends WC_Payment_Gateway
{
	public function __construct()
	{
		$this->id                   = 'genesis';
		$this->method_title         = __('eMerchantPay', 'woocommerce_emerchantpay');
		$this->icon                 = plugins_url( 'assets/images/logo.gif', plugin_dir_path(__FILE__) );
		$this->has_fields           = false;

		$this->init_form_fields();
		$this->init_settings();

		foreach ($this->settings as $name => $value) {
			if (!isset($this->$name)) {
				$this->$name = $value;
			}
		}

		// WooCommerce hooks
		add_action('init', array(&$this, 'process_gateway_response' ));

		add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'process_gateway_response' ) );

		add_action( 'woocommerce_receipt_' . $this->id, array(&$this, 'generate_form' ));

		add_action( 'woocommerce_thankyou_' . $this->id, array(&$this, 'process_return' ));

		// Save admin-panel options
		if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
		} else {
			add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
		}

		// Credentials Setup
		$this->setGenesisCredentials($this->settings);
	}

	/**
	 * Admin Panel Field Definition
	 *
	 * @return void
	 */
	public function init_form_fields()
	{
		$this->form_fields = array(
			'enabled' => array(
				'title'         => __('Enable/Disable', 'woocommerce_emerchantpay'),
				'type'          => 'checkbox',
				'label'         => __('Enable eMerchantPay Checkout', 'woocommerce_emerchantpay'),
				'default'       => 'no'
			),
			'title' => array(
				'title'         => __('Title:', 'woocommerce_emerchantpay'),
				'type'          => 'text',
				'description'   => __('This controls the title which the user sees during checkout.', 'woocommerce_emerchantpay'),
				'desc_tip'      => true,
				'default'       => __('eMerchantPay', 'woocommerce_emerchantpay')
			),
			'description' => array(
				'title'         => __('Description:', 'woocommerce_emerchantpay'),
				'type'          => 'textarea',
				'description'   => __('This controls the description which the user sees during checkout.', 'woocommerce_emerchantpay'),
				'desc_tip'      => true,
				'default'       => __('Pay securely by Debit or Credit card, through eMerchantPay\'s Secure Gateway.<br/>You will be redirected to your secure server', 'woocommerce_emerchantpay')
			),
			'test_mode' => array(
				'title'         => __('Test Mode', 'woocommerce_emerchantpay'),
				'type'          => 'checkbox',
				'label'          => __( 'Use Genesis Staging', 'woocommerce' ),
				'description'   =>  __('Selecting this would route all request to our test environment.<br/>NO Funds are being transferred!'),
				'desc_tip'      => true,
			),
			'transaction_types' => array(
				'title'         => __('Transcation Type', 'woocommerce_emerchantpay'),
				'type'          => 'select',
				'options'       => array(
					'auth'   => __('Authorize', 'woocommerce_emerchantpay'),
					'auth3d' => __('Authorize 3D', 'woocommerce_emerchantpay'),
					'sale'   => __('Sale', 'woocommerce_emerchantpay'),
					'sale3d' => __('Sale 3D', 'woocommerce_emerchantpay'),
				),
				'description'   =>  __('Authorize - authorize transaction type<br/><br/>Authorize3D - authorize transaction type with 3D Authentication<br/><br/>Sale - Sale transaction type<br/><br/>Sale3D - sale transaction type with 3D authentication.'),
				'desc_tip'      => true,
			),
			'api_credentials' => array(
				'title'       => __( 'API Credentials', 'woocommerce' ),
				'type'        => 'title',
				'description' => sprintf( __( 'Enter Genesis API Credentials below, in order to access the Gateway. If you forgot/lost your credentials, please %sget in touch%s with our technical support.', 'woocommerce' ), '<a href="mailto:tech-support@e-comprocessing.com">', '</a>' ),
			),
			'username' => array(
				'title'         => __('Gateway Username', 'woocommerce_emerchantpay'),
				'type'          => 'text',
				'description'   => __('This is your Genesis username.'),
				'desc_tip'      => true,
			),
			'password' => array(
				'title'         => __('Gateway Password', 'woocommerce_emerchantpay'),
				'type'          => 'text',
				'description'   =>  __('This is your Genesis password.', 'woocommerce_emerchantpay'),
				'desc_tip'      => true,
			),
			'token' => array(
				'title'         => __('Gateway Token', 'woocommerce_emerchantpay'),
				'type'          => 'text',
				'description'   =>  __('This is your Genesis Token', 'woocommerce_emerchantpay'),
				'desc_tip'      => true,
			),
		);
	}

	/**
	 * Render the HTML for the Admin settings
	 *
	 * @return void
	 */
	public function admin_options()
	{
		?>
		<h3>
			<?php _e('eMerchantPay', 'woocommerce_emerchantpay'); ?>
		</h3>
		<p>
			<?php _e("eMerchantPay's Gateway works by sending your client, to our secure (PCI-DSS certified) server.", "woocommerce_emerchantpay"); ?>
		</p>
		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
		</table>
		<?php
	}

	public function process_return($order_id)
	{
		$type = esc_sql($_GET['type']);

		if (isset($type) && !empty($type)) {

			$order = new WC_Order($order_id);

			switch ($type) {
				case 'success':
					$order->update_status('completed');
					break;
				case 'failure':
					$order->update_status('failed');
					break;
				case 'cancel':
					$order->update_status('cancelled');
					break;
			}

			header('Location: ' . $order->get_checkout_order_received_url());
		}

	}

	/**
	 * Generate HTML Payment form
	 *
	 * @param $order_id
	 *
	 * @return string HTML form
	 */
	public function process_payment($order_id)
	{
		global $woocommerce;

		$order = new WC_Order( $order_id );

		$trx_id = $this->genTransactionId($order_id);

		$return = array(
			'success'   => sprintf('%s&type=success', $order->get_checkout_order_received_url()),
			'failure'   => sprintf('%s&type=failure', $order->get_checkout_order_received_url()),
			'cancel'    => sprintf('%s&type=cancel', $order->get_checkout_order_received_url()),
		);

		$redirect_url = ( $this->redirect_page_id == "" || $this->redirect_page_id == 0 ) ? get_site_url() . "/" : get_permalink( $this->redirect_page_id );
		// For wooCoomerce 2.0
		$redirect_url = add_query_arg( 'wc-api', strtolower( get_class( $this ) ), $redirect_url );

		$genesis = new Genesis('WPF\Create');

		$genesis
			->request()
		        ->setTransactionId( $trx_id )
		        ->setCurrency( $order->get_order_currency() )
		        ->setAmount( $this->get_order_total() )
		        ->setUsage( 'TEST' )
		        ->setDescription( 'TEST' )
		        ->setCustomerEmail( $order->billing_email )
		        ->setCustomerPhone( $order->billing_phone )
		        ->setNotificationUrl( $redirect_url )
		        ->setReturnSuccessUrl( $return['success'] )
		        ->setReturnFailureUrl( $return['failure'] )
		        ->setReturnCancelUrl( $return['cancel'] )
		        ->setBillingFirstName( $order->billing_first_name )
		        ->setBillingLastName( $order->billing_last_name )
		        ->setBillingAddress1( $order->billing_address_1 )
		        ->setBillingAddress2( $order->billing_address_2 )
		        ->setBillingZipCode( $order->billing_postcode )
		        ->setBillingCity( $order->billing_city )
		        ->setBillingState( $order->billing_state )
		        ->setBillingCountry( $order->billing_country )
		        ->setShippingFirstName( $order->shipping_first_name )
		        ->setShippingLastName( $order->shipping_last_name )
		        ->setShippingAddress1( $order->shipping_address_1 )
		        ->setShippingAddress2( $order->shipping_address_2 )
		        ->setShippingZipCode( $order->shipping_postcode )
		        ->setShippingCity( $order->shipping_city )
		        ->setShippingState( $order->shipping_state )
		        ->setShippingCountry( $order->shipping_country )
		        ->addTransactionType( 'sale' );

		$genesis->sendRequest();

		$response = $genesis->response()->getResponseObject();

		$target_url = null;

		if ( isset( $response->redirect_url ) ) {
			$target_url = (string) $response->redirect_url;

			$order->update_status('pending');
		}

		if ( isset( $response->status) && (string)$response->status == 'error') {
			$woocommerce->add_error(__("We were unable to process your order, please make sure all the data is correct or try again later."));
		}

		return array(
			'result'    => 'success',
			'redirect'  => $target_url
		);
	}

	public function process_refund($order_id, $amount, $reason)
	{
		$order = new WC_Order($order_id);

		$genesis = new Genesis('Financial\Refund');

		$genesis
			->request()
				->setTransactionId($this->genTransactionId($order_id))
				->setUsage($reason)
				->setRemoteIp($_SERVER['REMOTE_ADDR'])
				->setReferenceId($order->get_transaction_id())
				->setCurrency($order->get_order_currency())
				->setAmount($amount);

		$genesis->sendRequest();

		$response = $genesis->response()->getResponseObject();

		if (isset($response->status) && $response->status == 'approved') {
			$order->add_order_note(sprintf('Refunded amount: %s%s, done by RID: %s', $amount, $response->unique_id), 0);

			return true;
		}

		return false;
	}

	/**
	 * Check Gateway response and update order status
	 *
	 * @return void
	 */
	public function process_notification()
	{
		global $woocommerce;

		if (isset($_POST['wpf_unique_id']) && isset($_POST['notification_type'])) {
			$notification = new \Genesis\API\Notification();

			$notification->parseNotification($_POST);

			if ($notification->isAuthentic()) {
				$notificationObj = $notification->getNotificaitonObject();

				$trx = $this->parseTransactionId($notificationObj->transaction_id);

				// It's recommended to Reconcile after Notification
				$genesis = new Genesis('WPF\Reconcile');
				$genesis->request()->setUniqueId($notificationObj->payment_transaction_unique_id);
				$genesis->sendRequest();

				$reconcile = $genesis->response()->getResponseObject();

				$order = new WC_Order($trx['order_id']);

				switch ($reconcile->status) {
					case 'approved':
						$order->payment_complete($reconcile->unique_id);

						$woocommerce->cart->empty_cart();
						break;
					case 'declined':
						$order->update_status('failure', $reconcile->technical_message);
						break;
					default:
					case 'error':
						$order->update_status('error', $reconcile->technical_message);
						break;
				}

				echo $notification->getEchoResponse();
			}
		}
	}

	/**
	 * Generate transaction id, unique to this instance
	 *
	 * @param string $input
	 *
	 * @return array|string
	 */
	private function genTransactionId($input)
	{
		// Why are we doing this?
		// We need to be sure that we have a unique string we can use as transaction id.
		// In order to do this, we use a few $_SERVER parameters to make some unique id.

		$unique = sprintf('%s|%s|%s', $_SERVER['SERVER_NAME'], microtime(true), $_SERVER['REMOTE_ADDR']);

		return sprintf('%s-%s', $input, strtoupper(md5($unique)));
	}

	/**
	 * Parse transaction id from a string to assoc array
	 *
	 * @param $input
	 *
	 * @return array|bool
	 */
	private function parseTransactionID($input)
	{
		$arr = explode('-', $input);

		// Use @ to silence notices/warnings
		return array (
			'order_id' => @$arr[0],
			'salt'     => @$arr[1],
		);
	}

	/**
	 * Set the Genesis PHP Lib Credentials, based on the customer's
	 * admin settings
	 *
	 * @param array $settings WooCommerce settings array
	 *
	 * @return void
	 */
	private function setGenesisCredentials($settings = array())
	{
		GenesisConf::setToken( $settings['token'] );
		GenesisConf::setUsername( $settings['username'] );
		GenesisConf::setPassword( $settings['password'] );

		GenesisConf::setEnvironment( (isset($settings['test_mode']) && $settings['test_mode']) ? 'sandbox' : 'production' );
	}

	/*
	private function searchOrderNotes($order_id, $needle)
	{
		remove_filter('comments_clauses', array( 'WC_Comments' ,'exclude_order_comments'), 10, 1 );

		$query = new WP_Comment_Query();

		$comments = $query->query(
			array(
				'type'      => 'order_note',
				'search'    => $needle,
				'post_id'   => intval($order_id),
			)
		);

		add_filter('comments_clauses', array( 'WC_Comments' ,'exclude_order_comments'), 10, 1 );

		return reset($comments);
	}
	*/
}