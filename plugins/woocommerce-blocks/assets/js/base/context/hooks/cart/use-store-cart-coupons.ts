/** @typedef { import('@woocommerce/type-defs/hooks').StoreCartCoupon } StoreCartCoupon */

/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { CART_STORE_KEY as storeKey } from '@woocommerce/block-data';
import { decodeEntities } from '@wordpress/html-entities';
import type { StoreCartCoupon } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { useStoreCart } from './use-store-cart';
import { useStoreSnackbarNotices } from '../use-store-snackbar-notices';
import { useValidationContext } from '../../providers/validation';

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
	const { addSnackbarNotice } = useStoreSnackbarNotices();
	const { setValidationErrors } = useValidationContext();

	const results: Pick<
		StoreCartCoupon,
		'applyCoupon' | 'removeCoupon' | 'isApplyingCoupon' | 'isRemovingCoupon'
	> = useSelect(
		( select, { dispatch } ) => {
			const store = select( storeKey );
			const isApplyingCoupon = store.isApplyingCoupon();
			const isRemovingCoupon = store.isRemovingCoupon();
			const {
				applyCoupon,
				removeCoupon,
				receiveApplyingCoupon,
			}: {
				applyCoupon: ( coupon: string ) => Promise< boolean >;
				removeCoupon: ( coupon: string ) => Promise< boolean >;
				receiveApplyingCoupon: ( coupon: string ) => void;
			} = dispatch( storeKey );

			const applyCouponWithNotices = ( couponCode: string ) => {
				applyCoupon( couponCode )
					.then( ( result ) => {
						if ( result === true ) {
							addSnackbarNotice(
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
							addSnackbarNotice(
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
				applyCoupon: applyCouponWithNotices,
				removeCoupon: removeCouponWithNotices,
				isApplyingCoupon,
				isRemovingCoupon,
			};
		},
		[ createErrorNotice, addSnackbarNotice ]
	);

	return {
		appliedCoupons: cartCoupons,
		isLoading: cartIsLoading,
		...results,
	};
};
