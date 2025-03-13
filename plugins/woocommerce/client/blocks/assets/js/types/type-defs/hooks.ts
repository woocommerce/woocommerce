/**
 * External dependencies
 */
import type { CurrencyResponse, ProductResponseItem } from '@woocommerce/types';

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
	applyCoupon: ( coupon: string ) => Promise< boolean >;
	removeCoupon: ( coupon: string ) => Promise< boolean >;
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
	/** @deprecated Use billingAddress instead */
	billingData: CartResponseBillingAddress;
	billingAddress: CartResponseBillingAddress;
	shippingAddress: CartResponseShippingAddress;
	shippingRates: CartResponseShippingRate[];
	extensions: Record< string, unknown >;
	isLoadingRates: boolean;
	cartHasCalculatedShipping: boolean;
	paymentMethods: string[];
	paymentRequirements: string[];
	receiveCart: ( cart: CartResponse ) => void;
	receiveCartContents: ( cart: CartResponse ) => void;
}

export type Query = {
	catalog_visibility: 'catalog';
	per_page: number;
	page: number;
	orderby: string;
	order: string;
};

export type RatingValues = 0 | 1 | 2 | 3 | 4 | 5;

export type AttributeCount = {
	term: number;
	count: number;
};

type RatingCount = {
	rating: RatingValues;
	count: number;
};

type StockStatusCount = {
	status: 'instock' | 'outofstock' | 'onbackorder';
	count: number;
};

type PriceRangeProps = CurrencyResponse & {
	min_price: string;
	max_price: string;
};

/*
 * Prop types for the `wc/store/v1/products/collection-data` endpoint
 */
export type WCStoreV1ProductsCollectionProps = {
	price_range: PriceRangeProps | null;

	attribute_counts: AttributeCount[] | null;

	rating_counts: RatingCount[] | null;

	stock_status_counts: StockStatusCount[] | null;
};
