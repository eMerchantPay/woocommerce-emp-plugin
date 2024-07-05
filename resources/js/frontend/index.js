/**
 * Copyright (C) 2018-2024 emerchantpay Ltd.
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
 * @copyright   2018-2024 emerchantpay Ltd.
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 * @package     resources/js/frontend/index
 */

import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import EmerchantpayBlocksCheckout from './EmerchantpayCheckout';
import EmerchantpayBlocksDirect from './EmerchantpayDirect';

if ( Object.keys( EmerchantpayBlocksCheckout ).length > 0 ) {
	registerPaymentMethod( EmerchantpayBlocksCheckout );
}

if ( Object.keys( EmerchantpayBlocksDirect ).length > 0 ) {
	registerPaymentMethod( EmerchantpayBlocksDirect );
}
