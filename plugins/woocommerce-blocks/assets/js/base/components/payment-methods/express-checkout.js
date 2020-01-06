/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useExpressPaymentMethods } from '@woocommerce/base-hooks';

/**
 * Internal dependencies
 */
import ExpressPaymentMethods from './express-payment-methods';

import './style.scss';

const ExpressCheckoutContainer = ( { children } ) => {
	return (
		<div className="wc-component__checkout-container wc-component__container-with-border">
			<div className="wc-component__text-overlay-on-border">
				<strong>
					{ __( 'Express checkout', 'woo-gutenberg-products-block' ) }
				</strong>
			</div>
			<div className="wc-component__container-content">{ children }</div>
		</div>
	);
};

const ExpressCheckoutFormControl = () => {
	const { paymentMethods, isInitialized } = useExpressPaymentMethods();

	// determine whether we even show this
	// @todo if in the editor we probably would want to show a placeholder maybe?
	if (
		! isInitialized ||
		( isInitialized && Object.keys( paymentMethods ).length === 0 )
	) {
		return null;
	}

	return (
		<ExpressCheckoutContainer>
			<p>
				{ __(
					'In a hurry? Use one of our express checkout options below:',
					'woo-gutenberg-products-block'
				) }
			</p>
			<ExpressPaymentMethods />
		</ExpressCheckoutContainer>
	);
};

export default ExpressCheckoutFormControl;
