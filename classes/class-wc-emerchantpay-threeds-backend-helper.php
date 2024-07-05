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
 * @copyright   2018-2022 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 * @package     classes\class-wc-emerchantpay-threeds-backend-helper
 */

use Genesis\API\Constants\DateTimeFormat;
use Genesis\API\Constants\Transaction\States;
use Genesis\API\Request\Financial\Cards\Threeds\V2\MethodContinue;
use Genesis\Exceptions\InvalidArgument;
use Genesis\Exceptions\InvalidClassMethod;
use Genesis\Exceptions\InvalidResponse;
use Genesis\Genesis;

if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

if ( ! class_exists( 'WC_Emerchantpay_Threeds_Base' ) ) {
	require_once __DIR__ . '/class-wc-emerchantpay-threeds-base.php';
}

/**
 * Emerchantpay 3DS v2 Backend Helper Class
 *
 * @class   WC_Emerchantpay_Threeds_Backend_Helper
 */
class WC_Emerchantpay_Threeds_Backend_Helper extends WC_Emerchantpay_Threeds_Base {

	const META_DATA_ORDER_STATUS = 'emp_order_status';

	/**
	 * Handles callback from Genesis
	 *
	 * @suppressWarnings(PHPMD.Superglobals)
	 * @suppressWarnings(PHPMD.ExitExpression)
	 * @return void|null
	 */
	public function callback_handler() {
		// TODO: Processing form data without nonce verification.
		// TODO: Fix Superglobals
		// phpcs:ignore WordPress.Security.NonceVerification
		$order_id = sanitize_text_field( wp_unslash( $_GET['order_id'] ?? null ) );
		$order    = WC_Emerchantpay_Order_Helper::get_order_by_id( $order_id );
		if ( ! WC_Emerchantpay_Order_Helper::is_valid_order( $order ) ) {
			wp_die( 'Invalid order!' );
		}
		// TODO Processing form data without nonce verification.
		// phpcs:ignore WordPress.Security.NonceVerification
		$status = sanitize_text_field( wp_unslash( $_POST['threeds_method_status'] ?? null ) );
		WC_Emerchantpay_Order_Helper::set_order_meta_data( $order_id, self::META_DATA_ORDER_STATUS, $status );

		exit;
	}

	/**
	 * Check callback status. Used with ajax request.
	 *
	 * @return void
	 */
	public function status_checker() {
		ob_start();

		$args = $this->get_data_from_url();

		if ( ! isset( $args['response_obj'] ) ) {
			wp_die( 'Missing data!' );
		}

		wp_send_json(
			array(
				'status' => WC_Emerchantpay_Order_Helper::get_order_meta_data( $args['order_id'], self::META_DATA_ORDER_STATUS ),
			)
		);
	}

	/**
	 * Makes PUT request to the Gateway
	 *
	 * @return void
	 *
	 * @throws InvalidArgument When the passed argument is not an array, a string or null.
	 * @throws InvalidClassMethod When attempting to call a method on an object that doesn't exist or isn't accessible within the current context.
	 * @throws InvalidResponse When the response is not valid.
	 */
	public function method_continue_handler() {
		ob_start();

		$args = $this->get_data_from_url();

		if ( ! isset( $args['response_obj'] ) ) {
			wp_die( 'Missing data!' );
		}

		$order           = WC_Emerchantpay_Order_Helper::get_order_by_id( $args['order_id'] );
		$payment_gateway = WC_Emerchantpay_Order_Helper::get_payment_method_instance_by_order( $order );

		$payment_gateway->set_credentials();

		$response_obj = $args['response_obj'];
		$date_add     = $response_obj->date_add;
		$timestamp    = date_create_from_format( 'U', $date_add );
		$return_url   = $args['response_obj']->return_failure_url;

		$genesis = new Genesis( 'Financial\Cards\Threeds\V2\MethodContinue' );

		/**
		 * Create instans of method continue class
		 *
		 * @var MethodContinue $request Method continue instance.
		 */
		$request = $genesis->request();

		try {
			$request
				->setAmount( $response_obj->amount )
				->setCurrency( $response_obj->currency )
				->setTransactionUniqueId( $response_obj->unique_id )
				->setTransactionTimestamp( $timestamp->format( DateTimeFormat::YYYY_MM_DD_H_I_S_ZULU ) );

			$genesis->execute();

			$result = $genesis->response()->getResponseObject();

			if ( in_array( $result->status, array( States::APPROVED, States::PENDING_ASYNC ), true ) ) {
				$return_url = $args['response_obj']->return_success_url;

				if ( property_exists( $result, 'redirect_url' ) ) {
					$return_url = $result->redirect_url;
				}
			}
		} catch ( \Exception $exception ) {
			WC_Emerchantpay_Helper::log_exception( $exception->getMessage() );
		}

		wp_send_json(
			array(
				'url' => $return_url,
			)
		);
	}
}
