/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	Button,
	PaymentMethodIcons,
} from '@woocommerce/base-components/cart-checkout';
import { CHECKOUT_URL } from '@woocommerce/block-settings';
import { useCheckoutContext } from '@woocommerce/base-context';
import { usePaymentMethods } from '@woocommerce/base-hooks';

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
 */
const CheckoutButton = ( { link } ) => {
	const { isCalculating } = useCheckoutContext();
	const [ showSpinner, setShowSpinner ] = useState( false );
	const { paymentMethods } = usePaymentMethods();

	return (
		<div className="wc-block-cart__submit-container">
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
		</div>
	);
};

export default CheckoutButton;
