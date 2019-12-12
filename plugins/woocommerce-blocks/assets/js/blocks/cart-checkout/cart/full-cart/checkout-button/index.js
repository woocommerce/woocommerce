/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Fragment } from '@wordpress/element';
import Button from '@woocommerce/base-components/button';
import { CHECKOUT_URL } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import { AmericanExpressLogo } from './american-express-logo';
import './style.scss';
import { MastercardLogo, VisaLogo } from './payment-logos'; // @todo we want to import this from `@automattic/composite-checkout` when it's published in NPM

/**
 * Checkout button rendered in the full cart page.
 */
const CheckoutButton = () => {
	return (
		<Fragment>
			<Button
				className="wc-block-cart__submit-button"
				href={ CHECKOUT_URL }
			>
				{ __( 'Proceed to Checkout', 'woo-gutenberg-products-block' ) }
			</Button>
			<div className="wc-block-cart__payment-methods">
				<MastercardLogo />
				<AmericanExpressLogo />
				<VisaLogo />
			</div>
		</Fragment>
	);
};

export default CheckoutButton;
