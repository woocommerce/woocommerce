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

type SubRequest = {
	method: 'POST';
	path: string;
	body: Record< string, unknown >;
};

type PendingEntry = {
	request: SubRequest;
	quantityChanges: QuantityChanges;
	showCartUpdatesNotices: boolean;
	snapshot: string;
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

// ---- Batching module state ----
let pending: PendingEntry[] = [];
let cyclePromise: Promise< void > | null = null;
let resolveCycle: ( () => void ) | null = null;

function startCycle(): void {
	cyclePromise = new Promise< void >( ( resolve ) => {
		resolveCycle = resolve;
	} );
	queueMicrotask( run );
}

function enqueue( entry: PendingEntry ): Promise< void > {
	pending.push( entry );
	if ( ! cyclePromise ) {
		startCycle();
	}
	return cyclePromise!;
}

async function run(): Promise< void > {
	// Use the snapshot from the first pending entry (taken before
	// the first optimistic update in this cycle).
	const snapshot = pending[ 0 ]?.snapshot ?? JSON.stringify( state.cart );

	let lastServerState: Cart | null = null;
	const accumulatedErrors: Notice[] = [];
	const allEntries: PendingEntry[] = [];

	while ( pending.length > 0 ) {
		const batch = pending;
		allEntries.push( ...batch );
		pending = [];

		const response = await sendBatch(
			batch.map( ( e ) => e.request )
		);
		const serverState = recordResponse( response, accumulatedErrors );
		if ( serverState !== null ) {
			lastServerState = serverState;
		}
	}

	reconcile( snapshot, lastServerState, accumulatedErrors, allEntries );

	if ( resolveCycle ) resolveCycle();

	cyclePromise = null;
	resolveCycle = null;
}

async function sendBatch(
	requests: SubRequest[]
): Promise< { ok: boolean; json: BatchResponse | null } > {
	try {
		const res = await fetch( `${ state.restUrl }wc/store/v1/batch`, {
			method: 'POST',
			headers: { Nonce: state.nonce, 'Content-Type': 'application/json' },
			body: JSON.stringify( { requests } ),
		} );
		if ( ! res.ok ) return { ok: false, json: null };
		return { ok: true, json: await res.json() };
	} catch {
		return { ok: false, json: null };
	}
}

function recordResponse(
	response: { ok: boolean; json: BatchResponse | null },
	errors: Notice[]
): Cart | null {
	if ( ! response.ok || ! response.json ) {
		errors.push(
			generateErrorNotice( {
				message: 'A network or server error occurred.',
			} as ApiErrorResponse )
		);
		return null;
	}

	const responses = response.json.responses;
	const successful = responses.filter(
		( r ) => r.status >= 200 && r.status < 300
	);
	const failed = responses.filter(
		( r ) => r.status < 200 || r.status >= 300
	);

	for ( const r of failed ) {
		errors.push(
			generateErrorNotice(
				( r.body as unknown as ApiErrorResponse ) ?? {
					message: 'Request failed.',
				}
			)
		);
	}

	if ( successful.length === 0 ) {
		return null;
	}

	const serverState = successful[ successful.length - 1 ].body as Cart;

	for ( const r of successful ) {
		const body = r.body as Cart;
		if ( body.errors?.length ) {
			for ( const err of body.errors ) {
				errors.push( generateErrorNotice( err ) );
			}
		}
	}

	return serverState;
}

function reconcile(
	snapshot: string,
	lastServerState: Cart | null,
	errors: Notice[],
	entries: PendingEntry[]
): void {
	const mergedQuantityChanges: QuantityChanges = {};
	for ( const entry of entries ) {
		const qc = entry.quantityChanges;
		if ( qc.cartItemsPendingQuantity ) {
			mergedQuantityChanges.cartItemsPendingQuantity = [
				...( mergedQuantityChanges.cartItemsPendingQuantity ?? [] ),
				...qc.cartItemsPendingQuantity,
			];
		}
		if ( qc.cartItemsPendingDelete ) {
			mergedQuantityChanges.cartItemsPendingDelete = [
				...( mergedQuantityChanges.cartItemsPendingDelete ?? [] ),
				...qc.cartItemsPendingDelete,
			];
		}
		if ( qc.productsPendingAdd ) {
			mergedQuantityChanges.productsPendingAdd = [
				...( mergedQuantityChanges.productsPendingAdd ?? [] ),
				...qc.productsPendingAdd,
			];
		}
	}

	const anyShowNotices = entries.some(
		( e ) => e.showCartUpdatesNotices
	);
	let infoNotices: Notice[] = [];
	if ( anyShowNotices && lastServerState ) {
		infoNotices = getInfoNoticesFromCartUpdates(
			state.cart,
			lastServerState,
			mergedQuantityChanges
		);
	}

	if ( lastServerState ) {
		state.cart = lastServerState;
	} else {
		state.cart = JSON.parse( snapshot );
	}

	const allNotices = [ ...infoNotices, ...errors ];
	if ( allNotices.length > 0 ) {
		actions.updateNotices( allNotices, true );
	}

	emitSyncEvent( { quantityChanges: mergedQuantityChanges } );
}

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
				const snapshot = JSON.stringify( state.cart );

				// Optimistic update.
				state.cart.items = state.cart.items.filter(
					( item ) => item.key !== key
				);

				yield enqueue( {
					request: {
						method: 'POST',
						path: '/wc/store/v1/cart/remove-item',
						body: { key } as unknown as Record< string, unknown >,
					},
					quantityChanges: { cartItemsPendingDelete: [ key ] },
					showCartUpdatesNotices: true,
					snapshot,
				} );
			},

			*addCartItem(
				{ id, key, quantity, variation }: ClientCartItem,
				{ showCartUpdatesNotices = true }: CartUpdateOptions = {}
			) {
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
				const snapshot = JSON.stringify( state.cart );
				const quantityChanges: QuantityChanges = {};

				// Optimistic update.
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

				yield enqueue( {
					request: {
						method: 'POST',
						path: `/wc/store/v1/cart/${ endpoint }`,
						body: updatedItem as unknown as Record<
							string,
							unknown
						>,
					},
					quantityChanges,
					showCartUpdatesNotices,
					snapshot,
				} );

				// Post-reconciliation side effects.
				triggerAddedToCartEvent( { preserveCartData: true } );
				const { messages } = getConfig(
					'woocommerce'
				) as WooCommerceConfig;
				if ( messages?.addedToCartText ) {
					const { speak } = yield import( '@wordpress/a11y' );
					speak( messages.addedToCartText, 'polite' );
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
