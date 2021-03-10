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
	CartResponseShippingRateItem,
	CartResponse,
} from './cart-response';
import type { ResponseError } from '../data/types';
export interface StoreCartItemQuantity {
	isPendingDelete: boolean;
	quantity: number;
	changeQuantity: ( quantity: number ) => void;
	removeItem: () => Promise< void > | false;
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
	cartCoupons: Array< CartResponseCouponItem >;
	cartItems: Array< CartResponseItem >;
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
	shippingRates: Array< CartResponseShippingRateItem >;
	extensions: Record< string, unknown >;
	shippingRatesLoading: boolean;
	cartHasCalculatedShipping: boolean;
	paymentRequirements: Array< string >;
	receiveCart: ( cart: CartResponse ) => void;
}
