/**
 * External dependencies
 */
import type { ReactNode } from 'react';
import { ApiErrorResponse } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type { CartTotals } from './cart';
import type {
	CartResponse,
	CartResponseBillingAddress,
	CartResponseShippingAddress,
} from './cart-response';
import type { EmptyObjectType } from './objects';
import type { CheckoutResponseSuccess } from './checkout';

/**
 * The shape of objects on the `globalPaymentMethods` object from `allSettings`.
 */
export interface GlobalPaymentMethod {
	id: string | number;
	title: string;
	description: string;
}

export interface SupportsConfiguration {
	showSavedCards?: boolean;
	showSaveOption?: boolean;
	features?: string[];
	// Deprecated, in favour of showSavedCards and showSaveOption
	savePaymentInfo?: boolean;
	style?: string[];
}

// we assign a value in the class for supports.features
export interface Supports extends SupportsConfiguration {
	features: string[];
}

export interface CanMakePaymentArgument {
	cart: CanMakePaymentArgumentCart;
	cartTotals: CartTotals;
	cartNeedsShipping: boolean;
	billingData: CartResponseBillingAddress; // This needs to stay as billingData as third parties already use this key
	shippingAddress: CartResponseShippingAddress;
	billingAddress: CartResponseBillingAddress;
	selectedShippingMethods: Record< string, unknown >;
	paymentRequirements: string[];
	paymentMethods: string[];
}

export interface CanMakePaymentArgumentCart {
	billingAddress: CartResponse[ 'billing_address' ];
	billingData: CartResponse[ 'billing_address' ];
	cartCoupons: CartResponse[ 'coupons' ];
	cartErrors: ApiErrorResponse[];
	cartFees: CartResponse[ 'fees' ];
	cartHasCalculatedShipping: CartResponse[ 'has_calculated_shipping' ];
	cartIsLoading: boolean;
	cartItemErrors: CartResponse[ 'errors' ];
	cartItems: CartResponse[ 'items' ];
	cartItemsCount: CartResponse[ 'items_count' ];
	cartItemsWeight: CartResponse[ 'items_weight' ];
	cartNeedsPayment: CartResponse[ 'needs_payment' ];
	cartNeedsShipping: CartResponse[ 'needs_shipping' ];
	cartTotals: CartResponse[ 'totals' ];
	extensions: CartResponse[ 'extensions' ];
	crossSellsProducts: CartResponse[ 'cross_sells' ];
	isLoadingRates: boolean;
	paymentRequirements: CartResponse[ 'payment_requirements' ];
	receiveCart: ( response: CartResponse ) => void;
	shippingAddress: CartResponse[ 'shipping_address' ];
	shippingRates: CartResponse[ 'shipping_rates' ];
}

export type CanMakePaymentReturnType =
	| boolean
	| Promise< boolean | { error: { message: string } } >;

export type CanMakePaymentCallback = (
	cartData: CanMakePaymentArgument
) => CanMakePaymentReturnType;

export type CanMakePaymentExtensionCallback = (
	cartData: CanMakePaymentArgument
) => boolean;

export interface PaymentMethodIcon {
	id: string;
	src: string | null;
	alt: string;
}

export type PaymentMethodIcons = ( PaymentMethodIcon | string )[];

export interface PaymentMethodConfiguration {
	// A unique string to identify the payment method client side.
	name: string;
	// A react node for your payment method UI.
	content: ReactNode;
	// A react node to display a preview of your payment method in the editor.
	edit: ReactNode;
	// A callback to determine whether the payment method should be shown in the checkout.
	canMakePayment: CanMakePaymentCallback;
	// A unique string to represent the payment method server side. If not provided, defaults to name.
	paymentMethodId?: string;
	// Object that describes various features provided by the payment method.
	supports: SupportsConfiguration;
	// Array of card types (brands) supported by the payment method.
	icons?: null | PaymentMethodIcons;
	// A react node that will be used as a label for the payment method in the checkout.
	label: ReactNode;
	// An accessibility label. Screen readers will output this label when the payment method is selected.
	ariaLabel: string;
	// Optionally customize the label text for the checkout submit (`Place Order`) button.
	placeOrderButtonLabel?: string;
	// A React node that contains logic handling any processing your payment method has to do with saved payment methods if your payment method supports them
	savedTokenComponent?: ReactNode | null;
}

export interface ExpressPaymentMethodConfiguration {
	// A unique string to identify the payment method client side.
	name: string;
	// A human readable title for the payment method.
	title?: string;
	// A human readable description for the payment method.
	description?: string;
	// The gateway ID for the payment method.
	gatewayId?: string;
	// A react node for your payment method UI.
	content: ReactNode;
	// A react node to display a preview of your payment method in the editor.
	edit: ReactNode;
	// A callback to determine whether the payment method should be shown in the checkout.
	canMakePayment: CanMakePaymentCallback;
	// A unique string to represent the payment method server side. If not provided, defaults to name.
	paymentMethodId?: string;
	// Object that describes various features provided by the payment method.
	supports: SupportsConfiguration;
	// A React node that contains logic handling any processing your payment method has to do with saved payment methods if your payment method supports them
	savedTokenComponent?: ReactNode | null;
}

export type PaymentMethods =
	| Record< string, PaymentMethodConfigInstance >
	| EmptyObjectType;

/**
 * Used to represent payment methods in a context where storing objects is not allowed, i.e. in data stores.
 */
export type PlainPaymentMethods = Record<
	string,
	{
		name: string;
		title: string;
		description: string;
		gatewayId: string;
		supportsStyle: string[];
	}
>;

/**
 * Used to represent payment methods in a context where storing objects is not allowed, i.e. in data stores.
 */
export type PlainExpressPaymentMethods = PlainPaymentMethods;

export type ExpressPaymentMethods =
	| Record< string, ExpressPaymentMethodConfigInstance >
	| EmptyObjectType;

export interface PaymentMethodConfigInstance {
	name: string;
	content: ReactNode;
	edit: ReactNode;
	paymentMethodId?: string;
	supports: Supports;
	icons: null | PaymentMethodIcons;
	label: ReactNode;
	ariaLabel: string;
	placeOrderButtonLabel?: string;
	savedTokenComponent?: ReactNode | null;
	canMakePaymentFromConfig: CanMakePaymentCallback;
	canMakePayment: CanMakePaymentCallback;
}

export interface ExpressPaymentMethodConfigInstance {
	name: string;
	title: string;
	description: string;
	gatewayId: string;
	content: ReactNode;
	edit: ReactNode;
	paymentMethodId?: string;
	placeOrderButtonLabel?: string;
	supports: Supports;
	canMakePaymentFromConfig: CanMakePaymentCallback;
	canMakePayment: CanMakePaymentCallback;
}

export interface PaymentResult {
	message: string;
	paymentStatus:
		| CheckoutResponseSuccess[ 'payment_result' ][ 'payment_status' ]
		| 'not set';
	paymentDetails: Record< string, string > | Record< string, never >;
	redirectUrl: string;
}
