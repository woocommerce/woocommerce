/**
 * Cart State Handler
 *
 * Bridges the Command Queue with the WordPress Interactivity API cart store.
 * This handler implements the StateHandler interface to provide:
 * - State access (getState/setState) for optimistic updates and reconciliation
 * - Deep cloning for snapshots (to enable rollback)
 * - Processing state callbacks for loading indicators
 * - Error handling through the store notices system
 *
 * The handler is designed to work with the iAPI store's reactive state system
 * while maintaining the command queue's source-of-truth model.
 */

/**
 * External dependencies
 */
import type { ApiErrorResponse, CartItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import type { StateHandler, BatchResult } from './types';
import type { CartState, OptimisticCartItem } from './cart-commands';

/**
 * Notice type for store notices.
 */
export type Notice = {
	notice: string;
	type: 'error' | 'success' | 'notice';
	dismissible: boolean;
};

/**
 * Quantity changes tracked during a batch operation.
 * Used for generating info notices about cart updates.
 */
export type QuantityChanges = {
	cartItemsPendingQuantity?: string[];
	cartItemsPendingDelete?: string[];
	productsPendingAdd?: number[];
};

/**
 * Configuration for creating a cart state handler.
 */
export interface CartStateHandlerConfig {
	/**
	 * Function to get the current cart state.
	 * Typically returns `state.cart` from the iAPI store.
	 */
	getCartState: () => CartState;

	/**
	 * Function to set the cart state.
	 * Typically sets `state.cart = newState` in the iAPI store.
	 */
	setCartState: ( cart: CartState ) => void;

	/**
	 * Error messages map for user-friendly error display.
	 * Maps error codes to localized messages.
	 */
	errorMessages?: Record< string, string >;

	/**
	 * Whether to show info notices about cart updates.
	 * Default: true
	 */
	showCartUpdatesNotices?: boolean;

	/**
	 * Callback when cart is updated successfully.
	 * Used for legacy event dispatch and data store sync.
	 */
	onCartUpdated?: ( quantityChanges: QuantityChanges ) => void;

	/**
	 * Optional function to show error notices.
	 * If not provided, uses the default iAPI store-notices integration.
	 */
	showErrorNotice?: (
		error: Error | ApiErrorResponse,
		errorMessages?: Record< string, string >
	) => void;

	/**
	 * Optional function to update notices.
	 * If not provided, uses the default iAPI store-notices integration.
	 */
	updateNotices?: ( notices: Notice[], removeOthers?: boolean ) => void;
}

/**
 * Guard to distinguish between optimistic and full cart items.
 */
function isCartItem( item: OptimisticCartItem | CartItem ): item is CartItem {
	return 'name' in item;
}

/**
 * Generate a Notice object from an error.
 */
function generateErrorNotice( error: Error | ApiErrorResponse ): Notice {
	return {
		notice: error.message,
		type: 'error',
		dismissible: true,
	};
}

/**
 * Generate an info notice.
 */
function generateInfoNotice( message: string ): Notice {
	return {
		notice: message,
		type: 'notice',
		dismissible: true,
	};
}

/**
 * Get info notices for automatic cart updates made by the server.
 * These inform users when items were removed or quantities changed
 * due to stock changes or other server-side constraints.
 */
function getInfoNoticesFromCartUpdates(
	oldCart: CartState,
	newCart: CartState,
	quantityChanges: QuantityChanges
): Notice[] {
	const oldItems = oldCart.items;
	const newItems = newCart.items;

	const {
		productsPendingAdd: pendingAdd = [],
		cartItemsPendingQuantity: pendingQuantity = [],
		cartItemsPendingDelete: pendingDelete = [],
	} = quantityChanges;

	// Items that were removed by the server (not by user action)
	const autoDeletedToNotify = oldItems.filter(
		( old ) =>
			old.key &&
			isCartItem( old ) &&
			! newItems.some( ( item ) => old.key === item.key ) &&
			! pendingDelete.includes( old.key )
	);

	// Items whose quantity was changed by the server (not by user action)
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
}

/**
 * Default implementation for showing error notices via the iAPI store-notices store.
 * Uses dynamic imports to avoid module resolution issues in tests.
 */
async function defaultShowErrorNotice(
	error: Error | ApiErrorResponse,
	errorMessages?: Record< string, string >
): Promise< void > {
	// Dynamic imports to avoid test module resolution issues
	// eslint-disable-next-line @typescript-eslint/no-unused-vars -- Import needed to register store
	const [ _storeNoticesModule, interactivityModule ] = await Promise.all( [
		import( '@woocommerce/stores/store-notices' ),
		import( '@wordpress/interactivity' ),
	] );

	type StoreNotices = {
		state: { notices: Array< { id: string } > };
		actions: {
			addNotice: ( notice: Notice ) => string;
			removeNotice: ( id: string ) => void;
		};
	};

	const { store } = interactivityModule;
	const { actions: noticeActions } = store< StoreNotices >(
		'woocommerce/store-notices',
		{},
		{
			lock: 'I acknowledge that using a private store means my plugin will inevitably break on the next store release.',
		}
	);

	const { code, message } = error as ApiErrorResponse;
	const userFriendlyMessage = errorMessages?.[ code ] || message;

	noticeActions.addNotice( {
		notice: userFriendlyMessage,
		type: 'error',
		dismissible: true,
	} );

	// Emit console.error for troubleshooting.
	// eslint-disable-next-line no-console
	console.error( error );
}

/**
 * Default implementation for updating notices via the iAPI store-notices store.
 * Uses dynamic imports to avoid module resolution issues in tests.
 */
async function defaultUpdateNotices(
	newNotices: Notice[] = [],
	removeOthers = false
): Promise< void > {
	// Dynamic imports to avoid test module resolution issues
	const [ , interactivityModule ] = await Promise.all( [
		import( '@woocommerce/stores/store-notices' ),
		import( '@wordpress/interactivity' ),
	] );

	type StoreNotices = {
		state: { notices: Array< { id: string } > };
		actions: {
			addNotice: ( notice: Notice ) => string;
			removeNotice: ( id: string ) => void;
		};
	};

	const { store } = interactivityModule;
	const { state: noticeState, actions: noticeActions } =
		store< StoreNotices >(
			'woocommerce/store-notices',
			{},
			{
				lock: 'I acknowledge that using a private store means my plugin will inevitably break on the next store release.',
			}
		);

	const noticeIds = newNotices.map( ( notice ) =>
		noticeActions.addNotice( notice )
	);

	const { notices } = noticeState;
	if ( removeOthers ) {
		notices
			.map( ( { id } ) => id )
			.filter( ( id: string ) => ! noticeIds.includes( id ) )
			.forEach( ( id: string ) => noticeActions.removeNotice( id ) );
	}
}

/**
 * Creates a StateHandler for the cart that integrates with the iAPI store.
 *
 * @example
 * ```typescript
 * const handler = createCartStateHandler({
 *   getCartState: () => state.cart,
 *   setCartState: (cart) => { state.cart = cart; },
 *   errorMessages: state.errorMessages,
 *   onCartUpdated: (changes) => emitSyncEvent({ quantityChanges: changes }),
 * });
 *
 * const queue = createCommandQueue(handler, { timeout: 30000 });
 * ```
 */
export function createCartStateHandler(
	config: CartStateHandlerConfig
): StateHandler< CartState > {
	const {
		getCartState,
		setCartState,
		errorMessages,
		showCartUpdatesNotices = true,
		onCartUpdated,
		showErrorNotice = defaultShowErrorNotice,
		updateNotices = defaultUpdateNotices,
	} = config;

	// Track the snapshot for generating info notices
	let snapshotForNotices: CartState | null = null;

	// Track quantity changes during the current batch
	let currentQuantityChanges: QuantityChanges = {};

	return {
		/**
		 * Get the current cart state from the iAPI store.
		 */
		getState(): CartState {
			return getCartState();
		},

		/**
		 * Set the cart state in the iAPI store.
		 * This is called during reconciliation with the server state.
		 */
		setState( cart: CartState ): void {
			setCartState( cart );
		},

		/**
		 * Deep clone the cart state for snapshots.
		 * Uses JSON parse/stringify which is safe for cart data
		 * (all cart data is JSON-serializable).
		 */
		cloneState( cart: CartState ): CartState {
			return JSON.parse( JSON.stringify( cart ) );
		},

		/**
		 * Called when the queue starts processing a batch.
		 * Saves the current state for generating info notices later.
		 */
		onProcessingStart(): void {
			// Save snapshot for notice generation
			snapshotForNotices = JSON.parse( JSON.stringify( getCartState() ) );
			currentQuantityChanges = {};
		},

		/**
		 * Called when the queue finishes processing.
		 * Generates and displays appropriate notices based on the result.
		 */
		onProcessingEnd( result: BatchResult< CartState > ): void {
			if ( result.hasErrors ) {
				// Errors are handled in onErrors
				return;
			}

			if ( result.finalState && snapshotForNotices ) {
				// Generate info notices for server-side cart changes
				if ( showCartUpdatesNotices ) {
					const infoNotices = getInfoNoticesFromCartUpdates(
						snapshotForNotices,
						result.finalState,
						currentQuantityChanges
					);

					// Get error notices from the cart's errors array
					const cartErrors = result.finalState.errors || [];
					const errorNotices = cartErrors.map( generateErrorNotice );

					if ( infoNotices.length > 0 || errorNotices.length > 0 ) {
						updateNotices(
							[ ...infoNotices, ...errorNotices ],
							true
						);
					}
				}

				// Notify about cart update for legacy event dispatch and data store sync
				if ( onCartUpdated ) {
					onCartUpdated( currentQuantityChanges );
				}
			}

			// Reset tracking state
			snapshotForNotices = null;
			currentQuantityChanges = {};
		},

		/**
		 * Called when errors occur during batch execution.
		 * Displays error notices to the user.
		 */
		onErrors( errors: Error[] ): void {
			// Show each error as a notice
			errors.forEach( ( error ) => {
				showErrorNotice( error, errorMessages );
			} );

			// Reset tracking state
			snapshotForNotices = null;
			currentQuantityChanges = {};
		},
	};
}

/**
 * Tracks a quantity change for the current batch.
 * Call this before applying an optimistic update to track what changed.
 *
 * @param type - Type of change: 'add', 'remove', or 'update'
 * @param id   - Product ID (for add) or item key (for remove/update)
 */
export function trackQuantityChange(
	type: 'add' | 'remove' | 'update',
	id: number | string
): QuantityChanges {
	const changes: QuantityChanges = {};

	switch ( type ) {
		case 'add':
			changes.productsPendingAdd = [ id as number ];
			break;
		case 'remove':
			changes.cartItemsPendingDelete = [ id as string ];
			break;
		case 'update':
			changes.cartItemsPendingQuantity = [ id as string ];
			break;
	}

	return changes;
}
