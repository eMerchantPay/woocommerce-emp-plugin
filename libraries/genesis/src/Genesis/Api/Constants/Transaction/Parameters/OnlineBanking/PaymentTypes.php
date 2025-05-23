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
 * @copyright   Copyright (C) 2015-2025 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/MIT The MIT License
 */

namespace Genesis\Api\Constants\Transaction\Parameters\OnlineBanking;

use Genesis\Utils\Common;

/**
 * Used for Online Banking PayIn Payment Types
 *
 * Class PaymentTypes
 * @package Genesis\Api\Constants\Transaction\Parameters\OnlineBanking
 */
class PaymentTypes
{
    /**
     * Payment Type Online Banking
     */
    const ONLINE_BANKING = 'online_banking';

    /**
     * Payment Type Qr Payment
     */
    const PAYMENT        = 'qr_payment';

    /**
     * Payment Type Quick Payment
     */
    const QUICK_PAYMENT  = 'quick_payment';

    /**
     * Payment Type Netbanking
     */
    const NETBANKING     = 'netbanking';

    /**
     * Payment Type AliPay QR
     */
    const ALIPAY_QR      = 'alipay_qr';

    /**
     * Payment Type Scotiabank
     */
    const SCOTIABANK     = 'scotiabank';

    /**
     * Payment Type SPEI
     */
    const SPEI           = 'spei';

    /**
     * Get all available Payment Types
     *
     * @return array
     */
    public static function getAll()
    {
        return array_values(Common::getClassConstants(self::class));
    }
}
