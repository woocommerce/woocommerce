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
import { cartStore, checkoutStore } from '@woocommerce/block-data';
import { applyCheckoutFilter } from '@woocommerce/blocks-checkout';
import { isErrorResponse } from '@woocommerce/types';
import { useCartEventsContext } from '@woocommerce/base-context/providers';
import { Spinner } from '@woocommerce/blocks-components';
import { useStoreCart } from '@woocommerce/base-context/hooks';

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
	const { cartIsLoading } = useStoreCart();
	const isCalculating = useSelect(
		( select ) => select( checkoutStore ).isCalculating(),
		[]
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
		return select( cartStore ).getCartData();
	}, [] );

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
			className={ clsx( 'wc-block-cart__submit-button', {
				'wc-block-cart__submit-button--loading': showSpinner,
			} ) }
			href={ filteredLink }
			disabled={ isCalculating || cartIsLoading }
			onClick={ ( e ) => {
				dispatchOnProceedToCheckout().then( ( observerResponses ) => {
					if ( observerResponses.some( isErrorResponse ) ) {
						e.preventDefault();
						return;
					}
					setShowSpinner( true );
				} );
			} }
		>
			{ showSpinner && <Spinner /> }
			{ label }
		</Button>
	);

	// Get the body background color to use as the sticky container background color.
	const backgroundColor = useMemo( () => {
		const computedColor = getComputedStyle( document.body ).backgroundColor;
		if (
			! computedColor ||
			computedColor === 'rgba(0, 0, 0, 0)' ||
			computedColor === 'transparent'
		) {
			return '#fff'; // default fallback
		}

		return computedColor;
	}, [] );

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
