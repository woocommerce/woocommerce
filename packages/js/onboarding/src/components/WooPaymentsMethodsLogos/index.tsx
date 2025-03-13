/**
 * External dependencies
 */
import { useState, useEffect } from 'react';
import { createElement } from '@wordpress/element';
import { Popover } from '@wordpress/components';
import { useDebounce } from '@wordpress/compose';

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
import Cartebancaire from '../../images/cards/cb';
import UnionPay from '../../images/cards/unionpay';
import Diners from '../../images/cards/diners';
import Eftpos from '../../images/cards/eftpos';
import Ideal from '../../images/payment-methods/ideal';
import Bancontact from '../../images/payment-methods/bancontact';
import Eps from '../../images/payment-methods/eps';
import Becs from '../../images/payment-methods/becs';
import Przelewy24 from '../../images/payment-methods/przelewy24';

/**
 * Payment methods list.
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
		name: 'cartebancaire',
		component: <Cartebancaire key="cartebancaire" />,
	},
	{
		name: 'unionpay',
		component: <UnionPay key="unionpay" />,
	},
	{
		name: 'diners',
		component: <Diners key="diners" />,
	},
	{
		name: 'eftpos',
		component: <Eftpos key="eftpos" />,
	},
	{
		name: 'jcb',
		component: <JCB key="jcb" />,
	},
	{
		name: 'bancontact',
		component: <Bancontact key="bancontact" />,
	},
	{
		name: 'becs',
		component: <Becs key="becs" />,
	},
	{
		name: 'eps',
		component: <Eps key="eps" />,
	},
	{
		name: 'ideal',
		component: <Ideal key="ideal" />,
	},
	{
		name: 'przelewy24',
		component: <Przelewy24 key="przelewy24" />,
	},
];

export const WooPaymentsMethodsLogos: React.FC< {
	isWooPayEligible: boolean;
	maxElements: number;
	tabletWidthBreakpoint?: number;
	maxElementsTablet?: number;
	mobileWidthBreakpoint?: number;
	maxElementsMobile?: number;
	totalPaymentMethods?: number;
} > = ( {
	/**
	 * Whether the store (location) is eligible for WooPay.
	 * Based on this we will include or not the WooPay logo in the list.
	 */
	isWooPayEligible = false,
	/**
	 * Maximum number of logos to be displayed (on a desktop screen).
	 */
	maxElements = 10,
	/**
	 * Breakpoint at which the number of logos to display changes to the tablet layout.
	 */
	tabletWidthBreakpoint = 768,
	/**
	 * Maximum number of logos to be displayed on a tablet screen.
	 */
	maxElementsTablet = 7,
	/**
	 * Breakpoint at which the number of logos to display changes to the mobile layout.
	 */
	mobileWidthBreakpoint = 480,
	/**
	 * Maximum number of logos to be displayed on a mobile screen.
	 */
	maxElementsMobile = 5,
	/**
	 * Total number of payment methods that WooPayments supports.
	 * The default is set according to https://woocommerce.com/document/woopayments/payment-methods.
	 * If not eligible for WooPay, the total number of payment methods is reduced by one.
	 */
	totalPaymentMethods = 20,
} ) => {
	const [ maxShownElements, setMaxShownElements ] = useState( maxElements );
	const [ isPopoverVisible, setPopoverVisible ] = useState( false );

	const hidePopoverDebounced = useDebounce( () => {
		setPopoverVisible( false );
	}, 350 );
	const showPopover = () => {
		setPopoverVisible( true );
		hidePopoverDebounced.cancel();
	};

	// Reduce the total number of payment methods by one if the store is not eligible for WooPay.
	const maxSupportedPaymentMethods = isWooPayEligible
		? totalPaymentMethods
		: totalPaymentMethods - 1;

	/**
	 * Determine the maximum number of logos to display, taking into account WooPay’s eligibility.
	 */
	const getMaxShownElements = ( maxElementsNumber: number ) => {
		if ( ! isWooPayEligible ) {
			return maxElementsNumber + 1;
		}

		return maxElementsNumber;
	};

	useEffect( () => {
		const updateMaxElements = () => {
			if ( window.innerWidth <= mobileWidthBreakpoint ) {
				setMaxShownElements( maxElementsMobile );
			} else if ( window.innerWidth <= tabletWidthBreakpoint ) {
				setMaxShownElements( maxElementsTablet );
			} else {
				setMaxShownElements( maxElements );
			}
		};

		updateMaxElements();

		// Update the number of logos to display when the window is resized.
		window.addEventListener( 'resize', updateMaxElements );

		// Cleanup on unmount.
		return () => {
			window.removeEventListener( 'resize', updateMaxElements );
		};
	}, [
		maxElements,
		maxElementsMobile,
		maxElementsTablet,
		tabletWidthBreakpoint,
		mobileWidthBreakpoint,
	] );

	const visiblePaymentMethods = PaymentMethods.slice(
		0,
		getMaxShownElements( maxShownElements )
	).filter( ( pm ) => isWooPayEligible || pm.name !== 'woopay' );

	const hiddenPaymentMethods = PaymentMethods.slice(
		getMaxShownElements( maxShownElements )
	).filter( ( pm ) => isWooPayEligible || pm.name !== 'woopay' );

	return (
		<div className="woocommerce-woopayments-payment-methods-logos">
			{ visiblePaymentMethods.map( ( pm ) => pm.component ) }
			{ maxShownElements < maxSupportedPaymentMethods && (
				<div
					className="woocommerce-woopayments-payment-methods-logos-count"
					role="button"
					tabIndex={ 0 }
					onClick={ () => setPopoverVisible( ! isPopoverVisible ) }
					onMouseEnter={ showPopover }
					onMouseLeave={ hidePopoverDebounced }
					onKeyDown={ ( event ) => {
						if ( event.key === 'Enter' || event.key === ' ' ) {
							setPopoverVisible( ! isPopoverVisible );
						}
					} }
				>
					+ { maxSupportedPaymentMethods - maxShownElements }
					{ isPopoverVisible && (
						<Popover
							placement="top-start"
							className="woocommerce-woopayments-payment-methods-logos-popover"
							focusOnMount={ false }
							noArrow={ true }
							shift={ true }
							onClose={ hidePopoverDebounced }
						>
							<div className="woocommerce-woopayments-payment-methods-logos">
								{ hiddenPaymentMethods.map(
									( pm ) => pm.component
								) }
							</div>
						</Popover>
					) }
				</div>
			) }
		</div>
	);
};
