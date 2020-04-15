/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

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
 * @param {Object}  props                       Incoming props for the provider.
 * @param {Object}  props.children              The children being wrapped.
 * @param {string}  [props.redirectUrl]         Initialize what the checkout will
 *                                              redirect to after successful
 *                                              submit.
 * @param {string}  [props.submitLabel]         What will be used for the checkout
 *                                              submit button label.
 */
export const CheckoutProvider = ( {
	children,
	redirectUrl,
	submitLabel = __( 'Place Order', 'woo-gutenberg-products-block' ),
} ) => {
	return (
		<CheckoutStateProvider
			redirectUrl={ redirectUrl }
			isCart={ false }
			submitLabel={ submitLabel }
		>
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
