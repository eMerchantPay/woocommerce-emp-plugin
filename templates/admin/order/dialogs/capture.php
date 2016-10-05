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

$captured_amount = WC_eMerchantPay_Helper::getOrderAmountMetaData($order->id, $payment_method::META_CAPTURED_AMOUNT);

?>
<div class="wc-order-data-row wc-order-capture-items wc-order-data-row-toggle" style="display: none;">
    <table class="wc-order-totals">
        <tr class="row-already-captured">
            <td class="label"><?php echo $payment_method::getTranslatedText('Amount already captured'); ?>:</td>
            <td class="total">-<?php echo WC_eMerchantPay_Helper::formatPrice($captured_amount, $order); ?></td>
        </tr>
        <tr class="row-capture-left">
            <td class="label"><?php echo $payment_method::getTranslatedText('Total available to capture'); ?>:</td>
            <td class="total"><?php echo WC_eMerchantPay_Helper::formatPrice( $order->get_total() - $captured_amount, $order); ?></td>
        </tr>
        <tr>
            <td class="label"><label for="capture_amount"><?php echo $payment_method::getTranslatedText('Capture amount'); ?>:</label></td>
            <td class="total">
                <input type="text" id="capture_amount" name="capture_amount" class="wc_input_price" data-max-value="<?php echo $order->get_total() - $captured_amount;?>" />
                <div class="clear"></div>
            </td>
        </tr>
        <tr>
            <td class="label"><label for="capture_reason"><?php echo $payment_method::getTranslatedText('Capture Text:'); ?>:</label></td>
            <td class="total">
                <input type="text" class="text" id="capture_reason" name="capture_reason" />
                <div class="clear"></div>
            </td>
        </tr>
    </table>
    <div class="clear"></div>
    <div class="capture-actions">
        <?php
            $capture_amount            = '<span class="wc-order-capture-amount">' . WC_eMerchantPay_Helper::formatPrice(0, $order) . '</span>';
            $gateway_supports_captures = $payment_method->supports( 'captures' );
            $gateway_name              = $payment_method->get_title();
        ?>
        <button type="button" class="button <?php echo $gateway_supports_captures ? 'button-primary do-api-capture' : 'tips disabled'; ?>" <?php echo $gateway_supports_captures ? '' : 'data-tip="' . $payment_method::getTranslatedText('The payment gateway used to place this order does not support automatic captures.') . '"'; ?>><?php printf( _x( 'Capture %s via %s', 'Capture $amount', $payment_method::$LANG_DOMAIN ), $capture_amount, $gateway_name ); ?></button>
        <span class="spinner"></span>
        <button type="button" class="button cancel-action"><?php _e( 'Cancel', 'woocommerce' ); ?></button>
        <div class="clear"></div>
    </div>
</div>

<script type="text/javascript">
    jQuery( function ( ) {
        jQuery( '#woocommerce-order-items' )
            .on( 'click', 'button.capture-items', doShowEMerchantPayOrderCaptureForm )
            .on( 'click', 'button.do-api-capture', doCaptureEMerchantPayOrderPaymentAmount)
            .on( 'change keyup', '.wc-order-capture-items #capture_amount', doOnChangeEMerchantPayOrderPaymentCaptureAmount );
    });

    function doRemoveEMerchantPayNoticesFromCaptureForm() {
        var $container = jQuery( 'div.wc-order-capture-items');
        $container.find('div.updated.notice').remove();
        $container.find('div.error.notice').remove();
    }

    function doShowEMerchantPayOrderCaptureForm() {
        if (jQuery.exists('div.wc-order-capture-items', '.wc-order-bulk-actions')) {
            var $captureFormDestParentContainer = jQuery( 'div.wc-order-refund-items').parent();
            var $captureForm = jQuery('.wc-order-bulk-actions').find('div.wc-order-capture-items');
            $captureForm.appendTo($captureFormDestParentContainer);
        } else {
            doRemoveEMerchantPayNoticesFromCaptureForm();
        }

        jQuery('#capture_reason').val('');

        var $captureAmountControl = jQuery('#capture_amount');
        $captureAmountControl
            .val($captureAmountControl.data('max-value'))
            .trigger('keyup');

        jQuery('div.wc-order-capture-items').slideDown();

        jQuery('div.wc-order-bulk-actions').slideUp();
        jQuery('div.wc-order-totals-items').slideUp();
        jQuery('.wc-order-edit-line-item .wc-order-edit-line-item-actions').hide();

        return false;
    }

    function doCaptureEMerchantPayOrderPaymentAmount() {
        if (!window.confirm( 'Are you sure you wish to do online capture through <?php echo $payment_method->get_title();?> Payment Gateway?' ) ) {
            return false;
        }

        $senderButton = jQuery(this);
        showHideEMerchantPayAjaxLoader($senderButton, true);

        var capture_amount = jQuery('input#capture_amount').val();
        var capture_text = jQuery('input#capture_reason').val();

        var data = {
            action: '<?php echo $payment_method->id;?>_capture',
            order_id: woocommerce_admin_meta_boxes.post_id,
            capture_amount: capture_amount,
            capture_reason: capture_text,
            api_capture: jQuery(this).is('.do-api-capture'),
            security: woocommerce_admin_meta_boxes.order_item_nonce
        };

        jQuery.post(woocommerce_admin_meta_boxes.ajax_url, data, function (response) {
            doRemoveEMerchantPayNoticesFromCaptureForm();

            if (response.success === true) {
                if (response.data.gateway.message) {
                    var $successNotice = doCreateEMerchantPayNotice(
                        response.data.gateway.message,
                        'success',
                        'div.wc-order-capture-items',
                        true
                    );

                    if ($successNotice !== false) {
                        $successNotice.slideDown('slow', function () {
                            var $orderTotalsTable = jQuery('div.wc-order-capture-items').find('table.wc-order-totals');
                            $orderTotalsTable
                                .find('tr.row-already-captured td.total span.amount').html(
                                    response.data.form.capture.total.formatted
                                );
                            $orderTotalsTable
                                .find('tr.row-capture-left td.total span.amount').html(
                                    response.data.form.capture.total_available.formatted
                                );

                            jQuery('div.wc-order-totals-items')
                                .find('table.wc-order-totals tr td.total.captured-total span.amount').html(
                                    response.data.form.capture.total.formatted
                                );

                            jQuery('#capture_amount')
                                .data('max-value', response.data.form.capture.total_available.amount)
                                .val(response.data.form.capture.total_available.amount)
                                .trigger('keyup');

                            setTimeout(function() { window.location.reload();}, 3000);
                        });
                    }
                }
            } else {
                var $errorNotice = doCreateEMerchantPayNotice(
                    response.data.error,
                    'error',
                    'div.wc-order-capture-items',
                    true
                );

                if ($errorNotice !== false) {
                    $errorNotice.slideDown('slow');
                }
            }
            showHideEMerchantPayAjaxLoader($senderButton, false);
        });
    }

    function doOnChangeEMerchantPayOrderPaymentCaptureAmount() {
        var total = accounting.unformat( jQuery( this ).val(), woocommerce_admin.mon_decimal_point );

        jQuery( 'button .wc-order-capture-amount .amount' ).text( accounting.formatMoney( total, {
            symbol:    woocommerce_admin_meta_boxes.currency_format_symbol,
            decimal:   woocommerce_admin_meta_boxes.currency_format_decimal_sep,
            thousand:  woocommerce_admin_meta_boxes.currency_format_thousand_sep,
            precision: woocommerce_admin_meta_boxes.currency_format_num_decimals,
            format:    woocommerce_admin_meta_boxes.currency_format
        } ) );
    }
</script>

<style type="text/css">
    #woocommerce-order-items .capture-actions .button {
        float: right;
        margin-left: 4px;
    }

    #woocommerce-order-items .capture-actions .cancel-action {
        float: left;
        margin-left: 0;
    }
</style>