/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';
import type { Cart, CartItem, ApiErrorResponse } from '@woocommerce/types';
import type { Store as StoreNotices } from '@woocommerce/stores/store-notices';

/**
 * Internal dependencies
 */
import { triggerAddedToCartEvent } from './legacy-events';

type OptimisticCartItem = {
	key?: string;
	id: number;
	quantity: number;
};

export type Store = {
	state: {
		restUrl: string;
		nonce: string;
		cart: Omit< Cart, 'items' > & {
			items: ( OptimisticCartItem | CartItem )[];
		};
	};
	actions: {
		addCartItem: ( args: { id: number; quantity: number } ) => void;
		// Todo: Check why if I switch to an async function here the types of the store stop working.
		refreshCartItems: () => void;
		showNoticeError: ( error: Error | ApiErrorResponse ) => void;
	};
};

type QuantityChanges = {
	cartItemsPendingQuantity?: string[];
	cartItemsPendingDelete?: string[];
	productsPendingAdd?: number[];
};

function isApiErrorResponse(
	res: Response,
	json: unknown
): json is ApiErrorResponse {
	return ! res.ok;
}

function generateError( error: ApiErrorResponse ): Error {
	return Object.assign( new Error( error.message || 'Unknown error.' ), {
		code: error.code || 'unknown_error',
	} );
}

let pendingRefresh = false;
let refreshTimeout = 3000;

function emitSyncEvent( {
	quantityChanges,
}: {
	quantityChanges: QuantityChanges;
} ) {
	window.dispatchEvent(
		new CustomEvent( 'wc-blocks_store_sync_required', {
			detail: {
				type: 'from_iAPI',
				quantityChanges,
			},
		} )
	);
}

// Todo: export this store once the store is public.
const { state, actions } = store< Store >(
	'woocommerce',
	{
		actions: {
			*addCartItem( { id, quantity }: { id: number; quantity: number } ) {
				let item = state.cart.items.find(
					( { id: productId } ) => id === productId
				);
				const endpoint = item ? 'update-item' : 'add-item';
				const previousCart = JSON.stringify( state.cart );
				const quantityChanges: QuantityChanges = {};

				// Optimistically updates the number of items in the cart.
				if ( item ) {
					item.quantity = quantity;
					if ( item.key )
						quantityChanges.cartItemsPendingQuantity = [ item.key ];
				} else {
					item = { id, quantity };
					state.cart.items.push( item );
					quantityChanges.productsPendingAdd = [ id ];
				}

				// Updates the database.
				try {
					const res: Response = yield fetch(
						`${ state.restUrl }wc/store/v1/cart/${ endpoint }`,
						{
							method: 'POST',
							headers: {
								Nonce: state.nonce,
								'Content-Type': 'application/json',
							},
							body: JSON.stringify( item ),
						}
					);
					const json: Cart = yield res.json();

					// Checks if the response contains an error.
					if ( isApiErrorResponse( res, json ) )
						throw generateError( json );

					// Checks if the response was successful, but still contains some errors.
					json.errors?.forEach( ( error ) => {
						actions.showNoticeError( error );
					} );

					// Updates the local cart.
					state.cart = json;

					// Dispatches a legacy event.
					triggerAddedToCartEvent( {
						preserveCartData: true,
					} );

					// Dispatches the event to sync the @wordpress/data store.
					emitSyncEvent( { quantityChanges } );
				} catch ( error ) {
					// Reverts the optimistic update.
					// Todo: Prevent racing conditions with multiple addToCart calls for the same item.
					state.cart = JSON.parse( previousCart );

					// Shows the error notice.
					actions.showNoticeError( error as Error );
				}
			},
			*refreshCartItems() {
				// Skips if there's a pending request.
				if ( pendingRefresh ) return;

				pendingRefresh = true;

				try {
					const res: Response = yield fetch(
						`${ state.restUrl }wc/store/v1/cart`,
						{ headers: { 'Content-Type': 'application/json' } }
					);
					const json: Cart = yield res.json();

					// Checks if the response contains an error.
					if ( isApiErrorResponse( res, json ) )
						throw generateError( json );

					// Updates the local cart.
					state.cart = json;

					// Resets the timeout.
					refreshTimeout = 3000;
				} catch ( error ) {
					// Tries again after the timeout.
					setTimeout( actions.refreshCartItems, refreshTimeout );

					// Increases the timeout exponentially.
					refreshTimeout *= 2;
				} finally {
					pendingRefresh = false;
				}
			},
			*showNoticeError( error: Error | ApiErrorResponse ) {
				// Todo: Use the module exports instead of `store()` once the store-notices
				// store is public.
				yield import( '@woocommerce/stores/store-notices' );
				const { actions: noticeActions } = store< StoreNotices >(
					'woocommerce/store-notices',
					{},
					{
						lock: 'I acknowledge that using a private store means my plugin will inevitably break on the next store release.',
					}
				);

				// Todo: Check what should happen if the notice is already displayed.
				noticeActions.addNotice( {
					notice: error.message,
					type: 'error',
					dismissible: true,
				} );

				// Emmits console.error for troubleshooting.
				// eslint-disable-next-line no-console
				console.error( error );
			},
		},
	},
	{ lock: true }
);

window.addEventListener(
	'wc-blocks_store_sync_required',
	async ( event: Event ) => {
		const customEvent = event as CustomEvent< {
			type: string;
			id: number;
		} >;
		if ( customEvent.detail.type === 'from_@wordpress/data' ) {
			actions.refreshCartItems();
		}
	}
);
