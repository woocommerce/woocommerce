# Cart Request Batching System - Fresh Implementation Specification

## Executive Summary

This specification defines a fresh implementation of a request batching/queueing system for WooCommerce cart operations. The design prioritizes simplicity, reliability, and developer experience while handling complex edge cases like race conditions, slow networks, and rapid user interactions.

## Core Design Principles

1. **Sequential Processing**: Only one batch can be in-flight at a time to prevent server-side race conditions
2. **Request Isolation**: All requests are immutably captured at submission time
3. **Smart Reconciliation**: Apply the latest successful server state, rollback only on total failure
4. **Developer-Friendly API**: Simple, predictable interface that hides complexity

## Algorithm Overview

The system follows a state machine approach with clear transitions:

```
IDLE → SNAPSHOT → COLLECTING → SENDING → RECORDING → RECONCILIATION → IDLE
```

### State Definitions

1. **IDLE**: No active operations, system ready for new requests
2. **SNAPSHOT**: Capturing current state before mutations begin
3. **COLLECTING**: Gathering requests within a single microtask tick
4. **SENDING**: Batch request in-flight to server
5. **RECORDING**: Processing responses as they arrive
6. **RECONCILIATION**: Applying final state and resolving promises

## API Design

### Consumer-Facing API

```typescript
interface CartMutationQueue {
	// Submit a mutation to the queue
	submit< T >( mutation: {
		// Unique identifier for this mutation type
		key: string;

		// The actual mutation function
		mutate: () => MutationRequest;

		// Optimistic update to apply immediately
		optimisticUpdate?: ( currentState: CartState ) => CartState;

		// Callback after this specific mutation settles
		onSettled?: ( result: MutationResult< T > ) => void;
	} ): Promise< MutationResult< T > >;

	// Check if any mutations are processing
	isProcessing(): boolean;

	// Subscribe to processing state changes
	subscribe( listener: ( processing: boolean ) => void ): () => void;
}

interface MutationRequest {
	path: string;
	method: 'POST' | 'PUT' | 'DELETE';
	body: unknown;
	headers?: Record< string, string >;
}

interface MutationResult< T > {
	success: boolean;
	data?: T;
	error?: Error;
}
```

### Usage Examples

```typescript
// Simple add to cart
await cartQueue.submit( {
	key: 'add-item',
	mutate: () => ( {
		path: '/wc/store/v1/cart/add-item',
		method: 'POST',
		body: { id: productId, quantity: 1 },
	} ),
	optimisticUpdate: ( state ) => ( {
		...state,
		items: [ ...state.items, { id: productId, quantity: 1 } ],
	} ),
} );

// Update quantity with delta
await cartQueue.submit( {
	key: 'update-quantity',
	mutate: () => ( {
		path: `/wc/store/v1/cart/items/${ cartKey }`,
		method: 'PUT',
		body: { quantity: currentQuantity + delta },
	} ),
	optimisticUpdate: ( state ) => ( {
		...state,
		items: state.items.map( ( item ) =>
			item.key === cartKey
				? { ...item, quantity: item.quantity + delta }
				: item
		),
	} ),
} );

// Remove item with event callback
await cartQueue.submit( {
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
			// Fire legacy event for compatibility
			dispatchEvent(
				new CustomEvent( 'wc-blocks_removed_from_cart', {
					detail: { cartItemKey: cartKey },
				} )
			);
		}
	},
} );
```

## Implementation Requirements

### 1. Request Isolation

**Problem**: Prevent mutations to request data after submission  
**Solution**: Deep clone all request bodies at submission time

```typescript
const isolatedBody = JSON.parse( JSON.stringify( mutation.mutate().body ) );
```

### 2. Micro-task Batching

**Problem**: Group synchronous operations efficiently  
**Solution**: Use `queueMicrotask` for collection window

```typescript
private startCollecting() {
  this.state = 'collecting';
  queueMicrotask(() => {
    if (this.state === 'collecting') {
      this.sendBatch();
    }
  });
}
```

### 3. Sequential Batch Processing

**Problem**: Concurrent batches cause server-side race conditions  
**Solution**: Queue new requests if a batch is in-flight

```typescript
if ( this.inFlightCount > 0 ) {
	// Queue for next cycle
	this.pendingQueue.push( mutation );
} else {
	// Process immediately
	this.currentBatch.push( mutation );
	this.startCollecting();
}
```

### 4. Smart State Reconciliation

**Problem**: Handle mixed success/failure scenarios correctly  
**Solution**: Track latest successful server state

```typescript
interface ReconciliationState {
	snapshot: CartState;
	lastServerState?: CartState;
	lastServerStateIndex: number;
	accumulatedErrors: Error[];
}

// During recording
if ( response.success && batchIndex > this.lastServerStateIndex ) {
	this.lastServerState = response.data;
	this.lastServerStateIndex = batchIndex;
}

// During reconciliation
const finalState = this.lastServerState || this.snapshot;
```

### 5. Event Timing Control

**Problem**: Legacy events must fire before processing flag clears  
**Solution**: Execute onSettled callbacks during reconciliation

```typescript
private reconcile() {
  // Apply final state
  this.applyState(finalState);

  // Fire all onSettled callbacks synchronously
  this.currentBatch.forEach((mutation, index) => {
    mutation.onSettled?.(results[index]);
  });

  // Clear processing flag last
  this.setProcessing(false);
}
```

### 6. Batch Endpoint Usage

**IMPORTANT**: All cart mutations MUST use the `/wc/store/v1/batch` endpoint. Individual endpoints should NOT be called directly.

```typescript
// All mutations go through batch endpoint
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
```

## Edge Case Handling

### 1. Rapid User Actions

**Scenario**: User clicks "Add to Cart" 5 times rapidly  
**Handling**:

-   First click starts batch collection
-   Clicks 2-4 are collected in same batch
-   Click 5 arrives during sending, queued for next batch
-   Each click's optimistic update applies immediately

### 2. Slow Network

**Scenario**: 30-second response time  
**Handling**:

-   UI shows optimistic update immediately
-   Processing indicator remains active
-   New requests queue up
-   On response, reconcile and process queue

### 3. Mixed Success/Failure

**Scenario**: Batch of 3 requests, middle one fails  
**Handling**:

-   Apply last successful server state
-   Show accumulated errors
-   Resolve promises with individual results

### 4. Total Failure

**Scenario**: Network error, all requests fail  
**Handling**:

-   Rollback to snapshot
-   Show all errors
-   Resolve promises with failure

### 5. Out-of-Order Responses

**Scenario**: Batch 2 returns before Batch 1  
**Handling**:

-   Check batch index before updating lastServerState
-   Older responses don't overwrite newer state

## Performance Considerations

1. **Batch Size Limits**: Consider limiting batch size to prevent large payloads
2. **Timeout Handling**: Add configurable timeout for slow networks
3. **Memory Management**: Clear snapshots and temporary state after reconciliation
4. **Event Debouncing**: Consider debouncing rapid successive submissions

## Testing Strategy

### Unit Tests

1. **State Transitions**: Verify correct state machine flow
2. **Batching Logic**: Test micro-task collection window
3. **Isolation**: Verify request body immutability
4. **Reconciliation**: Test all success/failure scenarios
5. **Queue Management**: Test sequential processing

### Integration Tests

1. **Rapid Actions**: Simulate fast clicking scenarios
2. **Network Delays**: Test with artificial latency
3. **Error Scenarios**: Test various failure modes
4. **Event Integration**: Verify legacy event firing

### E2E Tests

These tests should pass to ensure the implementation correctly handles all race conditions:

#### 1. Rapid Add to Cart Tests

**Test: Multiple rapid clicks on Add to Cart button**

```typescript
test( 'should handle 5 rapid add to cart clicks without race conditions', async ( {
	page,
} ) => {
	await page.goto( '/product/simple-product' );

	// Set up response monitoring
	const batchResponses: any[] = [];
	page.on( 'response', ( response ) => {
		if ( response.url().includes( '/wc/store/v1/batch' ) ) {
			batchResponses.push( response );
		}
	} );

	// Click Add to Cart 5 times rapidly
	const addButton = page.locator( 'button:has-text("Add to cart")' );
	await Promise.all( [
		addButton.click(),
		addButton.click(),
		addButton.click(),
		addButton.click(),
		addButton.click(),
	] );

	// Wait for processing to complete
	await page.waitForSelector(
		'button:has-text("Add to cart"):not([disabled])',
		{ timeout: 30000 }
	);

	// Verify: Should create multiple batches, not 5 separate requests
	expect( batchResponses.length ).toBeLessThan( 5 );
	expect( batchResponses.length ).toBeGreaterThanOrEqual( 2 );

	// Verify final quantity is correct (5 items)
	await page.goto( '/cart' );
	const quantity = await page
		.locator( 'input[name="cart[123][qty]"]' )
		.inputValue();
	expect( quantity ).toBe( '5' );
} );
```

#### 2. Mini-Cart Quantity Update Tests

**Test: Rapid quantity changes in mini-cart**

```typescript
test( 'should handle rapid quantity updates without UI flicker', async ( {
	page,
} ) => {
	// Add item to cart first
	await page.goto( '/product/simple-product' );
	await page.locator( 'button:has-text("Add to cart")' ).click();
	await page.waitForSelector( '.wc-block-mini-cart' );

	// Open mini-cart
	await page.locator( '.wc-block-mini-cart__button' ).click();

	// Record UI states during rapid clicks
	const uiStates: string[] = [];
	const recordState = async () => {
		const qty = await page
			.locator( '.wc-block-mini-cart-item__quantity' )
			.textContent();
		uiStates.push( qty || '' );
	};

	// Increase quantity rapidly 3 times
	const increaseButton = page.locator(
		'button[aria-label="Increase quantity"]'
	);
	await Promise.all( [
		increaseButton.click().then( recordState ),
		increaseButton.click().then( recordState ),
		increaseButton.click().then( recordState ),
	] );

	// Wait for final state
	await page.waitForResponse( '**/wc/store/v1/batch' );
	await page.waitForTimeout( 500 ); // Ensure UI settles

	// Verify: No intermediate incorrect states (e.g., shouldn't show 2 then 1 then 4)
	const finalQuantity = await page
		.locator( '.wc-block-mini-cart-item__quantity' )
		.textContent();
	expect( finalQuantity ).toBe( '4' );

	// Check that UI states progressed logically (1→2→3→4 or similar)
	const uniqueStates = [ ...new Set( uiStates ) ];
	expect( uniqueStates ).toBeSorted();
} );
```

#### 3. Concurrent Operations Test

**Test: Add to cart from product listing while updating mini-cart**

```typescript
test( 'should handle concurrent operations across different UI components', async ( {
	page,
} ) => {
	// Add initial item
	await page.goto( '/shop' );
	await page
		.locator( '[data-product-id="123"] button:has-text("Add to cart")' )
		.click();
	await page.waitForSelector( '.wc-block-mini-cart' );

	// Open mini-cart
	await page.locator( '.wc-block-mini-cart__button' ).click();

	// Start both operations concurrently
	const [ response1, response2 ] = await Promise.all( [
		// Update quantity in mini-cart
		page.locator( 'button[aria-label="Increase quantity"]' ).click(),
		// Add different product from listing
		page
			.locator( '[data-product-id="456"] button:has-text("Add to cart")' )
			.click(),
	] );

	// Wait for both batch requests to complete
	await page.waitForResponse(
		( response ) =>
			response.url().includes( '/wc/store/v1/batch' ) &&
			response.status() === 200
	);

	// Verify both operations succeeded
	await page.reload();
	const cartItems = await page.locator( '.wc-block-mini-cart-item' ).count();
	expect( cartItems ).toBe( 2 );

	const firstItemQty = await page
		.locator( '[data-product-id="123"] .quantity' )
		.textContent();
	expect( firstItemQty ).toBe( '2' );
} );
```

#### 4. Slow Network Simulation Test

**Test: Operations during slow network conditions**

```typescript
test( 'should maintain UI consistency during slow network', async ( {
	page,
	context,
} ) => {
	// Simulate slow 3G
	await context.route( '**/wc/store/v1/batch', async ( route ) => {
		await new Promise( ( resolve ) => setTimeout( resolve, 5000 ) ); // 5 second delay
		await route.continue();
	} );

	await page.goto( '/product/simple-product' );

	// Click Add to Cart
	await page.locator( 'button:has-text("Add to cart")' ).click();

	// Verify optimistic update appears immediately
	const buttonText = await page.locator( 'button' ).textContent();
	expect( buttonText ).toContain( 'Adding' );

	// Try to click again during processing
	await page.locator( 'button:has-text("Adding")' ).click();

	// Wait for completion
	await page.waitForSelector( 'button:has-text("Add to cart")', {
		timeout: 10000,
	} );

	// Verify cart has correct quantity (should batch both clicks)
	await page.goto( '/cart' );
	const quantity = await page
		.locator( 'input[name="cart[123][qty]"]' )
		.inputValue();
	expect( quantity ).toBe( '2' );
} );
```

#### 5. Error Handling Test

**Test: Mixed success/failure in batch**

```typescript
test( 'should handle partial batch failures gracefully', async ( {
	page,
	context,
} ) => {
	// Mock batch endpoint to fail middle request
	await context.route( '**/wc/store/v1/batch', async ( route ) => {
		const request = route.request();
		const body = await request.postDataJSON();

		// Create mixed response
		const response = {
			responses: body.requests.map( ( req: any, index: number ) => ( {
				status: index === 1 ? 400 : 200,
				body:
					index === 1
						? {
								code: 'invalid_quantity',
								message: 'Invalid quantity',
						  }
						: { cart: getMockCartState() },
			} ) ),
		};

		await route.fulfill( { json: response } );
	} );

	// Add three items rapidly
	await page.goto( '/shop' );
	await Promise.all( [
		page.locator( '[data-product-id="123"] button' ).click(),
		page.locator( '[data-product-id="456"] button' ).click(), // This will fail
		page.locator( '[data-product-id="789"] button' ).click(),
	] );

	// Wait for processing
	await page.waitForTimeout( 1000 );

	// Verify error is shown
	const errorNotice = page.locator( '.wc-block-error-notice' );
	await expect( errorNotice ).toContainText( 'Invalid quantity' );

	// Verify successful items were added
	await page.goto( '/cart' );
	const cartItems = await page.locator( '.wc-block-cart-item' ).count();
	expect( cartItems ).toBe( 2 ); // Only 2 items added successfully
} );
```

#### 6. Race Condition Prevention Test

**Test: Verify no duplicate items from rapid clicks**

```typescript
test( 'should prevent duplicate cart items from race conditions', async ( {
	page,
} ) => {
	await page.goto( '/product/simple-product' );

	// Monitor all network requests
	const requests: any[] = [];
	page.on( 'request', ( request ) => {
		if ( request.url().includes( '/wc/store/v1/' ) ) {
			requests.push( {
				url: request.url(),
				method: request.method(),
				postData: request.postData(),
			} );
		}
	} );

	// Click Add to Cart 10 times as fast as possible
	const addButton = page.locator( 'button:has-text("Add to cart")' );
	for ( let i = 0; i < 10; i++ ) {
		await addButton.click( { force: true, noWaitAfter: true } );
	}

	// Wait for all processing to complete
	await page.waitForSelector(
		'button:has-text("Add to cart"):not([disabled])',
		{ timeout: 30000 }
	);

	// Go to cart and verify
	await page.goto( '/cart' );

	// Should have exactly 1 cart item with quantity 10
	const cartItems = await page.locator( '.wc-block-cart-item' ).count();
	expect( cartItems ).toBe( 1 );

	const quantity = await page.locator( 'input[type="number"]' ).inputValue();
	expect( quantity ).toBe( '10' );

	// Verify no individual add-item requests were sent
	const addItemRequests = requests.filter( ( r ) =>
		r.url.includes( 'add-item' )
	);
	expect( addItemRequests.length ).toBe( 0 );

	// All requests should go through batch endpoint
	const batchRequests = requests.filter( ( r ) => r.url.includes( 'batch' ) );
	expect( batchRequests.length ).toBeGreaterThan( 0 );
} );
```

#### 7. Event Timing Test

**Test: Legacy events fire at correct time**

```typescript
test( 'should fire legacy events before processing completes', async ( {
	page,
} ) => {
	await page.goto( '/product/simple-product' );

	// Listen for custom events
	const eventData = await page.evaluate( () => {
		return new Promise( ( resolve ) => {
			const events: any[] = [];
			let isProcessing = false;

			// Monitor processing state
			const observer = new MutationObserver( ( mutations ) => {
				const button = document.querySelector(
					'button[type="submit"]'
				);
				if ( button ) {
					isProcessing = button.hasAttribute( 'disabled' );
				}
			} );
			observer.observe( document.body, {
				attributes: true,
				subtree: true,
			} );

			// Listen for legacy event
			document.addEventListener( 'wc-blocks_added_to_cart', ( e ) => {
				events.push( {
					type: 'event',
					isProcessingAtTime: isProcessing,
					detail: ( e as CustomEvent ).detail,
				} );

				// Resolve after a small delay to capture final state
				setTimeout( () => resolve( events ), 100 );
			} );
		} );
	} );

	// Click Add to Cart
	await page.locator( 'button:has-text("Add to cart")' ).click();

	// Wait for event data
	const events = await eventData;

	// Verify event fired while still processing
	expect( events ).toHaveLength( 1 );
	expect( events[ 0 ].isProcessingAtTime ).toBe( true );
} );
```

#### 8. State Consistency Test

**Test: Cart state remains consistent across page navigation**

```typescript
test( 'should maintain cart state consistency during concurrent updates', async ( {
	page,
} ) => {
	// Add initial items
	await page.goto( '/shop' );
	await page.locator( '[data-product-id="123"] button' ).click();
	await page.waitForResponse( '**/wc/store/v1/batch' );

	// Start updating quantity and navigate simultaneously
	await Promise.all( [
		page.locator( '.wc-block-mini-cart__button' ).click(),
		page.locator( 'button[aria-label="Increase quantity"]' ).click(),
		page.goto( '/cart' ),
	] );

	// Wait for any pending requests
	await page.waitForLoadState( 'networkidle' );

	// Verify cart shows correct quantity
	const cartQuantity = await page
		.locator( 'input[name="cart[123][qty]"]' )
		.inputValue();
	expect( cartQuantity ).toBe( '2' );

	// Go back to shop and verify mini-cart shows same
	await page.goto( '/shop' );
	await page.locator( '.wc-block-mini-cart__button' ).click();
	const miniCartQuantity = await page
		.locator( '.wc-block-mini-cart-item__quantity' )
		.textContent();
	expect( miniCartQuantity ).toBe( '2' );
} );
```

## Migration Path

For teams adopting this system:

1. **Gradual Adoption**: Implement alongside existing code
2. **Feature Flag**: Allow toggling between old/new systems
3. **Compatibility Layer**: Maintain legacy event firing
4. **Monitoring**: Track success rates and performance

## Success Metrics

1. **Race Condition Elimination**: Zero inconsistent cart states
2. **Performance**: Sub-100ms optimistic updates
3. **Developer Experience**: Reduced implementation complexity
4. **User Experience**: No UI flicker or stale data

## Implementation Checklist

-   [ ] Core queue implementation with state machine
-   [ ] Request isolation via deep cloning
-   [ ] Micro-task batching with queueMicrotask
-   [ ] Sequential batch processing
-   [ ] Smart reconciliation logic
-   [ ] Event timing control
-   [ ] Error accumulation and display
-   [ ] Promise resolution after reconciliation
-   [ ] isProcessing state management
-   [ ] Comprehensive test suite
-   [ ] Documentation and examples
-   [ ] Performance monitoring

## Appendix: Key Differences from Original Implementation

1. **Simpler API**: No batch concept exposed to consumers
2. **Better Isolation**: Immutable request capture
3. **Clearer State Machine**: Well-defined transitions
4. **Improved Error Handling**: Accumulation and display
5. **Event Timing**: Synchronized with reconciliation

## Complete Implementation Example

```typescript
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
}

interface CartTotals {
	total_price: string;
	total_items: string;
}

interface ItemPrices {
	price: string;
	regular_price: string;
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

		// Build batch request - ALWAYS use the batch endpoint
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

	private reconcile() {
		this.state = 'reconciling';

		// Determine final state
		const finalState = this.lastServerState || this.snapshot!;

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
// Context-Aware Cart Actions - The Clean API for Extenders
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
		},
		private stateProvider: () => CartState
	) {}

	/**
	 * Add item to cart - captures context automatically
	 */
	async addCartItem( productId?: number, quantity?: number ) {
		// Capture context values immediately before any async operations
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
	 */
	async updateQuantity(
		cartKey: string,
		update: { delta?: number; absolute?: number }
	) {
		const currentState = this.stateProvider();
		const currentItem = currentState.items.find(
			( item ) => item.key === cartKey
		);

		if ( ! currentItem ) {
			throw new Error( `Item ${ cartKey } not found in cart` );
		}

		const newQuantity =
			update.absolute ?? currentItem.quantity + ( update.delta ?? 0 );

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
	 * Batch add multiple items - all will be automatically batched
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
}

// ============================================================================
// Usage Examples for Extenders
// ============================================================================

// Example 1: Simple add to cart button
function CustomAddToCartButton( { useCartStore }: any ) {
	const { actions, isProcessing } = useCartStore();

	const handleClick = async () => {
		try {
			// No need to manually capture context!
			await actions.addCartItem();
			console.log( 'Added to cart successfully' );
		} catch ( error ) {
			console.error( 'Failed to add to cart:', error );
		}
	};

	return (
		<button onClick={ handleClick } disabled={ isProcessing }>
			{ isProcessing ? 'Adding...' : 'Add to Cart' }
		</button>
	);
}

// Example 2: Quantity selector with delta updates
function QuantitySelector( { cartKey, useCartStore }: any ) {
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
async function addProductBundle( actions: CartActions ) {
	const bundleItems = [
		{ productId: 123, quantity: 2 },
		{ productId: 456, quantity: 1 },
		{ productId: 789, quantity: 3 },
	];

	// All will be batched automatically!
	const results = await actions.batchAddItems( bundleItems );
	console.log( 'Bundle added:', results );
}

// Example 4: Plugin using the queue directly
function PluginCartButton( { useCartQueue }: any ) {
	const queue = useCartQueue();

	const handleCustomAction = () => {
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
			optimisticUpdate: ( state: any ) => ( {
				...state,
				pluginField: 'updated',
			} ),
			onSettled: ( result: any ) => {
				console.log( 'Plugin operation completed:', result );
			},
		} );
	};

	return <button onClick={ handleCustomAction }>Plugin Action</button>;
}

// ============================================================================
// Store Integration Example
// ============================================================================

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

	private getContext() {
		// This would integrate with Interactivity API
		return {
			productId: undefined, // Would come from context
			quantityToAdd: 1,
		};
	}

	private notifySubscribers() {
		// Notify React components or other subscribers
	}
}

/**
 * Key Benefits Summary:
 *
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
 */
```

## Acceptance Criteria

### Core Implementation

-   [ ] **Mutation Queue Implementation**

    -   [ ] Implements complete state machine (idle → collecting → sending → recording → reconciling)
    -   [ ] Uses queueMicrotask for micro-task batching
    -   [ ] Enforces sequential batch processing (no concurrent batches)
    -   [ ] Deep clones all request bodies for isolation
    -   [ ] ALL requests go through `/wc/store/v1/batch` endpoint

-   [ ] **Cart Actions API**

    -   [ ] Provides automatic context capture for extenders
    -   [ ] Supports addCartItem, updateQuantity, removeCartItem operations
    -   [ ] Handles both absolute and delta quantity updates
    -   [ ] Fires legacy events at correct time (during reconciliation)

-   [ ] **State Management**
    -   [ ] Takes single snapshot at start of batch cycle
    -   [ ] Tracks last successful server state with batch indexing
    -   [ ] Handles mixed success/failure scenarios correctly
    -   [ ] Rolls back to snapshot on total failure
    -   [ ] Accumulates and displays errors from failed requests

### Testing Coverage

-   [ ] **Unit Tests**

    -   [ ] State machine transitions work correctly
    -   [ ] Request isolation prevents mutation pollution
    -   [ ] Batch indexing handles out-of-order responses
    -   [ ] Reconciliation logic handles all success/failure combinations
    -   [ ] Queue management prevents concurrent batches

-   [ ] **E2E Tests - Race Conditions**

    -   [ ] Rapid add to cart (5+ clicks) creates correct final quantity
    -   [ ] No duplicate cart items from rapid clicking
    -   [ ] Mini-cart rapid quantity updates show no UI flicker
    -   [ ] Concurrent operations across UI components work correctly
    -   [ ] State remains consistent during page navigation

-   [ ] **E2E Tests - Network Conditions**

    -   [ ] Slow network (5+ second delay) maintains UI consistency
    -   [ ] Optimistic updates appear immediately
    -   [ ] Queue continues collecting during network delays
    -   [ ] Mixed success/failure batches show appropriate errors

-   [ ] **E2E Tests - Integration**
    -   [ ] Legacy events fire before processing completes
    -   [ ] All existing cart E2E tests pass
    -   [ ] Add to cart with options tests pass
    -   [ ] Mini-cart functionality tests pass
    -   [ ] No regressions in existing cart functionality

### Performance & Monitoring

-   [ ] **Performance Metrics**

    -   [ ] Optimistic updates apply in < 100ms
    -   [ ] Batch requests reduce total API calls by > 50%
    -   [ ] No memory leaks from state accumulation

-   [ ] **Developer Experience**
    -   [ ] Clear API documentation with examples
    -   [ ] No manual context capture required
    -   [ ] Error messages are actionable
    -   [ ] Easy migration path from existing code

## Final Notes

-   Do not store any generated documentation in the project repository
-   Use a separate directory for any temporary documentation
-   Focus on creating a clean, intuitive API that hides complexity from extenders
-   Ensure all cart operations use the batch endpoint for optimal performance
