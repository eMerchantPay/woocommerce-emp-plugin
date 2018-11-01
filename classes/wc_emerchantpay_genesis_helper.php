<?php
/*
 * Copyright (C) 2018 emerchantpay Ltd.
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
 * @copyright   2018 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

if (!defined( 'ABSPATH' )) {
    exit(0);
}

/**
 * Class wc_emerchantpay_genesis_helper
 */
class WC_emerchantpay_Genesis_Helper
{
    /**
     * Builds full Request Class Name by Transaction Type
     * @param string $transactionType
     * @return string
     */
    public static function getTransactionTypeRequestClassName($transactionType)
    {
        $requestClassName = \Genesis\Utils\Common::snakeCaseToCamelCase(
            str_replace('3d', '3D', $transactionType)
        );
        $recurringInnerNamespace =
            strpos($transactionType, 'recurring') !== false
                ? "Recurring\\"
                : '';
        return "Financial\\Cards\\{$recurringInnerNamespace}{$requestClassName}";
    }

    /**
     * Constructs a Gateway Request Instance depending on the selected Txn Type
     * @param string $transactionType
     * @return \Genesis\Genesis
     * @throws \Genesis\Exceptions\InvalidMethod
     */
    public static function getGatewayRequestByTxnType($transactionType)
    {
        $apiRequestClassName = static::getTransactionTypeRequestClassName(
            $transactionType
        );

        return new \Genesis\Genesis($apiRequestClassName);
    }

    /**
     * @param \stdClass $reconcile
     * @return \stdClass
     */
    public static function getReconcilePaymentTransaction($reconcile)
    {
        return
            isset($reconcile->payment_transaction)
                ? $reconcile->payment_transaction
                : $reconcile;
    }

    /**
     * @param \stdClass $response
     * @return \Genesis\API\Constants\Transaction\States
     */
    public static function getGatewayStatusInstance($response)
    {
        return new \Genesis\API\Constants\Transaction\States($response->status);
    }

    /**
     * @param bool $isRecurring
     * @return string
     */
    public static function getPaymentTransactionUsage($isRecurring)
    {
        return sprintf(
            $isRecurring ? '%s Recurring Transaction' : '%s Payment Transaction',
            get_bloginfo( 'name' )
        );
    }

    /**
     * Makes a check if all the requirements of Genesis Lib are verified
     *
     * @return true|WP_Error (True -> verified; WP_Error -> Exception Message)
     */
    public static function checkGenesisRequirementsVerified()
    {
        try {
            \Genesis\Utils\Requirements::verify();

            return true;
        } catch (\Exception $exception) {
            return WC_emerchantpay_Helper::getWPError($exception);
        }
    }

    /**
     * Retrieves the consumer's user id
     *
     * @return int
     */
    public static function getCurrentUserId()
    {
        return get_current_user_id();
    }

    /**
     * @param int $length
     * @return string
     */
    public static function getCurrentUserIdHash($length = 20)
    {
        $userId = self::getCurrentUserId();

        $userHash = $userId > 0 ? sha1($userId) : WC_emerchantpay_Method::generateTransactionId();

        return substr($userHash, 0, $length);
    }
}
