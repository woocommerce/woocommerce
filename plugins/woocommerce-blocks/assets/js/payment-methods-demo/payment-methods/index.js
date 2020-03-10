/**
 * Internal dependencies
 */
import { paypalSvg } from './paypal';
import { ccSvg } from './cc';

const PaypalActivePaymentMethod = () => {
	return (
		<div>
			<p>This is where paypal payment method stuff would be.</p>
		</div>
	);
};

const CreditCardActivePaymentMethod = () => {
	return (
		<div>
			<p>This is where cc payment method stuff would be.</p>
		</div>
	);
};

export const paypalPaymentMethod = {
	id: 'paypal',
	label: <img src={ paypalSvg } alt="" />,
	stepContent: <div>Billing steps</div>,
	activeContent: <PaypalActivePaymentMethod />,
	edit: <PaypalActivePaymentMethod />,
	canMakePayment: Promise.resolve( true ),
	ariaLabel: 'paypal payment method',
};

export const ccPaymentMethod = {
	id: 'cc',
	label: <img src={ ccSvg } alt="" />,
	stepContent: null,
	activeContent: <CreditCardActivePaymentMethod />,
	edit: <CreditCardActivePaymentMethod />,
	canMakePayment: Promise.resolve( true ),
	ariaLabel: 'credit-card-payment-method',
};
