/**
 * Internal dependencies
 */
import { PaymentMethodDataProvider } from '../payment-methods';
import { ShippingDataProvider } from '../shipping';
import { BillingDataProvider } from '../billing';
import { CheckoutStateProvider } from '../checkout-state';
import CheckoutProcessor from './processor';

/**
 * Checkout provider
 * This wraps the checkout and provides an api interface for the checkout to
 * children via various hooks.
 *
 * @param {Object}  props               Incoming props for the provider.
 * @param {Object}  props.children      The children being wrapped.
 * @param {string}  [props.redirectUrl] Initialize what the checkout will
 *                                      redirect to after successful
 *                                      submit.
 */
export const CheckoutProvider = ( { children, redirectUrl } ) => {
	return (
		<CheckoutStateProvider redirectUrl={ redirectUrl } isCart={ false }>
			<BillingDataProvider>
				<ShippingDataProvider>
					<PaymentMethodDataProvider>
						{ children }
						<CheckoutProcessor />
					</PaymentMethodDataProvider>
				</ShippingDataProvider>
			</BillingDataProvider>
		</CheckoutStateProvider>
	);
};
