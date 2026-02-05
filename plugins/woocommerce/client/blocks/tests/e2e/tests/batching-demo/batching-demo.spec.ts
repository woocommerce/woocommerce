/**
 * Batching Demonstration
 *
 * This test file demonstrates how the mutation batcher works and why it's
 * designed the way it is.
 *
 * KEY CONCEPTS:
 *
 * 1. MICROTICK BATCHING (not time-window batching):
 *    The batcher uses queueMicrotask() to batch requests. This means all
 *    synchronous calls within the same JavaScript task get batched together.
 *    There's no arbitrary time window (like 50ms) - it's purely based on
 *    the JavaScript event loop.
 *
 * 2. WHY THIS MATTERS:
 *    - Rapid user clicks happen synchronously in the same event handler
 *    - Multiple extensions/blocks calling the API get batched automatically
 *    - No race conditions from concurrent API calls
 *
 * 3. WHY IT'S HARD TO OBSERVE:
 *    Normal user interaction rarely triggers multiple cart operations in
 *    the same microtick. Each click is typically a separate event, and
 *    by the time the user clicks again, the first microtask has already
 *    processed.
 */

import {
	expect,
	test as base,
	CLASSIC_THEME_SLUG,
} from '@woocommerce/e2e-utils';

const test = base.extend( {} );

test.describe( 'Mutation Batcher Demonstration', () => {
	/**
	 * These tests use /product-collection/ page which has iAPI product-button
	 * blocks. This ensures the interactivity API and cart store are loaded.
	 */
	test.beforeEach( async ( { requestUtils, page } ) => {
		await requestUtils.activateTheme( CLASSIC_THEME_SLUG );
		await page.goto( '/product-collection/' );
		// Wait for iAPI blocks to hydrate
		await page.waitForSelector( '[data-wp-interactive]' );
	} );

	test( 'demonstrates microtick batching - synchronous calls become ONE request', async ( {
		page,
	} ) => {
		/**
		 * SCENARIO: Three "extensions" call addCartItem at the same time
		 *
		 * In real usage, this happens when:
		 * - Multiple blocks/extensions respond to the same event
		 * - Code loops through items and adds them synchronously
		 * - A single handler needs to add multiple items
		 */

		// Track batch API calls
		const batchRequests: string[] = [];
		await page.route( '**/wc/store/v1/batch**', async ( route ) => {
			const body = route.request().postDataJSON();
			const operations = body?.requests?.length || 0;
			batchRequests.push(
				`Batch request with ${ operations } operation(s)`
			);
			console.log( `📦 BATCH REQUEST: ${ operations } operations` );
			await route.continue();
		} );

		// Simulate three "extensions" calling addCartItem synchronously
		// All three happen in the same microtick = ONE batch request
		const result = await page.evaluate( async () => {
			const logs: string[] = [];

			// This is how any code (extension, block, etc) accesses the store
			const { store } = await import( '@wordpress/interactivity' );
			const unlockKey =
				'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

			// Import the cart store module
			await import( '@woocommerce/stores/woocommerce/cart' );

			// Get actions from the woocommerce store
			const { actions } = store( 'woocommerce', {}, { lock: unlockKey } );

			logs.push( '--- Starting synchronous calls ---' );
			logs.push( 'Call 1: addCartItem for product 15' );
			logs.push( 'Call 2: addCartItem for product 16' );
			logs.push( 'Call 3: addCartItem for product 17' );

			// All three calls happen synchronously - no await between them
			// The batcher will combine these into ONE API request
			const promise1 = actions.addCartItem( { id: 15, quantity: 1 } );
			const promise2 = actions.addCartItem( { id: 16, quantity: 1 } );
			const promise3 = actions.addCartItem( { id: 17, quantity: 1 } );

			logs.push( '--- All calls queued, waiting for batch... ---' );

			// Wait for all to complete
			await Promise.all( [ promise1, promise2, promise3 ] );

			logs.push( '--- Batch complete ---' );

			return logs;
		} );

		// Log the execution flow
		console.log( '\n=== EXECUTION FLOW ===' );
		result.forEach( ( log ) => console.log( log ) );
		console.log( '=== BATCH REQUESTS ===' );
		batchRequests.forEach( ( req ) => console.log( req ) );

		// VERIFY: Only ONE batch request was made for all three operations
		expect( batchRequests ).toHaveLength( 1 );
		expect( batchRequests[ 0 ] ).toContain( '3 operation(s)' );
	} );

	test( 'demonstrates separate batches - async separation creates MULTIPLE requests', async ( {
		page,
	} ) => {
		/**
		 * SCENARIO: Calls separated by await create separate batch windows
		 *
		 * When you await between calls, you allow the microtask queue to
		 * flush, which triggers a batch. The next call starts a new batch.
		 */

		const batchRequests: string[] = [];
		await page.route( '**/wc/store/v1/batch**', async ( route ) => {
			const body = route.request().postDataJSON();
			const operations = body?.requests?.length || 0;
			batchRequests.push(
				`Batch request with ${ operations } operation(s)`
			);
			console.log( `📦 BATCH REQUEST: ${ operations } operations` );
			await route.continue();
		} );

		const result = await page.evaluate( async () => {
			const logs: string[] = [];

			const { store } = await import( '@wordpress/interactivity' );
			const unlockKey =
				'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

			await import( '@woocommerce/stores/woocommerce/cart' );
			const { actions } = store( 'woocommerce', {}, { lock: unlockKey } );

			logs.push( '--- Call 1: addCartItem (then await) ---' );
			await actions.addCartItem( { id: 18, quantity: 1 } );
			logs.push( '--- Call 1 complete ---' );

			logs.push( '--- Call 2: addCartItem (then await) ---' );
			await actions.addCartItem( { id: 19, quantity: 1 } );
			logs.push( '--- Call 2 complete ---' );

			logs.push( '--- Call 3: addCartItem (then await) ---' );
			await actions.addCartItem( { id: 20, quantity: 1 } );
			logs.push( '--- Call 3 complete ---' );

			return logs;
		} );

		console.log( '\n=== EXECUTION FLOW ===' );
		result.forEach( ( log ) => console.log( log ) );
		console.log( '=== BATCH REQUESTS ===' );
		batchRequests.forEach( ( req ) => console.log( req ) );

		// VERIFY: Three separate batch requests (one per await)
		expect( batchRequests ).toHaveLength( 3 );
		batchRequests.forEach( ( req ) => {
			expect( req ).toContain( '1 operation(s)' );
		} );
	} );

	test( 'demonstrates mixed batching - some sync, some async', async ( {
		page,
	} ) => {
		/**
		 * SCENARIO: Real-world mix of synchronous and async calls
		 *
		 * This shows how batching naturally groups related operations:
		 * - Sync calls within same handler = batched
		 * - Await separates into new batch
		 */

		const batchRequests: string[] = [];
		await page.route( '**/wc/store/v1/batch**', async ( route ) => {
			const body = route.request().postDataJSON();
			const operations = body?.requests?.length || 0;
			batchRequests.push(
				`Batch request with ${ operations } operation(s)`
			);
			console.log( `📦 BATCH REQUEST: ${ operations } operations` );
			await route.continue();
		} );

		const result = await page.evaluate( async () => {
			const logs: string[] = [];

			const { store } = await import( '@wordpress/interactivity' );
			const unlockKey =
				'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

			await import( '@woocommerce/stores/woocommerce/cart' );
			const { actions } = store( 'woocommerce', {}, { lock: unlockKey } );

			// BATCH 1: Two sync calls
			logs.push( '--- Batch 1: Two synchronous calls ---' );
			const p1 = actions.addCartItem( { id: 21, quantity: 1 } );
			const p2 = actions.addCartItem( { id: 22, quantity: 1 } );
			await Promise.all( [ p1, p2 ] );
			logs.push( '--- Batch 1 complete ---' );

			// BATCH 2: One call after await
			logs.push( '--- Batch 2: Single call after await ---' );
			await actions.addCartItem( { id: 23, quantity: 1 } );
			logs.push( '--- Batch 2 complete ---' );

			// BATCH 3: Three sync calls
			logs.push( '--- Batch 3: Three synchronous calls ---' );
			const p3 = actions.addCartItem( { id: 24, quantity: 1 } );
			const p4 = actions.addCartItem( { id: 25, quantity: 1 } );
			const p5 = actions.addCartItem( { id: 26, quantity: 1 } );
			await Promise.all( [ p3, p4, p5 ] );
			logs.push( '--- Batch 3 complete ---' );

			return logs;
		} );

		console.log( '\n=== EXECUTION FLOW ===' );
		result.forEach( ( log ) => console.log( log ) );
		console.log( '=== BATCH REQUESTS ===' );
		batchRequests.forEach( ( req, i ) =>
			console.log( `Batch ${ i + 1 }: ${ req }` )
		);

		// VERIFY: Three batches with 2, 1, and 3 operations respectively
		expect( batchRequests ).toHaveLength( 3 );
		expect( batchRequests[ 0 ] ).toContain( '2 operation(s)' );
		expect( batchRequests[ 1 ] ).toContain( '1 operation(s)' );
		expect( batchRequests[ 2 ] ).toContain( '3 operation(s)' );
	} );
} );

test.describe( 'Realistic Batching Scenarios', () => {
	/**
	 * These tests demonstrate WHY batching is useful with realistic scenarios
	 * that extension developers and block authors might implement.
	 */
	test.beforeEach( async ( { requestUtils, page } ) => {
		await requestUtils.activateTheme( CLASSIC_THEME_SLUG );
		await page.goto( '/product-collection/' );
		await page.waitForSelector( '[data-wp-interactive]' );
	} );

	test( 'scenario: "Add All to Cart" button - extension adds 5 products at once', async ( {
		page,
	} ) => {
		/**
		 * REALISTIC SCENARIO: Wishlist Extension
		 *
		 * A wishlist extension has an "Add All to Cart" button that adds
		 * all wishlist items at once. Without batching, this would make
		 * 5 separate API calls. With batching, it's just ONE request.
		 *
		 * BENEFIT: Better UX (faster), less server load, atomic operation
		 */

		const batchRequests: { operations: number; items: string[] }[] = [];
		await page.route( '**/wc/store/v1/batch**', async ( route ) => {
			const body = route.request().postDataJSON();
			const requests = body?.requests || [];
			batchRequests.push( {
				operations: requests.length,
				items: requests.map(
					( r: any ) => `product ${ r.body?.id || 'unknown' }`
				),
			} );
			console.log(
				`📦 WISHLIST BATCH: Adding ${ requests.length } items in one request`
			);
			await route.continue();
		} );

		const result = await page.evaluate( async () => {
			const { store } = await import( '@wordpress/interactivity' );
			const unlockKey =
				'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

			await import( '@woocommerce/stores/woocommerce/cart' );
			const { actions } = store( 'woocommerce', {}, { lock: unlockKey } );

			// Simulate wishlist with 5 products
			const wishlistItems = [
				{ id: 15, name: 'T-Shirt' },
				{ id: 16, name: 'Hoodie' },
				{ id: 17, name: 'Cap' },
				{ id: 18, name: 'Belt' },
				{ id: 19, name: 'Sunglasses' },
			];

			console.log( '🛒 User clicked "Add All to Cart"' );
			console.log(
				`📋 Wishlist contains: ${ wishlistItems
					.map( ( i ) => i.name )
					.join( ', ' ) }`
			);

			// Add all items synchronously - they all batch into ONE request
			const promises = wishlistItems.map( ( item ) =>
				actions.addCartItem( { id: item.id, quantity: 1 } )
			);

			await Promise.all( promises );

			return `Added ${ wishlistItems.length } items to cart`;
		} );

		console.log( `\n✅ ${ result }` );
		console.log(
			`📊 Network requests made: ${ batchRequests.length } (instead of 5!)`
		);

		// VERIFY: Only ONE batch request for all 5 items
		expect( batchRequests ).toHaveLength( 1 );
		expect( batchRequests[ 0 ].operations ).toBe( 5 );
	} );

	test( 'scenario: Multiple blocks on same page respond to same event', async ( {
		page,
	} ) => {
		/**
		 * REALISTIC SCENARIO: Multiple Extensions Responding to Event
		 *
		 * Imagine a product page with:
		 * - Main "Add to Cart" button (core block)
		 * - "Also buy accessories" extension that auto-adds related items
		 * - "Warranty" extension that adds warranty product
		 *
		 * All three respond to the same user action. Without coordination,
		 * they'd make 3 API calls. Batching combines them automatically.
		 *
		 * BENEFIT: Extensions don't need to coordinate with each other
		 */

		const batchRequests: string[] = [];
		await page.route( '**/wc/store/v1/batch**', async ( route ) => {
			const body = route.request().postDataJSON();
			const operations = body?.requests?.length || 0;
			batchRequests.push( `${ operations } operation(s)` );
			console.log(
				`📦 COMBINED BATCH: ${ operations } items from different extensions`
			);
			await route.continue();
		} );

		await page.evaluate( async () => {
			const { store } = await import( '@wordpress/interactivity' );
			const unlockKey =
				'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

			await import( '@woocommerce/stores/woocommerce/cart' );
			const { actions } = store( 'woocommerce', {}, { lock: unlockKey } );

			console.log( '👆 User clicks "Add to Cart" on a laptop' );
			console.log( '' );

			// These would be separate extensions, but all fire synchronously
			// in response to the same DOM event

			console.log( '  📦 Core: Adding laptop (product 15)' );
			const coreAdd = actions.addCartItem( { id: 15, quantity: 1 } );

			console.log( '  🔌 Accessories Extension: Adding laptop bag (16)' );
			const accessoryAdd = actions.addCartItem( { id: 16, quantity: 1 } );

			console.log( '  🛡️ Warranty Extension: Adding 2-year warranty (17)' );
			const warrantyAdd = actions.addCartItem( { id: 17, quantity: 1 } );

			await Promise.all( [ coreAdd, accessoryAdd, warrantyAdd ] );

			console.log( '' );
			console.log( '✅ All items added in single atomic operation' );
		} );

		console.log(
			`\n📊 Extensions made separate calls, but batcher combined them: ${ batchRequests[ 0 ] }`
		);

		// VERIFY: All three extensions' calls were batched
		expect( batchRequests ).toHaveLength( 1 );
		expect( batchRequests[ 0 ] ).toContain( '3 operation(s)' );
	} );

	test( 'scenario: Form submission with multiple cart updates', async ( {
		page,
	} ) => {
		/**
		 * REALISTIC SCENARIO: Quantity Update Form
		 *
		 * A custom cart form lets users update quantities for multiple items
		 * at once. The form handler updates each item, and batching ensures
		 * they're all sent in one request.
		 *
		 * BENEFIT: Atomic updates - either all succeed or all fail together
		 */

		const batchRequests: string[] = [];
		await page.route( '**/wc/store/v1/batch**', async ( route ) => {
			const body = route.request().postDataJSON();
			const operations = body?.requests?.length || 0;
			batchRequests.push( `${ operations } updates` );
			console.log( `📦 FORM SUBMIT: ${ operations } quantity updates` );
			await route.continue();
		} );

		await page.evaluate( async () => {
			const { store } = await import( '@wordpress/interactivity' );
			const unlockKey =
				'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

			await import( '@woocommerce/stores/woocommerce/cart' );
			const { actions } = store( 'woocommerce', {}, { lock: unlockKey } );

			console.log( '📝 User submits cart update form:' );
			console.log( '   - T-Shirt: qty 1 → 3' );
			console.log( '   - Hoodie: qty 2 → 1' );
			console.log( '   - Cap: qty 1 → 5' );
			console.log( '' );

			// Process all form updates synchronously
			const updates = [
				actions.addCartItem( { id: 15, quantity: 3 } ),
				actions.addCartItem( { id: 16, quantity: 1 } ),
				actions.addCartItem( { id: 17, quantity: 5 } ),
			];

			await Promise.all( updates );

			console.log( '✅ All quantities updated atomically' );
		} );

		// VERIFY: All updates in one batch
		expect( batchRequests ).toHaveLength( 1 );
		expect( batchRequests[ 0 ] ).toContain( '3 updates' );
	} );
} );
