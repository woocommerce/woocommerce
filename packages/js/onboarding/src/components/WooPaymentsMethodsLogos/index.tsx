/**
 * External dependencies
 */
import React, { useState, useEffect } from 'react';
import { Fragment, createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import visaAsset from '../../images/cards/visa.svg';
import masterCardAsset from '../../images/cards/mastercard.svg';
import amexAsset from '../../images/cards/amex.svg';
import Discover from '../../images/cards/discover';
import applePayAsset from '../../images/cards/applepay.svg';
import googlePayAsset from '../../images/cards/googlepay.svg';
import jcbAsset from '../../images/cards/jcb.svg';
import wooPayAsset from '../../images/payment-methods/woopay.svg';
import afterPayAsset from '../../images/payment-methods/afterpay.svg';
import affirmAsset from '../../images/payment-methods/affirm.svg';
import klarnaAsset from '../../images/payment-methods/klarna.svg';

/**
 * Payment methods logos
 */
const paymentMethods = [
	{
		name: 'visa',
		Component: () => (
			<img src={ visaAsset } alt={ __( 'Visa logo', 'woocommerce' ) } />
		),
	},
	{
		name: 'mastercard',
		Component: () => (
			<img
				src={ masterCardAsset }
				alt={ __( 'MasterCard logo', 'woocommerce' ) }
			/>
		),
	},
	{
		name: 'amex',
		Component: () => (
			<img
				src={ amexAsset }
				alt={ __( 'American Express logo', 'woocommerce' ) }
			/>
		),
	},
	{
		name: 'discover',
		Component: () => <Discover key="discover" />,
	},
	{
		name: 'woopay',
		Component: (
			<img
				src={ wooPayAsset }
				alt={ __( 'WooPay logo', 'woocommerce' ) }
			/>
		),
	},
	{
		name: 'applepay',
		Component: (
			<img
				src={ applePayAsset }
				alt={ __( 'ApplePay logo', 'woocommerce' ) }
			/>
		),
	},
	{
		name: 'googlepay',
		Component: (
			<img
				src={ googlePayAsset }
				alt={ __( 'GooglePay logo', 'woocommerce' ) }
			/>
		),
	},
	{
		name: 'afterpay',
		Component: (
			<img
				src={ afterPayAsset }
				alt={ __( 'Afterpay logo', 'woocommerce' ) }
			/>
		),
	},
	{
		name: 'affirm',
		Component: (
			<img
				src={ affirmAsset }
				alt={ __( 'Affirm logo', 'woocommerce' ) }
			/>
		),
	},
	{
		name: 'klarna',
		Component: (
			<img
				src={ klarnaAsset }
				alt={ __( 'Klarna logo', 'woocommerce' ) }
			/>
		),
	},
	{
		name: 'jcb',
		Component: (
			<img src={ jcbAsset } alt={ __( 'JCB logo', 'woocommerce' ) } />
		),
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
		<div className="woocommerce-woopayments-payment-methods-logos">
			{ paymentMethods
				.slice(
					0,
					getMaxShownElements(
						maxShownElements,
						isWooPayEligible
					)
				)
				.map( ( { name, Component } ) => {
					if ( ! isWooPayEligible && name === 'woopay' ) {
						return null;
					}

					return <Component key={ name } />;
				} ) }
			{ maxShownElements < totalPaymentMethods && (
				<div className="woocommerce-woopayments-payment-methods-logos-count">
					+ { totalPaymentMethods - maxShownElements }
				</div>
			) }
		</div>
	);
};
