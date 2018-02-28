var $senderButton;
var $closeButton;
var $spinner;
var $modalInputs;

jQuery(document).ready(function() {
    jQuery('.tree').treegrid({
        expanderExpandedClass:  'dashicons dashicons-arrow-down',
        expanderCollapsedClass: 'dashicons dashicons-arrow-right'
    });

    jQuery.exists = function(selector) {
        return (jQuery(selector).length > 0);
    };

    jQuery('[data-toggle="tooltip"]').tooltip();

    jQuery.fn.bootstrapValidator.i18n.transactionAmount = jQuery.extend(jQuery.fn.bootstrapValidator.i18n.transactionAmount || {}, {
        'default': 'Please enter a valid transaction amount. (Ex. %s)'
    });

    jQuery.fn.bootstrapValidator.validators.transactionAmount = {
        html5Attributes: {
            message: 'message',
            exampleValue: 'exampleValue'
        },

        validate: function(validator, $field, options) {
            var fieldValue 	    = $field.val(),
                regexp          = /^(([0-9]*)|(([0-9]*)\.([0-9]*)))$/i,
                isValid    	    = true,
                errorMessage    = options.message || jQuery.fn.bootstrapValidator.i18n.transactionAmount['default'],
                exampleValue    = options.exampleValue || "123.45";

            errorMessage = jQuery.fn.bootstrapValidator.helpers.format(errorMessage, [exampleValue]);

            return {
                valid: regexp.test(fieldValue),
                message: errorMessage
            };
        }
    };

    jQuery('[data-toggle="emerchantpay-tooltip"]').tooltip();

    jQuery('.btn-transaction').click(function() {
        if (jQuery(this).is("[data-disabled]")) {
            return;
        }

        transactionModal(jQuery(this).attr('data-type'), jQuery(this).attr('data-id-unique'), jQuery(this).attr('data-amount'));
    });

    var modalObj = jQuery('#emerchantpay-modal'),
        transactionAmountInput = jQuery('#emerchantpay_transaction_amount', modalObj);

    jQuery('.btn-submit').click(function() {
        jQuery('#emerchantpay-modal-form').submit();
    });

    modalObj.on('hide.bs.modal', function() {
        destroyBootstrapValidator('#emerchantpay-modal-form');
    });

    modalObj.on('shown.bs.modal', function() {
        doRemoveEMerchantPayNotices();

        /* enable the submit button just in case (if the bootstrapValidator is enabled it will disable the button if necessary */
        jQuery('#emerchantpay-modal-submit').removeAttr('disabled');

        if (createBootstrapValidator('#emerchantpay-modal-form')) {
            executeBootstrapFieldValidator('#emerchantpay-modal-form', 'fieldAmount');
        }
    });

    transactionAmountInput.number(true, modalPopupDecimalValueFormatConsts.decimalPlaces,
        modalPopupDecimalValueFormatConsts.decimalSeparator,
        modalPopupDecimalValueFormatConsts.thousandSeparator);

    jQuery('#emerchantpay-modal-submit').on('click', transactionAction);

    jQuery( '#woocommerce-order-items' ).find( 'button.refund-items').remove();

    $senderButton = jQuery('#emerchantpay-modal-submit');
    $closeButton = jQuery('#emerchantpay-modal-close');
    $spinner = jQuery('#emerchantpay-modal-spinner');
    $modalInputs = jQuery('#emerchantpay-modal .form-group');
});

function transactionAction() {
    var
        submitBtn = jQuery('#emerchantpay-modal-submit'),
        paymentType = submitBtn.data('payment_type'),
        paymentTitle = submitBtn.data('payment_title'),
        transactionId = submitBtn.data('trx_id'),
        action;

    switch (submitBtn.data('trx_type')) {
        case 'capture':
            action = doCaptureEMerchantPayOrderPaymentAmount;
            break;
        case 'void':
            action = doVoidEMerchantPayOrderPayment;
            break;
        case 'refund':
            action = doRefundEMerchantPayOrderPaymentAmount;
            break;
    }

    action(
        paymentType,
        paymentTitle,
        transactionId
    );
}

function doVoidEMerchantPayOrderPayment(paymentType, paymentTitle, transactionId) {
    if (!window.confirm( 'Are you sure you wish to do online void through ' + paymentTitle + ' Payment Gateway?' ) ) {
        return false;
    }

    showHideEMerchantPayAjaxLoader(true);

    var void_text = jQuery('#emerchantpay_transaction_usage').val();

    var data = {
        action: paymentType + '_void',
        order_id: woocommerce_admin_meta_boxes.post_id,
        void_reason: void_text,
        trx_id: transactionId,
        security: woocommerce_admin_meta_boxes.order_item_nonce
    };

    jQuery.post(woocommerce_admin_meta_boxes.ajax_url, data, function (response) {
        doRemoveEMerchantPayNotices();

        if (response.success === true) {
            if (response.data.gateway.message) {
                var $successNotice = doCreateEMerchantPayNotice(
                    response.data.gateway.message,
                    'success',
                    '#emerchantpay-modal .modal-body',
                    true
                );

                if ($successNotice !== false) {
                    successfulRequest($successNotice);
                }
            }
        } else {
            var $errorNotice = doCreateEMerchantPayNotice(
                response.data.error,
                'error',
                '#emerchantpay-modal .modal-body',
                true
            );

            if ($errorNotice !== false) {
                $errorNotice.slideDown('slow');
            }
        }
        showHideEMerchantPayAjaxLoader(false);
    });
}

function doCaptureEMerchantPayOrderPaymentAmount(paymentType, paymentTitle, transactionId) {
    if (!window.confirm( 'Are you sure you wish to do online capture through ' + paymentTitle + ' Payment Gateway?' ) ) {
        return false;
    }

    showHideEMerchantPayAjaxLoader(true);

    var capture_amount = jQuery('#emerchantpay_transaction_amount').val();
    var capture_text = jQuery('#emerchantpay_transaction_usage').val();

    var data = {
        action: paymentType + '_capture',
        order_id: woocommerce_admin_meta_boxes.post_id,
        trx_id: transactionId,
        capture_amount: capture_amount,
        capture_reason: capture_text,
        security: woocommerce_admin_meta_boxes.order_item_nonce
    };

    jQuery.post(woocommerce_admin_meta_boxes.ajax_url, data, function (response) {
        doRemoveEMerchantPayNotices();

        if (response.success === true) {
            if (response.data.gateway.message) {
                var $successNotice = doCreateEMerchantPayNotice(
                    response.data.gateway.message,
                    'success',
                    '#emerchantpay-modal .modal-body',
                    true
                );

                if ($successNotice !== false) {
                    successfulRequest($successNotice);
                }
            }
        } else {
            var $errorNotice = doCreateEMerchantPayNotice(
                response.data.error,
                'error',
                '#emerchantpay-modal .modal-body',
                true
            );

            if ($errorNotice !== false) {
                $errorNotice.slideDown('slow');
            }
        }
        showHideEMerchantPayAjaxLoader(false);
    });
}

function doRefundEMerchantPayOrderPaymentAmount(paymentType, paymentTitle, transactionId) {
    if (!window.confirm( 'Are you sure you wish to do online refund through ' + paymentTitle + ' Payment Gateway?' ) ) {
        return false;
    }

    showHideEMerchantPayAjaxLoader(true);

    var amount = jQuery('#emerchantpay_transaction_amount').val();
    var reason = jQuery('#emerchantpay_transaction_usage').val();

    var data = {
        action: paymentType + '_refund',
        order_id: woocommerce_admin_meta_boxes.post_id,
        trx_id: transactionId,
        amount: amount,
        reason: reason,
        security: woocommerce_admin_meta_boxes.order_item_nonce
    };

    jQuery.post(woocommerce_admin_meta_boxes.ajax_url, data, function (response) {
        doRemoveEMerchantPayNotices();

        if (response.success === true) {
            if (response.data.gateway.message) {
                var $successNotice = doCreateEMerchantPayNotice(
                    response.data.gateway.message,
                    'success',
                    '#emerchantpay-modal .modal-body',
                    true
                );

                if ($successNotice !== false) {
                    successfulRequest($successNotice);
                }
            }
        } else {
            var $errorNotice = doCreateEMerchantPayNotice(
                response.data.error,
                'error',
                '#emerchantpay-modal .modal-body',
                true
            );

            if ($errorNotice !== false) {
                $errorNotice.slideDown('slow');
            }
        }
        showHideEMerchantPayAjaxLoader(false);
    });
}

function successfulRequest($successNotice) {
    $modalInputs.fadeOut('fast');
    $spinner.show();

    $successNotice.slideDown('slow', function () {
        setTimeout(function() { window.location.reload();}, 2000);
    });
}

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

function doRemoveEMerchantPayNotices() {
    jQuery('#emerchantpay-modal .modal-body .notice.error').remove();
}

function formatTransactionAmount(amount) {
    if ((typeof amount == 'undefined') || (amount == null))
        amount = 0;

    return jQuery.number(amount, modalPopupDecimalValueFormatConsts.decimalPlaces,
        modalPopupDecimalValueFormatConsts.decimalSeparator,
        modalPopupDecimalValueFormatConsts.thousandSeparator);
}

function destroyBootstrapValidator(submitFormId) {
    jQuery(submitFormId).bootstrapValidator('destroy');
}

function createBootstrapValidator(submitFormId) {
    var submitForm = jQuery(submitFormId),
        transactionAmount = formatTransactionAmount(jQuery('#emerchantpay_transaction_amount').val());

    destroyBootstrapValidator(submitFormId);

    var transactionAmountControlSelector = '#emerchantpay_transaction_amount';

    var shouldCreateValidator = jQuery.exists(transactionAmountControlSelector);

    /* it is not needed to create attach the bootstapValidator, when the field to validate is not visible (Void Transaction) */
    if (!shouldCreateValidator)
        return false;

    submitForm.bootstrapValidator({
        fields: {
            fieldAmount: {
                selector: transactionAmountControlSelector,
                container: '#emerchantpay-amount-error-container',
                trigger: 'keyup',
                validators: {
                    notEmpty: {
                        message: 'The transaction amount is a required field!'
                    },
                    stringLength: {
                        max: 10
                    },
                    greaterThan: {
                        value: 0,
                        inclusive: false
                    },
                    lessThan: {
                        value: transactionAmount,
                        inclusive: true
                    },
                    transactionAmount: {
                        exampleValue: transactionAmount,
                    }
                }
            }
        }
    })
        .on('error.field.bv', function(e, data) {
            jQuery('#emerchantpay-modal-submit').attr('disabled', 'disabled');
        })
        .on('success.field.bv', function(e) {
            jQuery('#emerchantpay-modal-submit').removeAttr('disabled');
        })
        .on('success.form.bv', function(e) {
            e.preventDefault(); // Prevent the form from submitting

            /* submits the transaction form (No validators have failed) */
            submitForm.bootstrapValidator('defaultSubmit');
        });

    return true;
}

function executeBootstrapFieldValidator(formId, validatorFieldName) {
    var submitForm = jQuery(formId);

    submitForm.bootstrapValidator('validateField', validatorFieldName);
    submitForm.bootstrapValidator('updateStatus', validatorFieldName, 'NOT_VALIDATED');
}

function transactionModal(type, id_unique, amount) {
    if ((typeof amount == 'undefined') || (amount == null)) {
        amount = 0;
    }

    modalObj = jQuery('#emerchantpay-modal');

    var modalTitle = modalObj.find('h3.emerchantpay-modal-title'),
        modalAmountInputContainer = modalObj.find('div.amount-input'),
        captureTransactionInfoHolder = jQuery('#emerchantpay_capture_trans_info', modalObj),
        refundTransactionInfoHolder = jQuery('#emerchantpay_refund_trans_info', modalObj),
        cancelTransactionWarningHolder = jQuery('#emerchantpay_cancel_trans_warning', modalObj),
        transactionAmountInput = jQuery('#emerchantpay_transaction_amount', modalObj),
        submitBtn = jQuery('#emerchantpay-modal-submit');

    submitBtn
        .data('trx_type', type)
        .data('trx_id', id_unique)
        .data('trx_amount', amount);

    switch (type) {
        case 'capture':
            modalTitle.text('Capture transaction');
            updateTransModalControlState([captureTransactionInfoHolder], allowPartialCapture);
            updateTransModalControlState([modalAmountInputContainer], true);
            updateTransModalControlState([refundTransactionInfoHolder, cancelTransactionWarningHolder], false);

            transactionAmountInput.removeAttr('readonly');
            break;

        case 'refund':
            modalTitle.text('Refund transaction');
            updateTransModalControlState([captureTransactionInfoHolder, cancelTransactionWarningHolder], false);
            updateTransModalControlState([refundTransactionInfoHolder], allowPartialRefund);
            updateTransModalControlState([modalAmountInputContainer], true);

            transactionAmountInput.removeAttr('readonly');
            break;

        case 'void':
            modalTitle.text('Cancel transaction');
            updateTransModalControlState([captureTransactionInfoHolder, refundTransactionInfoHolder, modalAmountInputContainer], false);
            updateTransModalControlState([cancelTransactionWarningHolder], true);
            break;
        default:
            return;
    }

    modalObj.find('input[name="emerchantpay_transaction_type"]').val(type);
    modalObj.find('input[name="emerchantpay_transaction_id"]').val(id_unique);

    transactionAmountInput.val(amount);

    $modalInputs.show();
    showHideEMerchantPayAjaxLoader(false);
    modalObj.modal({backdrop:'static'});
}

function updateTransModalControlState(controls, visibilityStatus) {
    jQuery.each(controls, function (index, control) {
        if (!jQuery.exists(control))
            return;
        /* continue to the next item */

        if (visibilityStatus)
            control.fadeIn('fast');
        else
            control.fadeOut('fast');
    });
}

function showHideEMerchantPayAjaxLoader(shouldShow) {
    if (shouldShow === true) {
        $senderButton.fadeOut();
        $closeButton.fadeOut();
        $spinner.show();
    } else {
        $spinner.hide();
        $senderButton.fadeIn();
        $closeButton.fadeIn();
    }
}