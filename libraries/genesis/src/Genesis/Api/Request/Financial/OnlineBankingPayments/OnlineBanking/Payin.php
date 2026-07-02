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

namespace Genesis\Api\Request\Financial\OnlineBankingPayments\OnlineBanking;

use master\libraries\genesis\src\Genesis\Api\Constants\Transaction\Parameters\OnlineBanking\BankCodeParameters;
use master\libraries\genesis\src\Genesis\Api\Constants\Transaction\Parameters\OnlineBanking\PaymentTypes;
use master\libraries\genesis\src\Genesis\Api\Request\Base\Financial;
use master\libraries\genesis\src\Genesis\Api\Traits\Request\AddressInfoAttributes;
use master\libraries\genesis\src\Genesis\Api\Traits\Request\DocumentAttributes;
use master\libraries\genesis\src\Genesis\Api\Traits\Request\Financial\AsyncAttributes;
use master\libraries\genesis\src\Genesis\Api\Traits\Request\Financial\OnlineBankingPayments\UserCategoryAttributes;
use master\libraries\genesis\src\Genesis\Api\Traits\Request\Financial\OnlineBankingPayments\VirtualPaymentAddressAttributes;
use master\libraries\genesis\src\Genesis\Api\Traits\Request\Financial\PaymentAttributes;
use master\libraries\genesis\src\Genesis\Exceptions\InvalidArgument;
use master\libraries\genesis\src\Genesis\Utils\Common as CommonUtils;

/**
 * Class Payin
 *
 * OnlineBanking Payin - oBeP-style alternative payment method
 *
 * @package Genesis\Api\Request\Financial\OnlineBankingPayments\OnlineBanking
 *
 * @method string getPaymentType()
 * @method $this  setBankCode($value) Customer’s bank code
 * @method string getConsumerReference()
 * @method $this  setConsumerReference($value)
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 */
class Payin extends Financial
{
    use AddressInfoAttributes;
    use AsyncAttributes;
    use DocumentAttributes;
    use PaymentAttributes;
    use UserCategoryAttributes;
    use VirtualPaymentAddressAttributes;

    /**
     * Customer’s bank code
     *
     * @var string
     */
    protected $bank_code;

    /**
     * The payment type describes the type of online banking used to process the transaction.
     *
     * @var string
     */
    protected $payment_type;

    /**
     * Consumer Reference
     *
     * @var string $consumer_reference
     */
    protected $consumer_reference;

    /**
     * 6-digit code used to authenticate the consumer within BLIK
     *
     * @var string
     */
    protected $auth_code;

    /**
     * @param $paymentType
     *
     * @return $this
     * @throws master\libraries\genesis\src\Genesis\Exceptions\InvalidArgument
     */
    public function setPaymentType($paymentType)
    {
        $allowedPaymentTypes = master\libraries\genesis\src\Genesis\Api\Constants\Transaction\Parameters\OnlineBanking\PaymentTypes::getAll();

        if (in_array($paymentType, $allowedPaymentTypes)) {
            $this->payment_type = $paymentType;

            return $this;
        }

        throw new InvalidArgument(
            'Invalid value for payment_type. Allowed values are ' . implode(', ', $allowedPaymentTypes)
        );
    }

    /**
     * Returns the Request transaction type
     * @return string
     */
    protected function getTransactionType()
    {
        return master\libraries\genesis\src\Genesis\Api\Constants\Transaction\Types::ONLINE_BANKING_PAYIN;
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
            'return_success_url',
            'return_failure_url',
            'amount',
            'currency',
            'bank_code'
        ];

        $this->requiredFields = CommonUtils::createArrayObject($requiredFields);

        $requiredFieldValues = [
            'currency' => master\libraries\genesis\src\Genesis\Api\Constants\Transaction\Parameters\OnlineBanking\BankCodeParameters::getAllowedCurrencies()
        ];

        $this->requiredFieldValues = CommonUtils::createArrayObject($requiredFieldValues);

        $this->setRequiredFieldsConditional();
    }

    /**
     * Set the required fields conditional
     *
     * @return void
     */
    protected function setRequiredFieldsConditional()
    {
        $requiredFieldsConditional = [
            'billing_country' => [
                'US' => ['billing_state'],
                'CA' => ['billing_state']
            ]
        ];

        $this->requiredFieldsConditional = CommonUtils::createArrayObject($requiredFieldsConditional);

        $requiredFieldValuesConditional = [
            'currency' => [
                'CNY' => [
                    ['bank_code' => master\libraries\genesis\src\Genesis\Api\Constants\Transaction\Parameters\OnlineBanking\BankCodeParameters::getBankCodesPerCurrency('CNY')]
                ],
                'CLP' => [
                    ['bank_code' => master\libraries\genesis\src\Genesis\Api\Constants\Transaction\Parameters\OnlineBanking\BankCodeParameters::getBankCodesPerCurrency('CLP')]
                ],
                'THB' => [
                    ['bank_code' => master\libraries\genesis\src\Genesis\Api\Constants\Transaction\Parameters\OnlineBanking\BankCodeParameters::getBankCodesPerCurrency('THB')]
                ],
                'MYR' => [
                    ['bank_code' => master\libraries\genesis\src\Genesis\Api\Constants\Transaction\Parameters\OnlineBanking\BankCodeParameters::getBankCodesPerCurrency('MYR')]
                ],
                'PYG' => [
                    ['bank_code' => master\libraries\genesis\src\Genesis\Api\Constants\Transaction\Parameters\OnlineBanking\BankCodeParameters::getBankCodesPerCurrency('PYG')]
                ],
                'IDR' => [
                    ['bank_code' => master\libraries\genesis\src\Genesis\Api\Constants\Transaction\Parameters\OnlineBanking\BankCodeParameters::getBankCodesPerCurrency('IDR')]
                ],
                'INR' => [
                    ['bank_code' => master\libraries\genesis\src\Genesis\Api\Constants\Transaction\Parameters\OnlineBanking\BankCodeParameters::getBankCodesPerCurrency('INR')]
                ],
                'PHP' => [
                    ['bank_code' => master\libraries\genesis\src\Genesis\Api\Constants\Transaction\Parameters\OnlineBanking\BankCodeParameters::getBankCodesPerCurrency('PHP')]
                ],
                'SGD' => [
                    ['bank_code' => master\libraries\genesis\src\Genesis\Api\Constants\Transaction\Parameters\OnlineBanking\BankCodeParameters::getBankCodesPerCurrency('SGD')]
                ],
                'UYU' => [
                    ['bank_code' => master\libraries\genesis\src\Genesis\Api\Constants\Transaction\Parameters\OnlineBanking\BankCodeParameters::getBankCodesPerCurrency('UYU')]
                ],
                'VND' => [
                    ['bank_code' => master\libraries\genesis\src\Genesis\Api\Constants\Transaction\Parameters\OnlineBanking\BankCodeParameters::getBankCodesPerCurrency('VND')]
                ],
                'PEN' => [
                    ['bank_code' => master\libraries\genesis\src\Genesis\Api\Constants\Transaction\Parameters\OnlineBanking\BankCodeParameters::getBankCodesPerCurrency('PEN')]
                ],
                'EUR' => [
                    ['bank_code' => master\libraries\genesis\src\Genesis\Api\Constants\Transaction\Parameters\OnlineBanking\BankCodeParameters::getBankCodesPerCurrency('EUR')]
                ],
                'USD' => [
                    ['bank_code' => master\libraries\genesis\src\Genesis\Api\Constants\Transaction\Parameters\OnlineBanking\BankCodeParameters::getBankCodesPerCurrency('USD')]
                ],
                'MXN' => [
                    ['bank_code' => master\libraries\genesis\src\Genesis\Api\Constants\Transaction\Parameters\OnlineBanking\BankCodeParameters::getBankCodesPerCurrency('MXN')]
                ],
                'BRL' => [
                    ['bank_code' => master\libraries\genesis\src\Genesis\Api\Constants\Transaction\Parameters\OnlineBanking\BankCodeParameters::getBankCodesPerCurrency('BRL')]
                ],
                'CHF' => [
                    ['bank_code' => master\libraries\genesis\src\Genesis\Api\Constants\Transaction\Parameters\OnlineBanking\BankCodeParameters::getBankCodesPerCurrency('CHF')]
                ],
                'CAD' => [
                    ['bank_code' => master\libraries\genesis\src\Genesis\Api\Constants\Transaction\Parameters\OnlineBanking\BankCodeParameters::getBankCodesPerCurrency('CAD')]
                ],
                'PLN' => [
                    ['bank_code' => master\libraries\genesis\src\Genesis\Api\Constants\Transaction\Parameters\OnlineBanking\BankCodeParameters::getBankCodesPerCurrency('PLN')]
                ],
                'AUD' => [
                    ['bank_code' => master\libraries\genesis\src\Genesis\Api\Constants\Transaction\Parameters\OnlineBanking\BankCodeParameters::getBankCodesPerCurrency('AUD')]
                ]
            ]
        ];

        $this->requiredFieldValuesConditional = CommonUtils::createArrayObject(
            $requiredFieldValuesConditional
        );
    }

    /**
     * Add document_id conditional validation if it is present
     *
     * @return void
     * @throws master\libraries\genesis\src\Genesis\Exceptions\InvalidArgument
     * @throws master\libraries\genesis\src\Genesis\Exceptions\ErrorParameter
     * @throws master\libraries\genesis\src\Genesis\Exceptions\InvalidClassMethod
     */
    protected function checkRequirements()
    {
        if ($this->document_id) {
            $this->requiredFieldValuesConditional = CommonUtils::createArrayObject(
                array_merge(
                    (array)$this->requiredFieldValuesConditional,
                    $this->getDocumentIdConditions()
                )
            );
        }

        parent::checkRequirements();
    }

    /**
     * Return additional request attributes
     * @return array
     */
    protected function getPaymentTransactionStructure()
    {
        return [
            'amount'                  => $this->transformAmount($this->amount, $this->currency),
            'currency'                => $this->currency,
            'bank_code'               => $this->bank_code,
            'return_success_url'      => $this->return_success_url,
            'return_failure_url'      => $this->return_failure_url,
            'customer_email'          => $this->customer_email,
            'customer_phone'          => $this->customer_phone,
            'payment_type'            => $this->payment_type,
            'document_id'             => $this->document_id,
            'billing_address'         => $this->getBillingAddressParamsStructure(),
            'shipping_address'        => $this->getShippingAddressParamsStructure(),
            'virtual_payment_address' => $this->virtual_payment_address,
            'consumer_reference'      => $this->consumer_reference,
            'user_category'           => $this->user_category,
            'auth_code'               => $this->auth_code
        ];
    }
}
