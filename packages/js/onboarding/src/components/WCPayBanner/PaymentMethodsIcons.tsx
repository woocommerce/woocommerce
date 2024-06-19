/**
 * External dependencies
 */
import { Fragment, createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Text } from '@woocommerce/experimental';

/**
 * Internal dependencies
 */
import {
	Visa,
	MasterCard,
	Amex,
	WooPay,
	ApplePay,
	Giropay,
	GooglePay,
	CB,
	Discover,
	UnionPay,
	JCB,
} from './Icons';
import { WooPaymentMethodLogos } from '../WooPaymentMethodLogos/WooPaymentMethodLogos';

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
		name: 'woopay',
		component: <WooPay />,
	},
	{
		name: 'applepay',
		component: <ApplePay />,
	},
	{
		name: 'giropay',
		component: <Giropay />,
	},
	{
		name: 'googlepay',
		component: <GooglePay />,
	},
	{
		name: 'cb',
		component: <CB />,
	},
	{
		name: 'discover',
		component: <Discover />,
	},
	{
		name: 'unionpay',
		component: <UnionPay />,
	},
	{
		name: 'jcb',
		component: <JCB />,
	},
];

export const PaymentMethodsIcons: React.VFC< {
	isWooPayEligible: boolean;
	maxNrElements: number;
} > = ( { isWooPayEligible = false, maxNrElements = 10 } ) => {
	return (
		<div className="woocommerce-recommended-payments-banner__footer_icon_container">
			<WooPaymentMethodLogos
				isWooPayEligible={ isWooPayEligible }
				maxNrElements={ 10 }
			/>
		</div>
		// <div className="woocommerce-recommended-payments-banner__footer_icon_container">
		// 	{ Payments.map( ( payment ) => {
		// 		if ( i == maxNrElements ) {
		// 			return <Fragment></Fragment>;
		// 		}
		// 		if ( ! isWooPayEligible && payment.name === 'woopay' ) {
		// 			return <Fragment></Fragment>;
		// 		}
		// 		i++;
		//
		// 		return payment.component;
		// 	} ) }
		// 	{ i < Payments.length && (
		// 		<div className="woocommerce-payments-method-logos_count">{ Payments.length - i }</div>
		// 	) }
		// </div>
	);
};
