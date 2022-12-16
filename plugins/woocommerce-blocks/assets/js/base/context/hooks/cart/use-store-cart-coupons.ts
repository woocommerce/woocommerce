/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import {
	CART_STORE_KEY as storeKey,
	VALIDATION_STORE_KEY,
} from '@woocommerce/block-data';
import { decodeEntities } from '@wordpress/html-entities';
import type { StoreCartCoupon } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { useStoreCart } from './use-store-cart';

/**
 * This is a custom hook for loading the Store API /cart/coupons endpoint and an
 * action for adding a coupon _to_ the cart.
 * See also: https://github.com/woocommerce/woocommerce-gutenberg-products-block/tree/trunk/src/RestApi/StoreApi
 *
 * @return {StoreCartCoupon} An object exposing data and actions from/for the
 * store api /cart/coupons endpoint.
 */
export const useStoreCartCoupons = ( context = '' ): StoreCartCoupon => {
	const { cartCoupons, cartIsLoading } = useStoreCart();
	const { createErrorNotice } = useDispatch( 'core/notices' );
	const { createNotice } = useDispatch( 'core/notices' );
	const { setValidationErrors } = useDispatch( VALIDATION_STORE_KEY );

	const {
		isApplyingCoupon,
		isRemovingCoupon,
	}: Pick< StoreCartCoupon, 'isApplyingCoupon' | 'isRemovingCoupon' > =
		useSelect(
			( select ) => {
				const store = select( storeKey );

				return {
					isApplyingCoupon: store.isApplyingCoupon(),
					isRemovingCoupon: store.isRemovingCoupon(),
				};
			},
			[ createErrorNotice, createNotice ]
		);

	const { applyCoupon, removeCoupon, receiveApplyingCoupon } =
		useDispatch( storeKey );

	const applyCouponWithNotices = ( couponCode: string ) => {
		applyCoupon( couponCode )
			.then( ( result ) => {
				if ( result === true ) {
					createNotice(
						'info',
						sprintf(
							/* translators: %s coupon code. */
							__(
								'Coupon code "%s" has been applied to your cart.',
								'woo-gutenberg-products-block'
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

	const removeCouponWithNotices = ( couponCode: string ) => {
		removeCoupon( couponCode )
			.then( ( result ) => {
				if ( result === true ) {
					createNotice(
						'info',
						sprintf(
							/* translators: %s coupon code. */
							__(
								'Coupon code "%s" has been removed from your cart.',
								'woo-gutenberg-products-block'
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
			} )
			.catch( ( error ) => {
				createErrorNotice( error.message, {
					id: 'coupon-form',
					context,
				} );
				// Finished handling the coupon.
				receiveApplyingCoupon( '' );
			} );
	};

	return {
		appliedCoupons: cartCoupons,
		isLoading: cartIsLoading,
		applyCoupon: applyCouponWithNotices,
		removeCoupon: removeCouponWithNotices,
		isApplyingCoupon,
		isRemovingCoupon,
	};
};
