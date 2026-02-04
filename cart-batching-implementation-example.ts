/**
 * Cart Request Batching System - Example Implementation
 *
 * This implementation provides a clean API that handles context capture
 * automatically, making it intuitive for extenders to use.
 */

// ============================================================================
// Core Types
// ============================================================================

interface CartState {
	items: CartItem[];
	totals: CartTotals;
	isProcessing: boolean;
}

interface CartItem {
	key: string;
	id: number;
	quantity: number;
	prices: ItemPrices;
	// ... other cart item properties
}

interface CartTotals {
	total_price: string;
	total_items: string;
	// ... other totals
}

interface ItemPrices {
	price: string;
	regular_price: string;
	// ... other prices
}

interface MutationRequest {
	path: string;
	method: 'POST' | 'PUT' | 'DELETE';
	body: unknown;
	headers?: Record< string, string >;
}

interface MutationResult< T = unknown > {
	success: boolean;
	data?: T;
	error?: Error;
}

interface BatchResponse {
	success: boolean;
	data?: {
		responses: Array< {
			status: number;
			body: unknown;
			headers?: Record< string, string >;
		} >;
	};
	error?: Error;
}

// ============================================================================
// State Manager Interface
// ============================================================================

interface StateManager {
	getSnapshot(): CartState;
	applyOptimisticUpdate( update: ( state: CartState ) => CartState ): void;
	applyServerState( state: CartState ): void;
	sendRequest< T >( request: MutationRequest ): Promise< T >;
	showErrors( errors: Error[] ): void;
}

// ============================================================================
// Core Mutation Queue Implementation
// ============================================================================

type QueueState =
	| 'idle'
	| 'collecting'
	| 'sending'
	| 'recording'
	| 'reconciling';

interface QueuedMutation< T = unknown > {
	id: string;
	key: string;
	request: MutationRequest;
	optimisticUpdate?: ( state: CartState ) => CartState;
	onSettled?: ( result: MutationResult< T > ) => void;
	resolve: ( result: MutationResult< T > ) => void;
	reject: ( error: Error ) => void;
}

class MutationQueue {
	private state: QueueState = 'idle';
	private currentBatch: QueuedMutation[] = [];
	private pendingQueue: QueuedMutation[] = [];
	private inFlightCount = 0;
	private batchIndex = 0;

	// Reconciliation state
	private snapshot: CartState | null = null;
	private lastServerState: CartState | null = null;
	private lastServerStateIndex = -1;
	private accumulatedErrors: Error[] = [];

	// State management
	private stateManager: StateManager;
	private listeners = new Set< ( processing: boolean ) => void >();

	constructor( stateManager: StateManager ) {
		this.stateManager = stateManager;
	}

	/**
	 * Submit a mutation to the queue
	 */
	async submit< T >( mutation: {
		key: string;
		mutate: () => MutationRequest;
		optimisticUpdate?: ( state: CartState ) => CartState;
		onSettled?: ( result: MutationResult< T > ) => void;
	} ): Promise< MutationResult< T > > {
		return new Promise( ( resolve, reject ) => {
			const id = `${ mutation.key }-${ Date.now() }-${ Math.random() }`;

			// Capture request data immediately (ensures isolation)
			const request = mutation.mutate();
			const isolatedRequest: MutationRequest = {
				path: request.path,
				method: request.method,
				body: JSON.parse( JSON.stringify( request.body ) ),
				headers: request.headers ? { ...request.headers } : undefined,
			};

			const queuedMutation: QueuedMutation< T > = {
				id,
				key: mutation.key,
				request: isolatedRequest,
				optimisticUpdate: mutation.optimisticUpdate,
				onSettled: mutation.onSettled,
				resolve,
				reject,
			};

			// Apply optimistic update immediately
			if ( mutation.optimisticUpdate ) {
				this.stateManager.applyOptimisticUpdate(
					mutation.optimisticUpdate
				);
			}

			this.enqueue( queuedMutation );
		} );
	}

	private enqueue( mutation: QueuedMutation ) {
		if ( this.state === 'idle' ) {
			// Take snapshot before first mutation
			this.snapshot = this.stateManager.getSnapshot();
			this.startCollecting();
		}

		if ( this.state === 'collecting' ) {
			// Add to current batch
			this.currentBatch.push( mutation );
		} else {
			// Queue for next cycle
			this.pendingQueue.push( mutation );
		}
	}

	private startCollecting() {
		this.state = 'collecting';
		this.notifyListeners( true );

		// Collect all synchronous submissions
		queueMicrotask( () => {
			if ( this.state === 'collecting' ) {
				this.sendBatch();
			}
		} );
	}

	private async sendBatch() {
		if ( this.currentBatch.length === 0 ) {
			this.state = 'idle';
			return;
		}

		this.state = 'sending';
		const batchIndex = this.batchIndex++;
		const batch = this.currentBatch;
		this.currentBatch = [];

		// Build batch request
		const batchRequest: MutationRequest = {
			path: '/wc/store/v1/batch',
			method: 'POST',
			body: {
				requests: batch.map( ( m ) => ( {
					path: m.request.path,
					method: m.request.method,
					body: m.request.body,
					headers: m.request.headers,
				} ) ),
			},
		};

		this.inFlightCount++;

		try {
			const response =
				await this.stateManager.sendRequest< BatchResponse >(
					batchRequest
				);
			this.recordResponse( batch, batchIndex, response );
		} catch ( error ) {
			this.recordResponse( batch, batchIndex, {
				success: false,
				error:
					error instanceof Error
						? error
						: new Error( String( error ) ),
			} );
		}
	}

	private recordResponse(
		batch: QueuedMutation[],
		batchIndex: number,
		response: BatchResponse
	) {
		this.state = 'recording';

		if ( ! response.success ) {
			// Total failure
			this.accumulatedErrors.push( response.error! );
			batch.forEach( ( mutation ) => {
				mutation.resolve( { success: false, error: response.error } );
			} );
		} else {
			// Process individual responses
			const responses = response.data!.responses;
			let hasSuccess = false;
			let latestServerState: CartState | null = null;

			batch.forEach( ( mutation, index ) => {
				const itemResponse = responses[ index ];
				const success =
					itemResponse.status >= 200 && itemResponse.status < 300;

				if ( success ) {
					hasSuccess = true;
					// Assuming the response body contains the updated cart state
					if (
						itemResponse.body &&
						typeof itemResponse.body === 'object' &&
						'cart' in itemResponse.body
					) {
						latestServerState = ( itemResponse.body as any ).cart;
					}
				} else {
					const error = new Error(
						`Request failed with status ${ itemResponse.status }`
					);
					this.accumulatedErrors.push( error );
					mutation.resolve( { success: false, error } );
					return;
				}

				mutation.resolve( {
					success: true,
					data: itemResponse.body,
				} );
			} );

			// Update last server state if newer
			if (
				hasSuccess &&
				latestServerState &&
				batchIndex > this.lastServerStateIndex
			) {
				this.lastServerState = latestServerState;
				this.lastServerStateIndex = batchIndex;
			}
		}

		this.inFlightCount--;

		if ( this.inFlightCount === 0 ) {
			this.reconcile();
		}
	}

	private reconcile() {
		this.state = 'reconciling';

		// Determine final state
		const finalState = this.lastServerState || this.snapshot!;

		// Apply final state
		this.stateManager.applyServerState( finalState );

		// Fire all onSettled callbacks
		this.currentBatch.forEach( ( mutation, index ) => {
			if ( mutation.onSettled ) {
				// Get the result that was already resolved
				const result = { success: true }; // This would be tracked properly
				mutation.onSettled( result );
			}
		} );

		// Show accumulated errors
		if ( this.accumulatedErrors.length > 0 ) {
			this.stateManager.showErrors( this.accumulatedErrors );
		}

		// Clear reconciliation state
		this.snapshot = null;
		this.lastServerState = null;
		this.lastServerStateIndex = -1;
		this.accumulatedErrors = [];

		// Process pending queue
		if ( this.pendingQueue.length > 0 ) {
			const pending = this.pendingQueue;
			this.pendingQueue = [];
			this.startCollecting();
			pending.forEach( ( m ) => this.currentBatch.push( m ) );
		} else {
			this.state = 'idle';
			this.notifyListeners( false );
		}
	}

	isProcessing(): boolean {
		return this.state !== 'idle';
	}

	subscribe( listener: ( processing: boolean ) => void ): () => void {
		this.listeners.add( listener );
		return () => this.listeners.delete( listener );
	}

	private notifyListeners( processing: boolean ) {
		this.listeners.forEach( ( listener ) => listener( processing ) );
	}
}

// ============================================================================
// Context-Aware Cart Actions
// ============================================================================

/**
 * Higher-level cart actions that handle context capture automatically
 * This is what extenders would actually use
 */
class CartActions {
	constructor(
		private queue: MutationQueue,
		private contextProvider: () => {
			productId?: number;
			quantityToAdd?: number;
		}
	) {}

	/**
	 * Add item to cart - captures context automatically
	 */
	async addCartItem( productId?: number, quantity?: number ) {
		// Capture context values immediately
		const context = this.contextProvider();
		const finalProductId = productId ?? context.productId;
		const finalQuantity = quantity ?? context.quantityToAdd ?? 1;

		if ( ! finalProductId ) {
			throw new Error( 'Product ID is required' );
		}

		return this.queue.submit< CartState >( {
			key: 'add-cart-item',
			mutate: () => ( {
				path: '/wc/store/v1/cart/add-item',
				method: 'POST',
				body: {
					id: finalProductId,
					quantity: finalQuantity,
				},
			} ),
			optimisticUpdate: ( state ) => ( {
				...state,
				items: this.optimisticallyAddItem(
					state.items,
					finalProductId,
					finalQuantity
				),
			} ),
			onSettled: ( result ) => {
				if ( result.success ) {
					// Fire legacy event
					dispatchEvent(
						new CustomEvent( 'wc-blocks_added_to_cart', {
							detail: {
								productId: finalProductId,
								quantity: finalQuantity,
							},
						} )
					);
				}
			},
		} );
	}

	/**
	 * Update item quantity - handles both absolute and delta updates
	 */
	async updateQuantity(
		cartKey: string,
		update: { delta?: number; absolute?: number }
	) {
		const currentItem = this.getCurrentItem( cartKey );
		if ( ! currentItem ) {
			throw new Error( `Item ${ cartKey } not found in cart` );
		}

		const newQuantity =
			update.absolute ?? currentItem.quantity + ( update.delta ?? 0 );
		const delta = newQuantity - currentItem.quantity;

		return this.queue.submit< CartState >( {
			key: 'update-quantity',
			mutate: () => ( {
				path: `/wc/store/v1/cart/items/${ cartKey }`,
				method: 'PUT',
				body: { quantity: newQuantity },
			} ),
			optimisticUpdate: ( state ) => ( {
				...state,
				items: state.items.map( ( item ) =>
					item.key === cartKey
						? { ...item, quantity: newQuantity }
						: item
				),
			} ),
		} );
	}

	/**
	 * Remove item from cart
	 */
	async removeCartItem( cartKey: string ) {
		return this.queue.submit< CartState >( {
			key: 'remove-item',
			mutate: () => ( {
				path: `/wc/store/v1/cart/items/${ cartKey }`,
				method: 'DELETE',
			} ),
			optimisticUpdate: ( state ) => ( {
				...state,
				items: state.items.filter( ( item ) => item.key !== cartKey ),
			} ),
			onSettled: ( result ) => {
				if ( result.success ) {
					dispatchEvent(
						new CustomEvent( 'wc-blocks_removed_from_cart', {
							detail: { cartKey },
						} )
					);
				}
			},
		} );
	}

	/**
	 * Batch add multiple items
	 */
	async batchAddItems(
		items: Array< { productId: number; quantity: number } >
	) {
		const promises = items.map( ( item ) =>
			this.addCartItem( item.productId, item.quantity )
		);

		return Promise.all( promises );
	}

	private optimisticallyAddItem(
		items: CartItem[],
		productId: number,
		quantity: number
	): CartItem[] {
		const existing = items.find( ( item ) => item.id === productId );

		if ( existing ) {
			return items.map( ( item ) =>
				item.id === productId
					? { ...item, quantity: item.quantity + quantity }
					: item
			);
		}

		// Create temporary item for optimistic update
		return [
			...items,
			{
				key: `temp-${ productId }-${ Date.now() }`,
				id: productId,
				quantity,
				prices: {} as ItemPrices, // Will be filled by server
			},
		];
	}

	private getCurrentItem( cartKey: string ): CartItem | undefined {
		// This would access the current state
		return undefined; // Placeholder
	}
}

// ============================================================================
// React Integration Example
// ============================================================================

interface UseCartStoreReturn {
	actions: CartActions;
	state: CartState;
	isProcessing: boolean;
}

// Mock hook for demonstration
declare function useCartStore(): UseCartStoreReturn;
declare function useProductContext(): number;
declare function useCartQueue(): MutationQueue;
declare function showSuccessNotice( message: string ): void;
declare function showErrorNotice( message: string ): void;

// Example 1: Custom button component
function CustomAddToCartButton() {
	const { actions } = useCartStore();
	const productId = useProductContext();

	const handleClick = async () => {
		try {
			// Simple! No need to capture context manually
			await actions.addCartItem();
			showSuccessNotice( 'Added to cart!' );
		} catch ( error ) {
			showErrorNotice( 'Failed to add to cart' );
		}
	};

	return <button onClick={ handleClick }>Add to Cart</button>;
}

// Example 2: Quantity selector with delta updates
function QuantitySelector( { cartKey }: { cartKey: string } ) {
	const { actions } = useCartStore();

	const increase = () => actions.updateQuantity( cartKey, { delta: 1 } );
	const decrease = () => actions.updateQuantity( cartKey, { delta: -1 } );

	return (
		<div>
			<button onClick={ decrease }>-</button>
			<span>Quantity</span>
			<button onClick={ increase }>+</button>
		</div>
	);
}

// Example 3: Bulk operations
function BulkAddToCart() {
	const { actions } = useCartStore();

	const addMultipleItems = async () => {
		const items = [
			{ productId: 123, quantity: 2 },
			{ productId: 456, quantity: 1 },
			{ productId: 789, quantity: 3 },
		];

		// All will be batched automatically!
		await actions.batchAddItems( items );
	};

	return <button onClick={ addMultipleItems }>Add Bundle to Cart</button>;
}

// Example 4: Plugin integration with custom context
function PluginCartButton() {
	const queue = useCartQueue();

	// Plugin can use the queue directly for custom operations
	const handleCustomAction = () => {
		return queue.submit( {
			key: 'plugin-custom-action',
			mutate: () => ( {
				path: '/wc/store/v1/cart/custom-endpoint',
				method: 'POST',
				body: {
					pluginData: 'custom',
					timestamp: Date.now(),
				},
			} ),
			optimisticUpdate: ( state ) =>
				( {
					...state,
					// Plugin's custom optimistic update
					customField: 'updated',
				} as any ),
			onSettled: ( result ) => {
				// Plugin's custom handling
				console.log( 'Plugin action completed:', result );
			},
		} );
	};

	return <button onClick={ handleCustomAction }>Plugin Action</button>;
}

// ============================================================================
// Store Implementation Example
// ============================================================================

/**
 * Example of how the WooCommerce cart store would integrate the queue
 */
class WooCommerceCartStore {
	private queue: MutationQueue;
	private actions: CartActions;
	private state: CartState = {
		items: [],
		totals: {} as CartTotals,
		isProcessing: false,
	};

	constructor() {
		const stateManager: StateManager = {
			getSnapshot: () => ( { ...this.state } ),
			applyOptimisticUpdate: ( update ) => {
				this.state = update( this.state );
				this.notifySubscribers();
			},
			applyServerState: ( state ) => {
				this.state = state;
				this.notifySubscribers();
			},
			sendRequest: async ( request ) => {
				// Actual fetch implementation
				const response = await fetch( request.path, {
					method: request.method,
					body: JSON.stringify( request.body ),
					headers: {
						'Content-Type': 'application/json',
						...request.headers,
					},
				} );

				if ( ! response.ok ) {
					throw new Error(
						`Request failed: ${ response.statusText }`
					);
				}

				return response.json();
			},
			showErrors: ( errors ) => {
				// Show error notices
				errors.forEach( ( error ) => {
					console.error( 'Cart error:', error );
					// Would trigger UI notifications
				} );
			},
		};

		this.queue = new MutationQueue( stateManager );

		// Create context-aware actions
		this.actions = new CartActions( this.queue, () => {
			// This would get context from Interactivity API
			return {
				productId: this.getContextValue( 'productId' ),
				quantityToAdd: this.getContextValue( 'quantityToAdd' ),
			};
		} );

		// Subscribe to processing state
		this.queue.subscribe( ( processing ) => {
			this.state.isProcessing = processing;
			this.notifySubscribers();
		} );
	}

	private getContextValue( key: string ): any {
		// Placeholder for actual context retrieval
		return undefined;
	}

	private notifySubscribers() {
		// Notify React components of state changes
	}

	getState() {
		return this.state;
	}

	getActions() {
		return this.actions;
	}
}

// ============================================================================
// Benefits for Extenders
// ============================================================================

/**
 * 1. Simple API - Just call actions.addCartItem(), no context capture needed
 * 2. Automatic batching - Multiple sync calls are batched
 * 3. Optimistic updates - UI updates immediately
 * 4. Error handling - Errors are accumulated and shown together
 * 5. Legacy compatibility - Events fire at the right time
 * 6. Type safety - Full TypeScript support
 * 7. Extensible - Plugins can use the queue directly for custom endpoints
 */
