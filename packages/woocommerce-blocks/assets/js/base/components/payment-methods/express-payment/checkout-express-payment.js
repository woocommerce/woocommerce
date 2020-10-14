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
import ExpressPaymentMethods from '../express-payment-methods';
import './style.scss';

const CheckoutExpressPayment = () => {
	const { paymentMethods, isInitialized } = useExpressPaymentMethods();

	if (
		! isInitialized ||
		( isInitialized && Object.keys( paymentMethods ).length === 0 )
	) {
		return null;
	}

	return (
		<>
			<div className="wc-block-components-express-payment wc-block-components-express-payment--checkout">
				<div className="wc-block-components-express-payment__title-container">
					<Title
						className="wc-block-components-express-payment__title"
						headingLevel="2"
					>
						{ __(
							'Express checkout',
							'woocommerce'
						) }
					</Title>
				</div>
				<div className="wc-block-components-express-payment__content">
					<StoreNoticesProvider context="wc/express-payment-area">
						<p>
							{ __(
								'In a hurry? Use one of our express checkout options below:',
								'woocommerce'
							) }
						</p>
						<ExpressPaymentMethods />
					</StoreNoticesProvider>
				</div>
			</div>
			<div className="wc-block-components-express-payment-continue-rule wc-block-components-express-payment-continue-rule--checkout">
				{ __( 'Or continue below', 'woocommerce' ) }
			</div>
		</>
	);
};

export default CheckoutExpressPayment;
