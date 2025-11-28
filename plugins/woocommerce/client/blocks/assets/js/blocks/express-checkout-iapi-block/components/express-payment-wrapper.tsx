/**
 * External dependencies
 */
import {
	CheckoutProvider,
	CheckoutEventsProvider,
} from '@woocommerce/base-context';
import { useEffect } from '@wordpress/element';

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

	// Performance measurement: component mounted.
	useEffect( () => {
		performance.mark( 'express-iapi-mounted' );
		performance.measure(
			'express-iapi-total',
			'express-iapi-script-start',
			'express-iapi-mounted'
		);
		performance.measure(
			'express-iapi-callback-to-mount',
			'express-iapi-callback-start',
			'express-iapi-mounted'
		);

		const total = performance.getEntriesByName( 'express-iapi-total' )[ 0 ];
		const cbToMount = performance.getEntriesByName(
			'express-iapi-callback-to-mount'
		)[ 0 ];
		// eslint-disable-next-line no-console
		console.log(
			`[Perf] iAPI Block: ${ total?.duration.toFixed(
				2
			) }ms (callback→mount: ${ cbToMount?.duration.toFixed( 2 ) }ms)`
		);

		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		( window as any ).__expressMetrics =
			// eslint-disable-next-line @typescript-eslint/no-explicit-any
			( window as any ).__expressMetrics || {};
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		( window as any ).__expressMetrics.iapi = total?.duration;
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		( window as any ).__expressMetrics.iapiCallbackToMount =
			cbToMount?.duration;
	}, [] );

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
