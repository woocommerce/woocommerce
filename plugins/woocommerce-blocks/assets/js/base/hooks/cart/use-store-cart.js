/** @typedef { import('@woocommerce/type-defs/hooks').StoreCart } StoreCart */

/**
 * External dependencies
 */
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';
import { useSelect } from '@wordpress/data';
import { useEditorContext } from '@woocommerce/base-context';

/**
 * @constant
 * @type  {StoreCart} Object containing cart data.
 */
export const defaultCartData = {
	cartCoupons: [],
	cartItems: [],
	cartItemsCount: 0,
	cartItemsWeight: 0,
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
	const { isEditor, previewCart } = useEditorContext();
	const { shouldSelect } = options;

	const results = useSelect(
		( select ) => {
			if ( ! shouldSelect ) {
				return defaultCartData;
			}

			if ( isEditor ) {
				return {
					cartCoupons: previewCart.coupons,
					cartItems: previewCart.items,
					cartItemsCount: previewCart.items_count,
					cartItemsWeight: previewCart.items_weight,
					cartNeedsShipping: previewCart.needs_shipping,
					cartItemErrors: [],
					cartTotals: previewCart.totals,
					cartIsLoading: false,
					cartErrors: [],
					shippingRates: previewCart.shipping_rates,
					shippingAddress: {
						country: '',
						state: '',
						city: '',
						postcode: '',
					},
				};
			}

			const store = select( storeKey );
			const cartData = store.getCartData();
			const cartErrors = store.getCartErrors();
			const cartTotals = store.getCartTotals();
			const cartIsLoading = ! store.hasFinishedResolution(
				'getCartData'
			);
			return {
				cartCoupons: cartData.coupons,
				shippingRates: cartData.shippingRates,
				shippingAddress: cartData.shippingAddress,
				cartItems: cartData.items,
				cartItemsCount: cartData.itemsCount,
				cartItemsWeight: cartData.itemsWeight,
				cartNeedsShipping: cartData.needsShipping,
				cartItemErrors: cartData.errors,
				cartTotals,
				cartIsLoading,
				cartErrors,
			};
		},
		[ shouldSelect ]
	);
	return results;
};
