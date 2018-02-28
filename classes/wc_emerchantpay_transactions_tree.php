<?php
/*
 * Copyright (C) 2017 eMerchantPay Ltd.
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
 * @copyright   2017 eMerchantPay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

if (!defined( 'ABSPATH' )) {
    exit(0);
}

/**
 * eMerchantPay Helper Class
 *
 * @class WC_eMerchantPay_Transactions_Tree

 */
class WC_eMerchantPay_Transactions_Tree
{
    const META_DATA_KEY_HIERARCHY = 'emp_trx_hierarchy';
    const META_DATA_KEY_LIST = 'emp_trx_list';

    /**
     * @var array $trx_hierarchy child_unique_id => parent_unique_id
     */
    public $trx_hierarchy;
    public $trx_list;

    /**
     * WC_eMerchantPay_Transactions_Tree constructor.
     *
     * @param array $trx_list_existing
     * @param array $trx_list_new
     * @param array $trx_hierarchy
     */
    public function __construct(array $trx_list_existing, array $trx_list_new, array $trx_hierarchy = [])
    {
        $this->setTrxHierarchy($trx_list_existing, $trx_list_new, $trx_hierarchy);
        $this->setTrxData($trx_list_existing, $trx_list_new);
        $this->parseTrxs();
        $this->updateParentTrxStatus();
    }

    /**
     * @param WC_Order $order
     *
     * @return array
     */
    public static function getTransactionsListFromOrder(WC_Order $order)
    {
        return static::getTransactionTree(
            WC_eMerchantPay_Helper::getOrderMetaData(
                WC_eMerchantPay_Helper::getOrderProp($order, 'id'),
                static::META_DATA_KEY_LIST
            )
        );
    }

    /**
     * @param WC_Order $order
     * @param string $unique_id
     * @param array $trx_tree
     *
     * @return array|null
     */
    public static function getTrxFromOrder(WC_Order $order, $unique_id, $trx_tree = null)
    {
        if ($trx_tree === null) {
            $trx_tree = static::getTransactionsListFromOrder($order);
        }
        foreach ($trx_tree AS $trx) {
            if ($trx['unique_id'] === $unique_id) {
                return $trx;
            }
        }

        return null;
    }

    /**
     * @param array $trx_list_existing
     * @param array $trx_list_new
     * @param array $trx_hierarchy
     */
    protected function setTrxHierarchy($trx_list_existing, $trx_list_new, $trx_hierarchy)
    {
        $this->trx_hierarchy = $trx_hierarchy;

        // Add reference_id to checkout trx so hierarchy will work out and
        // change checkout status the same way like refunds and voids
        if (count($trx_list_existing) === 1 &&
            $trx_list_existing[0]->type === WC_eMerchantPay_Transaction::TYPE_CHECKOUT) {
            foreach ($trx_list_new AS $trx_new) {
                $this->trx_hierarchy[$trx_new->unique_id] = $trx_list_existing[0]->unique_id;
            }
        }
    }

    /**
     * @param array $trx_list_existing
     * @param array $trx_list_new
     * @return array
     */
    protected function setTrxData(array $trx_list_existing, array $trx_list_new)
    {
        $this->trx_list = array_reduce(array_merge($trx_list_existing, $trx_list_new), function ($trx_list_hash, $trx) {
            // Set relations child => parent
            if (!empty($trx->parent_id)) {
                $this->trx_hierarchy[$trx->unique_id] = $trx->parent_id;
            } else if (isset($trx->reference_id)) {
                $this->trx_hierarchy[$trx->unique_id] = $trx->reference_id;
            }

            $trx_list_hash[$trx->unique_id] = $trx;

            return $trx_list_hash;
        }, []);
    }

    /**
     * Change genesis response objects to internal WC_eMerchantPay_Transaction
     */
    protected function parseTrxs()
    {
        foreach ($this->trx_list AS &$raw_trx) {
            if ($raw_trx instanceof stdClass) {
                $raw_trx = new WC_eMerchantPay_Transaction(
                    $raw_trx,
                    $this->findParentId($raw_trx)
                );
            }
        }
    }

    /**
     * @param stdClass|WC_eMerchantPay_Transaction $trx
     *
     * @return bool|string
     */
    protected function findParentId($trx)
    {
        if (!isset($this->trx_hierarchy[$trx->unique_id])) {
            return false;
        }

        return $this->trx_hierarchy[$trx->unique_id];
    }

    /**
     * Updates status of parent transactions
     */
    protected function updateParentTrxStatus() {
        foreach ($this->trx_list AS $trx) {
            if (!empty($trx->parent_id) && isset($this->trx_list[$trx->parent_id])) {
                if ($trx->shouldChangeParentStatus($this->trx_list[$trx->parent_id]->type)) {
                    $this->trx_list[$trx->parent_id]->status = $trx->getStatusText();
                }
            }
        }
    }

    /**
     * Returns an array with tree-structure where
     * every branch is a transaction related to
     * the order
     *
     * @param $transactions array WC_eMerchantPay_Transaction
     *
     * @return array
     */
    public static function getTransactionTree($transactions)
    {
        if (empty($transactions) || !is_array($transactions)) {
            return [];
        }

        $trx_arr = [];
        foreach($transactions AS $trx) {
            $trx_arr[] = (array) $trx;
        }

        // Sort the transactions list in the following order:
        //
        // 1. Sort by timestamp (date), i.e. most-recent transactions on top
        // 2. Sort by relations, i.e. every parent has the child nodes immediately after

        // Ascending Date/Timestamp sorting
        uasort($trx_arr, function ($a, $b) {
            // sort by timestamp (date) first
            if (@$a["date_add"] == @$b["date_add"]) {
                return 0;
            }

            return (@$a["date_add"] > @$b["date_add"]) ? 1 : -1;
        });

        // Process individual fields
        foreach ($trx_arr as &$transaction) {
            $transaction['date_add'] = date("H:i:s \n m/d/Y", $transaction['date_add']);

            $transaction['can_capture'] = static::canCapture($transaction);

            if ($transaction['can_capture']) {
                $totalAuthorizedAmount           = self::getTransactionsSumAmount(
                    $trx_arr,
                    $transaction['parent_id'],
                    array(
                        \Genesis\API\Constants\Transaction\Types::AUTHORIZE,
                        \Genesis\API\Constants\Transaction\Types::AUTHORIZE_3D
                    ),
                    \Genesis\API\Constants\Transaction\States::APPROVED
                );
                $totalCapturedAmount             = self::getTransactionsSumAmount(
                        $trx_arr,
                        $transaction['unique_id'],
                        \Genesis\API\Constants\Transaction\Types::CAPTURE,
                        \Genesis\API\Constants\Transaction\States::APPROVED
                    ) + self::getTransactionsSumAmount(
                        $trx_arr,
                        $transaction['unique_id'],
                        \Genesis\API\Constants\Transaction\Types::CAPTURE,
                        \Genesis\API\Constants\Transaction\States::REFUNDED
                    );
                $transaction['available_amount'] = $totalAuthorizedAmount - $totalCapturedAmount;

                if ($transaction['available_amount'] == 0) {
                    $transaction['can_capture'] = false;
                }
            }

            $transaction['can_refund'] = static::canRefund($transaction);

            if ($transaction['can_refund']) {
                $totalCapturedAmount             = $transaction['amount'];
                $totalRefundedAmount             = self::getTransactionsSumAmount(
                    $trx_arr,
                    $transaction['unique_id'],
                    \Genesis\API\Constants\Transaction\Types::REFUND,
                    \Genesis\API\Constants\Transaction\States::APPROVED
                );
                $transaction['available_amount'] = $totalCapturedAmount - $totalRefundedAmount;

                if ($transaction['available_amount'] == 0) {
                    $transaction['can_refund'] = false;
                }
            }

            $transaction['can_void'] = static::canVoid($transactions, $transaction);

            $transaction['amount'] = self::formatTransactionValue($transaction['amount']);

            if (!isset($transaction['available_amount'])) {
                $transaction['available_amount'] = $transaction['amount'];
            }

            $transaction['available_amount'] = self::formatTransactionValue($transaction['available_amount']);
        }

        // Create the parent/child relations from a flat array
        $array_asc = array();

        foreach ($trx_arr as $key => $val) {
            // create an array with ids as keys and children
            // with the assumption that parents are created earlier.
            // store the original key
            $array_asc[$val['unique_id']] = array_merge($val, array('org_key' => $key));

            if (isset($val['parent_id']) && (bool)$val['parent_id']) {
                $array_asc[$val['parent_id']]['children'][] = $val['unique_id'];
            }
        }

        // Order the parent/child entries
        $trx_arr = array();

        foreach ($array_asc as $val) {
            self::treeTransactionSort($trx_arr, $val, $array_asc);
        }

        return $trx_arr;
    }

    /**
     * @param array $transaction
     *
     * @return bool
     */
    public static function canCapture($transaction)
    {
        return $transaction['status'] === \Genesis\API\Constants\Transaction\States::APPROVED &&
               \Genesis\API\Constants\Transaction\Types::canCapture($transaction['type']);
    }

    /**
     * @param array $transaction
     *
     * @return bool
     */
    public static function canRefund($transaction)
    {
        return ($transaction['status'] === \Genesis\API\Constants\Transaction\States::APPROVED ||
                $transaction['status'] === \Genesis\API\Constants\Transaction\States::REFUNDED) &&
               \Genesis\API\Constants\Transaction\Types::canRefund($transaction['type']);
    }

    /**
     * @param array $transactions
     * @param array $transaction
     *
     * @return bool
     */
    public static function canVoid($transactions, $transaction)
    {
        if ($transaction['status'] !== \Genesis\API\Constants\Transaction\States::APPROVED ||
            !\Genesis\API\Constants\Transaction\Types::canVoid($transaction['type'])) {
            return false;
        }

        foreach($transactions AS $trx) {
            $trx = (array) $trx;

            if ($trx['parent_id'] === $transaction['unique_id'] &&
                ($trx['type'] === \Genesis\API\Constants\Transaction\Types::VOID ||
                 $trx['type'] === \Genesis\API\Constants\Transaction\Types::CAPTURE)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Recursive function used in the process of sorting
     * the Transactions list
     *
     * @param $array_out array
     * @param $val array
     * @param $array_asc array
     */
    public static function treeTransactionSort(&$array_out, $val, $array_asc)
    {
        if (isset($val['org_key'])) {
            $array_out[$val['org_key']] = $val;

            if (isset($val['children']) && sizeof($val['children'])) {
                foreach ($val['children'] as $id) {
                    self::treeTransactionSort($array_out, $array_asc[$id], $array_asc);
                }
            }
            unset($array_out[$val['org_key']]['children'], $array_out[$val['org_key']]['org_key']);
        }
    }

    /**
     * Get the sum of the amount for a list of transaction types and status
     *
     * @param array $transactions
     * @param string $parent_id
     * @param array|string $types
     * @param string $status
     *
     * @return float
     */
    private static function getTransactionsSumAmount($transactions, $parent_id, $types, $status)
    {
        $totalAmount  = 0.0;

        /** @var $transaction */
        foreach ($transactions as $transaction) {
            if (!empty($parent_id) && $parent_id !== $transaction['parent_id']) {
                continue;
            }
            if (is_array($types) ? !in_array($transaction['type'], $types) : $types !== $transaction['type']) {
                continue;
            }
            if ($transaction['status'] !== $status) {
                continue;
            }

            $totalAmount += $transaction['amount'];
        }

        return $totalAmount;
    }

    /**
     * Get a formatted transaction value for the Admin Transactions Panel
     *
     * @param float $amount
     *
     * @return string
     */
    private static function formatTransactionValue($amount)
    {
        /* DecimalSeparator   -> .
           Thousand Separator -> empty

           Otherwise an exception could be thrown from genesis
        */
        return number_format($amount, 2, ".", "");
    }

    /**
     * @param WC_Order $order
     *
     * @return float
     */
    public static function getTotalCapturedAmount(WC_Order $order)
    {
        return static::getTotalAmount(
                static::getTransactionsListFromOrder($order),
                \Genesis\API\Constants\Transaction\Types::CAPTURE
            ) + static::getTotalAmount(
                static::getTransactionsListFromOrder($order),
                \Genesis\API\Constants\Transaction\Types::CAPTURE,
                \Genesis\API\Constants\Transaction\States::REFUNDED
            );
    }

    /**
     * @param WC_Order $order
     *
     * @return float
     */
    public static function getTotalRefundedAmount(WC_Order $order)
    {
        return static::getTotalAmount(
            static::getTransactionsListFromOrder($order),
            \Genesis\API\Constants\Transaction\Types::REFUND
        );
    }

    /**
     * @param array $trx_tree
     * @param array|string $types
     * @param string $status
     *
     * @return float
     */
    public static function getTotalAmount(array $trx_tree, $types, $status = \Genesis\API\Constants\Transaction\States::APPROVED)
    {
        $totalAmount = 0.0;

        foreach ($trx_tree AS $trx) {
            $totalAmount += self::getTransactionsSumAmount(
                $trx_tree,
                $trx['unique_id'],
                $types,
                $status
            );
        }

        return $totalAmount;
    }
}