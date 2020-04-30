/**
 * Internal dependencies
 */
import { ShippingDataProvider } from '../shipping';
import { CheckoutStateProvider } from '../checkout-state';
import { PaymentMethodDataProvider } from '../payment-methods';

/**
 * Cart provider
 * This wraps the Cart and provides an api interface for the Cart to
 * children via various hooks.
 *
 * @param {Object}  props               Incoming props for the provider.
 * @param {Object}  [props.children]    The children being wrapped.
 * @param {string}  [props.redirectUrl] Initialize what the cart will
 *                                      redirect to after successful
 *                                      submit.
 */
export const CartProvider = ( { children, redirectUrl } ) => {
	return (
		<CheckoutStateProvider redirectUrl={ redirectUrl } isCart={ true }>
			<ShippingDataProvider>
				<PaymentMethodDataProvider>
					{ children }
				</PaymentMethodDataProvider>
			</ShippingDataProvider>
		</CheckoutStateProvider>
	);
};
