/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useExpressPaymentMethods } from '@woocommerce/base-hooks';
import { StoreNoticesProvider } from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import ExpressPaymentMethods from '../express-payment-methods';
import './style.scss';

const CartExpressPayment = () => {
	const { paymentMethods, isInitialized } = useExpressPaymentMethods();

	if (
		! isInitialized ||
		( isInitialized && Object.keys( paymentMethods ).length === 0 )
	) {
		return null;
	}

	return (
		<>
			<div className="wc-block-components-express-payment wc-block-components-express-payment--cart">
				<div className="wc-block-components-express-payment__content">
					<StoreNoticesProvider context="wc/express-payment-area">
						<ExpressPaymentMethods />
					</StoreNoticesProvider>
				</div>
			</div>
			<div className="wc-block-components-express-payment-continue-rule wc-block-components-express-payment-continue-rule--cart">
				{ /* translators: Shown in the Cart block between the express payment methods and the Proceed to Checkout button */ }
				{ __( 'Or', 'woocommerce' ) }
			</div>
		</>
	);
};

export default CartExpressPayment;
