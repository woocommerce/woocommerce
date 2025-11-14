/**
 * External dependencies
 */
import { CartExpressPayment } from '../../cart-checkout-shared/payment-methods';
import { ExpressPaymentContext } from '../../cart-checkout-shared/payment-methods/express-payment/express-payment-context';
import {
	CheckoutProvider,
	CheckoutEventsProvider,
} from '@woocommerce/base-context';
import { useStoreCart } from '@woocommerce/base-context/hooks';
import { useSelect } from '@wordpress/data';
import { PAYMENT_STORE_KEY } from '@woocommerce/block-data';

interface MiniCartExpressPaymentWrapperProps {
	buttonHeight?: number;
	buttonBorderRadius?: number;
	showButtonStyles?: boolean;
}

/**
 * Wrapper component for Express Payment Methods in iAPI Mini-Cart context.
 *
 * Uses the same structure as Cart block:
 * - CheckoutProvider: Provides access to the Redux payment data store
 * - CheckoutEventsProvider: Handles payment method initialization and checkout state & events
 * - ExpressPaymentContext: Provides button styling configuration
 * - CartExpressPayment: Renders express payment methods with loading states and notices
 */
const MiniCartExpressPaymentWrapper = ( {
	buttonHeight = 48,
	buttonBorderRadius = 4,
	showButtonStyles = true,
}: MiniCartExpressPaymentWrapperProps ) => {
	const { cartItems } = useStoreCart();
	const expressPaymentContextValue = {
		showButtonStyles,
		buttonHeight: String( buttonHeight ),
		buttonBorderRadius: String( buttonBorderRadius ),
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

	// Don't render if cart is empty
	if ( cartItems.length === 0 ) {
		return null;
	}

	// TO DO: Handle editor mode.

	return (
		<CheckoutProvider>
			<CheckoutEventsProvider redirectUrl="">
				<ExpressPaymentContext.Provider
					value={ expressPaymentContextValue }
				>
					<CartExpressPayment />
				</ExpressPaymentContext.Provider>
			</CheckoutEventsProvider>
		</CheckoutProvider>
	);
};

export default MiniCartExpressPaymentWrapper;
