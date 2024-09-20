/**
 * External dependencies
 */
import React, { useState, useEffect } from 'react';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import visaLogoAsset from '../../images/cards/visa.svg';
import masterCardLogoAsset from '../../images/cards/mastercard.svg';
import amexLogoAsset from '../../images/cards/amex.svg';
import discoverLogoAsset from '../../images/cards/discover.svg';
import applePayLogoAsset from '../../images/cards/applepay.svg';
import googlePayLogoAsset from '../../images/cards/googlepay.svg';
import jcbLogoAsset from '../../images/cards/jcb.svg';
import wooPayLogoAsset from '../../images/payment-methods/woopay.svg';
import afterPayLogoAsset from '../../images/payment-methods/afterpay.svg';
import affirmLogoAsset from '../../images/payment-methods/affirm.svg';
import klarnaLogoAsset from '../../images/payment-methods/klarna.svg';

/**
 * Payment methods logos
 */
const paymentMethods = [
	{
		name: 'visa',
		Component: () => (
			<img
				src={ visaLogoAsset }
				alt={ __( 'Visa logo', 'woocommerce' ) }
			/>
		),
	},
	{
		name: 'mastercard',
		Component: () => (
			<img
				src={ masterCardLogoAsset }
				alt={ __( 'MasterCard logo', 'woocommerce' ) }
			/>
		),
	},
	{
		name: 'amex',
		Component: () => (
			<img
				src={ amexLogoAsset }
				alt={ __( 'American Express logo', 'woocommerce' ) }
			/>
		),
	},
	{
		name: 'discover',
		Component: () => (
			<img
				src={ discoverLogoAsset }
				alt={ __( 'Discover logo', 'woocommerce' ) }
			/>
		),
	},
	{
		name: 'woopay',
		Component: () => (
			<img
				src={ wooPayLogoAsset }
				alt={ __( 'WooPay logo', 'woocommerce' ) }
			/>
		),
	},
	{
		name: 'applepay',
		Component: () => (
			<img
				src={ applePayLogoAsset }
				alt={ __( 'ApplePay logo', 'woocommerce' ) }
			/>
		),
	},
	{
		name: 'googlepay',
		Component: () => (
			<img
				src={ googlePayLogoAsset }
				alt={ __( 'GooglePay logo', 'woocommerce' ) }
			/>
		),
	},
	{
		name: 'afterpay',
		Component: () => (
			<img
				src={ afterPayLogoAsset }
				alt={ __( 'Afterpay logo', 'woocommerce' ) }
			/>
		),
	},
	{
		name: 'affirm',
		Component: () => (
			<img
				src={ affirmLogoAsset }
				alt={ __( 'Affirm logo', 'woocommerce' ) }
			/>
		),
	},
	{
		name: 'klarna',
		Component: () => (
			<img
				src={ klarnaLogoAsset }
				alt={ __( 'Klarna logo', 'woocommerce' ) }
			/>
		),
	},
	{
		name: 'jcb',
		Component: () => (
			<img src={ jcbLogoAsset } alt={ __( 'JCB logo', 'woocommerce' ) } />
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
				.filter(
					( icon ) => icon.name === 'woopay' && isWooPayEligible
				)
				.slice( 0, maxShownElements )
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
