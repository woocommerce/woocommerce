/**
 * External dependencies
 */
import { Fragment, createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	Visa,
	MasterCard,
	Amex,
	WooPay,
	ApplePay,
	GooglePay,
	Discover,
	JCB,
	Afterpay,
	Affirm,
	Klarna,
} from './Icons';

const Payments = [
	{
		name: 'visa',
		component: <Visa />,
	},
	{
		name: 'mastercard',
		component: <MasterCard />,
	},
	{
		name: 'amex',
		component: <Amex />,
	},
	{
		name: 'discover',
		component: <Discover />,
	},
	{
		name: 'woopay',
		component: <WooPay />,
	},
	{
		name: 'applepay',
		component: <ApplePay />,
	},
	{
		name: 'googlepay',
		component: <GooglePay />,
	},
	{
		name: 'afterpay',
		component: <Afterpay />,
	},
	{
		name: 'affirm',
		component: <Affirm />,
	},
	{
		name: 'klarna',
		component: <Klarna />,
	},
	{
		name: 'jcb',
		component: <JCB />,
	},
];

export const WooPaymentMethodLogos: React.VFC< {
	isWooPayEligible: boolean;
	maxNrElements: number;
} > = ( { isWooPayEligible = false, maxNrElements = 10 } ) => {
	let i = 0;
	let j = 0;
	return (
		<>
			<div className="woocommerce-payments-method-logos">
				{Payments.map((payment) => {
					if (i == maxNrElements) {
						return <Fragment></Fragment>;
					}
					if (!isWooPayEligible && payment.name === 'woopay') {
						return <Fragment></Fragment>;
					}
					i++;

					return payment.component;
				})}
				{ i < 21 && (
					<div className="woocommerce-payments-method-logos_count">
						+ { 21 - i }
					</div>
				)}
			</div>

			<div className="woocommerce-payments-method-logos_mini">
				{ Payments.map((payment) => {
					if (j == 5) {
						return <Fragment></Fragment>;
					}
					if (!isWooPayEligible && payment.name === 'woopay') {
						return <Fragment></Fragment>;
					}
					j++;

					return payment.component;
				})}
				{ j < 21 && (
					<div className="woocommerce-payments-method-logos_count">
						+ { 21 - j }
					</div>
				)}
			</div>
		</>
)
	;
};
