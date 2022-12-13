/**
 * External dependencies
 */
import classnames from 'classnames';
import { useState, useEffect } from '@wordpress/element';
import Button from '@woocommerce/base-components/button';
import { CHECKOUT_URL } from '@woocommerce/block-settings';
import { usePositionRelativeToViewport } from '@woocommerce/base-hooks';
import { getSetting } from '@woocommerce/settings';
import { useSelect } from '@wordpress/data';
import { CHECKOUT_STORE_KEY } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import './style.scss';
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
	const link = getSetting( 'page-' + checkoutPageId, false );
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

	const submitContainerContents = (
		<Button
			className="wc-block-cart__submit-button"
			href={ link || CHECKOUT_URL }
			disabled={ isCalculating }
			onClick={ () => setShowSpinner( true ) }
			showSpinner={ showSpinner }
		>
			{ buttonLabel || defaultButtonLabel }
		</Button>
	);

	return (
		<div className={ classnames( 'wc-block-cart__submit', className ) }>
			{ positionReferenceElement }
			{ /* The non-sticky container must always be visible because it gives height to its parent, which is required to calculate when it becomes visible in the viewport. */ }
			<div className="wc-block-cart__submit-container">
				{ submitContainerContents }
			</div>
			{ /* If the positionReferenceElement is below the viewport, display the sticky container. */ }
			{ positionRelativeToViewport === 'below' && (
				<div className="wc-block-cart__submit-container wc-block-cart__submit-container--sticky">
					{ submitContainerContents }
				</div>
			) }
		</div>
	);
};

export default Block;
