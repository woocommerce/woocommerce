/**
 * Internal dependencies
 */
import { CheckoutProvider } from '../checkout';

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
		<CheckoutProvider isCart={ true } redirectUrl={ redirectUrl }>
			{ children }
		</CheckoutProvider>
	);
};
