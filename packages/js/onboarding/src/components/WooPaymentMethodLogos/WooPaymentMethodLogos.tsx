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

const PaymentMethods = [
	{
		name: 'visa',
		component: <Visa key="visa" />,
	},
	{
		name: 'mastercard',
		component: <MasterCard key="mastercard" />,
	},
	{
		name: 'amex',
		component: <Amex key="amex" />,
	},
	{
		name: 'discover',
		component: <Discover key="discover" />,
	},
	{
		name: 'woopay',
		component: <WooPay key="woopay" />,
	},
	{
		name: 'applepay',
		component: <ApplePay key="applepay" />,
	},
	{
		name: 'googlepay',
		component: <GooglePay key="googlepay" />,
	},
	{
		name: 'afterpay',
		component: <Afterpay key="afterpay" />,
	},
	{
		name: 'affirm',
		component: <Affirm key="affirm" />,
	},
	{
		name: 'klarna',
		component: <Klarna key="klarna" />,
	},
	{
		name: 'jcb',
		component: <JCB key="jcb" />,
	},
];

export const WooPaymentMethodLogos: React.VFC< {
	isWooPayEligible: boolean;
	maxElements: number;
} > = ( { isWooPayEligible = false, maxElements = 10 } ) => {
	const totalPaymentMethods = 21;
	const maxElementsMiniView = 5;
	return (
		<>
			<div className="woocommerce-payments-method-logos">
				{ PaymentMethods.slice( 0, maxElements ).map( ( pm ) => {
					if ( ! isWooPayEligible && pm.name === 'woopay' ) {
						return <Fragment key={ pm.name }></Fragment>;
					}

					return pm.component;
				} ) }
				{ maxElements < totalPaymentMethods && (
					<div className="woocommerce-payments-method-logos_count">
						+ { totalPaymentMethods - maxElements }
					</div>
				) }
			</div>

			<div className="woocommerce-payments-method-logos_mini">
				{ PaymentMethods.slice( 0, maxElementsMiniView ).map(
					( pm ) => {
						if ( ! isWooPayEligible && pm.name === 'woopay' ) {
							return <Fragment key={ pm.name }></Fragment>;
						}
						return pm.component;
					}
				) }
				<div className="woocommerce-payments-method-logos_count">
					+ { totalPaymentMethods - maxElementsMiniView }
				</div>
			</div>
		</>
	);
};
