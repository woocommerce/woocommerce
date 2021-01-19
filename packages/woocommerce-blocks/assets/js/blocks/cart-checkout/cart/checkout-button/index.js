/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { PaymentMethodIcons } from '@woocommerce/base-components/cart-checkout';
import Button from '@woocommerce/base-components/button';
import { CHECKOUT_URL } from '@woocommerce/block-settings';
import { useCheckoutContext } from '@woocommerce/base-context';
import {
	usePaymentMethods,
	usePositionRelativeToViewport,
} from '@woocommerce/base-hooks';

/**
 * Internal dependencies
 */
import './style.scss';

const getIconsFromPaymentMethods = ( paymentMethods ) => {
	return Object.values( paymentMethods ).reduce( ( acc, paymentMethod ) => {
		if ( paymentMethod.icons !== null ) {
			acc = acc.concat( paymentMethod.icons );
		}
		return acc;
	}, [] );
};

/**
 * Checkout button rendered in the full cart page.
 *
 * @param {Object} props Incoming props for the component.
 * @param {string} props.link What the button is linked to.
 */
const CheckoutButton = ( { link } ) => {
	const { isCalculating } = useCheckoutContext();
	const [
		positionReferenceElement,
		positionRelativeToViewport,
	] = usePositionRelativeToViewport();
	const [ showSpinner, setShowSpinner ] = useState( false );
	const { paymentMethods } = usePaymentMethods();

	useEffect( () => {
		// Add a listener for when the page is unloaded (specifically needed for Safari)
		// to remove the spinner on the checkout button, so the saved page snapshot does not
		// contain the spinner class. See https://archive.is/lOEW0 for why this is needed.

		if (
			! window ||
			typeof window.addEventListener !== 'function' ||
			typeof window.removeEventListener !== 'function'
		) {
			return;
		}

		const hideSpinner = () => {
			setShowSpinner( false );
		};

		window.addEventListener( 'beforeunload', hideSpinner );

		return () => {
			window.removeEventListener( 'beforeunload', hideSpinner );
		};
	}, [] );

	const submitContainerContents = (
		<>
			<Button
				className="wc-block-cart__submit-button"
				href={ link || CHECKOUT_URL }
				disabled={ isCalculating }
				onClick={ () => setShowSpinner( true ) }
				showSpinner={ showSpinner }
			>
				{ __( 'Proceed to Checkout', 'woocommerce' ) }
			</Button>
			<PaymentMethodIcons
				icons={ getIconsFromPaymentMethods( paymentMethods ) }
			/>
		</>
	);

	return (
		<div className="wc-block-cart__submit">
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

export default CheckoutButton;
