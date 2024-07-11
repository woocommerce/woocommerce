/**
 * External dependencies
 */
import clsx from 'clsx';
import { useState, useEffect, useMemo } from '@wordpress/element';
import Button from '@woocommerce/base-components/button';
import { CHECKOUT_URL } from '@woocommerce/block-settings';
import { usePositionRelativeToViewport } from '@woocommerce/base-hooks';
import { getSetting } from '@woocommerce/settings';
import { useSelect } from '@wordpress/data';
import { CART_STORE_KEY, CHECKOUT_STORE_KEY } from '@woocommerce/block-data';
import { applyCheckoutFilter } from '@woocommerce/blocks-checkout';
import { isErrorResponse } from '@woocommerce/base-context';
import { useCartEventsContext } from '@woocommerce/base-context/providers';

/**
 * Internal dependencies
 */
import { defaultButtonLabel } from './constants';

/**
 * Checkout button rendered in the full cart page.
 */
const Block = ( {
	checkoutPageId,
	className,
	buttonLabel,
}: {
	checkoutPageId: number;
	className: string;
	buttonLabel: string;
} ): JSX.Element => {
	const link = getSetting< string >( 'page-' + checkoutPageId, false );
	const isCalculating = useSelect( ( select ) =>
		select( CHECKOUT_STORE_KEY ).isCalculating()
	);

	const [ positionReferenceElement, positionRelativeToViewport ] =
		usePositionRelativeToViewport();
	const [ showSpinner, setShowSpinner ] = useState( false );

	useEffect( () => {
		// Add a listener to remove the spinner on the checkout button, so the saved page snapshot does not
		// contain the spinner class. See https://archive.is/lOEW0 for why this is needed for Safari.

		if (
			typeof global.addEventListener !== 'function' ||
			typeof global.removeEventListener !== 'function'
		) {
			return;
		}

		const hideSpinner = () => {
			setShowSpinner( false );
		};

		global.addEventListener( 'pageshow', hideSpinner );

		return () => {
			global.removeEventListener( 'pageshow', hideSpinner );
		};
	}, [] );
	const cart = useSelect( ( select ) => {
		return select( CART_STORE_KEY ).getCartData();
	} );
	const label = applyCheckoutFilter< string >( {
		filterName: 'proceedToCheckoutButtonLabel',
		defaultValue: buttonLabel || defaultButtonLabel,
		arg: { cart },
	} );

	const filteredLink = applyCheckoutFilter< string >( {
		filterName: 'proceedToCheckoutButtonLink',
		defaultValue: link || CHECKOUT_URL,
		arg: { cart },
	} );

	const { dispatchOnProceedToCheckout } = useCartEventsContext();

	const submitContainerContents = (
		<Button
			className="wc-block-cart__submit-button"
			href={ filteredLink }
			disabled={ isCalculating }
			onClick={ ( e ) => {
				dispatchOnProceedToCheckout().then( ( observerResponses ) => {
					if ( observerResponses.some( isErrorResponse ) ) {
						e.preventDefault();
						return;
					}
					setShowSpinner( true );
				} );
			} }
			showSpinner={ showSpinner }
		>
			{ label }
		</Button>
	);

	// Get the body background color to use as the sticky container background color.
	const backgroundColor = useMemo(
		() => getComputedStyle( document.body ).backgroundColor,
		[]
	);

	const displayStickyContainer = positionRelativeToViewport === 'below';

	const submitContainerClass = clsx( 'wc-block-cart__submit-container', {
		'wc-block-cart__submit-container--sticky': displayStickyContainer,
	} );

	return (
		<div className={ clsx( 'wc-block-cart__submit', className ) }>
			{ positionReferenceElement }
			<div
				className={ submitContainerClass }
				style={ displayStickyContainer ? { backgroundColor } : {} }
			>
				{ submitContainerContents }
			</div>
		</div>
	);
};

export default Block;
