import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import EmerchantpayBlocksCheckout from './EmerchantpayCheckout';
import EmerchantpayBlocksDirect from './EmerchantpayDirect';

if (Object.keys(EmerchantpayBlocksCheckout).length > 0) {
	registerPaymentMethod(EmerchantpayBlocksCheckout);
}

if (Object.keys(EmerchantpayBlocksDirect).length > 0) {
	registerPaymentMethod(EmerchantpayBlocksDirect);
}
