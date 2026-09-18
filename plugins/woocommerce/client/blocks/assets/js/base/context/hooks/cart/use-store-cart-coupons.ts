/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { dispatch, useDispatch, useSelect } from '@wordpress/data';
import { useCallback } from '@wordpress/element';
import { cartStore, checkoutStore } from '@woocommerce/block-data';
import { decodeEntities } from '@wordpress/html-entities';
import type {
	StoreCartCoupon,
	CouponApiErrorResponse,
} from '@woocommerce/types';
import { applyCheckoutFilter } from '@woocommerce/blocks-checkout';

/**
 * Internal dependencies
 */
import { useStoreCart } from './use-store-cart';

/**
 * This is a custom hook for loading the Store API /cart/coupons endpoint and an
 * action for adding a coupon _to_ the cart.
 *
 * applyCoupon resolves true once the coupon is applied. When the Store API
 * rejects the coupon it rejects with an Error whose message is ready to show to
 * the shopper. The failure is feedback for the coupon form only, so it is never
 * written to the validation store, which would block checkout.
 *
 * See also: https://github.com/woocommerce/woocommerce-gutenberg-products-block/tree/trunk/src/RestApi/StoreApi
 */
export const useStoreCartCoupons = ( context = '' ): StoreCartCoupon => {
	const { cartCoupons, cartIsLoading } = useStoreCart();
	const { applyCoupon, removeCoupon } = useDispatch( cartStore );
	const { isApplyingCoupon, isRemovingCoupon, orderId } = useSelect(
		( select ) => ( {
			isApplyingCoupon: select( cartStore ).isApplyingCoupon(),
			isRemovingCoupon: select( cartStore ).isRemovingCoupon(),
			orderId: select( checkoutStore ).getOrderId(),
		} ),
		[]
	);

	// Return cart, checkout or generic error message.
	const getCouponErrorMessage = useCallback(
		( error: CouponApiErrorResponse ) => {
			if ( orderId && orderId > 0 && error?.data?.details?.checkout ) {
				return error.data.details.checkout;
			} else if ( error?.data?.details?.cart ) {
				return error.data.details.cart;
			}
			return error.message;
		},
		[ orderId ]
	);

	const applyCouponWithNotices = useCallback(
		( couponCode: string ) => {
			return applyCoupon( couponCode )
				.then( () => {
					if (
						applyCheckoutFilter( {
							filterName: 'showApplyCouponNotice',
							defaultValue: true,
							arg: { couponCode, context },
						} )
					) {
						dispatch( 'core/notices' ).createNotice(
							'info',
							sprintf(
								/* translators: %s coupon code. */
								__(
									'Coupon code "%s" has been applied to your cart.',
									'woocommerce'
								),
								couponCode
							),
							{
								id: 'coupon-form',
								type: 'snackbar',
								context,
							}
						);
					}
					return Promise.resolve( true );
				} )
				.catch( ( error ) => {
					throw new Error(
						decodeEntities( getCouponErrorMessage( error ) )
					);
				} );
		},
		[ applyCoupon, getCouponErrorMessage, context ]
	);

	const removeCouponWithNotices = useCallback(
		( couponCode: string ) => {
			return removeCoupon( couponCode )
				.then( () => {
					if (
						applyCheckoutFilter( {
							filterName: 'showRemoveCouponNotice',
							defaultValue: true,
							arg: { couponCode, context },
						} )
					) {
						dispatch( 'core/notices' ).createNotice(
							'info',
							sprintf(
								/* translators: %s coupon code. */
								__(
									'Coupon code "%s" has been removed from your cart.',
									'woocommerce'
								),
								decodeEntities( couponCode )
							),
							{
								id: 'coupon-form',
								type: 'snackbar',
								context,
							}
						);
					}
					return Promise.resolve( true );
				} )
				.catch( ( error ) => {
					dispatch( 'core/notices' ).createErrorNotice(
						error.message,
						{
							id: 'coupon-form',
							type: 'snackbar',
							context,
						}
					);
					return Promise.resolve( false );
				} );
		},
		[ removeCoupon, context ]
	);

	return {
		appliedCoupons: cartCoupons,
		isLoading: cartIsLoading,
		applyCoupon: applyCouponWithNotices,
		removeCoupon: removeCouponWithNotices,
		isApplyingCoupon,
		isRemovingCoupon,
	};
};
