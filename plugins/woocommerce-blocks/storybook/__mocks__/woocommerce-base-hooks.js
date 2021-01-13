/**
 * External dependencies
 */
import {
	previewCart,
	previewShippingRates,
} from '@woocommerce/resource-previews';

export * from '../../assets/js/base/hooks/index.js';
export const useStoreCart = () => ( {
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
	cartFees: [],
	billingAddress: {},
	shippingAddress: {},
	shippingRates: previewShippingRates,
	shippingRatesLoading: false,
	cartHasCalculatedShipping: previewCart.has_calculated_shipping,
	receiveCart: () => void null,
} );
export const useSelectShippingRate = () => ( {
	selectShippingRate: () => void null,
	selectedShippingRates: [],
	isSelectingRate: false,
} );
