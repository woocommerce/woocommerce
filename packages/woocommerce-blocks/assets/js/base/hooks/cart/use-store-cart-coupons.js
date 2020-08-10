/** @typedef { import('@woocommerce/type-defs/hooks').StoreCartCoupon } StoreCartCoupon */

/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';
import { useValidationContext } from '@woocommerce/base-context';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import { useStoreCart } from './use-store-cart';
import { useStoreNotices } from '../use-store-notices';

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
	const { addErrorNotice, addSnackbarNotice } = useStoreNotices();
	const { setValidationErrors } = useValidationContext();

	const results = useSelect(
		( select, { dispatch } ) => {
			const store = select( storeKey );
			const isApplyingCoupon = store.isApplyingCoupon();
			const isRemovingCoupon = store.isRemovingCoupon();
			const {
				applyCoupon,
				removeCoupon,
				receiveApplyingCoupon,
			} = dispatch( storeKey );

			const applyCouponWithNotices = ( couponCode ) => {
				applyCoupon( couponCode )
					.then( ( result ) => {
						if ( result === true ) {
							addSnackbarNotice(
								sprintf(
									// translators: %s coupon code.
									__(
										'Coupon code "%s" has been applied to your cart.',
										'woocommerce'
									),
									couponCode
								),
								{
									id: 'coupon-form',
								}
							);
						}
					} )
					.catch( ( error ) => {
						setValidationErrors( {
							coupon: {
								message: decodeEntities( error.message ),
								hidden: false,
							},
						} );
						// Finished handling the coupon.
						receiveApplyingCoupon( '' );
					} );
			};

			const removeCouponWithNotices = ( couponCode ) => {
				removeCoupon( couponCode )
					.then( ( result ) => {
						if ( result === true ) {
							addSnackbarNotice(
								sprintf(
									// translators: %s coupon code.
									__(
										'Coupon code "%s" has been removed from your cart.',
										'woocommerce'
									),
									couponCode
								),
								{
									id: 'coupon-form',
								}
							);
						}
					} )
					.catch( ( error ) => {
						addErrorNotice( error.message, {
							id: 'coupon-form',
						} );
						// Finished handling the coupon.
						receiveApplyingCoupon( '' );
					} );
			};

			return {
				applyCoupon: applyCouponWithNotices,
				removeCoupon: removeCouponWithNotices,
				isApplyingCoupon,
				isRemovingCoupon,
			};
		},
		[ addErrorNotice, addSnackbarNotice ]
	);

	return {
		appliedCoupons: cartCoupons,
		isLoading: cartIsLoading,
		...results,
	};
};
