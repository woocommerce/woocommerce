/**
 * External dependencies
 */
import { getConfig, store } from '@wordpress/interactivity';
import type {
	Cart,
	CartItem,
	CartVariationItem,
	ApiErrorResponse,
	ApiResponse,
	CartResponseTotals,
	Currency,
} from '@woocommerce/types';
import type {
	Store as StoreNotices,
	Notice,
} from '@woocommerce/stores/store-notices';

/**
 * Internal dependencies
 */
import { triggerAddedToCartEvent } from './legacy-events';

export type WooCommerceConfig = {
	products?: {
		[ productId: number ]: ProductData;
	};
	messages?: {
		addedToCartText?: string;
	};
	placeholderImgSrc?: string;
	currency?: Currency;
};

export type SelectedAttributes = Omit< CartVariationItem, 'raw_attribute' >;

export type OptimisticCartItem = {
	key?: string;
	id: number;
	quantity: number;
	variation?: CartVariationItem[];
	type: string;
};

export type ClientCartItem = Omit< OptimisticCartItem, 'variation' > & {
	variation?: SelectedAttributes[];
};

export type VariationData = {
	attributes: Record< string, string >;
	is_in_stock?: boolean;
	price_html?: string;
	image_id?: number;
	availability?: string;
	variation_description?: string;
	sku?: string;
	weight?: string;
	dimensions?: string;
	min?: number;
	max?: number;
	step?: number;
	sold_individually?: boolean;
};

export type ProductData = {
	type: string;
	price_html?: string;
	image_id?: number;
	availability?: string;
	sku?: string;
	weight?: string;
	dimensions?: string;
	min?: number;
	max?: number;
	step?: number;
	variations?: Record< number, VariationData >;
};

type CartUpdateOptions = { showCartUpdatesNotices?: boolean };

export type Store = {
	state: {
		errorMessages?: {
			[ key: string ]: string;
		};
		restUrl: string;
		nonce: string;
		cart: Omit< Cart, 'items' > & {
			items: ( OptimisticCartItem | CartItem )[];
			totals: CartResponseTotals;
		};
	};
	actions: {
		removeCartItem: ( key: string ) => void;
		addCartItem: (
			args: ClientCartItem,
			options?: CartUpdateOptions
		) => void;
		batchAddCartItems: (
			items: ClientCartItem[],
			options?: CartUpdateOptions
		) => void;
		// Todo: Check why if I switch to an async function here the types of the store stop working.
		refreshCartItems: () => void;
		showNoticeError: ( error: Error | ApiErrorResponse ) => void;
		updateNotices: ( notices: Notice[], removeOthers?: boolean ) => void;
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

// Guard to distinguish between optimistic and cart items.
function isCartItem( item: OptimisticCartItem | CartItem ): item is CartItem {
	return 'name' in item;
}

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

const generateErrorNotice = ( error: Error | ApiErrorResponse ): Notice => ( {
	notice: error.message,
	type: 'error',
	dismissible: true,
} );

const generateInfoNotice = ( message: string ): Notice => ( {
	notice: message,
	type: 'notice',
	dismissible: true,
} );

const getInfoNoticesFromCartUpdates = (
	oldCart: Store[ 'state' ][ 'cart' ],
	newCart: Cart,
	quantityChanges: QuantityChanges
): Notice[] => {
	const oldItems = oldCart.items;
	const newItems = newCart.items;

	const {
		productsPendingAdd: pendingAdd = [],
		cartItemsPendingQuantity: pendingQuantity = [],
		cartItemsPendingDelete: pendingDelete = [],
	} = quantityChanges;

	const autoDeletedToNotify = oldItems.filter(
		( old ) =>
			old.key &&
			isCartItem( old ) &&
			! newItems.some( ( item ) => old.key === item.key ) &&
			! pendingDelete.includes( old.key )
	);

	const autoUpdatedToNotify = newItems.filter( ( item ) => {
		if ( ! isCartItem( item ) ) {
			return false;
		}
		const old = oldItems.find( ( o ) => o.key === item.key );
		return old
			? ! pendingQuantity.includes( item.key ) &&
					item.quantity !== old.quantity
			: ! pendingAdd.includes( item.id );
	} );
	return [
		...autoDeletedToNotify.map( ( item ) =>
			// TODO: move the message template to iAPI config.
			generateInfoNotice(
				'"%s" was removed from your cart.'.replace( '%s', item.name )
			)
		),
		...autoUpdatedToNotify.map( ( item ) =>
			// TODO: move the message template to iAPI config.
			generateInfoNotice(
				'The quantity of "%1$s" was changed to %2$d.'
					.replace( '%1$s', item.name )
					.replace( '%2$d', item.quantity.toString() )
			)
		),
	];
};

// Same as the one in /assets/js/base/utils/variations/does-cart-item-match-attributes.ts.
const doesCartItemMatchAttributes = (
	cartItem: OptimisticCartItem,
	selectedAttributes: SelectedAttributes[]
) => {
	if (
		! Array.isArray( cartItem.variation ) ||
		! Array.isArray( selectedAttributes )
	) {
		return false;
	}

	if ( cartItem.variation.length !== selectedAttributes.length ) {
		return false;
	}

	return cartItem.variation.every(
		( {
			// eslint-disable-next-line
			raw_attribute,
			value,
		}: {
			raw_attribute: string;
			value: string;
		} ) =>
			selectedAttributes.some( ( item: SelectedAttributes ) => {
				return (
					item.attribute === raw_attribute &&
					( item.value.toLowerCase() === value.toLowerCase() ||
						( item.value && value === '' ) ) // Handle "any" attribute type
				);
			} )
	);
};

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
					const quantityChanges = { cartItemsPendingDelete: [ key ] };
					const infoNotices = getInfoNoticesFromCartUpdates(
						state.cart,
						json,
						quantityChanges
					);
					const errorNotices = json.errors.map( generateErrorNotice );
					yield actions.updateNotices(
						[ ...infoNotices, ...errorNotices ],
						true
					);

					state.cart = json;
					emitSyncEvent( { quantityChanges } );
				} catch ( error ) {
					state.cart = JSON.parse( previousCart );

					// Shows the error notice.
					actions.showNoticeError( error as Error );
				}
			},

			*addCartItem(
				{ id, quantity, variation }: ClientCartItem,
				{ showCartUpdatesNotices = true }: CartUpdateOptions = {}
			) {
				let item = state.cart.items.find( ( cartItem ) => {
					if ( cartItem.type === 'variation' ) {
						// If it's a variation, check that attributes match.
						// While different variations have different attributes,
						// some variations might accept 'Any' value for an attribute,
						// in which case, we need to check that the attributes match.
						if (
							id !== cartItem.id ||
							! cartItem.variation ||
							! variation ||
							cartItem.variation.length !== variation.length
						) {
							return false;
						}
						return doesCartItemMatchAttributes(
							cartItem,
							variation
						);
					}

					return id === cartItem.id;
				} );
				const endpoint = item ? 'update-item' : 'add-item';
				const previousCart = JSON.stringify( state.cart );
				const quantityChanges: QuantityChanges = {};

				// Optimistically update the number of items in the cart except
				// if the product is sold individually and is already in the
				// cart.
				let updatedItem = null;
				if ( item ) {
					const isSoldIndividually =
						isCartItem( item ) && item.sold_individually;
					updatedItem = { ...item, quantity };
					if ( item.key && ! isSoldIndividually ) {
						quantityChanges.cartItemsPendingQuantity = [ item.key ];
						item.quantity = quantity;
					}
				} else {
					item = {
						id,
						quantity,
						...( variation && { variation } ),
					} as OptimisticCartItem;
					quantityChanges.productsPendingAdd = [ id ];
					state.cart.items.push( item );
					updatedItem = item;
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
							body: JSON.stringify( updatedItem ),
						}
					);
					const json: Cart = yield res.json();

					// Checks if the response contains an error.
					if ( isApiErrorResponse( res, json ) )
						throw generateError( json );

					const infoNotices = showCartUpdatesNotices
						? getInfoNoticesFromCartUpdates(
								state.cart,
								json,
								quantityChanges
						  )
						: [];
					const errorNotices = json.errors.map( generateErrorNotice );
					yield actions.updateNotices(
						[ ...infoNotices, ...errorNotices ],
						true
					);

					// Updates the local cart.
					state.cart = json;

					// Dispatches a legacy event.
					triggerAddedToCartEvent( {
						preserveCartData: true,
					} );

					const { messages } = getConfig(
						'woocommerce'
					) as WooCommerceConfig;
					if ( messages?.addedToCartText ) {
						wp?.a11y?.speak( messages.addedToCartText, 'polite' );
					}

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

			*batchAddCartItems(
				items: ClientCartItem[],
				{ showCartUpdatesNotices = true }: CartUpdateOptions = {}
			) {
				const previousCart = JSON.stringify( state.cart );
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
									...( quantityChanges.cartItemsPendingQuantity ??
										[] ),
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
							...( item.variation && {
								variation: item.variation,
							} ),
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

					// Checks if the response contains an error.
					if ( isApiErrorResponse( res, json ) )
						throw generateError( json );

					const errorResponses = Array.isArray( json.responses )
						? json.responses.filter(
								( response ) =>
									response.status < 200 ||
									response.status >= 300
						  )
						: [];

					const successfulResponses = Array.isArray( json.responses )
						? json.responses.filter(
								( response ) =>
									response.status >= 200 &&
									response.status < 300
						  )
						: [];

					// Only update the cart and trigger events if there is at least one successful response.
					if ( successfulResponses.length > 0 ) {
						const lastSuccessfulCartResponse = successfulResponses[
							successfulResponses.length - 1
						]?.body as Cart;

						const infoNotices = showCartUpdatesNotices
							? getInfoNoticesFromCartUpdates(
									state.cart,
									lastSuccessfulCartResponse,
									quantityChanges
							  )
							: [];

						// Generate notices for any error that successful
						// responses may contain.
						const errorNotices = successfulResponses.flatMap(
							( response ) => {
								const errors = ( response.body.errors ??
									[] ) as ApiErrorResponse[];
								return errors.map( generateErrorNotice );
							}
						);

						yield actions.updateNotices(
							[ ...infoNotices, ...errorNotices ],
							true
						);

						// Use the last successful response to update the local cart.
						state.cart = lastSuccessfulCartResponse;

						// Dispatches a legacy event.
						triggerAddedToCartEvent( {
							preserveCartData: true,
						} );

						const { messages } = getConfig(
							'woocommerce'
						) as WooCommerceConfig;
						if ( messages?.addedToCartText ) {
							wp?.a11y?.speak(
								messages.addedToCartText,
								'polite'
							);
						}

						// Dispatches the event to sync the @wordpress/data store.
						emitSyncEvent( { quantityChanges } );
					}

					// Show error notices for all failed responses.
					yield actions.updateNotices(
						errorResponses
							.filter(
								( response ) =>
									response.body &&
									typeof response.body === 'object'
							)
							.map( ( { body } ) =>
								generateErrorNotice( body as ApiErrorResponse )
							)
					);
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

				const { code, message } = error as ApiErrorResponse;

				const userFriendlyMessage =
					state.errorMessages?.[ code ] || message;

				// Todo: Check what should happen if the notice is already displayed.
				noticeActions.addNotice( {
					notice: userFriendlyMessage,
					type: 'error',
					dismissible: true,
				} );

				// Emmits console.error for troubleshooting.
				// eslint-disable-next-line no-console
				console.error( error );
			},

			*updateNotices( newNotices: Notice[] = [], removeOthers = false ) {
				// Todo: Use the module exports instead of `store()` once the store-notices
				// store is public.
				yield import( '@woocommerce/stores/store-notices' );
				const { state: noticeState, actions: noticeActions } =
					store< StoreNotices >(
						'woocommerce/store-notices',
						{},
						{
							lock: 'I acknowledge that using a private store means my plugin will inevitably break on the next store release.',
						}
					);

				// Todo: Check what should happen if the notice is already displayed.
				const noticeIds = newNotices.map( ( notice ) =>
					noticeActions.addNotice( notice )
				);

				const { notices } = noticeState;
				if ( removeOthers ) {
					notices
						.map( ( { id } ) => id )
						.filter( ( id ) => ! noticeIds.includes( id ) )
						.forEach( ( id ) => noticeActions.removeNotice( id ) );
				}
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
