/**
 * External dependencies
 */
import { store } from '@wordpress/interactivity';
import type {
	Cart,
	CartItem,
	CartVariationItem,
	ApiErrorResponse,
	ApiResponse,
	CartResponseTotals,
} from '@woocommerce/types';
import type { Store as StoreNotices } from '@woocommerce/stores/store-notices';

/**
 * Internal dependencies
 */
import { triggerAddedToCartEvent } from './legacy-events';

export type OptimisticCartItem = {
	key?: string;
	id: number;
	quantity: number;
	variation?: CartVariationItem[];
};

export type Store = {
	state: {
		restUrl: string;
		nonce: string;
		cart: Omit< Cart, 'items' > & {
			items: ( OptimisticCartItem | CartItem )[];
			totals: CartResponseTotals;
		};
	};
	actions: {
		removeCartItem: ( key: string ) => void;
		addCartItem: ( args: OptimisticCartItem ) => void;
		batchAddCartItems: ( items: OptimisticCartItem[] ) => void;
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

type BatchResponse = {
	responses: ApiResponse< Cart >[];
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

function getUserFriendlyErrorMessage(
	error: Error | ApiErrorResponse
): string {
	if (
		( error as ApiErrorResponse ).code ===
		'woocommerce_rest_missing_attributes'
	) {
		return 'Please select product attributes before adding to cart.';
	}
	return error.message;
}

// Todo: export this store once the store is public.
const { state, actions } = store< Store >(
	'woocommerce',
	{
		actions: {
			*removeCartItem( key: string ) {
				const previousCart = JSON.stringify( state.cart );

				// optimistically update the cart
				state.cart.items = state.cart.items.filter(
					( item ) => item.key !== key
				);

				try {
					const res: Response = yield fetch(
						`${ state.restUrl }wc/store/v1/cart/remove-item`,
						{
							method: 'POST',
							headers: {
								Nonce: state.nonce,
								'Content-Type': 'application/json',
							},
							body: JSON.stringify( { key } ),
						}
					);

					const json: Cart | ApiErrorResponse = yield res.json();

					if ( isApiErrorResponse( res, json ) ) {
						throw generateError( json );
					}
					state.cart = json;
					emitSyncEvent( {
						quantityChanges: { cartItemsPendingDelete: [ key ] },
					} );
				} catch ( error ) {
					state.cart = JSON.parse( previousCart );

					// Shows the error notice.
					actions.showNoticeError( error as Error );
				}
			},

			*addCartItem( { id, quantity, variation }: OptimisticCartItem ) {
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
					item = { id, quantity, variation } as OptimisticCartItem;
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

			*batchAddCartItems( items: OptimisticCartItem[] ) {
				const previousCart = structuredClone( state.cart );
				const quantityChanges: QuantityChanges = {};

				// Updates the database.
				try {
					const requests = items.map( ( item ) => {
						const existingItem = state.cart.items.find(
							( { id: productId } ) => item.id === productId
						);

						// Updates existing cart item.
						if ( existingItem ) {
							// Optimistically updates the number of items in the cart.
							existingItem.quantity = item.quantity;
							if ( existingItem.key ) {
								quantityChanges.cartItemsPendingQuantity = [
									existingItem.key,
								];
							}

							return {
								method: 'POST',
								path: `/wc/store/v1/cart/update-item`,
								headers: {
									Nonce: state.nonce,
									'Content-Type': 'application/json',
								},
								body: existingItem,
							};
						}

						// Adds new cart item.
						item = {
							id: item.id,
							quantity: item.quantity,
							variation: item.variation,
						} as OptimisticCartItem;
						state.cart.items.push( item );
						quantityChanges.productsPendingAdd =
							quantityChanges.productsPendingAdd
								? [
										...quantityChanges.productsPendingAdd,
										item.id,
								  ]
								: [ item.id ];

						return {
							method: 'POST',
							path: `/wc/store/v1/cart/add-item`,
							headers: {
								Nonce: state.nonce,
								'Content-Type': 'application/json',
							},
							body: item,
						};
					} );

					const res: Response = yield fetch(
						`${ state.restUrl }wc/store/v1/batch`,
						{
							method: 'POST',
							headers: {
								Nonce: state.nonce,
								'Content-Type': 'application/json',
							},
							body: JSON.stringify( { requests } ),
						}
					);

					const json: BatchResponse = yield res.json();

					// Checks if any of the responses contain an error.
					json.responses?.forEach( ( response ) => {
						if ( isApiErrorResponse( res, response ) )
							throw generateError( response );
					} );

					// Gets the last successful cart response.
					const successfulResponses = Array.isArray( json.responses )
						? json.responses.filter(
								( response ) =>
									response.status >= 200 &&
									response.status < 300
						  )
						: [];
					const lastSuccessfulCartResponse = successfulResponses[
						successfulResponses.length - 1
					]?.body as Cart;

					// Checks if the last successful cart response is valid.
					if ( ! lastSuccessfulCartResponse ) {
						throw new Error(
							'No successful cart response received.'
						);
					}

					// Checks if the last successful response contains any errors.
					if (
						lastSuccessfulCartResponse?.errors &&
						Array.isArray( lastSuccessfulCartResponse.errors )
					) {
						lastSuccessfulCartResponse.errors.forEach(
							( error ) => {
								actions.showNoticeError( error );
							}
						);
					}

					// Use the last successful response to update the local cart.
					const cartResponse = lastSuccessfulCartResponse;

					// Updates the local cart.
					state.cart = cartResponse;

					// Dispatches a legacy event.
					triggerAddedToCartEvent( {
						preserveCartData: true,
					} );

					// Dispatches the event to sync the @wordpress/data store.
					emitSyncEvent( { quantityChanges } );
				} catch ( error ) {
					// Reverts the optimistic update.
					// Todo: Prevent racing conditions with multiple addToCart calls for the same item.
					state.cart = previousCart;

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
					notice: getUserFriendlyErrorMessage( error ),
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
