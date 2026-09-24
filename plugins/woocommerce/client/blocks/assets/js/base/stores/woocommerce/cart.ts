/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';
import type { AsyncAction } from '@wordpress/interactivity';
import type { ApiErrorResponse } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import {
	bindCartActions,
	findItemInCart,
	removeCartItem,
	addCartItem,
	updateCartItem,
	batchAddCartItems,
	refreshCart,
} from './cart-actions';
import { showNoticeError, updateNotices } from './notices';
import type { Store } from './cart-actions';

export type {
	Store,
	WooCommerceConfig,
	SelectedAttributes,
	OptimisticCartItem,
	AddCartItemPayload,
	ClientCartItem,
	AddCartItemError,
	AddCartItemOutcome,
} from './cart-actions';

// Stores are locked to prevent 3PD usage until the API is stable.
const universalLock =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

// Todo: export this store once the store is public.
const { state } = store< Store >( 'woocommerce', {}, { lock: universalLock } );
const { actions } = store< Store >(
	'woocommerce',
	{
		state: {
			findItemInCart,
		},
		actions: {
			removeCartItem,
			addCartItem,
			updateCartItem,
			batchAddCartItems,
			refreshCart,
			*showNoticeError(
				error: Error | ApiErrorResponse
			): AsyncAction< void > {
				yield* showNoticeError( error, state.errorMessages );
			},
			updateNotices,
		},
	},
	{ lock: universalLock }
);

bindCartActions( state, actions );

// Trigger initial cart refresh.
void actions.refreshCart();

window.addEventListener(
	'wc-blocks_store_sync_required',
	async ( event: Event ) => {
		const customEvent = event as CustomEvent< {
			type: string;
			id: number;
		} >;
		if ( customEvent.detail.type === 'from_@wordpress/data' ) {
			void actions.refreshCart();
		}
	}
);
