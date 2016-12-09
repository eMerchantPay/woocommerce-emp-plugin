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
<script type="text/javascript">
    jQuery( function ( ) {
        jQuery.exists = function (selector, container) {
            var $element = null;

            if (typeof container !== undefined && container != null) {
                $element = jQuery(container).find(selector);
            } else {
                $element = jQuery(selector)
            }

            return ($element.length > 0);
        };

        <?php if (!$is_refund_allowed) { ?>
            jQuery( '#woocommerce-order-items' ).find( 'button.refund-items').hide();
        <?php } ?>
    });

    function doCreateEMerchantPayNotice(message, type, containerSelector, prepend) {
        var noticeClasses = {
            'success' : 'updated notice',
            'error'   : 'error notice'
        };

        var $notice = jQuery('<div></div>')
            .attr('class', noticeClasses[type])
            .css({'display': 'none', 'text-align': 'left'})
            .text(message);


        if (jQuery.exists(containerSelector)) {
            $container = jQuery(containerSelector);
            if (prepend === true) {
                $notice.prependTo($container);
            } else {
                $notice.appendTo($container);
            }

            return $notice;
        } else {
            return false;
        }
    }

    function showHideEMerchantPayAjaxLoader(senderButton, shouldShow) {
        var $container = jQuery(senderButton).parent();
        var $spinner = $container.find('.spinner');

        if (shouldShow === true) {
            jQuery(senderButton).hide();
            $spinner.addClass('is-active');
        } else {
            $spinner.removeClass('is-active');
            jQuery(senderButton).slideDown();
        }
    }
</script>