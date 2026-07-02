<?php

/**
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author      emerchantpay
 * @copyright   Copyright (C) 2015-2024 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/MIT The MIT License
 */

namespace Genesis\Api\Request\Financial\Wallets;

use master\libraries\genesis\src\Genesis\Api\Traits\Request\AddressInfoAttributes;
use master\libraries\genesis\src\Genesis\Api\Traits\Request\Financial\AsyncAttributes;
use master\libraries\genesis\src\Genesis\Api\Traits\Request\Financial\PaymentAttributes;

/**
 * Class Neteller
 *
 * Electronic Wallet
 *
 * @package Genesis\Api\Request\Financial\Wallets
 *
 * @method Neteller setCustomerAccount($value) Set Neteller Account Id
 * @method Neteller setAccountPassword($value) Set Neteller Account Password
 */
class Neteller extends master\libraries\genesis\src\Genesis\Api\Request\Base\Financial
{
    use AddressInfoAttributes;
    use AsyncAttributes;
    use PaymentAttributes;

    /**
     * Neteller Account Id
     *
     * @var string
     */
    protected $customer_account;

    /**
     * Neteller Account Password
     *
     * @var string
     */
    protected $account_password;

    /**
     * Returns the Request transaction type
     * @return string
     */
    protected function getTransactionType()
    {
        return master\libraries\genesis\src\Genesis\Api\Constants\Transaction\Types::NETELLER;
    }

    /**
     * Set the required fields
     *
     * @return void
     */
    protected function setRequiredFields()
    {
        $requiredFields = [
            'transaction_id',
            'remote_ip',
            'amount',
            'currency',
            'return_success_url',
            'return_failure_url',
            'customer_email',
            'customer_account',
            'account_password',
            'billing_country'
        ];

        $this->requiredFields = master\libraries\genesis\src\Genesis\Utils\Common::createArrayObject($requiredFields);

        $requiredFieldValues = [
            'billing_country' => master\libraries\genesis\src\Genesis\Utils\Country::getList(),
            'currency'        => master\libraries\genesis\src\Genesis\Utils\Currency::getList()
        ];

        $this->requiredFieldValues = master\libraries\genesis\src\Genesis\Utils\Common::createArrayObject($requiredFieldValues);
    }

    /**
     * Return additional request attributes
     * @return array
     */
    protected function getPaymentTransactionStructure()
    {
        return [
            'return_success_url' => $this->return_success_url,
            'return_failure_url' => $this->return_failure_url,
            'amount'             => $this->transformAmount($this->amount, $this->currency),
            'currency'           => $this->currency,
            'customer_email'     => $this->customer_email,
            'customer_phone'     => $this->customer_phone,
            'customer_account'   => $this->customer_account,
            'account_password'   => $this->account_password,
            'billing_address'    => $this->getBillingAddressParamsStructure(),
            'shipping_address'   => $this->getShippingAddressParamsStructure()
        ];
    }
}
