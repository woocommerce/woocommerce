/**
 * Mutation Batcher E2E Tests
 *
 * These tests call the iAPI cart store directly via page.evaluate to verify
 * batching behavior at the network level. They're designed as regression
 * tests for the mutation batcher — if internals are refactored, these
 * should still pass.
 *
 * KEY IDEA: The batcher uses queueMicrotask() to collect requests.
 * - Synchronous calls within one microtick → batched into 1 request
 * - Calls separated by await → separate batch requests
 */

/**
 * External dependencies
 */
import { expect, test as base, guestFile } from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import {
	capProductStock,
	cartLineRows,
	createStockManagedProduct,
	readCartLineQuantities,
} from './utils';

const test = base.extend( {} );

test.describe( 'Mutation Batcher', () => {
	test.beforeEach( async ( { frontendUtils } ) => {
		// The shop page has iAPI product-button blocks, which means the
		// interactivity API and cart store are loaded and hydrated.
		await frontendUtils.goToShop();
	} );

	test( 'synchronous calls are batched into a single request', async ( {
		page,
	} ) => {
		const batchRequests: number[] = [];

		await page.route( '**/wc/store/v1/batch**', async ( route ) => {
			const body = route.request().postDataJSON();
			batchRequests.push( body?.requests?.length || 0 );
			await route.continue();
		} );

		await page.evaluate( async () => {
			const { store } = await import( '@wordpress/interactivity' );
			const unlockKey =
				'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

			await import( '@woocommerce/stores/woocommerce/cart' );
			const { actions } = store( 'woocommerce', {}, { lock: unlockKey } );

			// Three calls with no await between them — same microtick.
			const p1 = actions.addCartItem( { id: 15, quantityToAdd: 1 } );
			const p2 = actions.addCartItem( { id: 16, quantityToAdd: 1 } );
			const p3 = actions.addCartItem( { id: 17, quantityToAdd: 1 } );

			await Promise.all( [ p1, p2, p3 ] );
		} );

		// All 3 operations should have been sent in a single batch request.
		expect( batchRequests ).toHaveLength( 1 );
		expect( batchRequests[ 0 ] ).toBe( 3 );
	} );

	test( 'awaited calls produce separate batch requests', async ( {
		page,
	} ) => {
		const batchRequests: number[] = [];

		await page.route( '**/wc/store/v1/batch**', async ( route ) => {
			const body = route.request().postDataJSON();
			batchRequests.push( body?.requests?.length || 0 );
			await route.continue();
		} );

		await page.evaluate( async () => {
			const { store } = await import( '@wordpress/interactivity' );
			const unlockKey =
				'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

			await import( '@woocommerce/stores/woocommerce/cart' );
			const { actions } = store( 'woocommerce', {}, { lock: unlockKey } );

			// Each await breaks the microtick — each call becomes its own batch.
			await actions.addCartItem( { id: 18, quantityToAdd: 1 } );
			await actions.addCartItem( { id: 19, quantityToAdd: 1 } );
			await actions.addCartItem( { id: 20, quantityToAdd: 1 } );
		} );

		// Each call should have produced its own batch request.
		expect( batchRequests ).toHaveLength( 3 );
		batchRequests.forEach( ( count ) => {
			expect( count ).toBe( 1 );
		} );
	} );

	test( 'mixed sync and async calls produce expected batch grouping', async ( {
		page,
	} ) => {
		const batchRequests: number[] = [];

		await page.route( '**/wc/store/v1/batch**', async ( route ) => {
			const body = route.request().postDataJSON();
			batchRequests.push( body?.requests?.length || 0 );
			await route.continue();
		} );

		await page.evaluate( async () => {
			const { store } = await import( '@wordpress/interactivity' );
			const unlockKey =
				'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

			await import( '@woocommerce/stores/woocommerce/cart' );
			const { actions } = store( 'woocommerce', {}, { lock: unlockKey } );

			// Batch 1: two sync calls
			const p1 = actions.addCartItem( { id: 21, quantityToAdd: 1 } );
			const p2 = actions.addCartItem( { id: 22, quantityToAdd: 1 } );
			await Promise.all( [ p1, p2 ] );

			// Batch 2: one call after await
			await actions.addCartItem( { id: 23, quantityToAdd: 1 } );

			// Batch 3: three sync calls
			const p3 = actions.addCartItem( { id: 24, quantityToAdd: 1 } );
			const p4 = actions.addCartItem( { id: 25, quantityToAdd: 1 } );
			const p5 = actions.addCartItem( { id: 26, quantityToAdd: 1 } );
			await Promise.all( [ p3, p4, p5 ] );
		} );

		// Should produce 3 batches: 2, 1, 3 operations respectively.
		expect( batchRequests ).toHaveLength( 3 );
		expect( batchRequests[ 0 ] ).toBe( 2 );
		expect( batchRequests[ 1 ] ).toBe( 1 );
		expect( batchRequests[ 2 ] ).toBe( 3 );
	} );

	test( 'cart state is correct after batched operations', async ( {
		page,
	} ) => {
		const cartItemIds = await page.evaluate( async () => {
			const { store } = await import( '@wordpress/interactivity' );
			const unlockKey =
				'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

			await import( '@woocommerce/stores/woocommerce/cart' );
			const { actions, state } = store(
				'woocommerce',
				{},
				{ lock: unlockKey }
			);

			// Refresh to start with known state.
			await actions.refreshCartItems();

			// Remove all existing items to start clean.
			const existingKeys = state.cart.items.map(
				( item: { key: string } ) => item.key
			);
			for ( const key of existingKeys ) {
				await actions.removeCartItem( key );
			}

			// Now add 3 products synchronously (one batch).
			const p1 = actions.addCartItem( { id: 15, quantityToAdd: 1 } );
			const p2 = actions.addCartItem( { id: 16, quantityToAdd: 1 } );
			const p3 = actions.addCartItem( { id: 17, quantityToAdd: 1 } );
			await Promise.all( [ p1, p2, p3 ] );

			// Return the product IDs now in the cart.
			return state.cart.items.map( ( item: { id: number } ) => item.id );
		} );

		// All 3 products should be in the cart.
		expect( cartItemIds ).toContain( 15 );
		expect( cartItemIds ).toContain( 16 );
		expect( cartItemIds ).toContain( 17 );
	} );

	test( 'total batch failure rolls back product button UI to pre-failure state', async ( {
		page,
		frontendUtils,
	} ) => {
		await frontendUtils.goToShop();

		const productButtonBlock = page
			.locator( '.wc-block-components-product-button' )
			.first();
		const button = productButtonBlock.getByRole( 'button' );

		// Click to add one item — this request goes through normally.
		await button.click();
		await expect( button ).toHaveText( '1 in cart' );

		// Intercept batch requests with a delay so we can observe
		// the optimistic state before the failure triggers rollback.
		await page.route( '**/wc/store/v1/batch**', async ( route ) => {
			const body = route.request().postDataJSON();
			const requestCount = body?.requests?.length || 1;

			await new Promise( ( resolve ) => setTimeout( resolve, 1000 ) );

			await route.fulfill( {
				status: 200,
				contentType: 'application/json',
				body: JSON.stringify( {
					responses: Array.from( { length: requestCount }, () => ( {
						status: 500,
						body: {
							message: 'Simulated server error',
							code: 'internal_error',
						},
					} ) ),
				} ),
			} );
		} );

		// Click twice rapidly — both go through the intercepted batcher.
		await button.click();
		await button.click();

		// The optimistic updates should be visible in the UI.
		await expect( button ).toHaveText( '3 in cart' );

		// After the delayed failure response, the batcher rolls back
		// to the snapshot taken before any optimistic mutations.
		await expect( button ).toHaveText( '1 in cart' );
	} );

	test( 'partial failure in a batch does not prevent successful operations', async ( {
		page,
	} ) => {
		const batchRequests: number[] = [];

		await page.route( '**/wc/store/v1/batch**', async ( route ) => {
			const body = route.request().postDataJSON();
			batchRequests.push( body?.requests?.length || 0 );
			await route.continue();
		} );

		const result = await page.evaluate( async () => {
			const { store } = await import( '@wordpress/interactivity' );
			const unlockKey =
				'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

			await import( '@woocommerce/stores/woocommerce/cart' );
			const { actions, state } = store(
				'woocommerce',
				{},
				{ lock: unlockKey }
			);

			// Refresh to get clean state.
			await actions.refreshCartItems();

			// Mix valid and invalid product IDs — all in one microtick.
			const p1 = actions.addCartItem( { id: 15, quantityToAdd: 1 } );
			const p2 = actions.addCartItem( { id: 999999, quantityToAdd: 1 } ); // Invalid
			const p3 = actions.addCartItem( { id: 16, quantityToAdd: 1 } );

			// addCartItem catches errors internally so all promises resolve.
			await Promise.allSettled( [ p1, p2, p3 ] );

			const cartProductIds = state.cart.items.map(
				( item: { id: number } ) => item.id
			);

			return {
				has15: cartProductIds.includes( 15 ),
				has999999: cartProductIds.includes( 999999 ),
				has16: cartProductIds.includes( 16 ),
			};
		} );

		// Valid products should be in cart, invalid should not.
		expect( result.has15 ).toBe( true );
		expect( result.has999999 ).toBe( false );
		expect( result.has16 ).toBe( true );

		// Should still have been sent as a single batch.
		expect( batchRequests ).toHaveLength( 1 );
		expect( batchRequests[ 0 ] ).toBe( 3 );
	} );

	test.describe( 'Distinct-product batch with a stock cap', () => {
		// Guest isolation gives every run a brand-new, empty cart, so the
		// saved-cart assertions below aren't affected by state left over from
		// other tests that share the default (admin) session in this file.
		test.use( { storageState: guestFile } );

		test( 'a single batch of distinct products lands each at its requested quantity, with no flash to a wrong count, while a stock-capped product is rejected without affecting the others', async ( {
			page,
			frontendUtils,
		} ) => {
			await frontendUtils.goToShop();

			// Two products with ample stock, and one whose stock is capped
			// below the quantity this batch requests for it. Fresh products
			// (rather than shop sample data) keep the scenario deterministic:
			// their stock and cart history are entirely under this test's
			// control.
			const productA = await createStockManagedProduct(
				'Batch Product A'
			);
			const productB = await createStockManagedProduct(
				'Batch Product B'
			);
			const productC = await createStockManagedProduct(
				'Batch Product C'
			);

			const requestedA = 3;
			const requestedB = 2;
			const requestedC = 5;

			// Cap product C's stock below the quantity the batch will
			// request for it.
			await capProductStock( productC, 2 );

			// Delay the batch response so the optimistic (pre-commit) cart
			// can be inspected while the request is still in flight, the
			// same technique used in "total batch failure rolls back
			// product button UI to pre-failure state" above.
			await page.route( '**/wc/store/v1/batch**', async ( route ) => {
				await new Promise( ( resolve ) => setTimeout( resolve, 1000 ) );
				await route.continue();
			} );

			const requestSent = page.waitForRequest( '**/wc/store/v1/batch**' );

			// Fire the three distinct-product adds without awaiting the
			// evaluate call yet, so the batched request can be observed
			// in flight from the outside.
			const addSettled = page.evaluate(
				async ( { idA, idB, idC, qtyA, qtyB, qtyC } ) => {
					const { store } = await import(
						'@wordpress/interactivity'
					);
					const unlockKey =
						'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';

					await import( '@woocommerce/stores/woocommerce/cart' );
					const { actions } = store(
						'woocommerce',
						{},
						{ lock: unlockKey }
					);

					// Three distinct products, added synchronously in one
					// microtick — the mutation batcher groups them into a
					// single request.
					const pA = actions.addCartItem( {
						id: idA,
						quantityToAdd: qtyA,
					} );
					const pB = actions.addCartItem( {
						id: idB,
						quantityToAdd: qtyB,
					} );
					const pC = actions.addCartItem( {
						id: idC,
						quantityToAdd: qtyC,
					} );

					// addCartItem catches errors internally so all promises
					// resolve, even for the item the server rejects.
					await Promise.allSettled( [ pA, pB, pC ] );
				},
				{
					idA: productA,
					idB: productB,
					idC: productC,
					qtyA: requestedA,
					qtyB: requestedB,
					qtyC: requestedC,
				}
			);

			// The batched request has been sent to the network; the route
			// above is holding its response.
			await requestSent;

			// Reads each product's quantity from the live store state.
			const readQuantities = ( ids: number[] ) =>
				page.evaluate( async ( productIds ) => {
					const { store } = await import(
						'@wordpress/interactivity'
					);
					const unlockKey =
						'I acknowledge that using a private store means my plugin will inevitably break on the next store release.';
					await import( '@woocommerce/stores/woocommerce/cart' );
					const { state } = store(
						'woocommerce',
						{},
						{ lock: unlockKey }
					);
					return productIds.map(
						( id ) =>
							state.cart.items.find(
								( item: { id: number } ) => item.id === id
							)?.quantity ?? 0
					);
				}, ids );

			// While the response is still pending, the optimistic cart
			// already reflects every requested quantity — applyOptimistic
			// runs synchronously for all three adds before the batched
			// request is even sent, so there is no earlier, wrong value to
			// observe and no later flash once the response lands.
			const [ optimisticA, optimisticB, optimisticC ] =
				await readQuantities( [ productA, productB, productC ] );
			expect( optimisticA ).toBe( requestedA );
			expect( optimisticB ).toBe( requestedB );
			expect( optimisticC ).toBe( requestedC );

			// Let the delayed response resolve and let the add calls settle.
			await addSettled;

			// The real server's stock-cap behavior for a batched add-item
			// request, confirmed against a live server, is a full per-item
			// rejection (HTTP 400 `woocommerce_rest_product_partially_out_of_stock`)
			// rather than a partial commit: the two in-stock products commit
			// at their exact requested quantities, unaffected by the third
			// item's rejection, and the capped product's requested quantity
			// never lands — it stays absent from the cart at its
			// server-committed quantity of 0.
			const [ committedA, committedB, committedC ] = await readQuantities(
				[ productA, productB, productC ]
			);
			expect( committedA ).toBe( requestedA );
			expect( committedB ).toBe( requestedB );
			expect( committedC ).toBe( 0 );

			// Confirm the same outcome in the saved (persisted) cart, not
			// just in-memory store state — the two unaffected products keep
			// their exact quantities and the capped product has no line at
			// all.
			await frontendUtils.goToCart();
			expect(
				await readCartLineQuantities( page, 'Batch Product A' )
			).toEqual( [ requestedA ] );
			expect(
				await readCartLineQuantities( page, 'Batch Product B' )
			).toEqual( [ requestedB ] );
			await expect( cartLineRows( page, 'Batch Product C' ) ).toHaveCount(
				0
			);
		} );
	} );
} );
