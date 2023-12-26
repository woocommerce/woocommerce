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
	crossSellsProducts: previewCart.cross_sells,
	billingAddress: {},
	shippingAddress: {},
	shippingRates: previewShippingRates,
	isLoadingRates: false,
	cartHasCalculatedShipping: previewCart.has_calculated_shipping,
	receiveCart: () => void null,
} );
export const useShippingData = () => ( {
	selectShippingRate: () => void null,
	selectedRates: [],
	shippingRates: previewShippingRates,
	isSelectingRate: false,
	needsShipping: previewCart.needs_shipping,
	hasCalculatedShipping: previewCart.has_calculated_shipping,
	isLoadingRates: false,
	isCollectable: false,
} );
