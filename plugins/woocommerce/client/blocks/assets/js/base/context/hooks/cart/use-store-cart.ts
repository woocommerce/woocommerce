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
	CartShippingRate,
	CartShippingPackageShippingRate,
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
		| CartShippingPackageShippingRate
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

// Normalize address fields to ensure they are always in the same format and update the ref to track the latest value.
const normalizeAddress = <
	T extends CartResponseBillingAddress | CartResponseShippingAddress
>(
	address: T,
	addressRef: React.MutableRefObject< T >
): T => {
	const normalizedAddress = emptyHiddenAddressFields(
		decodeValues( address )
	);
	if ( ! fastDeepEqual( addressRef.current, normalizedAddress ) ) {
		addressRef.current = normalizedAddress;
	}
	return addressRef.current;
};

const normalizeCoupons = ( coupons: CartResponseCouponItem[] ) => {
	return coupons.length > 0
		? coupons.map( ( coupon: CartResponseCouponItem ) => ( {
				...coupon,
				label: decodeEntities( coupon.code ),
		  } ) )
		: EMPTY_CART_COUPONS;
};

const normalizeFees = ( fees: CartResponseFeeItem[] ) => {
	return fees.length > 0
		? fees.map( ( fee: CartResponseFeeItem ) => decodeValues( fee ) )
		: EMPTY_CART_FEES;
};

const normalizeShippingRates = ( shippingRates: CartShippingRate[] ) => {
	return shippingRates.length > 0
		? shippingRates.map( ( shippingRate: CartShippingRate ) => ( {
				...shippingRate,
				shipping_rates:
					shippingRate.shipping_rates.length > 0
						? shippingRate.shipping_rates.map(
								( rate: CartShippingPackageShippingRate ) =>
									decodeValues( rate )
						  )
						: [],
		  } ) )
		: [];
};

export const defaultCartData: StoreCart = {
	billingAddress: defaultBillingAddress,
	billingData: defaultBillingAddress,
	cartCoupons: EMPTY_CART_COUPONS,
	cartErrors: EMPTY_CART_ERRORS,
	cartFees: EMPTY_CART_FEES,
	cartHasCalculatedShipping: false,
	cartIsLoading: true,
	cartItemErrors: EMPTY_CART_ITEM_ERRORS,
	cartItems: EMPTY_CART_ITEMS,
	cartItemsCount: 0,
	cartItemsWeight: 0,
	cartNeedsPayment: true,
	cartNeedsShipping: true,
	cartTotals: defaultCartTotals,
	crossSellsProducts: EMPTY_CART_CROSS_SELLS,
	extensions: EMPTY_EXTENSIONS,
	hasPendingItemsOperations: false,
	isLoadingRates: false,
	paymentMethods: EMPTY_PAYMENT_METHODS,
	paymentRequirements: EMPTY_PAYMENT_REQUIREMENTS,
	receiveCart: () => undefined,
	receiveCartContents: () => undefined,
	shippingAddress: defaultShippingAddress,
	shippingRates: EMPTY_SHIPPING_RATES,
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

	const billingAddress = normalizeAddress(
		cartData.billingAddress,
		billingAddressRef
	);

	const shippingAddress = cartData.needsShipping
		? normalizeAddress( cartData.shippingAddress, shippingAddressRef )
		: billingAddress;

	const storeCart: StoreCart = {
		billingAddress,
		billingData: billingAddress,
		cartCoupons: normalizeCoupons( cartData.coupons ),
		cartErrors,
		cartFees: normalizeFees( cartData.fees ),
		cartHasCalculatedShipping: cartData.hasCalculatedShipping,
		cartIsLoading,
		cartItemErrors: cartData.errors,
		cartItems: cartData.items,
		cartItemsCount: cartData.itemsCount,
		cartItemsWeight: cartData.itemsWeight,
		cartNeedsPayment: cartData.needsPayment,
		cartNeedsShipping: cartData.needsShipping,
		cartTotals,
		crossSellsProducts: cartData.crossSells,
		extensions: cartData.extensions,
		hasPendingItemsOperations,
		isLoadingRates,
		paymentMethods: cartData.paymentMethods,
		paymentRequirements: cartData.paymentRequirements,
		receiveCart,
		receiveCartContents,
		shippingAddress,
		shippingRates: normalizeShippingRates( cartData.shippingRates ),
	};

	if (
		! currentStoreCart.current ||
		! fastDeepEqual( currentStoreCart.current, storeCart )
	) {
		currentStoreCart.current = storeCart;
	}

	return currentStoreCart.current;
};
