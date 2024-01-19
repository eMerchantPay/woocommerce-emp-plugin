import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';

const checkoutSettings = getSetting( 'emerchantpay-checkout-blocks_data', {} );

let EmerchantpayBlocksCheckout = {};

if (Object.keys(checkoutSettings).length) {
	const defaultLabel = __('Emerchantpay checkout', 'woocommerce-emerchantpay');
	const label        = decodeEntities(checkoutSettings.title) || defaultLabel;

	const Label        = (props) => {
		const {PaymentMethodLabel} = props.components;
		return <PaymentMethodLabel text={label}/>;
	};
	const Description  = () => {
		return (
			<p>{decodeEntities(checkoutSettings.description || '')}</p>
		);
	};

	EmerchantpayBlocksCheckout = {
		name: "emerchantpay_checkout",
		label: <Label/>,
		content: <Description/>,
		edit: <Description/>,
		canMakePayment: () => true,
		ariaLabel: label,
		supports: {
			features: checkoutSettings.supports
		},
	};
}

export default EmerchantpayBlocksCheckout;
