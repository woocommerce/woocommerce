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
import { AddressFormValues } from '@woocommerce/settings';
import { ADDRESS_FORM_KEYS } from '@woocommerce/block-settings';

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

const EMPTY_PENDING_QUANTITY: [] = [];
const EMPTY_PENDING_DELETE: [] = [];
const EMPTY_PENDING_ADD: [] = [];

export interface CartState {
	cartItemsPendingQuantity: string[];
	cartItemsPendingDelete: string[];
	productsPendingAdd: number[];
	cartData: Cart;
	metaData: CartMeta;
	errors: ApiErrorResponse[];
}

const shippingAddress: Partial< AddressFormValues > = {};
ADDRESS_FORM_KEYS.forEach( ( key ) => {
	shippingAddress[ key ] = '';
} );

const billingAddress: Partial< AddressFormValues & { email: string } > = {};
ADDRESS_FORM_KEYS.forEach( ( key ) => {
	billingAddress[ key ] = '';
} );
billingAddress.email = '';

export const defaultCartState: CartState = {
	cartItemsPendingQuantity: EMPTY_PENDING_QUANTITY,
	cartItemsPendingDelete: EMPTY_PENDING_DELETE,
	productsPendingAdd: EMPTY_PENDING_ADD,
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
	},
	metaData: {
		updatingCustomerData: false,
		updatingAddressFieldsForShippingRates: false,
		updatingSelectedRate: false,
		applyingCoupon: '',
		removingCoupon: '',
		isCartDataStale: false,
	},
	errors: EMPTY_CART_ERRORS,
};
