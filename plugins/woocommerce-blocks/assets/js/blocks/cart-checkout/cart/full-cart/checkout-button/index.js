/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Button } from '@woocommerce/base-components/cart-checkout';
import { CHECKOUT_URL } from '@woocommerce/block-settings';
import { useCheckoutContext } from '@woocommerce/base-context';
/**
 * Internal dependencies
 */
import { AmericanExpressLogo } from './american-express-logo';
import './style.scss';
import { MastercardLogo, VisaLogo } from './payment-logos'; // @todo we want to import this from `@automattic/composite-checkout` when it's published in NPM

/**
 * Checkout button rendered in the full cart page.
 */
const CheckoutButton = ( { link } ) => {
	const { isCalculating } = useCheckoutContext();
	const [ showSpinner, setShowSpinner ] = useState( false );

	return (
		<div className="wc-block-cart__submit-container">
			<Button
				className="wc-block-cart__submit-button"
				href={ link || CHECKOUT_URL }
				disabled={ isCalculating }
				onClick={ () => setShowSpinner( true ) }
				showSpinner={ showSpinner }
			>
				{ __( 'Proceed to Checkout', 'woo-gutenberg-products-block' ) }
			</Button>
			<div className="wc-block-cart__payment-methods">
				<MastercardLogo />
				<AmericanExpressLogo />
				<VisaLogo />
			</div>
		</div>
	);
};

export default CheckoutButton;
