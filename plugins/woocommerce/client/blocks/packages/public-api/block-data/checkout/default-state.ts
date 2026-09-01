/**
 * External dependencies
 */
import { isSameAddress } from '@woocommerce/base-utils';
import type { AddressFormType, OrderFormValues } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { STATUS, checkoutData } from './constants';

export type CheckoutState = {
	additionalFields: OrderFormValues; // Additional fields values that are collected on Checkout.
	calculatingCount: number; // If any of the totals, taxes, shipping, etc need to be calculated, the count will be increased here
	customerId: number; // This is the ID of the customer the draft order belongs to.
	customerPassword: string; // Customer password for account creation, if applicable.
	extensionData: Record< string, Record< string, unknown > >; // Custom checkout data passed to the store API on processing.
	hasError: boolean; // True when the checkout is in an error state. Whatever caused the error (validation/payment method) will likely have triggered a notice.
	orderId: number; // This is the ID for the draft order if one exists.
	orderNotes: string; // Order notes introduced by the user in the checkout form.
	prefersCollection?: boolean | undefined; // If customer wants to checkout with a local pickup option.
	redirectUrl: string; // This is the url that checkout will redirect to when it's ready.
	shouldCreateAccount: boolean; // Should a user account be created?
	status: STATUS; // Status of the checkout
	useShippingAsBilling: boolean; // Should the billing form be hidden and inherit the shipping address?
	editingBillingAddress: boolean; // Is the billing address being edited?
	editingShippingAddress: boolean; // Is the shipping address being edited?
	addressAutocompleteProviders: string[]; // IDs of registered address autocomplete providers, if any.
	activeAddressAutocompleteProvider: { [ key in AddressFormType ]: string }; // Active address autocomplete provider.
};

// Default editing state for CustomerAddress component comes from the current address and whether or not we're in the editor.
const hasBillingAddress = !! (
	checkoutData.billing_address.address_1 &&
	( checkoutData.billing_address.first_name ||
		checkoutData.billing_address.last_name )
);

const hasShippingAddress = !! (
	checkoutData.shipping_address.address_1 &&
	( checkoutData.shipping_address.first_name ||
		checkoutData.shipping_address.last_name )
);

const billingMatchesShipping = isSameAddress(
	checkoutData.billing_address,
	checkoutData.shipping_address
);

export const defaultState: CheckoutState = {
	additionalFields: checkoutData.additional_fields || {},
	calculatingCount: 0,
	customerId: checkoutData.customer_id,
	customerPassword: '',
	extensionData: {},
	hasError: false,
	orderId: checkoutData.order_id,
	orderNotes: checkoutData.customer_note || '',
	prefersCollection: undefined,
	redirectUrl: '',
	shouldCreateAccount: false,
	status: STATUS.IDLE,
	useShippingAsBilling: billingMatchesShipping,
	editingBillingAddress: ! hasBillingAddress,
	editingShippingAddress: ! hasShippingAddress,
	addressAutocompleteProviders: [],
	activeAddressAutocompleteProvider: {
		billing: '',
		shipping: '',
	},
};
