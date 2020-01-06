/**
 * Internal dependencies
 */
import { paypalSvg } from './paypal';
import { ccSvg } from './cc';

export const paypalPaymentMethod = {
	id: 'paypal',
	label: <img src={ paypalSvg } alt="" />,
	stepContent: <div>Billing steps</div>,
	activeContent: (
		<div>
			<p>This is where paypal payment method stuff would be.</p>
		</div>
	),
	canMakePayment: Promise.resolve( true ),
	ariaLabel: 'paypal payment method',
};

export const ccPaymentMethod = {
	id: 'cc',
	label: <img src={ ccSvg } alt="" />,
	stepContent: null,
	activeContent: (
		<div>
			<p>This is where cc payment method stuff would be.</p>
		</div>
	),
	canMakePayment: Promise.resolve( true ),
	ariaLabel: 'credit-card-payment-method',
};
