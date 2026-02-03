/**
 * External dependencies
 */
import { getConfig, store } from '@wordpress/interactivity';
import type {
	Cart,
	CartItem,
	CartVariationItem,
	ApiErrorResponse,
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
import {
	createCommandQueue,
	createCartStateHandler,
	createAddItemCommand,
	createRemoveItemCommand,
	createBatchAddItemCommands,
} from './command-queue';
import type { CartState, QuantityChanges } from './command-queue';

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

/**
 * The command queue instance and state handler.
 * These are initialized lazily when the store is first accessed to avoid
 * circular dependency issues with the store initialization.
 */
let cartQueue: ReturnType< typeof createCommandQueue< CartState > > | null =
	null;

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

/**
 * Emit sync event to notify @wordpress/data store of cart updates.
 */
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

/**
 * Announce cart addition via a11y API.
 */
async function announceCartAddition() {
	const { messages } = getConfig( 'woocommerce' ) as WooCommerceConfig;
	if ( messages?.addedToCartText ) {
		const { speak } = await import( '@wordpress/a11y' );
		speak( messages.addedToCartText, 'polite' );
	}
}

/**
 * Get or create the cart command queue.
 * Lazy initialization to avoid issues with store not being ready.
 * This function is defined here but uses `state` via closure after store is created.
 */
// eslint-disable-next-line @typescript-eslint/no-use-before-define
function getCartQueue(): ReturnType< typeof createCommandQueue< CartState > > {
	if ( ! cartQueue ) {
		// eslint-disable-next-line @typescript-eslint/no-use-before-define
		const stateHandler = createCartStateHandler( {
			// eslint-disable-next-line @typescript-eslint/no-use-before-define
			getCartState: () => state.cart as CartState,
			setCartState: ( cart: CartState ) => {
				// eslint-disable-next-line @typescript-eslint/no-use-before-define
				state.cart = cart;
			},
			// eslint-disable-next-line @typescript-eslint/no-use-before-define
			errorMessages: state.errorMessages,
			showCartUpdatesNotices: true,
			onCartUpdated: ( quantityChanges: QuantityChanges ) => {
				// Dispatch legacy event
				triggerAddedToCartEvent( { preserveCartData: true } );
				// Sync with @wordpress/data store
				emitSyncEvent( { quantityChanges } );
				// Announce via a11y
				void announceCartAddition();
			},
		} );

		cartQueue = createCommandQueue( stateHandler, {
			// eslint-disable-next-line @typescript-eslint/no-use-before-define
			restUrl: state.restUrl,
			// eslint-disable-next-line @typescript-eslint/no-use-before-define
			nonce: state.nonce,
			timeout: 30000,
		} );
	}

	return cartQueue;
}

// Todo: export this store once the store is public.
const { state, actions } = store< Store >(
	'woocommerce',
	{
		actions: {
			/**
			 * Remove an item from the cart.
			 * Uses the command queue for optimistic updates and batching.
			 */
			removeCartItem( key: string ) {
				const queue = getCartQueue();
				const command = createRemoveItemCommand( { key }, state.nonce );
				queue.enqueue( command );
			},

			/**
			 * Add or update an item in the cart.
			 * Uses the command queue for optimistic updates and batching.
			 */
			addCartItem(
				{ id, key, quantity, variation, type }: ClientCartItem,
				// eslint-disable-next-line @typescript-eslint/no-unused-vars
				{ showCartUpdatesNotices = true }: CartUpdateOptions = {}
			) {
				const queue = getCartQueue();
				const command = createAddItemCommand(
					{
						id,
						quantity,
						variation,
						key,
						type,
					},
					state.nonce
				);
				queue.enqueue( command );
			},

			/**
			 * Add multiple items to the cart in a batch.
			 * Uses the command queue for optimistic updates and batching.
			 */
			batchAddCartItems(
				items: ClientCartItem[],
				// eslint-disable-next-line @typescript-eslint/no-unused-vars
				{ showCartUpdatesNotices = true }: CartUpdateOptions = {}
			) {
				const queue = getCartQueue();
				const commands = createBatchAddItemCommands(
					items,
					state.nonce
				);
				commands.forEach( ( command ) => queue.enqueue( command ) );
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
