<?php
/**
 * Copyright (C) 2018-2025 emerchantpay Ltd.
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
 * @copyright   2018-2025 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 * @package     templates/admin/order/transactions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<tr>
	<td colspan="3">
		<section class="box bootstrap">
			<h3 class="text-left">
				<img src="<?php echo esc_attr( plugin_dir_url( realpath( __DIR__ . '/../../' ) ) ) . 'assets/images/logo.png'; ?>" alt="eMp" class="emp-logo" />
				<span><?php echo esc_html( $payment_method::get_translated_text( 'emerchantpay Transactions' ) ); ?></span>
			</h3>

			<table class="table table-hover tree">
				<thead class="thead-default">
				<tr>
					<th><?php echo esc_html( $payment_method::get_translated_text( 'ID' ) ); ?></th>
					<th><?php echo esc_html( $payment_method::get_translated_text( 'Type' ) ); ?></th>
					<th><?php echo esc_html( $payment_method::get_translated_text( 'Date' ) ); ?></th>
					<th><?php echo esc_html( $payment_method::get_translated_text( 'Amount' ) ); ?></th>
					<th><?php echo esc_html( $payment_method::get_translated_text( 'Status' ) ); ?></th>
					<th class="slim-message"><?php echo esc_html( $payment_method::get_translated_text( 'Message' ) ); ?></th>
					<th><?php echo esc_html( $payment_method::get_translated_text( 'Capture' ) ); ?></th>
					<th><?php echo esc_html( $payment_method::get_translated_text( 'Refund' ) ); ?></th>
					<th><?php echo esc_html( $payment_method::get_translated_text( 'Cancel' ) ); ?></th>
				</tr>
				</thead>
				<tbody>
				<?php foreach ( $transactions as $transaction ) : ?>
				<tr class="treegrid-<?php echo esc_attr( $transaction['unique_id'] ); ?> <?php echo $transaction['parent_id'] ? 'treegrid-parent-' . esc_attr( $transaction['parent_id'] ) : ''; ?>">
					<td class="text-left"><?php echo esc_html( $transaction['unique_id'] ); ?></td>
					<td class="text-left"><?php echo esc_html( $transaction['type'] ); ?></td>
					<td class="text-left"><?php echo esc_html( $transaction['date_add'] ); ?></td>
					<td class="text-right"><?php echo esc_html( $transaction['amount'] ); ?></td>
					<td class="text-left"><?php echo esc_html( $transaction['status'] ); ?></td>
					<td class="text-left"><?php echo esc_html( $transaction['message'] ); ?></td>
					<td class="text-center">
						<?php if ( $transaction['can_capture'] ) : ?>
						<div class="transaction-action-button">
							<a class="button btn-transaction button-success button-capture button" role="button" data-type="capture" data-id-unique="<?php echo esc_html( $transaction['unique_id'] ); ?>" data-amount="<?php echo esc_attr( $transaction['available_amount'] ); ?>">
							<i class="dashicons dashicons-yes"></i>
							</a>
						</div>
						<?php endif; ?>
					</td>
					<td class="text-center">
						<?php if ( $transaction['can_refund'] ) : ?>
						<div class="transaction-action-button">
							<a class="button btn-transaction button-warning button-refund button" role="button" data-type="refund" data-id-unique="<?php echo esc_attr( $transaction['unique_id'] ); ?>" data-amount="<?php echo esc_attr( $transaction['available_amount'] ); ?>">
							<i class="dashicons dashicons-undo"></i>
							</a>
						</div>
						<?php endif; ?>
					</td>
					<td class="text-center">
						<?php if ( $transaction['can_void'] ) : ?>
						<div class="transaction-action-button">
							<a class="button btn-transaction button-danger button-void button" role="button" data-type="void" data-id-unique="<?php echo esc_attr( $transaction['unique_id'] ); ?>" data-amount="0">
							<i class="dashicons dashicons-no"></i>
							</a>
						</div>
						<?php endif; ?>
					</td>
				</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</section>

		<div id="emerchantpay-modal" class="modal">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
							<i class="icon-times"></i>
						</button>
						<img src="<?php echo esc_attr( plugin_dir_url( realpath( __DIR__ . '/../../' ) ) ) . 'assets/images/logo.png'; ?>" style="width:16px;" />
						<h3 class="emerchantpay-modal-title" style="margin:0;display:inline-block;"></h3>
					</div>
					<div class="modal-body">
						<form id="<?php echo esc_attr( $payment_method->id ); ?>-modal-form" class="form modal-form" action="" method="post">
							<div id="emerchantpay_cancel_trans_warning" class="row" style="display: none;">
								<div class="col-xs-12">
									<div class="alert alert-warning">
										<?php echo esc_html( $payment_method::get_translated_text( 'This service is only available for particular acquirers!' ) ); ?>
										<br/>
										<?php echo esc_html( $payment_method::get_translated_text( 'For further Information please contact your Account Manager.' ) ); ?>
									</div>
								</div>
							</div>

							<div class="form-group amount-input">
								<label for="emerchantpay_transaction_amount"><?php echo esc_html( $payment_method::get_translated_text( 'Amount' ) ); ?></label>
								<div class="input-group">
									<span class="input-group-addon" data-toggle="emerchantpay-tooltip" data-placement="top" title="<?php echo esc_attr( $order_currency ); ?>"><?php echo esc_html( get_woocommerce_currency_symbol( $order_currency ) ); ?></span>
									<input type="text" class="form-control" id="emerchantpay_transaction_amount" name="emerchantpay_transaction_amount" placeholder="<?php echo esc_attr( $payment_method::get_translated_text( 'Amount' ) ); ?>" value="<?php echo esc_attr( $order->get_total() ); ?>" />
								</div>
								<span class="help-block" id="emerchantpay-amount-error-container"></span>
							</div>

							<div class="form-group usage-input">
								<label for="emerchantpay_transaction_usage"><?php echo esc_html( $payment_method::get_translated_text( 'Message (optional):' ) ); ?></label>
								<textarea class="form-control form-message" rows="3" id="emerchantpay_transaction_usage" name="emerchantpay_transaction_usage" placeholder="<?php echo esc_attr( $payment_method::get_translated_text( 'Message' ) ); ?>"></textarea>
							</div>
						</form>
						<div style="text-align: center">
							<div id="emerchantpay-modal-spinner" class="spinner"></div>
						</div>
					</div>
					<div class="modal-footer">
						<div class="form-group">
							<button id="emerchantpay-modal-close" class="btn btn-default" data-dismiss="modal" aria-hidden="true"><?php echo esc_html( $payment_method::get_translated_text( 'Close' ) ); ?></button>
							<button
									id="emerchantpay-modal-submit"
									class="btn btn-submit btn-primary btn-capture"
									value="partial"
									type="button"
									data-payment_type="<?php echo esc_attr( $payment_method->id ); ?>"
									data-payment_title="<?php echo esc_attr( $payment_method->get_title() ); ?>"
							><?php echo esc_html( $payment_method::get_translated_text( 'Submit' ) ); ?></button>
						</div>
					</div>
				</div>
			</div>
		</div>

		<script type="text/javascript">
			var modalPopupDecimalValueFormatConsts = {
				decimalPlaces     : <?php echo wp_json_encode( wc_get_price_decimals() ); ?>,
				decimalSeparator  : <?php echo wp_json_encode( wc_get_price_decimal_separator() ); ?>,
				thousandSeparator : <?php echo wp_json_encode( wc_get_price_thousand_separator() ); ?>
			},
			allowPartialCapture = <?php echo wp_json_encode( $allow_partial_capture ); ?>,
			allowPartialRefund  = <?php echo wp_json_encode( $allow_partial_refund ); ?>,
			original_order_id   = <?php echo (int) $order->get_id(); ?>;
		</script>
	</td>
</tr>
