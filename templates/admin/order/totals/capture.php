<?php
/*
 * Copyright (C) 2016 eMerchantPay Ltd.
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
 * @author      eMerchantPay Ltd.
 * @copyright   2016 eMerchantPay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$captured_amount = WC_eMerchantPay_Helper::getOrderAmountMetaData(
    $order->id,
    $payment_method::META_CAPTURED_AMOUNT
);
?>
<tr>
    <td class="label captured-total"><?php echo $payment_method::getTranslatedText('Captured'); ?>:</td>
    <?php if (WC_eMerchantPay_Helper::getIsWooCommerceVersion('2.6', '>=')) { ?>
        <td width="1%"></td>
    <?php } ?>
    <td class="total captured-total">
        <div class="view"><?php echo WC_eMerchantPay_Helper::formatPrice($captured_amount, $order); ?></div>
    </td>
</tr>

<style type="text/css">
    #woocommerce-order-items .wc-order-totals .captured-total {
        color: green;
    }
</style>