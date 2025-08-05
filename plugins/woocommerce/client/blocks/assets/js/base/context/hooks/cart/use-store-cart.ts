/**
 * External dependencies
 */
import fastDeepEqual from 'fast-deep-equal/es6';
import { useRef } from '@wordpress/element';
import {
	cartStore,
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
} from '@woocommerce/block-data';
import { useSelect, useDispatch } from '@wordpress/data';
import { decodeEntities } from '@wordpress/html-entities';
import type {
	StoreCart,
	CartResponseTotals,
	CartResponseFeeItem,
	CartResponseBillingAddress,
	CartResponseShippingAddress,
	CartResponseCouponItem,
} from '@woocommerce/types';
import { emptyHiddenAddressFields } from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import { useStoreCartEventListeners } from './use-store-cart-event-listeners';

declare module '@wordpress/html-entities' {
	// eslint-disable-next-line @typescript-eslint/no-shadow
	export function decodeEntities< T >( coupon: T ): T;
}
const defaultShippingAddress: CartResponseShippingAddress = {
	first_name: '',
	last_name: '',
	company: '',
	address_1: '',
	address_2: '',
	city: '',
	state: '',
	postcode: '',
	country: '',
	phone: '',
};

const defaultBillingAddress: CartResponseBillingAddress = {
	...defaultShippingAddress,
	email: '',
};

const defaultCartTotals: CartResponseTotals = {
	total_items: '',
	total_items_tax: '',
	total_fees: '',
	total_fees_tax: '',
	total_discount: '',
	total_discount_tax: '',
	total_shipping: '',
	total_shipping_tax: '',
	total_price: '',
	total_tax: '',
	tax_lines: EMPTY_TAX_LINES,
	currency_code: '',
	currency_symbol: '',
	currency_minor_unit: 2,
	currency_decimal_separator: '',
	currency_thousand_separator: '',
	currency_prefix: '',
	currency_suffix: '',
};

const decodeValues = <
	T extends
		| Record< string, unknown >
		| CartResponseBillingAddress
		| CartResponseShippingAddress
>(
	object: T
): T => {
	return Object.fromEntries(
		Object.entries( object ).map( ( [ key, value ] ) => [
			key,
			decodeEntities( value ),
		] )
	) as T;
};

export const defaultCartData: StoreCart = {
	cartCoupons: EMPTY_CART_COUPONS,
	cartItems: EMPTY_CART_ITEMS,
	cartFees: EMPTY_CART_FEES,
	cartItemsCount: 0,
	cartItemsWeight: 0,
	crossSellsProducts: EMPTY_CART_CROSS_SELLS,
	cartNeedsPayment: true,
	cartNeedsShipping: true,
	cartItemErrors: EMPTY_CART_ITEM_ERRORS,
	cartTotals: defaultCartTotals,
	cartIsLoading: true,
	cartErrors: EMPTY_CART_ERRORS,
	billingData: defaultBillingAddress,
	billingAddress: defaultBillingAddress,
	shippingAddress: defaultShippingAddress,
	shippingRates: EMPTY_SHIPPING_RATES,
	isLoadingRates: false,
	cartHasCalculatedShipping: false,
	paymentMethods: EMPTY_PAYMENT_METHODS,
	paymentRequirements: EMPTY_PAYMENT_REQUIREMENTS,
	receiveCart: () => undefined,
	receiveCartContents: () => undefined,
	extensions: EMPTY_EXTENSIONS,
	hasPendingItemsOperations: false,
};

/**
 * This is a custom hook that is wired up to the `wc/store/cart` data store.
 */
export const useStoreCart = (
	options: { shouldSelect: boolean } = { shouldSelect: true }
): StoreCart => {
	const { shouldSelect } = options;
	const currentStoreCart = useRef< StoreCart >();
	const billingAddressRef = useRef( defaultBillingAddress );
	const shippingAddressRef = useRef( defaultShippingAddress );

	// This will keep track of jQuery and DOM events that invalidate the store resolution.
	useStoreCartEventListeners();

	const { receiveCart, receiveCartContents } = useDispatch( cartStore );
	const {
		cartData,
		cartErrors,
		cartTotals,
		cartIsLoading,
		isLoadingRates,
		hasPendingItemsOperations,
	} = useSelect( ( select ) => {
		const store = select( cartStore );

		// Base loading state - whether initial cart data resolution has finished
		const baseCartIsLoading = ! store.hasFinishedResolution(
			'getCartData',
			[]
		);

		return {
			cartData: store.getCartData(),
			cartErrors: store.getCartErrors(),
			cartTotals: store.getCartTotals(),
			cartIsLoading: baseCartIsLoading,
			isLoadingRates: store.isAddressFieldsForShippingRatesUpdating(),
			hasPendingItemsOperations: store.hasPendingItemsOperations(),
		};
	}, [] );

	if ( ! shouldSelect ) {
		return defaultCartData;
	}

	const nextBillingAddress = emptyHiddenAddressFields(
		decodeValues( cartData.billingAddress )
	);

	if ( ! fastDeepEqual( billingAddressRef.current, nextBillingAddress ) ) {
		billingAddressRef.current = nextBillingAddress;
	}

	const billingAddress = billingAddressRef.current;

	const nextShippingAddress = cartData.needsShipping
		? emptyHiddenAddressFields( decodeValues( cartData.shippingAddress ) )
		: billingAddress;

	if ( ! fastDeepEqual( shippingAddressRef.current, nextShippingAddress ) ) {
		shippingAddressRef.current = nextShippingAddress;
	}

	const shippingAddress = shippingAddressRef.current;

	const storeCart: StoreCart = {
		cartCoupons:
			cartData.coupons.length > 0
				? cartData.coupons.map(
						( coupon: CartResponseCouponItem ) => ( {
							...coupon,
							label: decodeEntities( coupon.code ),
						} )
				  )
				: EMPTY_CART_COUPONS,
		cartItems: cartData.items,
		crossSellsProducts: cartData.crossSells,
		cartFees:
			cartData.fees.length > 0
				? cartData.fees.map( ( fee: CartResponseFeeItem ) =>
						decodeValues( fee )
				  )
				: EMPTY_CART_FEES,
		cartItemsCount: cartData.itemsCount,
		cartItemsWeight: cartData.itemsWeight,
		cartNeedsPayment: cartData.needsPayment,
		cartNeedsShipping: cartData.needsShipping,
		cartItemErrors: cartData.errors,
		cartTotals,
		cartIsLoading,
		cartErrors,
		billingData: billingAddress,
		billingAddress,
		shippingAddress,
		extensions: cartData.extensions,
		shippingRates: cartData.shippingRates,
		isLoadingRates,
		cartHasCalculatedShipping: cartData.hasCalculatedShipping,
		paymentRequirements: cartData.paymentRequirements,
		paymentMethods: cartData.paymentMethods,
		receiveCart,
		receiveCartContents,
		hasPendingItemsOperations,
	};

	if (
		! currentStoreCart.current ||
		! fastDeepEqual( currentStoreCart.current, storeCart )
	) {
		currentStoreCart.current = storeCart;
	}

	return currentStoreCart.current;
};
