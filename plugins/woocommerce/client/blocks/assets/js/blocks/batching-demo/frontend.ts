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
}

// Helper to get the cart store actions
const getCartActions = async () => {
	await import( '@woocommerce/stores/woocommerce/cart' );
	const { actions } = store< WooCommerce >(
		'woocommerce',
		{},
		{ lock: UNLOCK_KEY }
	);
	return actions;
};

const batchingDemoStore = {
	state: {
		get logText(): string {
			const { log } = getContext< Context >();
			return log.join( '\n' );
		},
	},
	actions: {
		/**
		 * Demo: Synchronous calls get batched into ONE request
		 */
		*syncCallsDemo(): Generator< unknown, void > {
			const context = getContext< Context >();
			context.isRunning = true;
			context.lastDemo = 'Sync Calls';
			context.log = [
				'=== SYNC CALLS DEMO ===',
				'Three addCartItem calls made synchronously...',
				'',
			];

			const actions = yield getCartActions();

			context.log = [
				...context.log,
				'→ Call 1: addCartItem({ id: 15 })',
				'→ Call 2: addCartItem({ id: 16 })',
				'→ Call 3: addCartItem({ id: 17 })',
				'',
				'⏳ All calls queued in same microtask...',
			];

			// All three calls happen synchronously - no await between them
			const p1 = actions.addCartItem( { id: 15, quantity: 1 } );
			const p2 = actions.addCartItem( { id: 16, quantity: 1 } );
			const p3 = actions.addCartItem( { id: 17, quantity: 1 } );

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
			context.isRunning = true;
			context.lastDemo = 'Async Calls';
			context.log = [
				'=== ASYNC CALLS DEMO ===',
				'Three addCartItem calls with await between each...',
				'',
			];

			const actions = yield getCartActions();

			context.log = [ ...context.log, '→ Call 1: addCartItem({ id: 18 })' ];
			yield actions.addCartItem( { id: 18, quantity: 1 } );
			context.log = [ ...context.log, '  ✓ Batch 1 complete' ];

			context.log = [ ...context.log, '→ Call 2: addCartItem({ id: 19 })' ];
			yield actions.addCartItem( { id: 19, quantity: 1 } );
			context.log = [ ...context.log, '  ✓ Batch 2 complete' ];

			context.log = [ ...context.log, '→ Call 3: addCartItem({ id: 20 })' ];
			yield actions.addCartItem( { id: 20, quantity: 1 } );
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
			context.isRunning = true;
			context.lastDemo = 'Mixed Calls';
			context.log = [
				'=== MIXED CALLS DEMO ===',
				'Combination of sync and async patterns...',
				'',
			];

			const actions = yield getCartActions();

			// Batch 1: Two sync calls
			context.log = [
				...context.log,
				'BATCH 1: Two synchronous calls',
				'  → addCartItem({ id: 21 })',
				'  → addCartItem({ id: 22 })',
			];
			const p1 = actions.addCartItem( { id: 21, quantity: 1 } );
			const p2 = actions.addCartItem( { id: 22, quantity: 1 } );
			yield Promise.all( [ p1, p2 ] );
			context.log = [ ...context.log, '  ✓ Batch 1: 2 operations' ];

			// Batch 2: One call after await
			context.log = [
				...context.log,
				'',
				'BATCH 2: Single call after await',
				'  → addCartItem({ id: 23 })',
			];
			yield actions.addCartItem( { id: 23, quantity: 1 } );
			context.log = [ ...context.log, '  ✓ Batch 2: 1 operation' ];

			// Batch 3: Three sync calls
			context.log = [
				...context.log,
				'',
				'BATCH 3: Three synchronous calls',
				'  → addCartItem({ id: 24 })',
				'  → addCartItem({ id: 25 })',
				'  → addCartItem({ id: 26 })',
			];
			const p3 = actions.addCartItem( { id: 24, quantity: 1 } );
			const p4 = actions.addCartItem( { id: 25, quantity: 1 } );
			const p5 = actions.addCartItem( { id: 26, quantity: 1 } );
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
			context.isRunning = true;
			context.lastDemo = 'Wishlist Add All';
			context.log = [
				'=== WISHLIST "ADD ALL" DEMO ===',
				'Scenario: User clicks "Add All to Cart" on their wishlist...',
				'',
			];

			const actions = yield getCartActions();

			const wishlistItems = [
				{ id: 15, name: 'T-Shirt' },
				{ id: 16, name: 'Hoodie' },
				{ id: 17, name: 'Cap' },
				{ id: 18, name: 'Belt' },
				{ id: 19, name: 'Sunglasses' },
			];

			context.log = [
				...context.log,
				'📋 Wishlist contains:',
				...wishlistItems.map( ( item ) => `   • ${ item.name }` ),
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
			context.isRunning = true;
			context.lastDemo = 'Multiple Extensions';
			context.log = [
				'=== MULTIPLE EXTENSIONS DEMO ===',
				'Scenario: User clicks "Add to Cart" on a laptop...',
				'Multiple extensions respond to the same event:',
				'',
			];

			const actions = yield getCartActions();

			context.log = [
				...context.log,
				'📦 Core Block: Adding laptop',
				'🔌 Accessories Extension: Auto-adding laptop bag',
				'🛡️ Warranty Extension: Auto-adding 2-year warranty',
				'',
				'⏳ All extensions fired synchronously...',
			];

			// Simulate multiple extensions responding to the same add-to-cart event
			const coreAdd = actions.addCartItem( { id: 15, quantity: 1 } );
			const accessoryAdd = actions.addCartItem( { id: 16, quantity: 1 } );
			const warrantyAdd = actions.addCartItem( { id: 17, quantity: 1 } );

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
