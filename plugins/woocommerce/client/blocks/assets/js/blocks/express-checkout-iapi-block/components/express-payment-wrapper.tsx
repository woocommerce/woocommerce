/**
 * External dependencies
 */
import {
	CheckoutProvider,
	CheckoutEventsProvider,
} from '@woocommerce/base-context';

/**
 * Internal dependencies
 */
import CheckoutExpressPayment from '../../cart-checkout-shared/payment-methods/express-payment/checkout-express-payment';
import { ExpressPaymentContext } from '../../cart-checkout-shared/payment-methods/express-payment/express-payment-context';

interface ExpressPaymentWrapperProps {
	buttonHeight?: number;
	buttonBorderRadius?: number;
	showButtonStyles?: boolean;
}

/**
 * Wrapper component for Express Payment Methods in iAPI context.
 *
 * Uses the same structure as Checkout block:
 * - CheckoutProvider: Provides access to the Redux payment data store
 * - CheckoutEventsProvider: Handles payment method initialization and checkout state & events
 * - ExpressPaymentContext: Provides button styling configuration
 * - CheckoutExpressPayment: Renders express payment methods with loading states and notices
 */
const ExpressPaymentWrapper = ( {
	buttonHeight = 48,
	buttonBorderRadius = 4,
	showButtonStyles = true,
}: ExpressPaymentWrapperProps ) => {
	const expressPaymentContextValue = {
		showButtonStyles,
		buttonHeight: String( buttonHeight ),
		buttonBorderRadius: String( buttonBorderRadius ),
	};

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

export default ExpressPaymentWrapper;
