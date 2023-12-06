import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';

const settings = wc_emerchantpay_settings.settings || {};
const supports = wc_emerchantpay_settings.supports || {};

const defaultLabel = __('Emerchantpay checkout', 'woocommerce-emerchantpay');
const label = decodeEntities(settings.title) || defaultLabel;

const Label = (props) => {
	const { PaymentMethodLabel } = props.components;
	return <PaymentMethodLabel text={label} />;
};

const Description = () => {
	return (
		<p>{decodeEntities(settings.description || '')}</p>
	);
};

const EmerchantpayBlocksCheckout = {
	name: "emerchantpay_checkout",
	label: <Label />,
	content: <Description />,
	edit: <Description />,
	canMakePayment: () => true,
	ariaLabel: label,
	supports: {
		features: supports,
	},
};

export default EmerchantpayBlocksCheckout;
