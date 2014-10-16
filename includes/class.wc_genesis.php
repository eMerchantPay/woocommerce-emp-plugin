<?php

require 'genesis/vendor/autoload.php';

use \Genesis\Base as Genesis;
use \Genesis\Configuration as GenesisConf;

class WC_Genesis extends WC_Payment_Gateway
{
	protected $msg = array();

	public function __construct()
	{
		$this->init_form_fields();
		$this->init_settings();

		$this->id                   = 'genesis';
		$this->method_title         = __('eMerchantPay', 'woocommerce_emerchantpay');
		$this->icon                 = sprintf('%s/%s/images/logo.gif', WP_PLUGIN_URL, plugin_basename(dirname(__FILE__)));
		$this->has_fields           = false;

		$this->title                = $this->settings['title'];
		$this->description          = $this->settings['description'];
		$this->username             = $this->settings['username'];
		$this->password             = $this->settings['password'];
		$this->token                = $this->settings['token'];
		$this->transaction_types    = $this->settings['transaction_types'];
		$this->iframe_mode          = $this->settings['iframe_mode'];

		$this->msg['message']     = "";
		$this->msg['class']       = "";

		add_action('init', array(&$this, 'check_genesis_response'));

		//update for woocommerce >2.0
		add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'check_genesis_response' ) );

		add_action('valid-genesis-request', array(&$this, 'successful_request'));

		if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
		} else {
			add_action( 'woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
		}

		add_action('woocommerce_receipt_' . $this->id, array(&$this, 'receipt_page'));
		add_action('woocommerce_thankyou_' . $this->id, array(&$this, 'thankyou_page'));
	}

	/**
	 * Set the Admin Panel options
	 *
	 * @return void
	 */
	public function init_form_fields()
	{
		$this->form_fields = array(
			'enabled' => array(
				'title'         => __('Enable/Disable', 'woocommerce_emerchantpay'),
				'type'          => 'checkbox',
				'label'         => __('Enable eMerchantPay plugin', 'woocommerce_emerchantpay'),
				'default'       => 'no'
			),
			'title' => array(
				'title'         => __('Title:', 'woocommerce_emerchantpay'),
				'type'          => 'text',
				'description'   => __('This controls the title which the user sees during checkout.', 'woocommerce_emerchantpay'),
				'default'       => __('eMerchantPay', 'woocommerce_emerchantpay')
			),
			'description' => array(
				'title'         => __('Description:', 'woocommerce_emerchantpay'),
				'type'          => 'textarea',
				'description'   => __('This controls the description which the user sees during checkout.', 'woocommerce_emerchantpay'),
				'default'       => __('Pay securely by Credit, Debit card, Alternative Methods through eMerchantPay.', 'woocommerce_emerchantpay')
			),
			'username' => array(
				'title'         => __('Username', 'woocommerce_emerchantpay'),
				'type'          => 'text',
				'description'   => __('Enter your Genesis username.')
			),
			'password' => array(
				'title'         => __('Password', 'woocommerce_emerchantpay'),
				'type'          => 'text',
				'description'   =>  __('Enter your Genesis password.', 'woocommerce_emerchantpay'),
			),
			'token' => array(
				'title'         => __('Token', 'woocommerce_emerchantpay'),
				'type'          => 'text',
				'description'   =>  __('Enter your Genesis Token', 'woocommerce_emerchantpay'),
			),
			'environment' => array(
				'title'         => __('Gateway Environment', 'woocommerce_emerchantpay'),
				'type'          => 'select',
				'options'       => array(
					'sandbox'       =>__('Sandbox', 'woocommerce_emerchantpay'),
					'production'    =>__('Production', 'woocommerce_emerchantpay')
				),
				'description'   =>  __('Sandbox - test mode, no money are being transferred | Production - live production environment. (Consult the Documentation for more information)')
			),
			'transaction_types' => array(
				'title'         => __('Auth/Sale', 'woocommerce_emerchantpay'),
				'type'          => 'select',
				'options'       => array(
					'sale'   => __('Sale', 'woocommerce_emerchantpay'),
					'sale3d' => __('Sale 3D', 'woocommerce_emerchantpay')
				),
				'description'   =>  __('Sale - Sale transaction type | Sale3D - Sale transaction type with 3D authentication. (Consult the Documentation for more information)')
			),
			'processing_mode' => array(
				'title'         => __('Processing Mode', 'woocommerce_emerchantpay'),
				'type'          => 'select',
				'options'       => array(
					'1' => __('iframe mode', 'woocommerce_emerchantpay'),
					'2' => __('Button mode', 'woocommerce_emerchantpay'),
				),
				'description'   =>  __('What mode of operation do you prefer: iframe - load the Payment Form as iframe | Button - set up a link and let the user decide when to start the Payment session', 'woocommerce_emerchantpay'),
				'default'       => '1',
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

	/**
	 * Process the Payment
	 *
	 * @param int $order_id
	 *
	 * @return array check result, url
	 */
	public function process_payment($order_id)
	{
		$order = new WC_Order($order_id);

		return array(
			'result'    => 'success',
			'redirect'  => $order->get_checkout_payment_url( true )
		);
	}

	/*
	function process_payment($order_id){
		$order = new WC_Order($order_id);
		return array('result' => 'success',
					'redirect' => add_query_arg
					('order',
						$order->id, add_query_arg('key',
						$order->order_key,
						get_permalink(get_option('woocommerce_pay_page_id'))))

		);
	}
	*/

	/**
	 * Generate HTML Payment form
	 *
	 * @param $order_id
	 *
	 * @return string HTML form
	 */
	private function receipt_page($order_id)
	{
		$order = new WC_Order( $order_id );

		$redirect_url = ( $this->redirect_page_id == "" || $this->redirect_page_id == 0 ) ? get_site_url() . "/" : get_permalink( $this->redirect_page_id );

		//For wooCoomerce 2.0
		$redirect_url = add_query_arg( 'wc-api', get_class( $this ), $redirect_url );

		$order_id = $order_id . '_' . date( "ymds" ) . '_' . mt_rand(42,1337);

		GenesisConf::setToken( $this->settings['token'] );
		GenesisConf::setUsername( $this->settings['username'] );
		GenesisConf::setPassword( $this->settings['password'] );
		GenesisConf::setEnvironment( $this->settings['environment'] );

		$genesis = new Genesis( 'WPF\Create' );

		$genesis->request()
		        ->setTransactionId( $order_id )
		        ->setCurrency( get_option( 'woocommerce_currency' ) )
		        ->setAmount( $order->order_total )
		        ->setUsage( 'TEST' )
		        ->setDescription( 'TEST' )
		        ->setCustomerEmail( $order->billing_email )
		        ->setCustomerPhone( $order->billing_phone )
		        ->setNotificationUrl( $redirect_url )
		        ->setReturnSuccessUrl( $redirect_url )
		        ->setReturnFailureUrl( $redirect_url )
		        ->setReturnCancelUrl( $redirect_url )
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
		}

		if ( isset( $response->status) && (string)$response->status == 'error') {
			die('ERROR: ' . $response->message);
		}

		//$target_url = 'https://payment-9e5.emerchantpay.com/payment/form/post?PS_SIGNATURE=72c0d3958272ef3fe358a3c99d9f81b5&PS_EXPIRETIME=1413447098&PS_SIGTYPE=PSMD5&approval_url=http%3A%2F%2Fgenesis.woo%2F%3Fwc-api%3DWC_Gateway_emp_ecom&client_id=548663&credit_card_trans_type=sale&customer_address=1st+Street&customer_address2=&customer_city=London&customer_company=&customer_country=GB&customer_email=admin%40local.host&customer_first_name=John&customer_last_name=Doe&customer_phone=%2B4422030201&customer_postcode=W12+7TS&customer_state=&decline_url=http%3A%2F%2Fgenesis.woo%2F%3Fwc-api%3DWC_Gateway_emp_ecom&form_id=4574&item_1_digital=1&item_1_name=20_14101538&item_1_unit_price_GBP=14.97&order_currency=GBP&order_reference=20_14101538&shipping_address=1st+Street&shipping_address2=&shipping_city=London&shipping_company=&shipping_country=GB&shipping_first_name=John&shipping_last_name=Doe&shipping_phone=%2B4422030201&shipping_postcode=W12+7TS&shipping_state=&test_transaction=1&transtype=';

		// Modes
		switch ( $this->iframe_mode ) {
			case 1:
				printf( '<iframe src="%s" frameborder="0" scrolling="true" width="800" height="600" ></iframe>', $target_url );
				break;
			case 2:
				printf( '', '');
				break;
			case 3:
				printf( '<script language="javascript">setTimeout(function(){location.replace("%s")}, 3000);</script>', $target_url );
				break;
		}
	}

	/**
	 * Check Gateway response and update order status
	 *
	 * @return void
	 */
	private function check_genesis_response()
	{
		// TODO - FIX
		global $woocommerce;

		$authenticatedParam = ParamSigner::paramAuthenticate($_GET, $this -> secret_key);
		if(!$authenticatedParam)
		{
			die("Data tampering detected or offer expired.");
		}
		else
		{

			if(isset($_REQUEST['order_reference']) && isset($_REQUEST['notification_type']) ){


				$redirect_url = ($this -> redirect_page_id=="" || $this -> redirect_page_id==0)?get_site_url() . "/":get_permalink($this -> redirect_page_id);
				$order_id_time = $_REQUEST['order_reference'];

				$order_id = explode('_', $_REQUEST['order_reference']);
				$order_id = (int)$order_id[0];
				$this -> msg['class'] = 'error';

				if($order_id != ''){

					$order = new WC_Order($order_id);

					$AuthDesc = $_REQUEST['notification_type'];

					if($order -> status !=='completed'){


						if($AuthDesc=="order"){

							$order -> payment_complete();
							$woocommerce -> cart -> empty_cart();
						}

						if($AuthDesc=="orderdeclined"){

							$order -> update_status('Failed');
							$this -> msg['message'] = "Thank you for shopping with us. However, the transaction has been declined.";
							$this -> msg['class'] = 'error';

						}

					}

				}
				$redirect_url = ($this -> redirect_page_id=="" || $this -> redirect_page_id==0)?get_site_url() . "/":get_permalink($this -> redirect_page_id);

				// For wooCoomerce 2.0
				$redirect_url = add_query_arg( array('msg'=> urlencode($this -> msg['message']), 'type'=>$this -> msg['class']), $redirect_url );

				wp_redirect( $redirect_url );

				exit;
			}
		}
	}


	/**
	 * Generate HTML Payment form
	 *
	 * @param $order_id
	 *
	 * @return string HTML form
	 */
	/*
	public function generate_genesis_form($order_id)
	{
		$order = new WC_Order( $order_id );

		$redirect_url = ( $this->redirect_page_id == "" || $this->redirect_page_id == 0 ) ? get_site_url() . "/" : get_permalink( $this->redirect_page_id );

		//For wooCoomerce 2.0
		$redirect_url = add_query_arg( 'wc-api', get_class( $this ), $redirect_url );

		$order_id = $order_id . '_' . date( "ymds" ) . '_' . mt_rand(42,1337);

		GenesisConf::setToken( $this->settings['token'] );
		GenesisConf::setUsername( $this->settings['username'] );
		GenesisConf::setPassword( $this->settings['password'] );
		GenesisConf::setEnvironment( $this->settings['environment'] );

		$genesis = new Genesis( 'WPF\Create' );

		$genesis->request()
		        ->setTransactionId( $order_id )
		        ->setCurrency( get_option( 'woocommerce_currency' ) )
		        ->setAmount( $order->order_total )
		        ->setUsage( 'TEST' )
		        ->setDescription( 'TEST' )
		        ->setCustomerEmail( $order->billing_email )
		        ->setCustomerPhone( $order->billing_phone )
		        ->setNotificationUrl( $redirect_url )
		        ->setReturnSuccessUrl( $redirect_url )
		        ->setReturnFailureUrl( $redirect_url )
		        ->setReturnCancelUrl( $redirect_url )
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
		}

		if ( isset( $response->status) && (string)$response->status == 'error') {
			die('ERROR: ' . $response->message);
		}

		//$target_url = 'https://payment-9e5.emerchantpay.com/payment/form/post?PS_SIGNATURE=72c0d3958272ef3fe358a3c99d9f81b5&PS_EXPIRETIME=1413447098&PS_SIGTYPE=PSMD5&approval_url=http%3A%2F%2Fgenesis.woo%2F%3Fwc-api%3DWC_Gateway_emp_ecom&client_id=548663&credit_card_trans_type=sale&customer_address=1st+Street&customer_address2=&customer_city=London&customer_company=&customer_country=GB&customer_email=admin%40local.host&customer_first_name=John&customer_last_name=Doe&customer_phone=%2B4422030201&customer_postcode=W12+7TS&customer_state=&decline_url=http%3A%2F%2Fgenesis.woo%2F%3Fwc-api%3DWC_Gateway_emp_ecom&form_id=4574&item_1_digital=1&item_1_name=20_14101538&item_1_unit_price_GBP=14.97&order_currency=GBP&order_reference=20_14101538&shipping_address=1st+Street&shipping_address2=&shipping_city=London&shipping_company=&shipping_country=GB&shipping_first_name=John&shipping_last_name=Doe&shipping_phone=%2B4422030201&shipping_postcode=W12+7TS&shipping_state=&test_transaction=1&transtype=';

		// Modes
		switch ( $this->iframe_mode ) {
			case 1:
				return sprintf( '<iframe src="%s" frameborder="0" scrolling="true" width="800" height="600" ></iframe>', $target_url );
				break;
			case 2:
				return sprintf( '<script language="javascript">setTimeout(function(){location.replace("%s")}, 3000);</script>', $target_url );
				break;
		}

	}
	*/

	/*
   function get_pages($title = false, $indent = true) {

		echo '<script type="text/javascript">
				if (top.location.href != self.location.href)
				top.location.href = self.location.href;
			  </script>';

		$wp_pages = get_pages('sort_column=menu_order');
		$page_list = array();
		if ($title) $page_list[] = $title;
		foreach ($wp_pages as $page) {
			$prefix = '';
			// show indented child pages?
			if ($indent) {
				$has_parent = $page->post_parent;
				while($has_parent) {
					$prefix .=  ' - ';
					$next_page = get_page($has_parent);
					$has_parent = $next_page->post_parent;
				}
			}
			// add to page list array array
			$page_list[$page->ID] = $prefix . $page->post_title;
		}
		return $page_list;
	}
	*/
}