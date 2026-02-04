/**
 * Cart Request Batching System - Example Implementation
 *
 * This implementation provides a clean API that handles context capture
 * automatically, making it intuitive for extenders to use.
 */

// ============================================================================
// Core Mutation Queue Implementation
// ============================================================================

class MutationQueue {
	constructor( stateManager ) {
		this.stateManager = stateManager;
		this.state = 'idle';
		this.currentBatch = [];
		this.pendingQueue = [];
		this.inFlightCount = 0;
		this.batchIndex = 0;

		// Reconciliation state
		this.snapshot = null;
		this.lastServerState = null;
		this.lastServerStateIndex = -1;
		this.accumulatedErrors = [];

		// Listeners for processing state
		this.listeners = new Set();
	}

	/**
	 * Submit a mutation to the queue
	 * @param {Object} mutation - Mutation configuration
	 * @param {string} mutation.key - Unique identifier for mutation type
	 * @param {Function} mutation.mutate - Function that returns request config
	 * @param {Function} [mutation.optimisticUpdate] - State update function
	 * @param {Function} [mutation.onSettled] - Callback after mutation settles
	 * @returns {Promise} Resolution of the mutation
	 */
	async submit( mutation ) {
		return new Promise( ( resolve, reject ) => {
			const id = `${ mutation.key }-${ Date.now() }-${ Math.random() }`;

			// Capture request data immediately (ensures isolation)
			const request = mutation.mutate();
			const isolatedRequest = {
				path: request.path,
				method: request.method,
				body: JSON.parse( JSON.stringify( request.body ) ),
				headers: request.headers ? { ...request.headers } : undefined,
			};

			const queuedMutation = {
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

	enqueue( mutation ) {
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

	startCollecting() {
		this.state = 'collecting';
		this.notifyListeners( true );

		// Collect all synchronous submissions
		queueMicrotask( () => {
			if ( this.state === 'collecting' ) {
				this.sendBatch();
			}
		} );
	}

	async sendBatch() {
		if ( this.currentBatch.length === 0 ) {
			this.state = 'idle';
			return;
		}

		this.state = 'sending';
		const batchIndex = this.batchIndex++;
		const batch = this.currentBatch;
		this.currentBatch = [];

		// Build batch request
		const batchRequest = {
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
			const response = await this.stateManager.sendRequest(
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

	recordResponse( batch, batchIndex, response ) {
		this.state = 'recording';

		if ( ! response.success ) {
			// Total failure
			this.accumulatedErrors.push( response.error );
			batch.forEach( ( mutation ) => {
				mutation.resolve( { success: false, error: response.error } );
			} );
		} else {
			// Process individual responses
			const responses = response.data.responses;
			let hasSuccess = false;
			let latestServerState = null;

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
						latestServerState = itemResponse.body.cart;
					}

					mutation.resolve( {
						success: true,
						data: itemResponse.body,
					} );
				} else {
					const error = new Error(
						`Request failed with status ${ itemResponse.status }`
					);
					this.accumulatedErrors.push( error );
					mutation.resolve( { success: false, error } );
				}
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

	reconcile() {
		this.state = 'reconciling';

		// Determine final state
		const finalState = this.lastServerState || this.snapshot;

		// Apply final state
		this.stateManager.applyServerState( finalState );

		// Fire all onSettled callbacks synchronously
		// This ensures legacy events fire before processing flag clears
		this.currentBatch.forEach( ( mutation ) => {
			if ( mutation.onSettled ) {
				mutation.onSettled( { success: true } );
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

	isProcessing() {
		return this.state !== 'idle';
	}

	subscribe( listener ) {
		this.listeners.add( listener );
		return () => this.listeners.delete( listener );
	}

	notifyListeners( processing ) {
		this.listeners.forEach( ( listener ) => listener( processing ) );
	}
}

// ============================================================================
// Context-Aware Cart Actions - The Clean API for Extenders
// ============================================================================

class CartActions {
	constructor( queue, contextProvider, stateProvider ) {
		this.queue = queue;
		this.contextProvider = contextProvider;
		this.stateProvider = stateProvider;
	}

	/**
	 * Add item to cart - captures context automatically
	 *
	 * @param {number} [productId] - Product ID (optional, uses context if not provided)
	 * @param {number} [quantity] - Quantity (optional, uses context if not provided)
	 * @returns {Promise} Result of the operation
	 */
	async addCartItem( productId, quantity ) {
		// Capture context values immediately before any async operations
		const context = this.contextProvider();
		const finalProductId = productId ?? context.productId;
		const finalQuantity = quantity ?? context.quantityToAdd ?? 1;

		if ( ! finalProductId ) {
			throw new Error( 'Product ID is required' );
		}

		return this.queue.submit( {
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
					// Fire legacy event for third-party compatibility
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
	 *
	 * @param {string} cartKey - Cart item key
	 * @param {Object} update - Update configuration
	 * @param {number} [update.delta] - Change in quantity (+1, -1, etc)
	 * @param {number} [update.absolute] - Absolute quantity to set
	 * @returns {Promise} Result of the operation
	 */
	async updateQuantity( cartKey, update ) {
		const currentState = this.stateProvider();
		const currentItem = currentState.items.find(
			( item ) => item.key === cartKey
		);

		if ( ! currentItem ) {
			throw new Error( `Item ${ cartKey } not found in cart` );
		}

		const newQuantity =
			update.absolute ?? currentItem.quantity + ( update.delta ?? 0 );

		return this.queue.submit( {
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
	 *
	 * @param {string} cartKey - Cart item key to remove
	 * @returns {Promise} Result of the operation
	 */
	async removeCartItem( cartKey ) {
		return this.queue.submit( {
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
	 * Batch add multiple items - all will be automatically batched
	 *
	 * @param {Array} items - Array of {productId, quantity} objects
	 * @returns {Promise} Results of all operations
	 */
	async batchAddItems( items ) {
		const promises = items.map( ( item ) =>
			this.addCartItem( item.productId, item.quantity )
		);

		return Promise.all( promises );
	}

	optimisticallyAddItem( items, productId, quantity ) {
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
				prices: {}, // Will be filled by server
			},
		];
	}
}

// ============================================================================
// Usage Examples for Extenders
// ============================================================================

// Example 1: Simple add to cart button
async function handleAddToCart( actions ) {
	try {
		// The API automatically captures context - no manual capture needed!
		await actions.addCartItem();
		console.log( 'Added to cart successfully' );
	} catch ( error ) {
		console.error( 'Failed to add to cart:', error );
	}
}

// Example 2: Custom quantity controls
function createQuantityControls( actions, cartKey ) {
	return {
		increase: () => actions.updateQuantity( cartKey, { delta: 1 } ),
		decrease: () => actions.updateQuantity( cartKey, { delta: -1 } ),
		setQuantity: ( qty ) =>
			actions.updateQuantity( cartKey, { absolute: qty } ),
	};
}

// Example 3: Bulk operations - all automatically batched
async function addProductBundle( actions ) {
	const bundleItems = [
		{ productId: 123, quantity: 2 },
		{ productId: 456, quantity: 1 },
		{ productId: 789, quantity: 3 },
	];

	// These will all be sent in a single batch request!
	const results = await actions.batchAddItems( bundleItems );
	console.log( 'Bundle added:', results );
}

// Example 4: Plugin using the queue directly for custom endpoints
async function customPluginOperation( queue ) {
	return queue.submit( {
		key: 'plugin-custom-action',
		mutate: () => ( {
			path: '/wc/store/v1/cart/plugin-endpoint',
			method: 'POST',
			body: {
				pluginData: 'custom',
				timestamp: Date.now(),
			},
		} ),
		optimisticUpdate: ( state ) => ( {
			...state,
			pluginField: 'updated',
		} ),
		onSettled: ( result ) => {
			console.log( 'Plugin operation completed:', result );
		},
	} );
}

// Example 5: React component integration
function AddToCartButton( { useCartStore, useProductContext } ) {
	const { actions, isProcessing } = useCartStore();
	const productId = useProductContext();

	const handleClick = async () => {
		try {
			// No need to manually capture productId from context!
			await actions.addCartItem();
			// Show success message
		} catch ( error ) {
			// Show error message
		}
	};

	return (
		<button onClick={ handleClick } disabled={ isProcessing }>
			{ isProcessing ? 'Adding...' : 'Add to Cart' }
		</button>
	);
}

// ============================================================================
// Store Integration Example
// ============================================================================

class WooCommerceCartStore {
	constructor() {
		this.state = {
			items: [],
			totals: {},
			isProcessing: false,
		};

		// Create state manager
		const stateManager = {
			getSnapshot: () => ( { ...this.state } ),

			applyOptimisticUpdate: ( updateFn ) => {
				this.state = updateFn( this.state );
				this.notifySubscribers();
			},

			applyServerState: ( state ) => {
				this.state = state;
				this.notifySubscribers();
			},

			sendRequest: async ( request ) => {
				const response = await fetch( request.path, {
					method: request.method,
					body: JSON.stringify( request.body ),
					headers: {
						'Content-Type': 'application/json',
						...request.headers,
					},
				} );

				const data = await response.json();

				if ( ! response.ok ) {
					throw new Error(
						data.message ||
							`Request failed: ${ response.statusText }`
					);
				}

				return { success: true, data };
			},

			showErrors: ( errors ) => {
				errors.forEach( ( error ) => {
					console.error( 'Cart error:', error );
					// Trigger UI error notifications
				} );
			},
		};

		// Create mutation queue
		this.queue = new MutationQueue( stateManager );

		// Create context-aware actions
		this.actions = new CartActions(
			this.queue,
			() => this.getContext(),
			() => this.state
		);

		// Subscribe to processing state
		this.queue.subscribe( ( processing ) => {
			this.state.isProcessing = processing;
			this.notifySubscribers();
		} );
	}

	getContext() {
		// This would integrate with Interactivity API
		// For now, return mock context
		return {
			productId: window.currentProductId,
			quantityToAdd: window.currentQuantity || 1,
		};
	}

	notifySubscribers() {
		// Notify React components or other subscribers
		if ( this.onStateChange ) {
			this.onStateChange( this.state );
		}
	}
}

// ============================================================================
// Key Benefits Summary
// ============================================================================

/**
 * 1. INTUITIVE API FOR EXTENDERS
 *    - No manual context capture needed
 *    - Simple method calls: actions.addCartItem()
 *    - Context is captured automatically at the right time
 *
 * 2. AUTOMATIC BATCHING
 *    - Multiple synchronous calls are batched into one request
 *    - Reduces server load and improves performance
 *
 * 3. RACE CONDITION PREVENTION
 *    - Sequential batch processing prevents server-side races
 *    - Request isolation prevents mutation pollution
 *    - Proper event timing prevents UI inconsistencies
 *
 * 4. OPTIMISTIC UPDATES
 *    - UI updates immediately for better UX
 *    - Automatic rollback on failure
 *    - Consistent state reconciliation
 *
 * 5. EXTENSIBILITY
 *    - Plugins can use the queue directly
 *    - Custom endpoints supported
 *    - Legacy event compatibility maintained
 *
 * 6. ERROR HANDLING
 *    - Errors accumulated and shown together
 *    - Individual promise resolution
 *    - Graceful failure handling
 */

// Export for use
export { MutationQueue, CartActions, WooCommerceCartStore };
