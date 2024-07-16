/**
 * External dependencies
 */
import React, { useState, useEffect } from 'react';
import { Fragment, createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Visa from '../../images/cards/visa';
import MasterCard from '../../images/cards/mastercard';
import Amex from '../../images/cards/amex';
import Discover from '../../images/cards/discover';
import ApplePay from '../../images/cards/applepay';
import GooglePay from '../../images/cards/googlepay';
import JCB from '../../images/cards/jcb';
import WooPay from '../../images/payment-methods/woopay';
import AfterPay from '../../images/payment-methods/afterpay';
import Affirm from '../../images/payment-methods/affirm';
import Klarna from '../../images/payment-methods/klarna';

/**
 * Payment methods logos
 */
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
		component: <AfterPay key="afterpay" />,
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

// Maximum number of logos to be displayed on a mobile screen.
const maxElementsMobile = 5;
// Maximum number of logos to be displayed on a tablet screen.
const maxElementsTablet = 7;
// Maximum number of logos to be displayed on a desktop screen.
const maxElementsDesktop = 10;
// Total number of available payment methods from https://woocommerce.com/document/woopayments/payment-methods.
const totalPaymentMethods = 20;

export const WooPaymentMethodsLogos: React.VFC< {
	isWooPayEligible: boolean;
	maxElements: number;
} > = ( { isWooPayEligible = false, maxElements = maxElementsDesktop } ) => {
	const [ maxShownElements, setMaxShownElements ] = useState( maxElements );

	// Determine the maximum number of logos to display, taking into account WooPay’s eligibility.
	const getMaxShownElements = (
		maxElementsNumber: number,
		isWooPayAvailable: boolean
	) => {
		if ( ! isWooPayAvailable ) {
			return maxElementsNumber + 1;
		}

		return maxElementsNumber;
	};

	useEffect( () => {
		const updateMaxElements = () => {
			if ( window.innerWidth <= 480 ) {
				setMaxShownElements( maxElementsMobile );
			} else if ( window.innerWidth <= 768 ) {
				setMaxShownElements( maxElementsTablet );
			} else {
				setMaxShownElements( maxElements );
			}
		};

		updateMaxElements();

		window.addEventListener( 'resize', updateMaxElements );

		return () => {
			window.removeEventListener( 'resize', updateMaxElements );
		};
	}, [ maxElements ] );

	return (
		<>
			<div className="woocommerce-woopayments-payment-methods-logos">
				{ PaymentMethods.slice(
					0,
					getMaxShownElements( maxShownElements, isWooPayEligible )
				).map( ( pm ) => {
					if ( ! isWooPayEligible && pm.name === 'woopay' ) {
						return null;
					}

					return pm.component;
				} ) }
				{ maxShownElements < totalPaymentMethods && (
					<div className="woocommerce-woopayments-payment-methods-logos-count">
						+ { totalPaymentMethods - maxShownElements }
					</div>
				) }
			</div>
		</>
	);
};
