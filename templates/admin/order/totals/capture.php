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
 * @copyright   2018-2024 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 * @package     templates/admin/order/totals/capture
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<tr>
	<td class="label captured-total"><?php echo esc_html( $payment_method::get_translated_text( 'Captured' ) ); ?>:</td>
	<?php if ( WC_Emerchantpay_Helper::is_woocommerce_version( '2.6', '>=' ) ) { ?>
		<td width="1%"></td>
	<?php } ?>
	<td class="total captured-total">
		<div class="view">
			<?php
			echo WC_Emerchantpay_Order_Helper::format_price( $captured_amount, $order ); // phpcs:ignore
			?>
		</div>
	</td>
</tr>

<style type="text/css">
	#woocommerce-order-items .wc-order-totals .captured-total {
		color: green;
	}
</style>
