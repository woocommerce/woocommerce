/** @typedef { import('@woocommerce/type-defs/hooks').StoreCart } StoreCart */

/**
 * External dependencies
 */
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';
import { useSelect } from '@wordpress/data';
import { useEditorContext } from '@woocommerce/base-context';
import { decodeEntities } from '@wordpress/html-entities';
import { mapValues } from 'lodash';

/**
 * @constant
 * @type  {StoreCart} Object containing cart data.
 */
export const defaultCartData = {
	cartCoupons: [],
	cartItems: [],
	cartItemsCount: 0,
	cartItemsWeight: 0,
	cartNeedsPayment: true,
	cartNeedsShipping: true,
	cartItemErrors: [],
	cartTotals: {},
	cartIsLoading: true,
	cartErrors: [],
	shippingAddress: {
		first_name: '',
		last_name: '',
		company: '',
		address_1: '',
		address_2: '',
		city: '',
		state: '',
		postcode: '',
		country: '',
	},
	shippingRates: [],
	shippingRatesLoading: false,
	hasShippingAddress: false,
	receiveCart: () => {},
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
export const useStoreCart = ( options = { shouldSelect: true } ) => {
	const { isEditor, previewData } = useEditorContext();
	const previewCart = previewData?.previewCart || {};
	const { shouldSelect } = options;

	const results = useSelect(
		( select, { dispatch } ) => {
			if ( ! shouldSelect ) {
				return defaultCartData;
			}

			if ( isEditor ) {
				return {
					cartCoupons: previewCart.coupons,
					cartItems: previewCart.items,
					cartItemsCount: previewCart.items_count,
					cartItemsWeight: previewCart.items_weight,
					cartNeedsPayment: previewCart.needs_payment,
					cartNeedsShipping: previewCart.needs_shipping,
					cartItemErrors: [],
					cartTotals: previewCart.totals,
					cartIsLoading: false,
					cartErrors: [],
					shippingAddress: {
						first_name: '',
						last_name: '',
						company: '',
						address_1: '',
						address_2: '',
						city: '',
						state: '',
						postcode: '',
						country: '',
					},
					shippingRates: previewCart.shipping_rates,
					shippingRatesLoading: false,
					hasShippingAddress: false,
					receiveCart:
						typeof previewCart?.receiveCart === 'function'
							? previewCart.receiveCart
							: () => {},
				};
			}

			const store = select( storeKey );
			const cartData = store.getCartData();
			const cartErrors = store.getCartErrors();
			const cartTotals = store.getCartTotals();
			const cartIsLoading = ! store.hasFinishedResolution(
				'getCartData'
			);
			const shippingRatesLoading = store.areShippingRatesLoading();
			const { receiveCart } = dispatch( storeKey );
			const shippingAddress = mapValues(
				cartData.shippingAddress,
				( value ) => decodeEntities( value )
			);

			return {
				cartCoupons: cartData.coupons,
				cartItems: cartData.items || [],
				cartItemsCount: cartData.itemsCount,
				cartItemsWeight: cartData.itemsWeight,
				cartNeedsPayment: cartData.needsPayment,
				cartNeedsShipping: cartData.needsShipping,
				cartItemErrors: cartData.errors || [],
				cartTotals,
				cartIsLoading,
				cartErrors,
				shippingAddress,
				shippingRates: cartData.shippingRates || [],
				shippingRatesLoading,
				hasShippingAddress: !! shippingAddress.country,
				receiveCart,
			};
		},
		[ shouldSelect ]
	);
	return results;
};
