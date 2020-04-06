/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useExpressPaymentMethods } from '@woocommerce/base-hooks';
import { StoreNoticesProvider } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import ExpressPaymentMethods from './express-payment-methods';
import './style.scss';

const ExpressCheckoutContainer = ( { children } ) => {
	return (
		<>
			<div className="wc-block-component-express-checkout">
				<div className="wc-block-component-express-checkout__title">
					{ __( 'Express checkout', 'woo-gutenberg-products-block' ) }
				</div>
				<div className="wc-block-component-express-checkout__content">
					<StoreNoticesProvider context="wc/express-payment-area">
						{ children }
					</StoreNoticesProvider>
				</div>
			</div>
			<div className="wc-block-component-express-checkout-continue-rule">
				{ __( 'Or continue below', 'woo-gutenberg-products-block' ) }
			</div>
		</>
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
