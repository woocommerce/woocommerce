/**
 * External dependencies
 */
import type { ProductResponseItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type {
	CartResponseErrorItem,
	CartResponseCouponItem,
	CartResponseItem,
	CartResponseFeeItem,
	CartResponseTotals,
	CartResponseShippingAddress,
	CartResponseBillingAddress,
	CartResponseShippingRate,
	CartResponse,
	CartResponseCoupons,
} from './cart-response';
import type { ApiErrorResponse } from './api-error-response';
export interface StoreCartItemQuantity {
	isPendingDelete: boolean;
	quantity: number;
	setItemQuantity: React.Dispatch< React.SetStateAction< number > >;
	removeItem: () => Promise< boolean >;
	cartItemQuantityErrors: CartResponseErrorItem[];
}

// An object exposing data and actions from/for the store api /cart/coupons endpoint.
export interface StoreCartCoupon {
	appliedCoupons: CartResponseCouponItem[];
	isLoading: boolean;
	applyCoupon: ( coupon: string ) => void;
	removeCoupon: ( coupon: string ) => void;
	isApplyingCoupon: boolean;
	isRemovingCoupon: boolean;
}

export interface StoreCart {
	cartCoupons: CartResponseCoupons;
	cartItems: CartResponseItem[];
	crossSellsProducts: ProductResponseItem[];
	cartFees: CartResponseFeeItem[];
	cartItemsCount: number;
	cartItemsWeight: number;
	cartNeedsPayment: boolean;
	cartNeedsShipping: boolean;
	cartItemErrors: CartResponseErrorItem[];
	cartTotals: CartResponseTotals;
	cartIsLoading: boolean;
	cartErrors: ApiErrorResponse[];
	billingAddress: CartResponseBillingAddress;
	shippingAddress: CartResponseShippingAddress;
	shippingRates: CartResponseShippingRate[];
	extensions: Record< string, unknown >;
	isLoadingRates: boolean;
	cartHasCalculatedShipping: boolean;
	paymentRequirements: string[];
	receiveCart: ( cart: CartResponse ) => void;
}

export type Query = {
	catalog_visibility: 'catalog';
	per_page: number;
	page: number;
	orderby: string;
	order: string;
};
