/**
 * External dependencies
 */
import { ProductResponseItem } from '@woocommerce/type-defs/product-response';

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
import type { ResponseError } from '../../data/types';
export interface StoreCartItemQuantity {
	isPendingDelete: boolean;
	quantity: number;
	setItemQuantity: React.Dispatch< React.SetStateAction< number > >;
	removeItem: () => Promise< boolean >;
	cartItemQuantityErrors: Array< CartResponseErrorItem >;
}

export interface StoreCartCoupon {
	appliedCoupons: Array< CartResponseCouponItem >;
	isLoading: boolean;
	applyCoupon: ( coupon: string ) => void;
	removeCoupon: ( coupon: string ) => void;
	isApplyingCoupon: boolean;
	isRemovingCoupon: boolean;
}

export interface StoreCart {
	cartCoupons: CartResponseCoupons;
	cartItems: Array< CartResponseItem >;
	crossSellsProducts: Array< ProductResponseItem >;
	cartFees: Array< CartResponseFeeItem >;
	cartItemsCount: number;
	cartItemsWeight: number;
	cartNeedsPayment: boolean;
	cartNeedsShipping: boolean;
	cartItemErrors: Array< CartResponseErrorItem >;
	cartTotals: CartResponseTotals;
	cartIsLoading: boolean;
	cartErrors: Array< ResponseError >;
	billingAddress: CartResponseBillingAddress;
	shippingAddress: CartResponseShippingAddress;
	shippingRates: Array< CartResponseShippingRate >;
	extensions: Record< string, unknown >;
	isLoadingRates: boolean;
	cartHasCalculatedShipping: boolean;
	paymentRequirements: Array< string >;
	receiveCart: ( cart: CartResponse ) => void;
}

export type Query = {
	catalog_visibility: 'catalog';
	per_page: number;
	page: number;
	orderby: string;
	order: string;
};
