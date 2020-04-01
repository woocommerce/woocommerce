/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ShippingDataProvider } from '../shipping';
import { CheckoutStateProvider } from '../checkout-state';

/**
 * Cart provider
 * This wraps the Cart and provides an api interface for the Cart to
 * children via various hooks.
 *
 * @param {Object}  props                     Incoming props for the provider.
 * @param {Object}  [props.children]          The children being wrapped.
 * @param {string}  [props.redirectUrl]       Initialize what the cart will
 *                                            redirect to after successful
 *                                            submit.
 * @param {string}  [props.submitLabel]       What will be used for the cart
 *                                            submit button label.
 */
export const CartProvider = ( {
	children,
	redirectUrl,
	submitLabel = __( 'Proceed to Checkout', 'woo-gutenberg-products-block' ),
} ) => {
	return (
		<CheckoutStateProvider
			redirectUrl={ redirectUrl }
			isCart={ true }
			submitLabel={ submitLabel }
		>
			<ShippingDataProvider>{ children }</ShippingDataProvider>
		</CheckoutStateProvider>
	);
};
