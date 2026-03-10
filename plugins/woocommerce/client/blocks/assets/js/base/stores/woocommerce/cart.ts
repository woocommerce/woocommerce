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
	key?: string | undefined;
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
	is_in_stock: boolean;
	sold_individually: boolean;
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
};

export type ProductData = {
	type: string;
	is_in_stock: boolean;
	sold_individually: boolean;
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
		// Todo: Check why if I switch to an async function here the types of the store stop working.
		refreshCartItems: () => void;
		showNoticeError: ( error: Error | ApiErrorResponse ) => void;
		updateNotices: ( notices: Notice[], removeOthers?: boolean ) => void;
		_processLifecycle: ( chainCount: number ) => void;
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

type PendingEntry = {
	request: {
		method: string;
		path: string;
		headers: Record< string, string >;
		body: unknown;
	};
	addItem: boolean;
	showNotices: boolean;
	/** IDs accumulated across batches; deduplicated before reconciliation (set semantics). */
	qtyChanges: QuantityChanges;
	resolve: () => void;
};

const MAX_LIFECYCLE_CHAINS = 10;

let pending: PendingEntry[] = [];
let running = false;
let savedSnapshot: string | null = null;

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
		( variationEntry: Record< string, string > ) => {
			const attrName =
				variationEntry.raw_attribute ?? variationEntry.attribute;
			const { value } = variationEntry;
			return selectedAttributes.some(
				( item: SelectedAttributes ) =>
					item.attribute === attrName &&
					( item.value.toLowerCase() === value.toLowerCase() ||
						( item.value && value === '' ) ) // Handle "any" attribute type
			);
		}
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

function enqueue( entry: Omit< PendingEntry, 'resolve' > ): {
	promise: Promise< void >;
	entry: PendingEntry;
} {
	let resolve: () => void;
	const promise = new Promise< void >( ( res ) => {
		resolve = res;
	} );

	const fullEntry: PendingEntry = { ...entry, resolve: resolve! };
	pending.push( fullEntry );

	if ( ! running ) {
		running = true;
		// Snapshot BEFORE optimistic updates — matches flowchart ordering
		// (IDLE → SNAPSHOT → DEBOUNCE → COLLECTING). Actions call enqueue()
		// before applying optimistic state mutations.
		try {
			// eslint-disable-next-line @typescript-eslint/no-use-before-define -- safe: enqueue() is only called after store() initializes state
			savedSnapshot = JSON.stringify( state.cart );
		} catch ( e ) {
			// eslint-disable-next-line no-console
			console.error( 'Cart snapshot failed:', e );
			running = false;
			pending.pop(); // remove the entry we just pushed
			throw e; // propagate to caller
		}
		queueMicrotask( () => {
			// The Interactivity API runtime auto-drives generators
			// and returns a Promise. If store() returns the raw
			// generator (e.g., in test mocks), drive it manually.
			// eslint-disable-next-line @typescript-eslint/no-use-before-define -- safe: runs in queueMicrotask after store() completes
			const result: unknown = actions._processLifecycle( 0 );
			if (
				result &&
				typeof ( result as Generator ).next === 'function'
			) {
				const gen = result as Generator;
				( function step( it: IteratorResult< unknown > ) {
					if ( it.done ) return;
					Promise.resolve( it.value ).then(
						( v ) => step( gen.next( v ) ),
						( e ) => {
							try {
								step( gen.throw!( e ) );
							} catch {
								/* generator done */
							}
						}
					);
				} )( gen.next() );
			}
		} );
	}

	return { promise, entry: fullEntry };
}

// Todo: export this store once the store is public.
const { state, actions } = store< Store >(
	'woocommerce',
	{
		actions: {
			*removeCartItem( key: string ) {
				// 1. Enqueue FIRST — snapshot taken here if idle → collecting
				const { promise, entry: enqueuedEntry } = enqueue( {
					request: {
						method: 'POST',
						path: '/wc/store/v1/cart/remove-item',
						headers: {
							Nonce: state.nonce,
							'Content-Type': 'application/json',
						},
						body: { key },
					},
					addItem: false,
					showNotices: true,
					qtyChanges: { cartItemsPendingDelete: [ key ] },
				} );

				// 2. Apply optimistic update AFTER enqueue()
				try {
					state.cart.items = state.cart.items.filter(
						( item ) => item.key !== key
					);
				} catch ( optimisticError ) {
					const idx = pending.indexOf( enqueuedEntry );
					if ( idx !== -1 ) {
						pending.splice( idx, 1 );
					}
					throw optimisticError;
				}

				// 3. Wait for reconciliation
				yield promise;
			},

			*addCartItem(
				{ id, key, quantity, variation }: ClientCartItem,
				{ showCartUpdatesNotices = true }: CartUpdateOptions = {}
			) {
				// 1. Prepare request metadata (no state mutation yet)
				let item = state.cart.items.find( ( cartItem ) => {
					if ( cartItem.type === 'variation' ) {
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
					return key ? key === cartItem.key : id === cartItem.id;
				} );
				const endpoint = item ? 'update-item' : 'add-item';
				const quantityChanges: QuantityChanges = {};

				let updatedItem;
				if ( item ) {
					updatedItem = { ...item, quantity };
				} else {
					updatedItem = {
						id,
						quantity,
						...( variation && { variation } ),
					} as OptimisticCartItem;
				}

				// 2. Enqueue FIRST — snapshot taken here if idle → collecting
				const { promise, entry: enqueuedEntry } = enqueue( {
					request: {
						method: 'POST',
						path: `/wc/store/v1/cart/${ endpoint }`,
						headers: {
							Nonce: state.nonce,
							'Content-Type': 'application/json',
						},
						body: updatedItem,
					},
					addItem: true,
					showNotices: showCartUpdatesNotices,
					qtyChanges: quantityChanges,
				} );

				// 3. Apply optimistic update AFTER enqueue() (snapshot already saved).
				try {
					if ( item ) {
						const isSoldIndividually =
							isCartItem( item ) && item.sold_individually;
						if ( item.key && ! isSoldIndividually ) {
							quantityChanges.cartItemsPendingQuantity = [
								item.key,
							];
							item.quantity = quantity;
						}
					} else {
						item = updatedItem as OptimisticCartItem;
						quantityChanges.productsPendingAdd = [ id ];
						state.cart.items.push( item );
					}
				} catch ( optimisticError ) {
					const idx = pending.indexOf( enqueuedEntry );
					if ( idx !== -1 ) {
						pending.splice( idx, 1 );
					}
					throw optimisticError;
				}

				// 4. Wait for reconciliation
				yield promise;
			},

			*_processLifecycle( chainCount: number ) {
				const processedEntries: PendingEntry[] = [];

				// Snapshot consumed — chained lifecycles get fresh snapshot
				if ( ! savedSnapshot ) {
					// eslint-disable-next-line no-console
					console.error( 'Cart batch: no snapshot available' );
					processedEntries.push( ...pending );
					pending = [];
					return; // → finally resolves entries, sets running = false
				}
				const snapshot = savedSnapshot;
				savedSnapshot = null;

				// Lifecycle accumulators
				let lastServerState: Cart | null = null;
				const allErrors: Array< {
					error: Error | ApiErrorResponse;
					isFromCartErrors: boolean;
				} > = [];
				let hadSuccessfulAdd = false;
				let showNotices = false;
				const qtyChanges: Required< QuantityChanges > = {
					productsPendingAdd: [],
					cartItemsPendingQuantity: [],
					cartItemsPendingDelete: [],
				};

				try {
					// ── BATCH LOOP ──
					// The first lifecycle (chainCount===0) re-drains pending
					// to pick up items enqueued during in-flight yields.
					// Chained lifecycles (chainCount>0) drain once — new items
					// are handled by chaining in the finally block.
					do {
						const entries = pending;
						pending = [];
						processedEntries.push( ...entries );

						const requests = entries.map( ( e ) => e.request );

						try {
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

							// Total failure: non-207 or malformed
							if (
								res.status !== 207 ||
								! Array.isArray( json.responses )
							) {
								if ( isApiErrorResponse( res, json ) ) {
									allErrors.push( {
										error: generateError(
											json as unknown as ApiErrorResponse
										),
										isFromCartErrors: false,
									} );
								} else {
									allErrors.push( {
										error: new Error(
											`Batch failed: ${ res.status }`
										),
										isFromCartErrors: false,
									} );
								}
							} else {
								// Process sub-responses
								const count = Math.min(
									json.responses.length,
									entries.length
								);
								if (
									json.responses.length !== entries.length
								) {
									// eslint-disable-next-line no-console
									console.warn(
										`Batch response count mismatch: sent ${ entries.length }, ` +
											`received ${ json.responses.length }`
									);
								}

								for ( let i = 0; i < count; i++ ) {
									const r = json.responses[ i ];
									if ( r.status >= 200 && r.status < 300 ) {
										lastServerState = r.body as Cart;

										if ( entries[ i ].addItem ) {
											hadSuccessfulAdd = true;
										}

										const entry = entries[ i ];
										showNotices ||= entry.showNotices;
										qtyChanges.productsPendingAdd.push(
											...( entry.qtyChanges
												.productsPendingAdd ?? [] )
										);
										qtyChanges.cartItemsPendingQuantity.push(
											...( entry.qtyChanges
												.cartItemsPendingQuantity ??
												[] )
										);
										qtyChanges.cartItemsPendingDelete.push(
											...( entry.qtyChanges
												.cartItemsPendingDelete ?? [] )
										);

										const cart = r.body as Cart;
										if ( cart.errors?.length ) {
											for ( const cartError of cart.errors ) {
												if (
													typeof cartError ===
														'object' &&
													cartError !== null &&
													'message' in cartError
												) {
													allErrors.push( {
														error: cartError as unknown as ApiErrorResponse,
														isFromCartErrors: true,
													} );
												} else {
													allErrors.push( {
														error: new Error(
															String( cartError )
														),
														isFromCartErrors: true,
													} );
												}
											}
										}
									} else {
										if (
											r.body &&
											typeof r.body === 'object'
										) {
											allErrors.push( {
												error: generateError(
													r.body as ApiErrorResponse
												),
												isFromCartErrors: false,
											} );
										}

										showNotices ||=
											entries[ i ].showNotices;
									}
								}

								for ( let i = count; i < entries.length; i++ ) {
									allErrors.push( {
										error: new Error(
											'Server did not return a response for this request'
										),
										isFromCartErrors: false,
									} );
									showNotices ||= entries[ i ].showNotices;
								}
							}
						} catch ( networkError ) {
							allErrors.push( {
								error: networkError as Error,
								isFromCartErrors: false,
							} );
						}
					} while ( pending.length > 0 && chainCount === 0 );

					// ── Deduplicate metadata arrays ──
					qtyChanges.productsPendingAdd = [
						...new Set( qtyChanges.productsPendingAdd ),
					];
					qtyChanges.cartItemsPendingQuantity = [
						...new Set( qtyChanges.cartItemsPendingQuantity ),
					];
					qtyChanges.cartItemsPendingDelete = [
						...new Set( qtyChanges.cartItemsPendingDelete ),
					];

					// ── RECONCILIATION ──
					running = false;

					// State resolution
					if ( lastServerState !== null ) {
						state.cart = lastServerState;
					} else {
						state.cart = JSON.parse( snapshot );
					}

					// Build notices array (synchronous)
					const notices: Notice[] = [];

					for ( const { error, isFromCartErrors } of allErrors ) {
						notices.push( generateErrorNotice( error ) );
						if ( ! isFromCartErrors ) {
							// eslint-disable-next-line no-console
							console.error( error );
						}
					}

					if ( showNotices ) {
						const previousCart = JSON.parse( snapshot );
						const infoNotices = getInfoNoticesFromCartUpdates(
							previousCart,
							state.cart,
							{
								productsPendingAdd:
									qtyChanges.productsPendingAdd,
								cartItemsPendingQuantity:
									qtyChanges.cartItemsPendingQuantity,
							}
						);
						notices.push( ...infoNotices );
					}

					// Synchronous side effects (before yields so callers
					// observe them within the same generator step)
					if ( hadSuccessfulAdd ) {
						triggerAddedToCartEvent( { preserveCartData: true } );
					}

					if ( lastServerState !== null ) {
						emitSyncEvent( { quantityChanges: qtyChanges } );
					}

					// Resolve processed entries now — state.cart is final.
					// Callers' microtasks run after this synchronous block,
					// seeing the reconciled state. The finally block also
					// calls resolve() as a safety net (double-resolve is a
					// no-op on native Promises).
					for ( const entry of processedEntries ) {
						try {
							entry.resolve();
						} catch {
							// defensive
						}
					}

					// Display notices (requires yield)
					if ( notices.length > 0 ) {
						try {
							yield actions.updateNotices( notices, true );
						} catch {
							try {
								yield actions.updateNotices( notices, true );
							} catch ( noticeError ) {
								// eslint-disable-next-line no-console
								console.error(
									'Failed to display cart notices:',
									noticeError
								);
								for ( const n of notices ) {
									// eslint-disable-next-line no-console
									console.warn(
										'Lost cart notice:',
										n.notice ?? n
									);
								}
							}
						}
					}

					// Async side effects (requires yield for dynamic import)
					if ( hadSuccessfulAdd ) {
						/* eslint-disable @typescript-eslint/no-var-requires */
						const { getConfig: cfg } =
							require( '@wordpress/interactivity' ) as {
								getConfig: typeof getConfig;
							};
						/* eslint-enable @typescript-eslint/no-var-requires */
						const { messages } = ( cfg( 'woocommerce' ) ??
							{} ) as WooCommerceConfig;
						if ( messages?.addedToCartText ) {
							const { speak } = yield import( '@wordpress/a11y' );
							speak( messages.addedToCartText, 'polite' );
						}
					}
				} catch ( error ) {
					if ( allErrors.length > 0 ) {
						// eslint-disable-next-line no-console
						console.error(
							`Cart batch: ${ allErrors.length } accumulated error(s) lost due to reconciliation failure:`
						);
						for ( const { error: accError } of allErrors ) {
							// eslint-disable-next-line no-console
							console.error( accError );
						}
					}

					if ( ! ( error instanceof Error ) ) {
						throw error;
					}
					// eslint-disable-next-line no-console
					console.error( 'Cart batch error:', error );
				} finally {
					// ── Resolve all processed entries — SOLE resolution point ──
					for ( const entry of processedEntries ) {
						try {
							entry.resolve();
						} catch {
							// defensive
						}
					}

					// ── Schedule next lifecycle or transition to idle ──
					if (
						pending.length > 0 &&
						chainCount < MAX_LIFECYCLE_CHAINS
					) {
						try {
							running = true;
							savedSnapshot = JSON.stringify( state.cart );
							queueMicrotask( () => {
								actions._processLifecycle( chainCount + 1 );
							} );
						} catch {
							for ( const entry of pending ) {
								try {
									entry.resolve();
								} catch {
									/* defensive */
								}
							}
							pending = [];
							running = false;
						}
					} else {
						if ( pending.length > 0 ) {
							// eslint-disable-next-line no-console
							console.error(
								`Cart batch circuit breaker: ${ MAX_LIFECYCLE_CHAINS } ` +
									`consecutive lifecycles. ${ pending.length } entries dropped.`
							);
							for ( const entry of pending ) {
								try {
									entry.resolve();
								} catch {
									/* defensive */
								}
							}
							pending = [];
						}
						running = false;
					}
				}
			},

			*refreshCartItems() {
				// Skips if there's a pending request.
				if ( pendingRefresh ) return;

				pendingRefresh = true;

				try {
					const res: Response = yield fetch(
						`${ state.restUrl }wc/store/v1/cart`,
						{
							method: 'GET',
							cache: 'no-store',
							headers: { 'Content-Type': 'application/json' },
						}
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
