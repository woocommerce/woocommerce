/**
 * External dependencies
 */
import type {
	Cart,
	CartMeta,
	ApiErrorResponse,
	CartShippingAddress,
	CartBillingAddress,
} from '@woocommerce/types';
import { FormField, defaultFields } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import {
	EMPTY_CART_COUPONS,
	EMPTY_CART_ITEMS,
	EMPTY_CART_CROSS_SELLS,
	EMPTY_CART_FEES,
	EMPTY_CART_ITEM_ERRORS,
	EMPTY_CART_ERRORS,
	EMPTY_SHIPPING_RATES,
	EMPTY_TAX_LINES,
	EMPTY_PAYMENT_METHODS,
	EMPTY_PAYMENT_REQUIREMENTS,
	EMPTY_EXTENSIONS,
} from '../constants';
import { persistenceLayer } from './persistence-layer';

const EMPTY_PENDING_QUANTITY: [] = [];
const EMPTY_PENDING_DELETE: [] = [];

export interface CartState {
	cartItemsPendingQuantity: string[];
	cartItemsPendingDelete: string[];
	cartData: Cart;
	metaData: CartMeta;
	errors: ApiErrorResponse[];
}

const shippingAddress: Partial< CartShippingAddress & { email: FormField } > =
	{};
Object.keys( defaultFields ).forEach( ( key ) => {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore the default fields contain keys for each field.
	shippingAddress[ key ] = '';
} );
delete shippingAddress.email;

const billingAddress: Partial< CartBillingAddress & { email: FormField } > = {};
Object.keys( defaultFields ).forEach( ( key ) => {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore the default fields contain keys for each field.
	billingAddress[ key ] = '';
} );

export const defaultCartState: CartState = {
	cartItemsPendingQuantity: EMPTY_PENDING_QUANTITY,
	cartItemsPendingDelete: EMPTY_PENDING_DELETE,
	cartData: {
		coupons: EMPTY_CART_COUPONS,
		shippingRates: EMPTY_SHIPPING_RATES,
		shippingAddress: shippingAddress as CartShippingAddress,
		billingAddress: billingAddress as CartBillingAddress,
		items: EMPTY_CART_ITEMS,
		itemsCount: 0,
		itemsWeight: 0,
		crossSells: EMPTY_CART_CROSS_SELLS,
		needsShipping: true,
		needsPayment: false,
		hasCalculatedShipping: true,
		fees: EMPTY_CART_FEES,
		totals: {
			currency_code: '',
			currency_symbol: '',
			currency_minor_unit: 2,
			currency_decimal_separator: '.',
			currency_thousand_separator: ',',
			currency_prefix: '',
			currency_suffix: '',
			total_items: '0',
			total_items_tax: '0',
			total_fees: '0',
			total_fees_tax: '0',
			total_discount: '0',
			total_discount_tax: '0',
			total_shipping: '0',
			total_shipping_tax: '0',
			total_price: '0',
			total_tax: '0',
			tax_lines: EMPTY_TAX_LINES,
		},
		errors: EMPTY_CART_ITEM_ERRORS,
		paymentMethods: EMPTY_PAYMENT_METHODS,
		paymentRequirements: EMPTY_PAYMENT_REQUIREMENTS,
		extensions: EMPTY_EXTENSIONS,
		...persistenceLayer.get(),
	},
	metaData: {
		updatingCustomerData: false,
		updatingSelectedRate: false,
		applyingCoupon: '',
		removingCoupon: '',
		isCartDataStale: false,
	},
	errors: EMPTY_CART_ERRORS,
};
