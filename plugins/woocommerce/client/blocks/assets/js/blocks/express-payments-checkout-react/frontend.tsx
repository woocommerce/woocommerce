/**
 * External dependencies
 */
import { renderFrontend } from '@woocommerce/base-utils';
import CheckoutExpressPayment from '../cart-checkout-shared/payment-methods/express-payment/checkout-express-payment';
import {
	CheckoutProvider,
	CheckoutEventsProvider,
} from '@woocommerce/base-context';
import { ExpressPaymentContext } from '../cart-checkout-shared/payment-methods/express-payment/express-payment-context';
import { useSelect } from '@wordpress/data';
import { PAYMENT_STORE_KEY } from '@woocommerce/block-data';

const Block = (): JSX.Element => {
	const expressPaymentContextValue = {
		showButtonStyles: true,
		buttonHeight: '48',
		buttonBorderRadius: '4',
	};

	// Debug: Log payment store state
	const paymentState = useSelect( ( select ) => {
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		const store = select( PAYMENT_STORE_KEY ) as any;
		return {
			availableExpressPaymentMethods:
				store.getAvailableExpressPaymentMethods(),
			registeredExpressPaymentMethods:
				store.getRegisteredExpressPaymentMethods(),
			expressPaymentMethodsInitialized:
				store.expressPaymentMethodsInitialized(),
		};
	} );

	console.log(
		'>>>>>>> MiniCartExpressPaymentWrapper paymentState',
		paymentState
	);

	return (
		<CheckoutProvider>
			<CheckoutEventsProvider redirectUrl="">
				<ExpressPaymentContext.Provider
					value={ expressPaymentContextValue }
				>
					<CheckoutExpressPayment />
				</ExpressPaymentContext.Provider>
			</CheckoutEventsProvider>
		</CheckoutProvider>
	);
};

renderFrontend( {
	Block,
	selector:
		'.wp-block-woocommerce-express-payments-checkout-react[data-block-name="woocommerce/express-payments-checkout-react"]',
} );
