/**
 * Batching Demo Block - Frontend Store
 *
 * This is a proper iAPI block that demonstrates how the mutation batcher works.
 * It has full context for notices and all other stores.
 *
 * The demos show:
 * - Synchronous calls get batched into ONE request
 * - Async calls (with awaits between) create SEPARATE requests
 * - Real-world scenarios like "Add All to Cart" and multi-extension responses
 */

/**
 * External dependencies
 */
import { store, getContext } from '@wordpress/interactivity';
import type { Store as WooCommerce } from '@woocommerce/stores/woocommerce/cart';

const UNLOCK_KEY =
	'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

interface Context {
	isRunning: boolean;
	lastDemo: string;
	log: string[];
	productIds: number[];
}

// Helper to get the cart store, refreshing cart state first to avoid
// stale keys from previous demo runs causing 409 errors.
const getCartStore = async () => {
	await import( '@woocommerce/stores/woocommerce/cart' );
	const cartStore = store< WooCommerce >(
		'woocommerce',
		{},
		{ lock: UNLOCK_KEY }
	);
	await cartStore.actions.refreshCartItems();
	return cartStore;
};

// Helper to get product IDs from context, with validation
const getProductIds = ( context: Context, count: number ): number[] => {
	const ids = context.productIds || [];
	if ( ids.length < count ) {
		// Not enough products - return what we have
		return ids.slice( 0, Math.min( ids.length, count ) );
	}
	return ids.slice( 0, count );
};

const batchingDemoStore = {
	state: {
		get logText(): string {
			const { log } = getContext< Context >();
			return log.join( '\n' );
		},
		get hasProducts(): boolean {
			const { productIds } = getContext< Context >();
			return productIds && productIds.length > 0;
		},
		get productCount(): number {
			const { productIds } = getContext< Context >();
			return productIds?.length || 0;
		},
	},
	actions: {
		/**
		 * Demo: Synchronous calls get batched into ONE request
		 */
		*syncCallsDemo(): Generator< unknown, void > {
			const context = getContext< Context >();
			const ids = getProductIds( context, 3 );

			if ( ids.length < 3 ) {
				context.log = [
					'❌ ERROR: Need at least 3 products for this demo.',
					`   Found only ${ ids.length } products in the store.`,
				];
				return;
			}

			context.isRunning = true;
			context.lastDemo = 'Sync Calls';
			context.log = [
				'=== SYNC CALLS DEMO ===',
				'Three addCartItem calls made synchronously...',
				'',
			];

			const { actions } = yield getCartStore();

			context.log = [
				...context.log,
				`→ Call 1: addCartItem({ id: ${ ids[ 0 ] } })`,
				`→ Call 2: addCartItem({ id: ${ ids[ 1 ] } })`,
				`→ Call 3: addCartItem({ id: ${ ids[ 2 ] } })`,
				'',
				'⏳ All calls queued in same microtask...',
			];

			// All three calls happen synchronously - no await between them
			const p1 = actions.addCartItem( { id: ids[ 0 ], quantity: 1 } );
			const p2 = actions.addCartItem( { id: ids[ 1 ], quantity: 1 } );
			const p3 = actions.addCartItem( { id: ids[ 2 ], quantity: 1 } );

			yield Promise.all( [ p1, p2, p3 ] );

			context.log = [
				...context.log,
				'',
				'📦 RESULT: All 3 calls combined into ONE batch request!',
				'✅ Check Network tab - only 1 request to /batch endpoint',
			];
			context.isRunning = false;
		},

		/**
		 * Demo: Async calls create SEPARATE batches
		 */
		*asyncCallsDemo(): Generator< unknown, void > {
			const context = getContext< Context >();
			const ids = getProductIds( context, 3 );

			if ( ids.length < 3 ) {
				context.log = [
					'❌ ERROR: Need at least 3 products for this demo.',
					`   Found only ${ ids.length } products in the store.`,
				];
				return;
			}

			context.isRunning = true;
			context.lastDemo = 'Async Calls';
			context.log = [
				'=== ASYNC CALLS DEMO ===',
				'Three addCartItem calls with await between each...',
				'',
			];

			const { actions } = yield getCartStore();

			context.log = [
				...context.log,
				`→ Call 1: addCartItem({ id: ${ ids[ 0 ] } })`,
			];
			yield actions.addCartItem( { id: ids[ 0 ], quantity: 1 } );
			context.log = [ ...context.log, '  ✓ Batch 1 complete' ];

			context.log = [
				...context.log,
				`→ Call 2: addCartItem({ id: ${ ids[ 1 ] } })`,
			];
			yield actions.addCartItem( { id: ids[ 1 ], quantity: 1 } );
			context.log = [ ...context.log, '  ✓ Batch 2 complete' ];

			context.log = [
				...context.log,
				`→ Call 3: addCartItem({ id: ${ ids[ 2 ] } })`,
			];
			yield actions.addCartItem( { id: ids[ 2 ], quantity: 1 } );
			context.log = [ ...context.log, '  ✓ Batch 3 complete' ];

			context.log = [
				...context.log,
				'',
				'📦 RESULT: 3 separate batch requests!',
				'   Each await allows microtask queue to flush → new batch',
			];
			context.isRunning = false;
		},

		/**
		 * Demo: Mixed sync/async creates predictable batching
		 */
		*mixedCallsDemo(): Generator< unknown, void > {
			const context = getContext< Context >();
			const ids = getProductIds( context, 6 );

			if ( ids.length < 6 ) {
				context.log = [
					'❌ ERROR: Need at least 6 products for this demo.',
					`   Found only ${ ids.length } products in the store.`,
				];
				return;
			}

			context.isRunning = true;
			context.lastDemo = 'Mixed Calls';
			context.log = [
				'=== MIXED CALLS DEMO ===',
				'Combination of sync and async patterns...',
				'',
			];

			const { actions } = yield getCartStore();

			// Batch 1: Two sync calls
			context.log = [
				...context.log,
				'BATCH 1: Two synchronous calls',
				`  → addCartItem({ id: ${ ids[ 0 ] } })`,
				`  → addCartItem({ id: ${ ids[ 1 ] } })`,
			];
			const p1 = actions.addCartItem( { id: ids[ 0 ], quantity: 1 } );
			const p2 = actions.addCartItem( { id: ids[ 1 ], quantity: 1 } );
			yield Promise.all( [ p1, p2 ] );
			context.log = [ ...context.log, '  ✓ Batch 1: 2 operations' ];

			// Batch 2: One call after await
			context.log = [
				...context.log,
				'',
				'BATCH 2: Single call after await',
				`  → addCartItem({ id: ${ ids[ 2 ] } })`,
			];
			yield actions.addCartItem( { id: ids[ 2 ], quantity: 1 } );
			context.log = [ ...context.log, '  ✓ Batch 2: 1 operation' ];

			// Batch 3: Three sync calls
			context.log = [
				...context.log,
				'',
				'BATCH 3: Three synchronous calls',
				`  → addCartItem({ id: ${ ids[ 3 ] } })`,
				`  → addCartItem({ id: ${ ids[ 4 ] } })`,
				`  → addCartItem({ id: ${ ids[ 5 ] } })`,
			];
			const p3 = actions.addCartItem( { id: ids[ 3 ], quantity: 1 } );
			const p4 = actions.addCartItem( { id: ids[ 4 ], quantity: 1 } );
			const p5 = actions.addCartItem( { id: ids[ 5 ], quantity: 1 } );
			yield Promise.all( [ p3, p4, p5 ] );
			context.log = [ ...context.log, '  ✓ Batch 3: 3 operations' ];

			context.log = [
				...context.log,
				'',
				'📦 RESULT: 3 batches with 2, 1, and 3 operations',
			];
			context.isRunning = false;
		},

		/**
		 * Demo: Realistic "Add All to Cart" scenario
		 */
		*wishlistDemo(): Generator< unknown, void > {
			const context = getContext< Context >();
			const ids = getProductIds( context, 5 );

			if ( ids.length < 5 ) {
				context.log = [
					'❌ ERROR: Need at least 5 products for this demo.',
					`   Found only ${ ids.length } products in the store.`,
				];
				return;
			}

			context.isRunning = true;
			context.lastDemo = 'Wishlist Add All';
			context.log = [
				'=== WISHLIST "ADD ALL" DEMO ===',
				'Scenario: User clicks "Add All to Cart" on their wishlist...',
				'',
			];

			const { actions } = yield getCartStore();

			const wishlistItems = [
				{ id: ids[ 0 ], name: 'Product A' },
				{ id: ids[ 1 ], name: 'Product B' },
				{ id: ids[ 2 ], name: 'Product C' },
				{ id: ids[ 3 ], name: 'Product D' },
				{ id: ids[ 4 ], name: 'Product E' },
			];

			context.log = [
				...context.log,
				'📋 Wishlist contains:',
				...wishlistItems.map(
					( item ) => `   • ${ item.name } (ID: ${ item.id })`
				),
				'',
				'🛒 Adding all items synchronously...',
			];

			// This is how a wishlist extension would add all items
			const promises = wishlistItems.map( ( item ) =>
				actions.addCartItem( { id: item.id, quantity: 1 } )
			);

			yield Promise.all( promises );

			context.log = [
				...context.log,
				'',
				'📦 RESULT: All 5 items in ONE batch request!',
				'✅ Without batching, this would be 5 separate API calls!',
			];
			context.isRunning = false;
		},

		/**
		 * Demo: Multiple extensions responding to same event
		 */
		*extensionsDemo(): Generator< unknown, void > {
			const context = getContext< Context >();
			const ids = getProductIds( context, 3 );

			if ( ids.length < 3 ) {
				context.log = [
					'❌ ERROR: Need at least 3 products for this demo.',
					`   Found only ${ ids.length } products in the store.`,
				];
				return;
			}

			context.isRunning = true;
			context.lastDemo = 'Multiple Extensions';
			context.log = [
				'=== MULTIPLE EXTENSIONS DEMO ===',
				'Scenario: User clicks "Add to Cart" on a laptop...',
				'Multiple extensions respond to the same event:',
				'',
			];

			const { actions } = yield getCartStore();

			context.log = [
				...context.log,
				`📦 Core Block: Adding main product (ID: ${ ids[ 0 ] })`,
				`🔌 Accessories Extension: Auto-adding accessory (ID: ${ ids[ 1 ] })`,
				`🛡️ Warranty Extension: Auto-adding warranty (ID: ${ ids[ 2 ] })`,
				'',
				'⏳ All extensions fired synchronously...',
			];

			// Simulate multiple extensions responding to the same add-to-cart event
			const coreAdd = actions.addCartItem( { id: ids[ 0 ], quantity: 1 } );
			const accessoryAdd = actions.addCartItem( {
				id: ids[ 1 ],
				quantity: 1,
			} );
			const warrantyAdd = actions.addCartItem( {
				id: ids[ 2 ],
				quantity: 1,
			} );

			yield Promise.all( [ coreAdd, accessoryAdd, warrantyAdd ] );

			context.log = [
				...context.log,
				'',
				'📦 RESULT: All 3 extensions combined into ONE request!',
				'✅ Extensions dont need to coordinate - batcher handles it!',
			];
			context.isRunning = false;
		},

		/**
		 * Demo: Failure handling - batch includes an invalid product
		 *
		 * Shows what happens when some operations in a batch fail.
		 * The batcher sends them all in one request, and the server
		 * returns individual success/failure for each operation.
		 *
		 * NOTE: addCartItem handles errors internally (catches + shows a
		 * notice), so promises always resolve from the caller's perspective.
		 * We verify success/failure by checking actual cart contents.
		 */
		*failureDemo(): Generator< unknown, void > {
			const context = getContext< Context >();
			const ids = getProductIds( context, 2 );

			if ( ids.length < 2 ) {
				context.log = [
					'❌ ERROR: Need at least 2 products for this demo.',
					`   Found only ${ ids.length } products in the store.`,
				];
				return;
			}

			const INVALID_ID = 999999;

			context.isRunning = true;
			context.lastDemo = 'Failure Handling';
			context.log = [
				'=== FAILURE HANDLING DEMO ===',
				'Batch includes a non-existent product to show error handling...',
				'',
				`→ Call 1: addCartItem({ id: ${ ids[ 0 ] } })  (valid)`,
				`→ Call 2: addCartItem({ id: ${ INVALID_ID } })  (INVALID - does not exist)`,
				`→ Call 3: addCartItem({ id: ${ ids[ 1 ] } })  (valid)`,
				'',
				'⏳ All 3 calls queued synchronously → 1 batch request...',
			];

			const { actions, state } = yield getCartStore();

			// All three calls in the same microtick - batched together
			const p1 = actions.addCartItem( { id: ids[ 0 ], quantity: 1 } );
			const p2 = actions.addCartItem( {
				id: INVALID_ID,
				quantity: 1,
			} );
			const p3 = actions.addCartItem( { id: ids[ 1 ], quantity: 1 } );

			// addCartItem handles errors internally (shows notice, doesn't
			// re-throw), so all promises resolve from the caller's perspective.
			yield Promise.allSettled( [ p1, p2, p3 ] );

			// Check which products actually ended up in the cart
			const cartProductIds = new Set(
				state.cart.items.map( ( item: { id: number } ) => item.id )
			);

			const requested = [
				{ id: ids[ 0 ], label: `${ ids[ 0 ] } (valid)` },
				{ id: INVALID_ID, label: `${ INVALID_ID } (invalid)` },
				{ id: ids[ 1 ], label: `${ ids[ 1 ] } (valid)` },
			];

			const resultLines = requested.map( ( { id, label }, i ) => {
				const inCart = cartProductIds.has( id );
				return inCart
					? `  ✅ Call ${ i + 1 } (ID: ${ label }): In cart`
					: `  ❌ Call ${ i + 1 } (ID: ${ label }): NOT in cart (server rejected)`;
			} );

			context.log = [
				...context.log,
				'',
				'📦 RESULT: Still only 1 batch request, with per-operation results:',
				...resultLines,
				'',
				'💡 HOW IT WORKS:',
				'   1. Batcher sends all 3 operations in one request',
				'   2. Server returns per-item status (200 or 400)',
				'   3. Batcher rejects the failed item\'s internal promise',
				'   4. addCartItem catches the rejection → shows error notice',
				'   5. Valid items succeed; invalid item is rolled back',
				'   6. Cart state is consistent: only valid products present',
			];
			context.isRunning = false;
		},

		/**
		 * Clear the log
		 */
		clearLog() {
			const context = getContext< Context >();
			context.log = [];
			context.lastDemo = '';
		},
	},
};

store( 'woocommerce/batching-demo', batchingDemoStore, { lock: true } );
