/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useExpressPaymentMethods } from '@woocommerce/base-hooks';
import { StoreNoticesProvider } from '@woocommerce/base-context';
import Title from '@woocommerce/base-components/title';

/**
 * Internal dependencies
 */
import ExpressPaymentMethods from './express-payment-methods';
import './style.scss';

const ExpressCheckoutContainer = ( { children } ) => {
	return (
		<>
			<div className="wc-block-components-express-checkout">
				<Title
					className="wc-block-components-express-checkout__title"
					headingLevel="2"
				>
					{ __( 'Express checkout', 'woocommerce' ) }
				</Title>
				<div className="wc-block-components-express-checkout__content">
					<StoreNoticesProvider context="wc/express-payment-area">
						{ children }
					</StoreNoticesProvider>
				</div>
			</div>
			<div className="wc-block-components-express-checkout-continue-rule">
				{ __( 'Or continue below', 'woocommerce' ) }
			</div>
		</>
	);
};

const ExpressCheckoutFormControl = () => {
	const { paymentMethods, isInitialized } = useExpressPaymentMethods();

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
					'woocommerce'
				) }
			</p>
			<ExpressPaymentMethods />
		</ExpressCheckoutContainer>
	);
};

export default ExpressCheckoutFormControl;
