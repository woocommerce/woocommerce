/**
 * External dependencies
 */
import { useState, useEffect, useRef } from 'react';
import { createElement } from '@wordpress/element';
import { Popover } from '@wordpress/components';

/**
 * Internal dependencies
 */
import visa from '../../images/cards/visa.svg';
import mastercard from '../../images/cards/mastercard.svg';
import amex from '../../images/cards/amex.svg';
import discover from '../../images/cards/discover.svg';
import applepay from '../../images/cards/applepay.svg';
import googlepay from '../../images/cards/googlepay.svg';
import jcb from '../../images/cards/jcb.svg';
import cartebancaire from '../../images/cards/cb.svg';
import unionpay from '../../images/cards/unionpay.svg';
import diners from '../../images/cards/diners.svg';
import eftpos from '../../images/cards/eftpos.svg';
import woopay from '../../images/payment-methods/woopay.svg';
import afterpay from '../../images/payment-methods/afterpay.svg';
import affirm from '../../images/payment-methods/affirm.svg';
import klarna from '../../images/payment-methods/klarna.svg';
import ideal from '../../images/payment-methods/ideal.svg';
import bancontact from '../../images/payment-methods/bancontact.svg';
import eps from '../../images/payment-methods/eps.svg';
import becs from '../../images/payment-methods/becs.svg';
import przelewy24 from '../../images/payment-methods/przelewy24.svg';
import grabpay from '../../images/payment-methods/grabpay.svg';

interface PaymentMethod {
	name: string;
	/** URL of the logo asset. */
	src: string;
	/**
	 * CSS background painted on the element behind the logo. Some marks ship
	 * with a non-transparent backdrop; since the logo is rendered through an
	 * <img> (and letterboxed to the fixed box), the backdrop has to live on
	 * the element rather than inside the asset so it fills the whole box.
	 */
	background?: string;
}

/**
 * Payment methods list.
 */
const PaymentMethods: PaymentMethod[] = [
	{ name: 'visa', src: visa },
	{ name: 'mastercard', src: mastercard },
	{ name: 'amex', src: amex, background: '#006FCF' },
	{ name: 'discover', src: discover },
	{ name: 'woopay', src: woopay, background: '#873EFF' },
	{ name: 'applepay', src: applepay },
	{ name: 'googlepay', src: googlepay },
	{ name: 'afterpay', src: afterpay, background: '#B2FCE4' },
	{ name: 'affirm', src: affirm },
	{ name: 'klarna', src: klarna, background: '#FFB3C7' },
	{
		name: 'cartebancaire',
		src: cartebancaire,
		background:
			'linear-gradient(30deg, #2E2E79 0%, #2581C4 25%, #E6D6DB 50%, #E3756A 75%, #C90C0F 100%)',
	},
	{ name: 'unionpay', src: unionpay },
	{ name: 'diners', src: diners },
	{ name: 'eftpos', src: eftpos, background: 'rgba(31, 0, 56, 1)' },
	{ name: 'jcb', src: jcb, background: 'rgba(14, 76, 150, 1)' },
	{ name: 'bancontact', src: bancontact },
	{ name: 'becs', src: becs },
	{ name: 'eps', src: eps },
	{ name: 'ideal', src: ideal },
	{ name: 'przelewy24', src: przelewy24 },
	{ name: 'grabpay', src: grabpay },
];

// Logos are decorative: the surrounding copy and the "+N" affordance convey
// the meaning, so they carry no accessible name (matching the prior inline SVGs).
const renderLogo = ( pm: PaymentMethod ) => (
	<img
		key={ pm.name }
		src={ pm.src }
		alt=""
		width={ pm.background ? 40 : 38 }
		height={ pm.background ? 26 : 24 }
		className={ pm.background ? 'has-background' : undefined }
		style={ pm.background ? { background: pm.background } : undefined }
		loading="lazy"
	/>
);

export const WooPaymentsMethodsLogos = ( {
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
	totalPaymentMethods = 21,
}: {
	isWooPayEligible: boolean;
	maxElements: number;
	tabletWidthBreakpoint?: number;
	maxElementsTablet?: number;
	mobileWidthBreakpoint?: number;
	maxElementsMobile?: number;
	totalPaymentMethods?: number;
} ) => {
	const [ maxShownElements, setMaxShownElements ] = useState( maxElements );
	const [ isPopoverVisible, setPopoverVisible ] = useState( false );
	const buttonRef = useRef< HTMLDivElement >( null );

	const handleClick = ( event: React.MouseEvent | React.KeyboardEvent ) => {
		const clickedElement = event.target as HTMLElement;
		const parentDiv = clickedElement.closest(
			'.woocommerce-woopayments-payment-methods-logos-count'
		);

		if ( buttonRef.current && parentDiv !== buttonRef.current ) {
			return;
		}

		setPopoverVisible( ( prev ) => ! prev );
	};

	const handleFocusOutside = () => {
		setPopoverVisible( false );
	};

	const handleKeyDown = ( event: React.KeyboardEvent ) => {
		if ( event.key === 'Escape' && isPopoverVisible ) {
			event.stopPropagation();
			setPopoverVisible( false );
			buttonRef.current?.focus();
		} else if ( event.key === 'Enter' || event.key === ' ' ) {
			event.preventDefault();
			handleClick( event );
		}
	};

	// Handle Escape key globally when popover is open (for portal focus)
	useEffect( () => {
		if ( ! isPopoverVisible ) {
			return;
		}

		const handleGlobalKeyDown = ( event: KeyboardEvent ) => {
			if ( event.key === 'Escape' ) {
				event.stopPropagation();
				setPopoverVisible( false );
				buttonRef.current?.focus();
			}
		};

		document.addEventListener( 'keydown', handleGlobalKeyDown );
		return () => {
			document.removeEventListener( 'keydown', handleGlobalKeyDown );
		};
	}, [ isPopoverVisible ] );

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
			{ visiblePaymentMethods.map( renderLogo ) }
			{ maxShownElements < maxSupportedPaymentMethods && (
				<div
					className="woocommerce-woopayments-payment-methods-logos-count"
					role="button"
					tabIndex={ 0 }
					ref={ buttonRef }
					onClick={ handleClick }
					onKeyDown={ handleKeyDown }
				>
					+ { maxSupportedPaymentMethods - maxShownElements }
					{ isPopoverVisible && (
						<Popover
							className="woocommerce-woopayments-payment-methods-logos-popover"
							placement="top-start"
							offset={ 4 }
							variant="unstyled"
							focusOnMount={ true }
							noArrow={ true }
							shift={ true }
							onFocusOutside={ handleFocusOutside }
							onKeyDown={ handleKeyDown }
						>
							<div className="woocommerce-woopayments-payment-methods-logos">
								{ hiddenPaymentMethods.map( renderLogo ) }
							</div>
						</Popover>
					) }
				</div>
			) }
		</div>
	);
};
