/**
 * External dependencies
 */
import { PluginArea } from '@wordpress/plugins';
import { CURRENT_USER_IS_ADMIN } from '@woocommerce/settings';
import BlockErrorBoundary from '@woocommerce/base-components/block-error-boundary';
/**
 * Internal dependencies
 */
import { PaymentMethodDataProvider } from '../payment-methods';
import { ShippingDataProvider } from '../shipping';
import { CustomerDataProvider } from '../customer';
import { CheckoutStateProvider } from '../checkout-state';
import CheckoutProcessor from './processor';

/**
 * Checkout provider
 * This wraps the checkout and provides an api interface for the checkout to
 * children via various hooks.
 *
 * @param {Object}  props               Incoming props for the provider.
 * @param {Object}  props.children      The children being wrapped.
 * @param {boolean} [props.isCart]      Whether it's rendered in the Cart
 *                                      component.
 * @param {string}  [props.redirectUrl] Initialize what the checkout will
 *                                      redirect to after successful
 *                                      submit.
 */
export const CheckoutProvider = ( {
	children,
	isCart = false,
	redirectUrl,
} ) => {
	return (
		<CheckoutStateProvider redirectUrl={ redirectUrl } isCart={ isCart }>
			<CustomerDataProvider>
				<ShippingDataProvider>
					<PaymentMethodDataProvider>
						{ children }
						{ /* If the current user is an admin, we let BlockErrorBoundary render
								the error, or we simply die silently. */ }
						<BlockErrorBoundary
							renderError={
								CURRENT_USER_IS_ADMIN ? null : () => null
							}
						>
							<PluginArea scope="woocommerce-checkout" />
						</BlockErrorBoundary>
						<CheckoutProcessor />
					</PaymentMethodDataProvider>
				</ShippingDataProvider>
			</CustomerDataProvider>
		</CheckoutStateProvider>
	);
};
