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
<tr>
    <td colspan="3">
        <section class="box bootstrap">
            <h3 class="text-left">
                <img src="<?=plugin_dir_url( realpath(dirname(__FILE__) . '/../../' ) ) . 'assets/images/logo.png';?>" alt="eMp" class="emp-logo" />
                <span><?= $payment_method::getTranslatedText('eMerchantPay Transactions'); ?></span>
            </h3>

            <table class="table table-hover tree">
                <thead class="thead-default">
                <tr>
                    <th><?= $payment_method::getTranslatedText('ID'); ?></th>
                    <th><?= $payment_method::getTranslatedText('Type'); ?></th>
                    <th><?= $payment_method::getTranslatedText('Date'); ?></th>
                    <th><?= $payment_method::getTranslatedText('Amount'); ?></th>
                    <th><?= $payment_method::getTranslatedText('Status'); ?></th>
                    <th class="slim-message"><?= $payment_method::getTranslatedText('Message'); ?></th>
                    <th><?= $payment_method::getTranslatedText('Capture'); ?></th>
                    <th><?= $payment_method::getTranslatedText('Refund'); ?></th>
                    <th><?= $payment_method::getTranslatedText('Cancel'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($transactions AS $transaction): ?>
                <tr class="treegrid-<?=$transaction['unique_id']; ?> <?=$transaction['parent_id'] ? "treegrid-parent-{$transaction['parent_id']}" : ''; ?>">
                    <td class="text-left"><?=$transaction['unique_id']; ?></td>
                    <td class="text-left"><?=$transaction['type']; ?></td>
                    <td class="text-left"><?=$transaction['date_add']; ?></td>
                    <td class="text-right"><?=$transaction['amount']; ?></td>
                    <td class="text-left"><?=$transaction['status']; ?></td>
                    <td class="text-left"><?=$transaction['message']; ?></td>
                    <td class="text-center">
                        <?php if ($transaction['can_capture']):?>
                        <div class="transaction-action-button">
                            <a class="button btn-transaction button-success button-capture button" role="button" data-type="capture" data-id-unique="<?= $transaction['unique_id']?>" data-amount="<?=$transaction['available_amount']?>">
                            <i class="dashicons dashicons-yes"></i>
                            </a>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($transaction['can_refund']):?>
                        <div class="transaction-action-button">
                            <a class="button btn-transaction button-warning button-refund button" role="button" data-type="refund" data-id-unique="<?= $transaction['unique_id']?>" data-amount="<?=$transaction['available_amount']?>">
                            <i class="dashicons dashicons-undo"></i>
                            </a>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($transaction['can_void']):?>
                        <div class="transaction-action-button">
                            <a class="button btn-transaction button-danger button-void button" role="button" data-type="void" data-id-unique="<?= $transaction['unique_id']?>" data-amount="0">
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

        <div id="emerchantpay-modal" class="modal fade">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">
                            <i class="icon-times"></i>
                        </button>
                        <img src="<?=plugin_dir_url( realpath(dirname(__FILE__) . '/../../' ) ) . 'assets/images/logo.png';?>" style="width:16px;" />
                        <h3 class="emerchantpay-modal-title" style="margin:0;display:inline-block;"></h3>
                    </div>
                    <div class="modal-body">
                        <form id="<?= $payment_method->id; ?>-modal-form" class="form modal-form" action="" method="post">
                            <div id="emerchantpay_cancel_trans_warning" class="row" style="display: none;">
                                <div class="col-xs-12">
                                    <div class="alert alert-warning">
                                        <?= $payment_method::getTranslatedText('This service is only available for particular acquirers!'); ?>
                                        <br/>
                                        <?= $payment_method::getTranslatedText('For further Information please contact your Account Manager.'); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group amount-input">
                                <label for="emerchantpay_transaction_amount"><?= $payment_method::getTranslatedText('Amount'); ?></label>
                                <div class="input-group">
                                    <span class="input-group-addon" data-toggle="emerchantpay-tooltip" data-placement="top" title="<?=$order_currency?>"><?=get_woocommerce_currency_symbol($order_currency);?></span>
                                    <input type="text" class="form-control" id="emerchantpay_transaction_amount" name="emerchantpay_transaction_amount" placeholder="<?= $payment_method::getTranslatedText('Amount'); ?>" value="<?=$order->get_total()?>" />
                                </div>
                                <span class="help-block" id="emerchantpay-amount-error-container"></span>
                            </div>

                            <div class="form-group usage-input">
                                <label for="emerchantpay_transaction_usage"><?= $payment_method::getTranslatedText('Message (optional):'); ?></label>
                                <textarea class="form-control form-message" rows="3" id="emerchantpay_transaction_usage" name="emerchantpay_transaction_usage" placeholder="<?= $payment_method::getTranslatedText('Message'); ?>"></textarea>
                            </div>
                        </form>
                        <div style="text-align: center">
                            <div id="emerchantpay-modal-spinner" class="spinner"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="form-group">
                            <button id="emerchantpay-modal-close" class="btn btn-default" data-dismiss="modal" aria-hidden="true"><?= $payment_method::getTranslatedText('Close'); ?></button>
                            <button
                                    id="emerchantpay-modal-submit"
                                    class="btn btn-submit btn-primary btn-capture"
                                    value="partial"
                                    type="button"
                                    data-payment_type="<?= $payment_method->id; ?>"
                                    data-payment_title="<?= htmlentities($payment_method->get_title(), ENT_QUOTES);?>"
                            ><?= $payment_method::getTranslatedText('Submit'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script type="text/javascript">
            var modalPopupDecimalValueFormatConsts = {
                decimalPlaces     : <?= json_encode(wc_get_price_decimals()); ?>,
                decimalSeparator  : <?= json_encode(wc_get_price_decimal_separator()); ?>,
                thousandSeparator : <?= json_encode(wc_get_price_thousand_separator()); ?>
            },
            allowPartialCapture = true,
            allowPartialRefund = true;
        </script>
    </td>
</tr>