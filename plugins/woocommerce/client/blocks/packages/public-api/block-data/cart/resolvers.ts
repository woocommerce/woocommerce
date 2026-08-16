/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { CartResponse } from '@woocommerce/types';
import { previewCart } from '@woocommerce/resource-previews';

/**
 * Internal dependencies
 */
import { CART_API_ERROR } from './constants';
import type { CartDispatchFromMap, CartResolveSelectFromMap } from './index';
import { setTriggerStoreSyncEvent } from './utils';
import { isEditor } from '../utils';

/**
 * Resolver for retrieving all cart data.
 */
export const getCartData =
	() =>
	async ( { dispatch }: { dispatch: CartDispatchFromMap } ) => {
		if ( isEditor() ) {
			dispatch.receiveCart( previewCart );
			return;
		}

		const response = await apiFetch< Response >( {
			path: '/wc/store/v1/cart',
			method: 'GET',
			cache: 'no-store',
			parse: false,
		} );

		if (
			// @ts-expect-error setCartHash exists but is not typed
			typeof apiFetch.setCartHash === 'function'
		) {
			// @ts-expect-error setCartHash exists but is not typed
			apiFetch.setCartHash( response?.headers );
		}

		try {
			const cartData: CartResponse = await response.json();
			const { receiveCart, receiveError } = dispatch;

			if ( ! cartData ) {
				receiveError( CART_API_ERROR );
				return;
			}

			setTriggerStoreSyncEvent( false );
			receiveCart( cartData );
			setTriggerStoreSyncEvent( true );
		} catch ( error ) {
			const { receiveError } = dispatch;
			receiveError( CART_API_ERROR );
		}
	};

/**
 * Resolver for retrieving cart totals.
 */
export const getCartTotals =
	() =>
	async ( {
		resolveSelect,
	}: {
		resolveSelect: CartResolveSelectFromMap;
	} ) => {
		await resolveSelect.getCartData();
	};
