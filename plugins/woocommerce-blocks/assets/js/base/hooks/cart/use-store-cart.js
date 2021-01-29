/** @typedef { import('@woocommerce/type-defs/hooks').StoreCart } StoreCart */

/**
 * External dependencies
 */
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';
import { useSelect } from '@wordpress/data';
import { useEditorContext } from '@woocommerce/base-context';
import { decodeEntities } from '@wordpress/html-entities';
import { mapValues } from 'lodash';

const defaultShippingAddress = {
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

const defaultBillingAddress = {
	...defaultShippingAddress,
	email: '',
	phone: '',
};

const decodeAddress = ( address ) =>
	mapValues( address, ( value ) => decodeEntities( value ) );

/**
 * @constant
 * @type  {StoreCart} Object containing cart data.
 */
export const defaultCartData = {
	cartCoupons: [],
	cartItems: [],
	cartFees: [],
	cartItemsCount: 0,
	cartItemsWeight: 0,
	cartNeedsPayment: true,
	cartNeedsShipping: true,
	cartItemErrors: [],
	cartTotals: {},
	cartIsLoading: true,
	cartErrors: [],
	billingAddress: defaultBillingAddress,
	shippingAddress: defaultShippingAddress,
	shippingRates: [],
	shippingRatesLoading: false,
	cartHasCalculatedShipping: false,
	paymentRequirements: [],
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
			const shippingRatesLoading = store.isCustomerDataUpdating();
			const { receiveCart } = dispatch( storeKey );
			const billingAddress = decodeAddress( cartData.billingAddress );
			const shippingAddress = cartData.needsShipping
				? decodeAddress( cartData.shippingAddress )
				: billingAddress;
			return {
				cartCoupons: cartData.coupons,
				cartItems: cartData.items || [],
				cartFees: cartData.fees || [],
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
	return results;
};
