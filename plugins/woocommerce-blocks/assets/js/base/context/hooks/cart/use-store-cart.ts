/** @typedef { import('@woocommerce/type-defs/hooks').StoreCart } StoreCart */

/**
 * External dependencies
 */
import { isEqual } from 'lodash';
import { useRef } from '@wordpress/element';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';
import { useSelect } from '@wordpress/data';
import { decodeEntities } from '@wordpress/html-entities';
import type {
	StoreCart,
	CartResponseTotals,
	CartResponseFeeItem,
	CartResponseBillingAddress,
	CartResponseShippingAddress,
} from '@woocommerce/types';
import { fromEntriesPolyfill } from '@woocommerce/base-utils';

/**
 * Internal dependencies
 */
import { useEditorContext } from '../../providers/editor-context';

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
};

const defaultBillingAddress: CartResponseBillingAddress = {
	...defaultShippingAddress,
	email: '',
	phone: '',
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
	tax_lines: [],
	currency_code: '',
	currency_symbol: '',
	currency_minor_unit: 2,
	currency_decimal_separator: '',
	currency_thousand_separator: '',
	currency_prefix: '',
	currency_suffix: '',
};

const decodeValues = (
	object: Record< string, unknown >
): Record< string, unknown > =>
	fromEntriesPolyfill(
		Object.entries( object ).map( ( [ key, value ] ) => [
			key,
			decodeEntities( value ),
		] )
	);

/**
 * @constant
 * @type  {StoreCart} Object containing cart data.
 */
export const defaultCartData: StoreCart = {
	cartCoupons: [],
	cartItems: [],
	cartFees: [],
	cartItemsCount: 0,
	cartItemsWeight: 0,
	cartNeedsPayment: true,
	cartNeedsShipping: true,
	cartItemErrors: [],
	cartTotals: defaultCartTotals,
	cartIsLoading: true,
	cartErrors: [],
	billingAddress: defaultBillingAddress,
	shippingAddress: defaultShippingAddress,
	shippingRates: [],
	shippingRatesLoading: false,
	cartHasCalculatedShipping: false,
	paymentRequirements: [],
	receiveCart: () => undefined,
	extensions: {},
};

/**
 * This is a custom hook that is wired up to the `wc/store/cart` data
 * store.
 *
 * @param {Object} options                An object declaring the various
 *                                        collection arguments.
 * @param {boolean} options.shouldSelect  If false, the previous results will be
 *                                        returned and internal selects will not
 *                                        fire.
 *
 * @return {StoreCart} Object containing cart data.
 */
export const useStoreCart = (
	options: { shouldSelect: boolean } = { shouldSelect: true }
): StoreCart => {
	const { isEditor, previewData } = useEditorContext();
	const previewCart = previewData?.previewCart || {};
	const { shouldSelect } = options;
	const currentResults = useRef();

	const results: StoreCart = useSelect(
		( select, { dispatch } ) => {
			if ( ! shouldSelect ) {
				return defaultCartData;
			}

			if ( isEditor ) {
				return {
					cartCoupons: previewCart.coupons,
					cartItems: previewCart.items,
					cartFees: previewCart.fees,
					cartItemsCount: previewCart.items_count,
					cartItemsWeight: previewCart.items_weight,
					cartNeedsPayment: previewCart.needs_payment,
					cartNeedsShipping: previewCart.needs_shipping,
					cartItemErrors: [],
					cartTotals: previewCart.totals,
					cartIsLoading: false,
					cartErrors: [],
					billingAddress: defaultBillingAddress,
					shippingAddress: defaultShippingAddress,
					extensions: {},
					shippingRates: previewCart.shipping_rates,
					shippingRatesLoading: false,
					cartHasCalculatedShipping:
						previewCart.has_calculated_shipping,
					paymentRequirements: previewCart.paymentRequirements,
					receiveCart:
						typeof previewCart?.receiveCart === 'function'
							? previewCart.receiveCart
							: () => undefined,
				};
			}

			const store = select( storeKey );
			const cartData = store.getCartData();
			const cartErrors = store.getCartErrors();
			const cartTotals = store.getCartTotals();
			const cartIsLoading = ! store.hasFinishedResolution(
				'getCartData'
			);
			const shippingRatesLoading = store.isCustomerDataUpdating();
			const { receiveCart } = dispatch( storeKey );
			const billingAddress = decodeValues( cartData.billingAddress );
			const shippingAddress = cartData.needsShipping
				? decodeValues( cartData.shippingAddress )
				: billingAddress;
			const cartFees = cartData.fees.map( ( fee: CartResponseFeeItem ) =>
				decodeValues( fee )
			);
			return {
				cartCoupons: cartData.coupons,
				cartItems: cartData.items || [],
				cartFees,
				cartItemsCount: cartData.itemsCount,
				cartItemsWeight: cartData.itemsWeight,
				cartNeedsPayment: cartData.needsPayment,
				cartNeedsShipping: cartData.needsShipping,
				cartItemErrors: cartData.errors || [],
				cartTotals,
				cartIsLoading,
				cartErrors,
				billingAddress,
				shippingAddress,
				extensions: cartData.extensions || {},
				shippingRates: cartData.shippingRates || [],
				shippingRatesLoading,
				cartHasCalculatedShipping: cartData.hasCalculatedShipping,
				paymentRequirements: cartData.paymentRequirements || [],
				receiveCart,
			};
		},
		[ shouldSelect ]
	);

	if (
		! currentResults.current ||
		! isEqual( currentResults.current, results )
	) {
		currentResults.current = results;
	}

	return currentResults.current;
};
