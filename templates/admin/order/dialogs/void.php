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

?>

<div class="wc-order-data-row wc-order-void-items wc-order-data-row-toggle" style="display: none;">
    <table class="wc-order-totals">
        <tr class="row-capture-left">
            <td class="label"><?php echo $payment_method::getTranslatedText('Total amount to void'); ?>:</td>
            <td class="total"><?php echo WC_eMerchantPay_Helper::formatPrice( $order->get_total(), $order); ?></td>
        </tr>
        <tr>
            <td class="label"><label for="void_reason"><?php echo $payment_method::getTranslatedText('Void Text:'); ?>:</label></td>
            <td class="total">
                <input type="text" class="text" id="void_reason" name="void_reason" />
                <div class="clear"></div>
            </td>
        </tr>
    </table>
    <div class="clear"></div>
    <div class="void-actions">
        <?php
            $gateway_supports_voids    = $payment_method->supports( 'voids' );
            $gateway_name              = $payment_method->get_title();
        ?>
        <button type="button" class="button <?php echo $gateway_supports_voids ? 'button-primary do-api-void' : 'tips disabled'; ?>" <?php echo $gateway_supports_voids ? '' : 'data-tip="' . $payment_method::getTranslatedText('The payment gateway used to place this order does not support automatic voids.') . '"'; ?>><?php echo $payment_method::getTranslatedText( "Void Payment via $gateway_name"); ?></button>
        <span class="spinner"></span>
        <button type="button" class="button cancel-action"><?php _e( 'Cancel', 'woocommerce' ); ?></button>
        <div class="clear"></div>
    </div>
</div>

<script type="text/javascript">
    jQuery( function ( ) {
        jQuery( '#woocommerce-order-items' )
            .on( 'click', 'button.void-items', doShowEMerchantPayOrderVoidForm )
            .on( 'click', 'button.do-api-void', doVoidEMerchantPayOrderPayment);
    });

    function doRemoveEMerchantPayNoticesFromVoidForm() {
        var $container = jQuery( 'div.wc-order-void-items');
        $container.find('div.updated.notice').remove();
        $container.find('div.error.notice').remove();
    }

    function doShowEMerchantPayOrderVoidForm() {
        if (jQuery.exists('div.wc-order-void-items', '.wc-order-bulk-actions')) {
            var $voidFormDestParentContainer = jQuery( 'div.wc-order-refund-items').parent();
            var $voidForm = jQuery('.wc-order-bulk-actions').find('div.wc-order-void-items');
            $voidForm.appendTo($voidFormDestParentContainer);
        } else {
            doRemoveEMerchantPayNoticesFromVoidForm();
        }

        jQuery('#void_reason').val('');

        jQuery('div.wc-order-void-items').slideDown();

        jQuery('div.wc-order-bulk-actions').slideUp();
        jQuery('div.wc-order-totals-items').slideUp();
        jQuery('.wc-order-edit-line-item .wc-order-edit-line-item-actions').hide();

        return false;
    }

    function doVoidEMerchantPayOrderPayment() {
        if (!window.confirm( 'Are you sure you wish to do online void through <?php echo $payment_method->get_title();?> Payment Gateway?' ) ) {
            return false;
        }

        $senderButton = jQuery(this);
        showHideEMerchantPayAjaxLoader($senderButton, true);

        var void_text = jQuery('input#void_reason').val();

        var data = {
            action: '<?php echo $payment_method->id;?>_void',
            order_id: woocommerce_admin_meta_boxes.post_id,
            void_reason: void_text,
            api_void: jQuery(this).is('.do-api-void'),
            security: woocommerce_admin_meta_boxes.order_item_nonce
        };

        jQuery.post(woocommerce_admin_meta_boxes.ajax_url, data, function (response) {
            doRemoveEMerchantPayNoticesFromVoidForm();

            if (response.success === true) {
                if (response.data.gateway.message) {
                    var $successNotice = doCreateEMerchantPayNotice(
                        response.data.gateway.message,
                        'success',
                        'div.wc-order-void-items',
                        true
                    );

                    if ($successNotice !== false) {
                        $successNotice.slideDown('slow', function () {
                            setTimeout(function() { window.location.reload();}, 3000);
                        });
                    }
                }
            } else {
                var $errorNotice = doCreateEMerchantPayNotice(
                    response.data.error,
                    'error',
                    'div.wc-order-void-items',
                    true
                );

                if ($errorNotice !== false) {
                    $errorNotice.slideDown('slow');
                }
            }
            showHideEMerchantPayAjaxLoader($senderButton, false);
        });
    }
</script>

<style type="text/css">
    #woocommerce-order-items .void-actions .button {
        float: right;
        margin-left: 4px;
    }

    #woocommerce-order-items .void-actions .cancel-action {
        float: left;
        margin-left: 0;
    }
</style>