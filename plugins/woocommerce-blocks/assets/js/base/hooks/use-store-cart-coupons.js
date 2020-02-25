/** @typedef { import('@woocommerce/type-defs/hooks').StoreCartCoupon } StoreCartCoupon */

/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import { useStoreCart } from './use-store-cart';

/**
 * This is a custom hook for loading the Store API /cart/coupons endpoint and an
 * action for adding a coupon _to_ the cart.
 * See also: https://github.com/woocommerce/woocommerce-gutenberg-products-block/tree/master/src/RestApi/StoreApi
 *
 * @return {StoreCartCoupon} An object exposing data and actions from/for the
 * store api /cart/coupons endpoint.
 */
export const useStoreCartCoupons = () => {
	const { cartCoupons, cartIsLoading } = useStoreCart();

	const results = useSelect( ( select, { dispatch } ) => {
		const store = select( storeKey );
		const isApplyingCoupon = store.isApplyingCoupon();
		const isRemovingCoupon = store.isRemovingCoupon();
		const { applyCoupon, removeCoupon } = dispatch( storeKey );

		return {
			applyCoupon,
			removeCoupon,
			isApplyingCoupon,
			isRemovingCoupon,
		};
	}, [] );

	return {
		appliedCoupons: cartCoupons,
		isLoading: cartIsLoading,
		...results,
	};
};
