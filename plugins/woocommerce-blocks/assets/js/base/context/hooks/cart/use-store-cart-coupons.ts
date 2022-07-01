/** @typedef { import('@woocommerce/type-defs/hooks').StoreCartCoupon } StoreCartCoupon */

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
		applyCoupon,
		removeCoupon,
		isApplyingCoupon,
		isRemovingCoupon,
	}: Pick<
		StoreCartCoupon,
		| 'applyCoupon'
		| 'removeCoupon'
		| 'isApplyingCoupon'
		| 'isRemovingCoupon'
		| 'receiveApplyingCoupon'
	> = useSelect(
		( select, { dispatch } ) => {
			const store = select( storeKey );
			const actions = dispatch( storeKey );

			return {
				applyCoupon: actions.applyCoupon,
				removeCoupon: actions.removeCoupon,
				isApplyingCoupon: store.isApplyingCoupon(),
				isRemovingCoupon: store.isRemovingCoupon(),
				receiveApplyingCoupon: actions.receiveApplyingCoupon,
			};
		},
		[ createErrorNotice, createNotice ]
	);

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
